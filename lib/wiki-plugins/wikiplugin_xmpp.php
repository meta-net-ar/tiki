<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function wikiplugin_xmpp_info()
{
    return [
        'name' => tra('Xmpp'),
        'documentation' => 'PluginXmpp',
        'description' => tra('Chat using Xmpp'),
        'prefs' => [ 'wikiplugin_xmpp' ],
        'iconname' => 'comments',
        'introduced' => 19,
        'params' => [
            'room' => [
                'required' => false,
                'name' => tra('Room Name'),
                'description' => tr('Room to auto-join'),
                'since' => 19,
                'default' => '',
                'filter' => 'text',
            ],
            'view_mode' => [
                'required' => false,
                'name' => tra('View Mode'),
                'description' => tra('Choose how the chat room is displayed: overlayed as a popup, embedded in the page, or fullscreen.'),
                'since' => 19,
                'default' => 'overlayed',
                'filter' => 'word',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Overlayed'), 'value' => 'overlayed'],
                    ['text' => tra('Embedded'), 'value' => 'embedded'],
                    ['text' => tra('Fullscreen'), 'value' => 'fullscreen'],
                ],
            ],
            'width' => [
                'required' => false,
                'name' => tra('Width'),
                'description' => tra('Chat room width in CSS units'),
                'since' => 19,
                'default' => '100%',
                'filter' => 'imgsize',
            ],
            'height' => [
                'required' => false,
                'name' => tra('Height'),
                'description' => tra('Chat room height in CSS units'),
                'since' => 19,
                'default' => '600px',
                'filter' => 'imgsize',
            ],
            'visibility' => [
                'required' => false,
                'name' => tra('Visibility'),
                'description' => tra('This room is visible to anyone or only for members'),
                'since' => 20,
                'filter' => 'alpha',
                'default' => 'anonymous',
                'options' => [
                    ['text' => tra('Members only'), 'value' => 'members_only'],
                    ['text' => tra('Anonymous'), 'value' => 'anonymous'],
                ],
            ],
            'can_anyone_discover_jid' => [
                'required' => false,
                'name' => tra('Show Real JIDs of Occupants to'),
                'description' => tra('If just moderator or anyone else can fetch information about an occupant.')
                    . tra('If just "moderator", anonymous user will not be able to change their nicknames.'),
                'since' => 20,
                'filter' => 'alpha',
                'default' => 'anyone',
                'options' => [
                    ['text' => tra('Anyone'), 'value' => 'anyone'],
                    ['text' => tra('Moderator'), 'value' => 'moderator'],
                ],
            ],
            'show_controlbox_by_default' => [
                'required' => false,
                'name' => tra('Show controlbox on load'),
                'description' => tra('If controlbox should be shown after page load.')
                    . ' ' . tra('This preference only works when view mode is overlayed'),
                'since' => 20,
                'filter' => 'alpha',
                'default' => 'n',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n'],
                ],
            ],
            'show_occupants_by_default' => [
                'required' => false,
                'name' => tra('Show occupants'),
                'description' => tra('If occupants window should be visible by default')
                    . ' ' . tra('This preference only works when view mode is embedded'),
                'since' => 20,
                'filter' => 'alpha',
                'default' => 'y',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n'],
                ],
            ],
            'groups' => [
                'name' => tra('Groups (comma-separated)'),
                'description' => tra('Allowed groups to use this resource'),
                'default' => '',
                'filter' => 'alpha',
                'required' => false,
                'separator' => ',',
            ],
            'secret' => [
                'name' => tra('Is secret?'),
                'description' => tra('If the room will be listed on public chat room list'),
                'default' => 'n',
                'filter' => 'alpha',
                'required' => false,
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n'],
                ],
            ],
            'archiving' => [
                'name' => tra('Archiving'),
                'description' => tra('If room messages will be stored'),
                'default' => 'y',
                'filter' => 'alpha',
                'required' => false,
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n'],
                ],
            ],
            'persistent' => [
                'name' => tra('Persistent'),
                'description' => tra('If room will continue to exist after last user leaves'),
                'default' => 'y',
                'filter' => 'alpha',
                'required' => false,
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n'],
                ],
            ],
            'moderated' => [
                'name' => tra('Moderated'),
                'description' => tra('If room is moderated'),
                'default' => 'y',
                'filter' => 'alpha',
                'required' => false,
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n'],
                ],
            ],
            'auto_open' => [
                'name' => tra('Auto-open'),
                'description' => tra('If this room should open automatically instead of staying minimized/hidden until the visitor clicks the chat button. Always shown when view_mode is embedded or fullscreen, regardless of this setting.'),
                'default' => 'n',
                'filter' => 'alpha',
                'required' => false,
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n'],
                ],
            ],
        ],
    ];
}

