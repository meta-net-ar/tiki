<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

use Fabiang\Xmpp\Protocol\Presence;
use Fabiang\Xmpp\Protocol\Message;
use Fabiang\Xmpp\Protocol\Invitation;
use Fabiang\Xmpp\Util\JID;
use Tiki\Lib\Auth\Tokens;

require_once __DIR__ . '/ConverseJS.php';
require_once __DIR__ . '/TikiXmppChat.php';
require_once __DIR__ . '/TikiXmppPrebind.php';

class XMPPLib extends TikiLib
{
    private $server_host = '';
    private $server_http_bind = '';
    private $restapi = null;
    private $xmppapi = null;
    private ?\Fabiang\Xmpp\Client $adminClient = null;

    /**
     * @return string
     */
    public function getServerHttpBind()
    {
        return $this->server_http_bind;
    }

    public function __construct()
    {
        global $prefs;

        $this->server_host = $prefs['xmpp_server_host'];
        $this->server_http_bind = $prefs['xmpp_server_http_bind'];
    }

    /**
     * ===== Prosody integration helpers =====
     * These methods are designed for Prosody (mod_auth_http, group mapping, etc.)
     * They do not depend on Openfire REST API or XmppPrebind.
     */

    /**
     * Return the JID of a user (custom preference or built from the domain)
     */
    public function getUserJidForLogin(string $login): string
    {
        global $prefs;

        $domain = $prefs['xmpp_domain_users'] ?: $this->server_host;
        return sprintf('%s@%s', $login, $domain);
    }

    /**
     * Return auto-join rooms for a given user
     */
    public function getXmppRoomsForUser(string $login): array
    {
        global $prefs;
        $cachelib = TikiLib::lib('cache');
        $key = 'xmpp_rooms_' . md5($login);
        if ($c = $cachelib->getSerialized($key)) {
            return $c;
        }

        $rooms = [];
        $tikilib = TikiLib::lib('tiki');
        $saved = json_decode($tikilib->get_user_preference($login, 'xmpp_rooms', '[]'), true);
        if (is_array($saved)) {
            $rooms = $saved;
        }

        $strategy = $prefs['xmpp_auto_join_strategy'] ?? 'by-groups';
        if ($strategy === 'none') {
            return $rooms;
        }

        if ($strategy === 'static' && ! empty($prefs['xmpp_registered_room'])) {
            $rooms[] = $prefs['xmpp_registered_room'];
        }

        if ($strategy === 'by-groups') {
            $map = json_decode($prefs['xmpp_group_room_map'] ?? '{}', true) ?: [];
            $groups = TikiLib::lib('user')->get_user_groups($login);
            foreach ($groups as $g) {
                if (! empty($map[$g])) {
                    $rooms[] = $map[$g];
                }
            }
            if (! empty($prefs['xmpp_registered_room'])) {
                $rooms[] = $prefs['xmpp_registered_room'];
            }
        }

        // Admins should always see/respond in anonymous/support rooms (community)
        $userslib = TikiLib::lib('user');
        $isAdmin = $login && $userslib->user_has_permission($login, 'tiki_p_admin');
        if ($isAdmin) {
            if (! empty($prefs['xmpp_anonymous_room'])) {
                $rooms[] = $prefs['xmpp_anonymous_room'];
            }
            if (! empty($prefs['xmpp_anonymous_support_room'])) {
                $rooms[] = $prefs['xmpp_anonymous_support_room'];
            }
        }

        $rooms = array_values(array_unique(array_filter($rooms)));
        $cachelib->cacheItem($key, serialize($rooms), 300);
        return $rooms;
    }


    public function saveUserRooms(string $user, array $rooms): void
    {
        $tikilib = TikiLib::lib('tiki');
        $existing = json_decode($tikilib->get_user_preference($user, 'xmpp_rooms', '[]'), true);
        if (! is_array($existing)) {
            $existing = [];
        }

        $all = array_values(array_unique(array_merge($existing, $rooms)));
        $tikilib->set_user_preference($user, 'xmpp_rooms', json_encode($all));

        error_log("[XMPP] Saved rooms for {$user}: " . implode(', ', $all));
    }

    /**
     * Build a full room JID with the MUC component domain
     */
    public function buildRoomJid(string $roomName): string
    {
        global $prefs;
        $mucDomain = $prefs['xmpp_muc_component_domain'] ?: 'conference.' . $this->server_host;

        // If it's already a full JID, do not add anything
        if (strpos($roomName, '@') !== false) {
            return $roomName;
        }

        return $roomName . '@' . $mucDomain;
    }

