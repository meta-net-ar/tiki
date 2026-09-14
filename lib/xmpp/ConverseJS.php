<?php

class ConverseJS
{
    public const CACHE_EPOCH = '14.0.49-enable-muc-mam-fetch-before-join';

    /**
     * Tiki-owned PHP/JS settings contract. Keep keys, types, allowed values and
     * defaults aligned with TIKI_SETTINGS_CONTRACT in conversejs-tiki.js.
     * A regression test checks both definitions. set_option() validates all
     * contracted keys; render() uses the same validator for presentation text.
     * These are Tiki extensions, not stock Converse configuration options.
     */
    private static $TIKI_JS_SETTINGS_CONTRACT = [
        'dm_target' => ['type' => 'string', 'default' => null, 'doc' => 'Bare JID of the private support contact for anonymous guests. Set by set_auth() when xmpp_anonymous_mode is "support".'],
        'anon_room' => ['type' => 'string', 'default' => null, 'doc' => 'JID of the community MUC room anonymous guests are dropped into. Set by set_auth() from xmpp_anonymous_room.'],
        'anonymous' => ['type' => 'string', 'values' => ['y', 'n'], 'default' => null, 'doc' => '"y" when this is a Tiki guest session; independent of the XMPP authentication mechanism.'],
        'anonymous_auto_open' => ['type' => 'string', 'values' => ['y', 'n'], 'default' => null, 'doc' => '"n"/unset prepares the guest support DM; "y" skips preparation, without opening it or bypassing the chooser.'],
        'auto_open' => ['type' => 'string', 'values' => ['y', 'n'], 'default' => null, 'doc' => '"y" opens the configured authenticated DM once on the dedicated embedded/fullscreen page. Set by set_auth()/getUserAuthOptions() from the `auto_open` request param.'],
        'on_xmpp_page' => ['type' => 'string', 'values' => ['y', 'n'], 'default' => null, 'doc' => '"y" when Converse is embedded on the dedicated tiki-xmpp page. Set by set_auth().'],
        'current_room' => ['type' => 'string', 'default' => null, 'doc' => 'JID of the room the current tiki-xmpp page should show/join. Set by set_auth() from the `room` request param.'],
        'external_jid' => ['type' => 'string', 'default' => null, 'doc' => "Bare JID of the user's external (non-Tiki-domain) XMPP account, when available. Set by getUserAuthOptions()."],
        'using_external' => ['type' => 'string', 'values' => ['y', 'n'], 'default' => null, 'doc' => '"y" when the session authenticated with external_jid rather than the Tiki-internal JID. Set by getUserAuthOptions().'],
        'local_jid' => ['type' => 'string', 'default' => null, 'doc' => 'The Tiki-internal JID counterpart to external_jid. Set by getUserAuthOptions().'],
        'tiki_site_name' => ['type' => 'string', 'default' => 'Tiki', 'doc' => 'Site display name for chat UI chrome.'],
        'tiki_site_logo' => ['type' => 'string', 'default' => '', 'doc' => 'Relative or absolute URL of the site logo, or empty to let the browser plugin auto-detect one already rendered on the page.'],
        'tiki_support_label' => ['type' => 'string', 'default' => 'Support', 'doc' => "Display name substituted for dm_target's JID in chat headers."],
        'tiki_destination_close_label' => ['type' => 'string', 'default' => 'Close', 'doc' => 'aria-label for the close button of the anonymous destination-choice dialog.'],
        'tiki_destination_title' => ['type' => 'string', 'default' => 'How would you like to chat?', 'doc' => 'Heading of the anonymous destination-choice dialog.'],
        'tiki_destination_description' => ['type' => 'string', 'default' => 'Community chat is best for general questions and shared discussion. Choose private support for help specific to you.', 'doc' => 'Body text of the anonymous destination-choice dialog.'],
        'tiki_destination_community_label' => ['type' => 'string', 'default' => 'Join community chat', 'doc' => 'Label of the "join the public room" button in the destination-choice dialog.'],
        'tiki_destination_private_label' => ['type' => 'string', 'default' => 'Start private chat', 'doc' => 'Label of the "start a private support chat" button in the destination-choice dialog.'],
        'tiki_support_end_title' => ['type' => 'string', 'default' => 'End this conversation?', 'doc' => 'Heading of the guest session end confirmation.'],
        'tiki_support_end_description' => ['type' => 'string', 'default' => 'Your guest session will end and you will leave all chats. When you start chatting again, you will have a new guest identity. Previous messages may remain visible to other participants.', 'doc' => 'Explains the consequences of ending a guest session.'],
        'tiki_support_end_cancel_label' => ['type' => 'string', 'default' => 'Continue chatting', 'doc' => 'Cancels ending the guest session.'],
        'tiki_support_end_confirm_label' => ['type' => 'string', 'default' => 'End conversation', 'doc' => 'Confirms ending the guest session.'],
        'tiki_anonymous_nick' => ['type' => 'string', 'values' => ['custom', 'visitor'], 'default' => 'visitor', 'doc' => '"custom" lets an anonymous guest set/keep their own MUC nickname; "visitor" uses the existing nickname fallback, generating a visitor nickname when needed. Set by set_auth() from xmpp_anonymous_allow_custom_nickname.'],
    ];