function wikiplugin_xmpp($data, $params)
{
    global $user, $prefs, $tiki_p_list_users, $tiki_p_admin;

    $headerlib = TikiLib::lib('header');
    $servicelib = TikiLib::lib('service');
    $smarty = TikiLib::lib('smarty');

    $params['view_mode'] = $params['view_mode'] ?? 'overlayed';
    $pluginDefaultViewMode = $params['view_mode'];
    $viewModeCookie = $_COOKIE['tiki_xmpp_view_mode'] ?? '';
    [$cookieDefault, $cookieChoice] = array_pad(explode(':', $viewModeCookie, 2), 2, '');
    if ($cookieDefault === $pluginDefaultViewMode && in_array($cookieChoice, ['overlayed', 'embedded', 'fullscreen'], true)) {
        $params['view_mode'] = $cookieChoice;
    }
    $params['width'] = $params['width'] ?? '100%';
    $params['height'] = $params['height'] ?? '600px';
    if (! isset($params['auto_open'])) {
        $params['auto_open'] = 'n';
    }

    $params['anonymous_auto_open'] = $params['auto_open'];

    if (empty($params['room'])) {
        Feedback::error(tr('PluginXMPP Error: No room specified'));
        return '';
    }

    $visibility = $params['visibility'] ?? 'anonymous';
    $params['anonymous'] = $visibility === 'anonymous' ? 'y' : 'n';

    if (empty($user) && $params['anonymous'] !== 'y') {
        return '<div class="alert alert-warning">'
            . tra('This chat room is restricted.')
            . '</div>';
    }

    if (! empty($user) && ($params['anonymous'] ?? 'n') !== 'y') {
        $xmpplib = TikiLib::lib('xmpp');

        $requestedFull = $xmpplib->buildRoomJid($params['room']);

        $authorizedRooms = $xmpplib->getXmppRoomsForUser($user);
        $authorizedFull = array_map(fn($r) => $xmpplib->buildRoomJid($r), $authorizedRooms);

        if (! in_array($requestedFull, $authorizedFull, true)) {
            return '<div class="alert alert-warning">'
                . tra('This chat room is restricted.')
                . '</div>';
        }
    }

    $xmpplib = $xmpplib ?? TikiLib::lib('xmpp');
    $roomJid = $xmpplib->buildRoomJid($params['room']);
    $roomConfig = [
        'muc#roomconfig_persistentroom' => ($params['persistent'] ?? 'y') === 'y' ? '1' : '0',
        'muc#roomconfig_enablelogging' => ($params['archiving'] ?? 'y') === 'y' ? '1' : '0',
        'muc#roomconfig_moderatedroom' => ($params['moderated'] ?? 'y') === 'y' ? '1' : '0',
        'muc#roomconfig_publicroom' => ($params['secret'] ?? 'n') === 'y' ? '0' : '1',
    ];
    $configCacheKey = md5(serialize($roomConfig));
    if (($_SESSION['xmpp_room_configured'][$roomJid] ?? '') !== $configCacheKey) {
        $xmpplib->ensureRoomExists($roomJid, $roomConfig);
        $_SESSION['xmpp_room_configured'][$roomJid] = $configCacheKey;
    }

    $identitySwitcher = '';
    if (! empty($user)) {
        $jidInfo = $xmpplib->getJidInfoForUser($user);
        if ($jidInfo['isExternal']) {
            $usingExternal = ($_COOKIE['tiki_xmpp_external'] ?? '') === '1';
            $localJid = htmlspecialchars((string) ($jidInfo['localJid'] ?? ''), ENT_QUOTES, 'UTF-8');
            $externalJid = htmlspecialchars((string) ($jidInfo['externalJid'] ?? ''), ENT_QUOTES, 'UTF-8');

            $identityButton = function (string $identity, string $jidLabel, bool $active) {
                $class = $active ? 'btn btn-sm btn-primary' : 'btn btn-sm btn-outline-primary';
                $check = $active ? '<i class="fa fa-check-circle me-1" aria-hidden="true"></i>' : '';
                return '<button type="button" class="' . $class . '" data-tiki-xmpp-identity="' . $identity . '"'
                    . ' aria-pressed="' . ($active ? 'true' : 'false') . '">' . $check . $jidLabel . '</button>';
            };

            $identitySwitcher = '<div class="tiki-xmpp-identity-switcher mb-3 d-flex flex-wrap align-items-center gap-2" role="group" aria-label="' . tra('Chat identity') . '">'
                . '<strong>' . tra('Chat as:') . '</strong> '
                . $identityButton('local', $localJid, ! $usingExternal)
                . $identityButton('external', $externalJid, $usingExternal)
                . '</div>';
        }
    }

    $conversejsTag = $params['view_mode'] === 'embedded' ? 'converse-root' : 'div';
    $result = $identitySwitcher
        . '<style type="text/css">#page-bar .dropdown-menu { z-index: 1031; }</style>'
        . '<' . $conversejsTag . ' id="conversejs"'
        . ' data-view-mode="' . $params['view_mode'] . '"'
        . ' data-plugin-default-view-mode="' . htmlspecialchars($pluginDefaultViewMode, ENT_QUOTES, 'UTF-8') . '"'
        . ' style="' . "width:{$params['width']}; height:{$params['height']}" . '"'
        . '></' . $conversejsTag . '>';

    unset($params['width'], $params['height']);

    $openfire_api_enabled = ! empty($prefs['xmpp_openfire_rest_api']);
    $openfire_api_enabled = $openfire_api_enabled && ! empty($prefs['xmpp_openfire_rest_api_username']);
    $openfire_api_enabled = $openfire_api_enabled && ! empty($prefs['xmpp_openfire_rest_api_password']);
    $openfire_api_enabled = $openfire_api_enabled && ! empty($params['room']);
    $openfire_api_enabled = $openfire_api_enabled && $tiki_p_list_users === 'y';
    $openfire_api_enabled = $openfire_api_enabled && $tiki_p_admin === 'y';

    if ($openfire_api_enabled) {
        $url = $servicelib->getUrl(['controller' => 'xmpp', 'action' => 'groups_in_room']);
        $item = '<a class="dropdown-item btn btn-link"'
            . ' data-xmpp="' . $params['room'] . '"'
            . ' data-xmpp-action="' . $url . '"'
            . '>' . tra('Add a group to room') . '</a>';
        $smarty->append('tiki_page_bar_more_items', $item);

        $url = $servicelib->getUrl(['controller' => 'xmpp', 'action' => 'users_in_room']);
        $item = '<a class="dropdown-item btn btn-link"'
            . ' data-xmpp="' . $params['room'] . '"'
            . ' data-xmpp-action="' . $url . '"'
            . '>' . tra('Add users to room') . '</a>';
        $smarty->append('tiki_page_bar_more_items', $item);
        unset($url, $item);
    }

    $javascript = 'lib/jquery_tiki/wikiplugin-xmpp.js';
    $headerlib->add_jsfile_late($javascript . '?_=' . filemtime(TIKI_PATH . "/$javascript"), false);

    $params['on_xmpp_page'] = 'y';
    $params['auto_open'] = $params['auto_open'] ?? 'n';
    TikiLib::lib('xmpp')->render_xmpp_client($params);

    $result .= $smarty->fetch('wiki-plugins/wikiplugin_xmpp.tpl');

    return $result;
}