    public function checkXmppSessionToken(string $user, string $token): bool
    {
        global $prefs;
        $tokenlib = Tokens::build($prefs);
        $data = $tokenlib->getToken($token);

        if (! $data || $data['entry'] !== 'xmppauthtoken') {
            return false;
        }

        $params = json_decode($data['parameters'], true);
        $valid = is_array($params) && ($params['user'] ?? null) === $user;

        if ($valid) {
            $tokenlib->deleteToken($data['tokenId']);
        }

        return $valid;
    }

    /**
     * Get effective JID (external preferred or local JID with fallback)
     */
    public function getEffectiveJidForUser(string $username): string
    {
        if (empty($username)) {
            return '';
        }

        $userslib = TikiLib::lib('user');
        $info = $userslib->get_user_info($username);

        $ext = trim($info['preferences']['xmpp_jid'] ?? '');
        if (! empty($ext) && strpos($ext, '@') !== false) {
            return $ext;
        }

        global $prefs;
        $domain = '';
        if (! empty($prefs['xmpp_domain_users'])) {
            $domain = $prefs['xmpp_domain_users'];
        } elseif (! empty($prefs['xmpp_domain'])) {
            $domain = $prefs['xmpp_domain'];
        } else {
            $domain = parse_url($prefs['tiki_url'], PHP_URL_HOST);
        }

        return "{$username}@{$domain}";
    }

    /**
     * Sync user's Tiki groups -> XMPP MUC affiliations (member).
     * Minimal: ensure room exists and set affiliation member.
     */
    public function syncUserGroupsToXmpp(string $u): void
    {
        global $prefs;
        if ($prefs['xmpp_feature'] !== 'y') {
            return;
        }
        $this->invalidateUserCache($u);
        $jid = $this->getEffectiveJidForUser($u);
        if (! $jid) {
            return;
        }
        $rooms = $this->getXmppRoomsForUser($u);
        foreach ($rooms as $r) {
            try {
                $room = $this->buildRoomJid($r);
                $this->ensureRoomExists($room);
                $this->setUserAffiliation($room, $jid, 'member');
            } catch (\Throwable $e) {
                error_log("[XMPP] Sync error {$u} -> {$r}: " . $e->getMessage());
            }
        }
    }

    public function invalidateUserCache(string $u): void
    {
        TikiLib::lib('cache')->invalidate('xmpp_rooms_' . md5($u));
        error_log("[XMPP] Cache invalidated for {$u}");
    }

    /**
     * ===== Legacy Openfire integration =====
     * Methods kept for compatibility with Openfire (REST API, prebind, tokens).
     * Some are reused internally for Prosody (e.g. render_xmpp_client).
     */

    public function get_user_connection_info($user)
    {
        global $prefs;
        global $tikilib;

        $query = 'SELECT'
        . '     MAX(CASE WHEN `prefName`="xmpp_jid" THEN `value` END) AS `jid`,'
        . '     MAX(CASE WHEN `prefName`="xmpp_password" THEN `value` END) AS `password`,'
        . '     MAX(CASE WHEN `prefName`="xmpp_custom_server_http_bind" THEN `value` END) AS `http_bind`,'
        . '     MAX(CASE WHEN `prefName`="realName" THEN `value` END) AS `nickname`'
        . ' FROM `tiki_user_preferences` WHERE `user`=?'
        . '     AND `prefName` IN ("xmpp_jid", "xmpp_password", "xmpp_custom_server_http_bind", "realName")';

        $query = $this->query($query, [$user]);
        $login = $query->fetchRow();

        if (empty($login['jid']) && $user) {
            $login['jid'] = $user;
        }

        $info = [
            'domain'    => $this->server_host,
            'http_bind' => $this->server_http_bind,
            'jid'       => $prefs['xmpp_server_host'] ? JID::buildJid($login['jid'], $prefs['xmpp_server_host']) : '',
            'password'  => $login['password'] ?: '',
            'username'  => $login['jid'],
            'nickname'  => $login['nickname'] ?: $user,
        ];

        $jid_parts = JID::parseJid($login['jid']);
        if ($jid_parts) {
            $info['jid']       = $login['jid'];
            $info['username']  = $jid_parts['node'];
            $info['domain']    = $jid_parts['domain'];
            $info['http_bind'] = $login['http_bind'] ?: $this->server_http_bind;
        }

        return $info;
    }