    private $options;
    private $tiki_root;

    public function __construct($options = [])
    {
        $this->options = [];
        $this->set_options(array_merge(
            [
                'auto_reconnect' => true,
                'debug' => false,
                'show_controlbox_by_default' => false,
                'show_occupants_by_default' => true,
                'use_emojione' => false,
                'view_mode' => 'overlayed',
                'auto_register_muc_nickname' => false,
                'muc_show_logs_before_join' => true,
                'assets_path'   => CONVERSEJS_DIST_PATH . '/',
                'whitelisted_plugins' => ['tiki', 'tiki-oauth'],
                'blacklisted_plugins' => ['converse-omemo'],
                'omemo_default' => false,
                'discover_connection_methods' => false,
            ],
            $options
        ));
        $this->tiki_root = dirname(dirname(__DIR__));
        $this->load_prefs();
    }

    public function load_prefs()
    {
        global $prefs;
        $this->set_option('debug', $prefs['xmpp_conversejs_debug'] === 'y');

        if (! empty($prefs['xmpp_conversejs_init_json'])) {
            $extraOptions = json_decode($prefs['xmpp_conversejs_init_json'], true);
            if ($extraOptions) {
                $this->set_options($extraOptions);
            }
        }

        if (! empty($prefs['xmpp_muc_component_domain'])) {
            $this->set_option('muc_domain', $prefs['xmpp_muc_component_domain']);
        }

        if (! empty($prefs['xmpp_ws_url'])) {
            $this->set_option('websocket_url', $prefs['xmpp_ws_url']);
        }
    }

    public function get_oauth_parameters()
    {
        $client_id = 'org.tiki.rtc.internal-conversejs-id';
        $oauthserverlib = TikiLib::lib('oauthserver');
        $accesslib = TikiLib::lib('access');

        $client = $oauthserverlib->getClient($client_id)
            ?: $oauthserverlib->createClient([
                'client_id' => $client_id,
                'name' => 'ConverseJS OAuth Client',
                'redirect_uri' => $accesslib->absoluteUrl('lib/xmpp/html/redirect.html')
            ]);

        return [
            'client_id' => $client->getClientId(),
            'name' => $client->getName(),
            'authorize_url' => TikiLib::lib('service')->getUrl([
                'action' => 'authorize',
                'controller' => 'oauthserver',
                'client_id' => $client_id,
                'response_type' => 'token',
                'skip_keypair' => 1,
            ])
        ];
    }

