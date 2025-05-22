<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

class OAuthLib extends TikiDb_Bridge
{
    private $providers = [];

    /**
     * Check if a token is already stored
     */
    public function is_authorized($provider_key)
    {
        return ! is_null($this->retrieve_token($provider_key));
    }

    /**
     * Handle OAuth API request with GET, POST, PATCH or DELETE using stored token
     */
    public function do_request($provider_key, $arguments)
    {
        $token = $this->retrieve_token($provider_key);
        $url = $arguments['url'];
        $method = isset($arguments['post']) ? 'POST'
                : (isset($arguments['patch']) ? 'PATCH'
                : (isset($arguments['delete']) ? 'DELETE' : 'GET'));

        $client = TikiLib::lib('tiki')->get_http_client($url);

        if (is_string($token)) {
            $client->setHeaders(['Authorization' => 'Bearer ' . $token]);
        }

        $client->setMethod($method);

        if (! empty($arguments['get'])) {
            $client->setParameterGet($arguments['get']);
        }

        if (! empty($arguments['post'])) {
            $client->setParameterPost($arguments['post']);
        } elseif (! empty($arguments['patch'])) {
            $client->setRawBody(json_encode($arguments['patch']));
            $client->setHeaders(['Content-Type' => 'application/json']);
        }

        try {
            return $client->send();
        } catch (\Exception $e) {
            error_log("OAuth request failed for $provider_key: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Start OAuth request flow (redirects to provider)
     */
    public function request_token($provider_key)
    {
        try {
            $provider = $this->get_consumer($provider_key);

            if ($provider instanceof \League\OAuth1\Client\Server\Server) {
                $tempCredentials = $provider->getTemporaryCredentials();
                $_SESSION['OAUTH_REQUEST_' . $provider_key] = serialize($tempCredentials);
                $authUrl = $provider->getAuthorizationUrl($tempCredentials);
            } else {
                $authUrl = $provider->getAuthorizationUrl();
                $_SESSION['OAUTH_STATE_' . $provider_key] = $provider->getState();
            }

            header('Location: ' . $authUrl);
            exit;
        } catch (\Exception $e) {
            error_log("OAuth request_token error for $provider_key: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Handle callback and exchange code for access token
     */
    public function request_access($provider_key)
    {
        try {
            $provider = $this->get_consumer($provider_key);

            if ($provider instanceof \League\OAuth1\Client\Server\Server) {
                $temp = @unserialize($_SESSION['OAUTH_REQUEST_' . $provider_key]);
                if (! $temp) {
                    throw new \RuntimeException("Missing OAuth1 temp credentials for $provider_key");
                }

                $token = $provider->getTokenCredentials(
                    $temp,
                    $_GET['oauth_token'],
                    $_GET['oauth_verifier']
                );
            } else {
                if (
                    empty($_GET['code']) ||
                    empty($_GET['state']) ||
                    $_GET['state'] !== ($_SESSION['OAUTH_STATE_' . $provider_key] ?? null)
                ) {
                    throw new \RuntimeException("Invalid or missing OAuth2 state/code for $provider_key");
                }

                $token = $provider->getAccessToken('authorization_code', [
                    'code' => $_GET['code']
                ]);
            }

            $this->store_token($provider_key, $token instanceof \League\OAuth2\Client\Token\AccessTokenInterface ? $token->getToken() : $token);

            unset($_SESSION['OAUTH_REQUEST_' . $provider_key], $_SESSION['OAUTH_STATE_' . $provider_key]);
            return true;
        } catch (\Exception $e) {
            error_log("OAuth request_access error for $provider_key: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Store token in user preference
     */
    private function store_token($provider_key, $accessToken)
    {
        TikiLib::lib('tiki')->set_preference('oauth_token_' . $provider_key, is_string($accessToken) ? $accessToken : serialize($accessToken));
    }

    /**
     * Retrieve token from preference
     */
    public function retrieve_token($provider_key)
    {
        $stored = TikiLib::lib('tiki')->get_preference('oauth_token_' . $provider_key);
        return $this->isSerialized($stored) ? @unserialize($stored) : $stored;
    }

    private function isSerialized($data)
    {
        return is_string($data) && preg_match('/^O:\d+:"/', $data);
    }

    /**
     * Return OAuth config array per provider
     */
    private function get_configuration($provider_key)
    {
        global $prefs;
        $tikilib = TikiLib::lib('tiki');
        $servicelib = TikiLib::lib('service');
        $callback = $tikilib->tikiUrl($servicelib->getUrl([
            'controller' => 'oauth',
            'action' => 'callback',
            'oauth_callback' => $provider_key,
        ]));

        return [
            'twitter' => [
                'clientId' => $prefs['socialnetworks_twitter_client_id'],
                'clientSecret' => $prefs['socialnetworks_twitter_client_secret'],
                'redirectUri' => $callback,
            ],
            'vimeo' => [
                'clientId' => $prefs['vimeo_consumer_key'],
                'clientSecret' => $prefs['vimeo_consumer_secret'],
                'redirectUri' => $callback,
            ],
            'zotero' => [
                'identifier' => $prefs['zotero_client_key'],
                'secret' => $prefs['zotero_client_secret'],
                'callback_uri' => $callback,
            ],
        ][$provider_key] ?? null;
    }

    /**
     * Instantiate and memoize OAuth consumer/provider
     */
    private function get_consumer($provider_key)
    {
        if (! isset($this->providers[$provider_key])) {
            $config = $this->get_configuration($provider_key);

            if (! $config) {
                throw new \RuntimeException("Missing configuration for OAuth provider: $provider_key");
            }

            switch ($provider_key) {
                case 'twitter':
                    $this->providers[$provider_key] = new \League\OAuth2\Client\Provider\GenericProvider([
                        'clientId' => $config['clientId'],
                        'clientSecret' => $config['clientSecret'],
                        'redirectUri' => $config['redirectUri'],
                        'urlAuthorize' => 'https://twitter.com/i/oauth2/authorize',
                        'urlAccessToken' => 'https://api.twitter.com/2/oauth2/token',
                        'urlResourceOwnerDetails' => 'https://api.twitter.com/2/users/me',
                        'scopes' => ['tweet.read', 'users.read', 'offline.access'], // Twitter v2 scopes
                    ]);
                    break;

                case 'vimeo':
                    $this->providers[$provider_key] = new \League\OAuth2\Client\Provider\GenericProvider([
                        'clientId' => $config['clientId'],
                        'clientSecret' => $config['clientSecret'],
                        'redirectUri' => $config['redirectUri'],
                        'urlAuthorize' => 'https://api.vimeo.com/oauth/authorize',
                        'urlAccessToken' => 'https://api.vimeo.com/oauth/access_token',
                        'urlResourceOwnerDetails' => 'https://api.vimeo.com/me',
                    ]);
                    break;

                case 'zotero':
                    $this->providers[$provider_key] = new \Tiki\OAuth\Provider\Zotero($config);
                    break;

                default:
                    throw new \RuntimeException("Unsupported OAuth provider: $provider_key");
            }
        }

        return $this->providers[$provider_key];
    }
}