    public function check_token($givenUser, $givenToken)
    {
        global $prefs;

        $tokenlib = Tokens::build($prefs);
        $token = $tokenlib->getToken($givenToken);

        if (! $token || $token['entry'] !== 'openfireauthtoken') {
            return false;
        }
        // TODO: figure out how to delete token after n usages
        $tokenlib->deleteToken($token['tokenId']);

        $param = json_decode($token['parameters'], true);
        return is_array($param)
            && ! empty($param['user'])
            && $param['user'] === $givenUser;
    }

    public function create_room_from_wikipage($args, $name, $priority)
    {
        global $prefs;
        global $user;

        $info = $this->get_user_connection_info($user);
        $userJid = $info['jid'];

        if (! is_array($args) || empty($args['data'])) {
            return;
        }

        preg_match('/\{xmpp\b[^}]*\}/i', $args['data'], $match)
            && preg_match_all('/(?:(\w+)=(?:"([^"]*)"|([^\s]+)))/', $match[0], $match);

        if (empty($match)) {
            return;
        }

        $params = [];
        for ($i = 0; $i < count($match[1]); $i += 1) {
            $key = $match[ 1 ][ $i ];
            $val = $match[ 2 ][ $i ] ?: $match[ 3 ][ $i ];
            $params[ $key ] = $val;
        }

        if (empty($params['room'])) {
            return;
        }
        $room = $params['room'];

        $args = [
            'whois' => [
                'moderator',
                'participant',
                'visitor'
            ],
        ];

        $args['roomname'] = $room;
        $atpos = strpos($room, '@');

        if ($atpos) {
            $args['roomname'] = substr($room, 0, $atpos);
        } else {
            $room = $room . '@' . $prefs['xmpp_muc_component_domain'];
        }

        $args['roomdesc'] = $args['roomname'];
        if (! empty($params['roomdesc'])) {
            $args['roomdesc'] = $params['roomdesc'];
        }

        $args['maxusers'] = 30;
        if (! empty($params['maxUsers']) && is_numeric($params['maxUsers'])) {
            $params['maxUsers'] = (int)$params['maxUsers'];
        }

        if (! empty($params['can_anyone_discover_jid'])) {
            $args['whois'] = ($params['can_anyone_discover_jid'] === 'anyone');
        }

        $args['persistentroom'] = isset($params['persistent']) && $params['persistent'] === 'y';
        $args['moderatedroom'] = isset($params['moderated']) && $params['moderated'] === 'y';
        $args['enablelogging'] = isset($params['archiving']) && $params['archiving'] === 'y';
        $args['membersonly'] = ! empty($params['visibility']) && $params['visibility'] === 'members_only';
        $args['publicroom'] = ! isset($params['secret']) || $params['secret'] !== 'y';
        $args['roomadmins'] = [ $userJid ];

        if ($this->create_room($room, $args)) {
            if (! empty($params['groups'])) {
                $groups = explode(',', $params['groups']);

                foreach ($groups as $group) {
                    $this->add_group_to_room($args['roomname'], $group, 'members');
                }
            }
        }
    }

    public function create_room($room, $args)
    {
        if (empty($args) || empty($args['roomname'])) {
            return;
        }

        $xmppapi = $this->getXmppApi();
        $return = $xmppapi->createRoom(
            $xmppapi->getJid(),
            $room . '/' . $xmppapi->getUsername(),
            $args
        );
        return $return;
    }

    public function sanitize_name($text)
    {
        global $tikilib;
        $result = $tikilib->take_away_accent($text);
        $result = preg_replace('*[ $&`:<>\[\]{}"+#%@/;=?^|~\',]+*', '-', $result);
        $result = strtolower($result);
        $result = trim($result);
        return $result;
    }