    public function set_auth($params)
    {
        global $user;
        global $prefs;
        $authMethod = TikiLib::lib('tiki')->get_preference('xmpp_auth_method');
        $this->set_option('on_xmpp_page', $params['on_xmpp_page'] ?? 'n');

        $currentRoom = trim($params['room'] ?? '');
        if ($currentRoom !== '') {
            $currentRoom = TikiLib::lib('xmpp')->buildRoomJid($currentRoom);
        }
        $this->set_option('current_room', $currentRoom);

        // Anonymous login
        if (empty($user) && isset($params['anonymous']) && $params['anonymous'] === 'y') {
            $autoOpen = $params['auto_open'] ?? 'n';
            $onXmppPage = ($params['on_xmpp_page'] ?? 'n') === 'y';
            $anonymousMode = $prefs['xmpp_anonymous_mode'] ?? 'community';
            $anonRoom = '';
            $dmTarget = '';

            if ($onXmppPage) {
                $anonRoom = trim($prefs['xmpp_anonymous_room'] ?? '');
            } elseif ($anonymousMode === 'support') {
                $dmTarget = trim($params['dm_target'] ?? ($prefs['xmpp_admin_jid'] ?? ''));
                $anonRoom = trim($prefs['xmpp_anonymous_room'] ?? '');
            } else {
                $anonRoom = trim($prefs['xmpp_anonymous_room'] ?? '');
            }

            $this->set_options([
                'authentication'      => 'anonymous',
                'auto_login'          => true,
                'jid'                 => $prefs['xmpp_domain_guest'] ?? null,
                'bosh_service_url'    => $prefs['xmpp_server_http_bind'] ?? null,
                'websocket_url'       => $prefs['xmpp_ws_url'] ?? null,
                'dm_target'           => $dmTarget,
                'anon_room'           => $anonRoom,
                'tiki_anonymous_nick' => ($prefs['xmpp_anonymous_allow_custom_nickname'] ?? 'n') === 'y' ? 'custom' : 'visitor',
                'auto_open'           => $autoOpen,
                'allow_contact_requests' => false,
            ]);
        // Prebind (tikitoken)
        } elseif ($authMethod === 'tikitoken') {
            $this->set_options([
                'auto_login' => true,
                'authentication'   => 'prebind',
                'prebind_url'      => TikiLib::lib('service')->getUrl([
                    'action' => 'prebind',
                    'controller' => 'xmpp',
                ]),
            ]);
        } elseif ($authMethod === 'oauth') {
            $this->set_options([
                'authentication'   => 'login',
                'oauth_providers' => [
                    'tiki' => $this->get_oauth_parameters(),
                ]]);
        } elseif ($authMethod === 'http') {
            if (! empty($user)) {
                $autoOpen = $params['auto_open'] ?? 'n';
                $this->set_options($this->getUserAuthOptions($user, $autoOpen));
            }
        } else {
            global $user, $prefs;
            if (! empty($user)) {
                $autoOpen = $params['auto_open'] ?? 'n';
                $this->set_options($this->getUserAuthOptions($user, $autoOpen));
            }
        }
    }

    private function getUserAuthOptions(string $user, string $auto_open = 'n'): array
    {
        $xmpplib = TikiLib::lib('xmpp');
        $xmpp = $xmpplib->get_user_connection_info($user);
        $jidInfo = $xmpplib->getJidInfoForUser($user);
        $externalAvailable = $jidInfo['isExternal'];
        $onXmppPage = $this->get_option('on_xmpp_page') === 'y';
        $useExternalNow = $externalAvailable && ($_COOKIE['tiki_xmpp_external'] ?? '') === '1';

        if ($useExternalNow) {
            $transportOptions = $this->getTransportOptions($xmpp, true);
            // The saved password stays server-side. Prebind returns only BOSH
            // session credentials; WebSocket-only accounts keep the login form.
            if ($xmpplib->canPrebindExternal($user, $xmpp)) {
                return array_merge([
                    'authentication' => 'prebind',
                    'auto_login' => true,
                    'prebind_url' => TikiLib::lib('service')->getUrl([
                        'controller' => 'xmpp',
                        'action' => 'external_prebind',
                    ]),
                    'jid' => $jidInfo['jid'],
                    'auto_open' => $auto_open,
                    'external_jid' => $jidInfo['jid'],
                    'local_jid' => $jidInfo['localJid'] ?? '',
                    'using_external' => 'y',
                    'discover_connection_methods' => false,
                ], $transportOptions, ['websocket_url' => '']);
            }

            return array_merge([
                'authentication' => 'login',
                'auto_login'     => false,
                'jid'            => $jidInfo['jid'],
                'auto_open'      => $auto_open,
                'external_jid'   => $jidInfo['jid'],
                'local_jid'      => $jidInfo['localJid'] ?? '',
                'using_external' => 'y',
                'discover_connection_methods' => empty($xmpp['custom_endpoint']),
            ], $transportOptions);
        }

        $transportOptions = $this->getTransportOptions($xmpp, false);
        return array_merge([
            'authentication' => 'login',
            'auto_login'     => true,
            'jid'            => $jidInfo['localJid'] ?? '',
            'password'       => $xmpplib->getXmppSessionToken($user),
            'auto_open'      => $auto_open,
            'external_jid'   => ($onXmppPage && $externalAvailable) ? $jidInfo['jid'] : '',
            'local_jid'      => $jidInfo['localJid'] ?? '',
            'using_external' => 'n',
        ], $transportOptions);
    }

