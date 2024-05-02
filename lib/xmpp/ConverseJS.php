<?php

class ConverseJS
{
    private $options;
    private $tiki_root;

    public function __construct($options = [])
    {
        $this->options = [];
        $this->set_options(array_merge(
            [
                'auto_reconnect' => true,
                'debug' => false,
                'show_controlbox_by_default' => true,
                'show_occupants_by_default' => true,
                'use_emojione' => false,
                'view_mode' => 'overlayed',
                'assets_path'   => CONVERSEJS_DIST_PATH . '/',
                'whitelisted_plugins' => ['tiki', 'tiki-oauth'],
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
        $authMethod = TikiLib::lib('tiki')->get_preference('xmpp_auth_method');

        if (empty($user) && isset($params['anonymous']) && $params['anonymous'] === 'y') {
            $this->set_options([
                'authentication'   => 'anonymous',
                'auto_login'       => true,
            ]);
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
        } else {
            $this->set_options([
                'authentication' => 'login'
            ]);
        }
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
        $this->options[ $name ] = $value;
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
        ];
        return array_map([$this, 'append_mtime'], $deps);
    }

    public function get_js_dependencies()
    {
        $deps = [
            CONVERSEJS_DIST_PATH . '/converse.min.js',
            'lib/xmpp/js/conversejs-tiki.js',
            'lib/xmpp/js/conversejs-tiki-oauth.js',
        ];
        return array_map([$this, 'append_mtime'], $deps);
    }

    public function render()
    {
        global $user, $prefs;
        $options = $this->get_options();

        $output = '';

        if ($prefs['xmpp_conversejs_always_load'] !== 'y') {
            array_map([TikiLib::lib('header'), 'add_jsfile'], $this->get_js_dependencies());

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
        $chat_session_init = ($user ?: 'anonymous') . $_SESSION['chat-session-init'];

        $output .= PHP_EOL . ';(function(){';
        $output .= PHP_EOL . "  if (localStorage['chat-session-init'] !== '{$chat_session_init}') {";
        $output .= PHP_EOL . '      console.warn("Cleaning environment for ' . $chat_session_init . '");';
        $output .= PHP_EOL . "      localStorage['chat-session-init'] = '{$chat_session_init}';";
        $output .= PHP_EOL . '      for (var key in localStorage) { key.match(/(converse|strophe)/) && localStorage.removeItem(key); }';
        $output .= PHP_EOL . '      for (var key in sessionStorage) { key.match(/(converse|strophe)/) && sessionStorage.removeItem(key); }';
        $output .= PHP_EOL . "  }";
        $output .= PHP_EOL . '})();';
        $output .= PHP_EOL;

        if ($this->get_option('view_mode') === 'embedded') {
            // TODO: remove this a line after fixing conversejs
            $output .= 'delete sessionStorage["converse.chatboxes-' . $this->get_option('jid') . '"];' . PHP_EOL;
            $output .= 'delete sessionStorage["converse.chatboxes-' . $this->get_option('jid') . '-controlbox"];' . PHP_EOL;
            $options['singleton'] = true;
        }

        ksort($options);

        $optionString = json_encode($options, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $output .= 'converse.initialize(' . $optionString . ');' . PHP_EOL;
        return TikiLib::lib('header')->add_jq_onready($output, 10);
    }
}