    public function prebind($user)
    {
        global $prefs;
        global $tikilib;

        if (! class_exists('XmppPrebind')) {
            throw new Exception(
                "class 'XmppPrebind' does not exists."
                . " Install with `composer require candy-chat/xmpp-prebind-php:dev-master`.",
                1
            );
        }

        $session_id = substr($tikilib->sessionId, 0, 5);
        $browser_title = $prefs['browsertitle'];
        $browser_title = $this->sanitize_name($browser_title);
        $resource_name = "{$browser_title}-{$session_id}";

        $tokenlib = Tokens::build($prefs);

        if (empty($this->server_host) ||  empty($this->server_http_bind)) {
            header("HTTP/1.0 500 Internal Server Error");
            header('Content-Type: application/json');
            return ["msg" => "No XMPP server to bind."];
        }

        if ($user === null && $prefs['xmpp_openfire_allow_anonymous'] === 'y') {
            $user = $user ?: 'anonymous_' . $session_id;
        }

        $xmpp = $this->get_user_connection_info($user);
        $xmpp_prebind_class = 'XmppPrebind';

        $use_tikitoken = $xmpp['username'] === $user;
        $use_tikitoken = $use_tikitoken && $xmpp['domain'] === $this->server_host;
        $use_tikitoken = $use_tikitoken && ! empty($prefs['xmpp_auth_method']);
        $use_tikitoken = $use_tikitoken && $prefs['xmpp_auth_method'] === 'tikitoken';

        if ($use_tikitoken) {
            $token = $tokenlib->createToken(
                'openfireauthtoken',
                ['user' => $user],  // parameters
                [],                 // groups
                [
                    'timeout' => 300,
                    'createUser' => 'n',
                ]
            );
            $xmpp['password'] = "$token";
            $xmpp_prebind_class = 'TikiXmppPrebind';
        } else {
            if (empty($xmpp['password'])) {
                return [];
            }
        }

        $xmppPrebind = new $xmpp_prebind_class(
            $xmpp['domain'],
            $xmpp['http_bind'],
            $resource_name,
            false,
            false
        );

        $xmppPrebind->connect($xmpp['username'], $xmpp['password']);

        try {
            $xmppPrebind->auth();
            $result = $xmppPrebind->getSessionInfo();
        } catch (XmppPrebindException $e) {
            throw new Exception($e->getMessage(), 401);
        }

        return $result;
    }

    /**
     * Add css and js files and initializes xmpp client page
     *
     * @param array $params :
     *        view__mode => overlayed | fullscreen | mobile | embedded
     *
     * @return string
     * @throws Exception
     */
    public function render_xmpp_client($params = [])
    {
        global $user, $prefs;

        static $instance = 0;
        $instance++;

        if ($instance > 1) {
            return '';
        }

        $xmpplib = TikiLib::lib('xmpp');
        $xmpp = $xmpplib->get_user_connection_info($user);

        $params = array_merge([
            'view_mode' => 'overlayed',
            'room' => '',
            'show_controlbox_by_default' => 'y',
            'show_occupants_by_default' => 'y',
        ], $params);

        $xmppclient = new ConverseJS();
        $xmppclient->set_auth($params);

        $nickname = '';

        // Auto-join only if user is logged in
        if (! empty($user)) {
            $allowedRooms = $xmpplib->getXmppRoomsForUser($user);
            $allowedFullJids = array_map(fn($r) => $xmpplib->buildRoomJid($r), $allowedRooms);

            $joinRooms = [];

            if (! empty($params['room'])) {
                $requestedRoom = $xmpplib->buildRoomJid($params['room']);

                if (in_array($requestedRoom, $allowedFullJids, true)) {
                    $joinRooms[] = $requestedRoom;
                } else {
                    if (! empty($prefs['xmpp_conversejs_debug']) && $prefs['xmpp_conversejs_debug'] === 'y') {
                        error_log("[XMPP] User {$user} requested {$requestedRoom} but not authorized");
                    }
                }
            }

            if ($joinRooms) {
                $xmppclient->set_auto_join_rooms(implode(',', $joinRooms));
            }
        }

        $xmppclient->set_options(
            [
                'bosh_service_url'           => $xmpp['http_bind'],
                'websocket_url'              => isset($xmpp['websocket_url']) ? $xmpp['websocket_url'] : '',
                'jid'                        => $xmppclient->get_option('jid') ?: $xmpp['jid'],
                'nickname'                   => $nickname,
                'view_mode'                  => $params['view_mode'],
                'show_controlbox_by_default' => $params['show_controlbox_by_default'] === 'y',
                'show_occupants_by_default'  => $params['show_occupants_by_default'] === 'y',
                'dm_target'                  => $params['dm_target'] ?? '',
                'anonymous'                  => $params['anonymous'] ?? '',
            ]
        );

        // Auto-join only if user is logged in
        if (! empty($user)) {
            $xmppclient->set_auto_join_rooms($params['room']);
        }

        $xmppclient->render();
    }