    private function getTransportOptions(array $xmpp, bool $usesExternalJid): array
    {
        global $prefs;

        if (! $usesExternalJid) {
            return [
                'websocket_url'    => $prefs['xmpp_ws_url'] ?? '',
                'bosh_service_url' => $prefs['xmpp_server_http_bind'] ?? '',
            ];
        }

        if (empty($xmpp['custom_endpoint'])) {
            return [];
        }

        return [
            'websocket_url'    => $xmpp['websocket_url'] ?? '',
            'bosh_service_url' => $xmpp['http_bind'] ?? '',
        ];
    }

    public function set_options($options)
    {
        foreach ($options as $name => $value) {
            $this->set_option($name, $value);
        }
    }

    public function get_options()
    {
        return $this->options ?: [];
    }

    public function set_option($name, $value)
    {
        $this->options[ $name ] = self::validateTikiSetting($name, $value);
    }

    /**
     * Validate every Tiki-owned option at its PHP boundary. Preserve flat keys
     * and string flags for existing callers; Converse-owned options pass through.
     */
    private static function validateTikiSetting($key, $value)
    {
        if (! isset(self::$TIKI_JS_SETTINGS_CONTRACT[$key])) {
            return $value;
        }
        $rule = self::$TIKI_JS_SETTINGS_CONTRACT[$key];
        if ($value === null) {
            return $rule['default'];
        }
        if (! is_string($value) || (isset($rule['values']) && ! in_array($value, $rule['values'], true))) {
            // Do not expose identifiers or other configuration values in logs.
            TikiLib::lib('logs')->add_log('xmpp', 'ConverseJS: invalid Tiki setting "' . $key . '"; using its documented default.');
            return $rule['default'];
        }
        return $value;
    }

    public function get_option($name, $fallback = null)
    {
        if (isset($this->options[$name])) {
            return $this->options[$name];
        }
        return $fallback;
    }

    public function set_auto_join_rooms($room)
    {
        if (! is_string($room) || empty($room)) {
            return;
        }

        $marker = strrpos($room, '@');
        $domain = $this->get_option('muc_domain');

        if (! $marker && $domain) {
            $room = $room . '@' . $domain;
        }

        $this->options['auto_join_rooms'] = [ $room ];
    }

    public function append_mtime($file, $argname = '_')
    {
        $mtime = '';
        $file_path = $this->tiki_root . '/' . $file;

        if (! file_exists($file_path)) {
            return $file;
        }
        $mtime = stat($file_path)['mtime'];
        return "{$file}?{$argname}={$mtime}";
    }

    public function get_css_dependencies()
    {
        $deps = [
            CONVERSEJS_DIST_PATH . '/converse.min.css',
            'lib/xmpp/css/conversejs.css',
            JS_ASSETS_PATH . '/xmpp/conversejs-tiki.css',
        ];
        return array_map([$this, 'append_mtime'], $deps);
    }

    public function get_js_dependencies()
    {
        $deps = [
            // Loaded first: conversejs-tiki.js (and, in its presentation
            // module, conversejs-tiki-presentation.js) read
            // window.TikiConverseStorage/window.TikiConversePresentation
            // when their plugin initialize() runs, so both must have run
            // by then. Both files avoid import/export syntax so they load
            // identically here (classic <script> tags, when
            // xmpp_conversejs_always_load is "y") and via
            // registerJsDependencies()'s dynamic import() below.
            'lib/xmpp/js/conversejs-tiki-storage.js',
            'lib/xmpp/js/conversejs-tiki-presentation.js',
            'lib/xmpp/js/conversejs-tiki.js',
            'lib/xmpp/js/conversejs-tiki-oauth.js',
        ];
        return array_map([$this, 'append_mtime'], $deps);
    }

    public function registerJsDependencies()
    {
        $header = TikiLib::lib('header');
        $pluginImports = array_map(
            fn($file) => 'await import(new URL(' . json_encode($file) . ', document.baseURI).href);',
            $this->get_js_dependencies()
        );

        $header->add_js_module(
            "import * as converseModule from 'converse.js';"
            . PHP_EOL . "window.tikiConverseReady = (async function () {"
            . PHP_EOL . "    const converse = converseModule.default || converseModule.converse || window.converse || (typeof converseModule.initialize === 'function' ? converseModule : null);"
            . PHP_EOL . "    if (!converse || typeof converse.initialize !== 'function') {"
            . PHP_EOL . "        throw new Error('converse is not defined');"
            . PHP_EOL . "    }"
            . PHP_EOL . "    window.converse = converse;"
            . PHP_EOL . "    " . implode(PHP_EOL . "    ", $pluginImports)
            . PHP_EOL . "    return converse;"
            . PHP_EOL . "})();"
        );
    }