    public function initializeRestApi()
    {
        global $prefs;
        $endpoint = $prefs['xmpp_openfire_rest_api'];
        $username = $prefs['xmpp_openfire_rest_api_username'];
        $password = $prefs['xmpp_openfire_rest_api_password'];

        $url = parse_url($endpoint);

        $ssl = $url['scheme'] === 'https';
        $host = $url['host'];
        $port = $url['port'] ?: ($ssl ? 9091 : 9090);
        $path = rtrim($url['path'], '/');

        // TODO: gidkom/php-openfire-restapi doesn't work with latest guzzle which prevents PHP8 packages from working fine
        // need to find another rest api or implement it if somebody needs to use this.
        $api = null;

        $this->restapi = $api;
        return $api;
    }

    public function getRestApi()
    {
        if ($this->restapi == null) {
            return $this->initializeRestApi();
        }
        return $this->restapi;
    }

    public function getXmppApi()
    {
        global $prefs;

        if (empty($this->xmppapi)) {
            $params = [
                "scheme" => "tcp",
                "host" => $this->server_host,
                "port" => (int) ($prefs['xmpp_client_port'] ?? 5222) ?: 5222,
                "user" => $prefs['xmpp_openfire_rest_api_username'],
                "pass" => $prefs['xmpp_openfire_rest_api_password'],
            ];

            $this->xmppapi = new TikiXmppChat($params);
            $this->xmppapi->connect();
        }
        return $this->xmppapi;
    }

    public function addUserToRoom($room, $userJid, $role = 'members')
    {
        global $prefs;
        // first, allow myself to join the room
        $ownerJid = new JID($this->getXmppApi()->getJid());
        $onwerName = $ownerJid->getNode();

        $roomJid = new JID($room);
        $roomName = $roomJid->getNode();


        //$result = $this->getRestApi()->addUserRoleToChatRoom($roomName, $onwerName, 'owners');
        //$result = $this->getRestApi()->addUserRoleToChatRoom($roomName, $userJid, $role);
        $result = [];

        $this->getXmppApi()
            ->sendPresence(1, (string) $roomJid, $onwerName)
            ->sendInvitation((string) $roomJid, $userJid)
        ;

        return $result;
    }

    public function addUsersToRoom($params = [], $defaultRoom = '', $defaultRole = 'members')
    {
        $params = array_map(function ($item) use ($defaultRoom, $defaultRole) {
            $status = is_array($item);
            $item = $status ? $item : [];

            $status = $status && ! empty($item['name']);
            $status = ! (empty($item['room']) && empty($defaultRoom));

            return array_merge([
                'role' => $defaultRole,
                'room' => $defaultRoom,
                'name' => '',
                'status' => $status
            ], $item);
        }, $params);

        $self = $this;
        return array_map(function ($item) use ($self) {
            if (empty($item['status'])) {
                return $item;
            }

            $response = $self->addUserToRoom($item['room'], $item['jid'], $item['role']);
            return array_merge($item, $response);
        }, $params);
    }

    public function add_group_to_room($room, $name, $role = 'members')
    {
        return [];
        //$restapi = $this->getRestApi();
        //return $restapi->addGroupRoleToChatRoom($room, $name, $role);
    }

    public function add_groups_to_room($params = [], $defaultRoom = '', $defaultRole = 'members')
    {
        $params = array_map(function ($item) use ($defaultRoom, $defaultRole) {
            $status = is_array($item);
            $item = $status ? $item : [];

            $status = $status && ! empty($item['name']);
            $status = ! (empty($item['room']) && empty($defaultRoom));

            return array_merge([
                'role' => $defaultRole,
                'room' => $defaultRoom,
                'name' => '',
                'status' => $status
            ], $item);
        }, $params);

        $self = $this;
        return array_map(function ($item) use ($self) {
            if (empty($item['status'])) {
                return $item;
            }

            $response = $self->add_group_to_room($item['room'], $item['name'], $item['role']);
            return array_merge($item, $response);
        }, $params);
    }

    public function get_groups()
    {
        //$restapi = $this->getRestApi();
        //$response = $restapi->getGroups();
        $response = [];

        $items = [];
        if (! empty($response['data']) && ! empty($response['data']->groups)) {
            $items = $response['data']->groups;
        }

        // groups has attr `name` and `description`
        // users  has attr `username` and `name`
        // let's make a common `name` and `fullname`
        return array_map(function ($item) {
            return [
                'name' => $item->name,
                'fullname' => $item->description
            ];
        }, $items);
    }

    /**
     * Return all known users with their JIDs
     * Works for both Prosody and Openfire setups.
     */
    public function getUsers()
    {
        $cachelib = TikiLib::lib('cache');

        $cache_key = 'xmppJidList';
        if ($items = $cachelib->getSerialized($cache_key)) {
            return $items;
        }

        $query = 'SELECT'
        . ' user as username,'
        . ' MAX(CASE WHEN `prefName`="xmpp_jid" THEN `value` END) AS `jid`,'
        . ' MAX(CASE WHEN `prefName`="realName" THEN `value` END) AS `name`'
        . ' FROM `tiki_user_preferences` WHERE'
        . ' `prefName` IN ("xmpp_jid", "realName")'
        . ' GROUP BY user;';

        $items = $this->query($query);
        $items = array_map(function ($item) {
            return [
                'name' => $item['username'],
                'fullname' => $item['name'],
                'jid' => $item['jid'] ?: "{$item['username']}@{$this->server_host}"
            ];
        }, $items->result);

        $cachelib->cacheItem($cache_key, serialize($items));
        return $items;
    }

    /** Reusable admin connection */
    private function getAdminXmppClient(): ?\Fabiang\Xmpp\Client
    {
        if ($this->adminClient instanceof \Fabiang\Xmpp\Client) {
            return $this->adminClient;
        }

        global $prefs;
        if (empty($prefs['xmpp_admin_jid']) || empty($prefs['xmpp_admin_password'])) {
            error_log('[XMPP] Missing xmpp_admin_jid/password in prefs');
            return null;
        }

        try {
            $port = (int) ($prefs['xmpp_client_port'] ?? 5222) ?: 5222;
            $options = new \Fabiang\Xmpp\Options("tcp://{$this->server_host}:{$port}");

            $admin = $prefs['xmpp_admin_jid'];
            if (strpos($admin, '@') !== false) {
                [$node, $domain] = explode('@', $admin, 2);
            } else {
                $node = $admin;
                $domain = $this->server_host; // e.g.: xmpp.local.test
            }
            $options->setUsername($node);
            $options->setPassword($prefs['xmpp_admin_password']);

            // Dev/local setup: allow self-signed SSL certificates for XMPP TCP connection
            $options->setContextOptions(['ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ]]);

            $this->adminClient = new \Fabiang\Xmpp\Client($options);
            $this->adminClient->connect();

            register_shutdown_function(function () {
                if ($this->adminClient) {
                    try {
                        $this->adminClient->disconnect();
                    } catch (\Throwable $e) {
                        error_log('[XMPP] Admin disconnect error: ' . $e->getMessage());
                    }
                }
            });

            return $this->adminClient;
        } catch (\Throwable $e) {
            error_log('[XMPP] Admin connect failed: ' . $e->getMessage());
            return null;
        }
    }

    /** Create/configure the room (persistent + logging) if necessary */
    public function ensureRoomExists(string $roomJid): bool
    {
        $client = $this->getAdminXmppClient();
        if (! $client) {
            return false;
        }

        try {
            require_once __DIR__ . '/MucJoin.php';
            $client->send(new \Tiki\Xmpp\MucJoin($roomJid, 'admin'));

            if (class_exists('\Tiki\Xmpp\MucConfigure')) {
                require_once __DIR__ . '/MucConfigure.php';
                $cfg = new \Tiki\Xmpp\MucConfigure($roomJid);
                $client->send($cfg);
                error_log("[XMPP] MUC configured for {$roomJid}");
            }

            return true;
        } catch (\Throwable $e) {
            error_log('[XMPP] ensureRoomExists error: ' . $e->getMessage());
            return false;
        }
    }

    /** Set a user's affiliation in the room (member/admin/owner) */
    public function setUserAffiliation(string $roomJid, string $userJid, string $affiliation = 'member'): bool
    {
        $client = $this->getAdminXmppClient();
        if (! $client) {
            return false;
        }

        try {
            require_once __DIR__ . '/MucAdmin.php';
            $iq = new \Tiki\Xmpp\MucAdmin($roomJid, $userJid, $affiliation);
            $client->send($iq);

            error_log("[XMPP] Set affiliation {$affiliation} for {$userJid} in {$roomJid}");
            return true;
        } catch (\Throwable $e) {
            error_log('[XMPP] setUserAffiliation error: ' . $e->getMessage());
            return false;
        }
    }

    public function getXmppSessionToken(string $username): string
    {
        global $prefs;

        $tokenlib = Tokens::build($prefs);

        $token = $tokenlib->createToken(
            'xmppauthtoken',
            ['user' => $username],
            [],
            [
                'timeout' => 300,
                'createUser' => 'n',
            ]
        );

        return $token;
    }
}