    public function render()
    {
        global $user, $prefs;
        $options = $this->get_options();

        if (! isset($options['auto_open'])) {
            $options['auto_open'] = 'n';
        }

        if (! isset($options['anonymous_auto_open'])) {
            $options['anonymous_auto_open'] = 'n';
        }

        $output = ';(async function () {' . PHP_EOL . 'try {' . PHP_EOL;
        $output .= <<<JS
// The dependency promise resolves after the storage and presentation helpers load.
await (window.tikiConverseReady || Promise.resolve(window.converse));
var storage = window.TikiConverseStorage;
if (!storage) throw new Error('TikiConverseStorage is not loaded');
var initializeConverse = function (opts) {
    var ready = window.tikiConverseReady || Promise.resolve(window.converse);
    return ready.then(function (converse) {
        if (!converse || typeof converse.initialize !== 'function') {
            throw new Error('converse is not defined');
        }
        return converse.initialize(opts);
    });
};

JS;

        if ($prefs['xmpp_conversejs_always_load'] !== 'y') {
            $this->registerJsDependencies();

            $output .= PHP_EOL . ';(function() {';
            $output .= PHP_EOL . '    var link;';
            foreach ($this->get_css_dependencies() as $file) {
                $output .= PHP_EOL . '    link = document.createElement("link");';
                $output .= PHP_EOL . '    link.rel = "stylesheet";';
                $output .= PHP_EOL . '    link.href = "' . $file . '";';
                $output .= PHP_EOL . '    document.body.appendChild(link);';
            }
            $output .= PHP_EOL . '})();';
        }

        if (empty($_SESSION['chat-session-init'])) {
            $_SESSION['chat-session-init'] = time();
        }
        $chat_session_init = ($user ?: 'anonymous') . '|' . ($options['jid'] ?? '') . '|' . $_SESSION['chat-session-init'] . '|' . self::CACHE_EPOCH;
        $chat_session_init_js = json_encode($chat_session_init);

        $output .= PHP_EOL . ';(function(){';
        $output .= PHP_EOL . "  if (storage.get('localStorage', 'chat-session-init') !== {$chat_session_init_js}) {";
        $output .= PHP_EOL . '      console.warn("Cleaning environment for ' . 'converse session change");';
        $output .= PHP_EOL . "      storage.set('localStorage', 'chat-session-init', {$chat_session_init_js});";
        $output .= PHP_EOL . '      storage.remove("localStorage", "tiki-xmpp-guest-identity");';
        $output .= PHP_EOL . '      storage.clearMatching("localStorage", /(converse|strophe)/);';
        $output .= PHP_EOL . '      storage.clearMatching("sessionStorage", /(converse|strophe)/);';
        $output .= PHP_EOL . '      storage.clearIndexedDbMatching(/(converse|strophe)/i);';
        $output .= PHP_EOL . "  }";
        $output .= PHP_EOL . '})();';
        $output .= PHP_EOL;

        if ($this->get_option('view_mode') === 'embedded') {
            $currentRoom = $this->get_option('current_room');
            $dmTarget = $this->get_option('dm_target');
            if ($currentRoom) {
                $options['singleton'] = true;
                $options['auto_join_rooms'] = [$currentRoom];
            } elseif ($dmTarget) {
                $options['singleton'] = true;
                $options['auto_join_private_chats'] = [$dmTarget];
            }
        }

        $options['auto_open'] = $this->get_option('auto_open', 'n');
        $options['anonymous_auto_open'] = $this->get_option('anonymous_auto_open', 'n');
        $usingExternal = $this->get_option('using_external') === 'y';
        $options['bookmarks'] = $usingExternal;
        $options['auto_join_bookmarks'] = $usingExternal;
        $options['show_desktop_notifications'] = true;
        $options['show_tab_notifications'] = true;
        $options['play_sounds'] = true;
        $options['notify_all_room_messages'] = true;
        $options['omemo_default'] = false;
        $siteName = trim((string) ($prefs['sitetitle'] ?? ''))
            ?: trim((string) ($prefs['browsertitle'] ?? ''))
            ?: trim((string) ($prefs['sitelogo_title'] ?? ''))
            ?: 'Tiki';

        // Tiki-only presentation strings from the settings contract (see
        // self::$TIKI_JS_SETTINGS_CONTRACT above). Validating each key
        // against that contract here - the one place these are assembled -
        // means a typo'd or renamed key is reported immediately instead of
        // silently reaching the browser as a setting conversejs-tiki.js
        // never reads.
        $tikiUiOptions = [
            'tiki_site_name' => $siteName,
            // An empty value is intentional: the browser plugin then
            // discovers the logo currently rendered by Tiki instead of
            // assuming a particular file.
            'tiki_site_logo' => trim((string) ($prefs['sitelogo_src'] ?? '')),
            'tiki_support_label' => tra('Support team'),
            'tiki_destination_close_label' => tra('Close'),
            'tiki_destination_title' => tra('How would you like to chat?'),
            'tiki_destination_description' => tra('Community chat is best for general questions and shared discussion. Choose private support for help specific to you.'),
            'tiki_destination_community_label' => tra('Join community chat'),
            'tiki_destination_private_label' => tra('Start private chat'),
            'tiki_support_end_title' => tra('End this conversation?'),
            'tiki_support_end_description' => tra('Your guest session will end and you will leave all chats. When you start chatting again, you will have a new guest identity. Previous messages may remain visible to other participants.'),
            'tiki_support_end_cancel_label' => tra('Continue chatting'),
            'tiki_support_end_confirm_label' => tra('End conversation'),
        ];
        foreach ($tikiUiOptions as $key => $value) {
            if (! array_key_exists($key, self::$TIKI_JS_SETTINGS_CONTRACT)) {
                // Should only happen if this array and the contract above
                // are edited out of step with each other.
                trigger_error("ConverseJS: '$key' is not declared in \$TIKI_JS_SETTINGS_CONTRACT", E_USER_WARNING);
                continue;
            }
            $options[$key] = self::validateTikiSetting($key, $value);
        }

        if ($this->get_option('authentication') === 'anonymous') {
            $guestDomain = $this->get_option('jid') ?: ($prefs['xmpp_domain_guest'] ?? '');

            unset($options['authentication'], $options['jid'], $options['auto_login']);
            $options['authentication'] = 'login';
            $options['auto_login'] = true;

            ksort($options);
            $optionsWithoutCreds = json_encode($options, JSON_PRETTY_PRINT);
            $guestDomainJs = json_encode($guestDomain);

            $guestIdentityStorageKey = json_encode('tiki-xmpp-guest-identity');

            $output .= <<<JS

;(function () {
    try {
        var STORAGE_KEY = {$guestIdentityStorageKey};
        var identity = storage.getObject('localStorage', STORAGE_KEY, null);
        if (!identity || !identity.jid || !identity.password) {
            var cryptoObj = window.crypto || window.msCrypto;
            if (!cryptoObj || !cryptoObj.getRandomValues) {
                throw new Error('window.crypto unavailable - cannot generate guest identity');
            }
            var randHex = function (len) {
                var arr = new Uint8Array(len);
                cryptoObj.getRandomValues(arr);
                return Array.prototype.map.call(arr, function (b) {
                    return ('0' + b.toString(16)).slice(-2);
                }).join('');
            };
            identity = {
                jid: 'guest-' + randHex(10) + '@' + {$guestDomainJs},
                password: randHex(24)
            };
            storage.setJSON('localStorage', STORAGE_KEY, identity);
        }
        var opts = {$optionsWithoutCreds};
        opts.jid = identity.jid;
        opts.password = identity.password;
        initializeConverse(opts).catch(function (e) {
            console.error('[tiki-guest] FATAL - ConverseJS initialization failed:', e);
        });
    } catch (e) {
        console.error('[tiki-guest] FATAL - guest identity setup failed:', e);
    }
})();

JS;

            $output .= PHP_EOL . '} catch (e) { console.error(\'[tiki-xmpp] FATAL - setup failed:\', e); }' . PHP_EOL . '})();';
            return TikiLib::lib('header')->add_jq_onready($output, 10);
        }

        ksort($options);

        // Do not use JSON_UNESCAPED_SLASHES to avoid </script> injection.
        $optionString = json_encode($options, JSON_PRETTY_PRINT);
        $output .= 'initializeConverse(' . $optionString . ').catch(function (e) {' . PHP_EOL;
        $output .= '    console.error(\'[tiki-xmpp] FATAL - ConverseJS initialization failed:\', e);' . PHP_EOL;
        $output .= '});' . PHP_EOL;
        $output .= '} catch (e) { console.error(\'[tiki-xmpp] FATAL - setup failed:\', e); }' . PHP_EOL . '})();';
        return TikiLib::lib('header')->add_jq_onready($output, 10);
    }
}
