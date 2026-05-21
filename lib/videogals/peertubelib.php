<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Videogals;

class PeerTubeLib
{
    private $sessionType;
    private $initialized = false;
    private $localClientCache = null;

    public function __construct($session_type = 0)
    {
        $this->sessionType = $session_type;
    }

    public function testSetup()
    {
        global $prefs;
        return ! empty($prefs['peertube_service_url'])
            && ! empty($prefs['peertube_username'])
            && ! empty($prefs['peertube_password']);
    }

    public function getAccessToken()
    {
        global $prefs, $user;
        $base = rtrim($prefs['peertube_service_url'] ?? '', '/');
        $userKey = $user ?: 'anonymous';
        $session = "peertube_session_{$this->sessionType}_" . md5($base . '|' . $userKey);

        if (isset($_SESSION[$session]) && $_SESSION[$session]['expiry'] > \TikiLib::lib('tiki')->now) {
            return $_SESSION[$session]['token'];
        }

        [$clientId, $clientSecret] = $this->getLocalOAuthClient($base);

        if (empty($clientId) || empty($clientSecret)) {
            throw new \Exception('Unable to retrieve client credentials from PeerTube');
        }

        $url = $base . '/api/v1/users/token';
        $data = [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'grant_type' => 'password',
            'username' => $prefs['peertube_username'],
            'password' => $prefs['peertube_password'],
        ];

        $response = $this->makeRequest('POST', $url, http_build_query($data, '', '&', PHP_QUERY_RFC3986), false, true);
        $token = json_decode($response, true);

        if (! isset($token['access_token']) || ! isset($token['expires_in'])) {
            throw new \Exception('Invalid token response from PeerTube');
        }

        $_SESSION[$session] = [
            'token' => $token['access_token'],
            'expiry' => \TikiLib::lib('tiki')->now + $token['expires_in'],
        ];

        return $token['access_token'];
    }

    private function makeRequest($method, $url, $data = [], $auth = true, $rawBody = false)
    {
        $ch = curl_init();
        $method = strtoupper($method);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        // Enable verbose logging only in debug mode
        global $prefs;
        if (! empty($prefs['debug']) && $prefs['debug'] === 'y') {
            curl_setopt($ch, CURLOPT_VERBOSE, true);
            $verbose = fopen('php://temp', 'w+');
            curl_setopt($ch, CURLOPT_STDERR, $verbose);
        }

        // Prepare headers
        $headers = ['Accept: application/json'];
        if ($method === 'POST' && is_array($data) && isset($data['videofile'])) {
            // Multipart request (e.g., file upload)
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        } else {
            // Standard POST or other methods
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';
            if ($method === 'POST') {
                curl_setopt($ch, CURLOPT_POST, true);
                $postFields = $rawBody ? $data : http_build_query($data);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
            }
        }

        // Add authentication header if required
        if ($auth) {
            try {
                $token = $this->getAccessToken();
                if (empty($token)) {
                    throw new \Exception('Authentication token is empty');
                }
                $headers[] = 'Authorization: Bearer ' . $token;
            } catch (\Exception $e) {
                error_log("Authentication error: " . $e->getMessage()); // @phpstan-ignore disallowedFunctions.errorLog (PeerTube HTTP/cURL error reporting)
                throw new \Exception('Failed to obtain access token: ' . $e->getMessage());
            }
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            if (isset($verbose)) {
                rewind($verbose);
                $verboseLog = stream_get_contents($verbose);
                fclose($verbose);
                error_log("cURL verbose log: $verboseLog"); // @phpstan-ignore disallowedFunctions.errorLog (PeerTube HTTP/cURL error reporting)
            }
            throw new \Exception("cURL error: $error");
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (isset($verbose)) {
            rewind($verbose);
            $verboseLog = stream_get_contents($verbose);
            fclose($verbose);
            if (! empty($verboseLog)) {
                error_log("cURL verbose log: $verboseLog"); // @phpstan-ignore disallowedFunctions.errorLog (PeerTube HTTP/cURL error reporting)
            }
        }

        if ($httpCode === 401 && $auth) {
            $this->clearCachedToken();
            return $this->makeRequest($method, $url, $data, $auth, $rawBody);
        } elseif ($httpCode >= 400) {
            error_log("HTTP error $httpCode: $response"); // @phpstan-ignore disallowedFunctions.errorLog (PeerTube HTTP/cURL error reporting)
            $msg = "HTTP error $httpCode";
            $j = json_decode($response, true);
            if (is_array($j)) {
                if (! empty($j['detail'])) {
                    $msg .= ' — ' . $j['detail'];
                } elseif (! empty($j['error'])) {
                    $msg .= ' — ' . $j['error'];
                }
                if (! empty($j['invalid-params']) && is_array($j['invalid-params'])) {
                    $first = reset($j['invalid-params']);
                    $paramName = array_key_first($j['invalid-params']);
                    if (is_array($first) && ! empty($paramName) && ! empty($first['msg'])) {
                        $msg .= " (invalid '{$paramName}': " . $first['msg'] . ")";
                    }
                }
            } elseif (is_string($response) && trim($response) !== '') {
                $msg .= ' — ' . trim($response);
            }
            throw new \Exception($msg);
        }

        return $response;
    }

    public function uploadVideo($file, $metadata)
    {
        global $prefs;

        $url = rtrim($prefs['peertube_service_url'], '/') . '/api/v1/videos/upload';

        $data = [
            'channelId' => $metadata['channelId'],
            'name' => $metadata['name'],
            'videofile' => $file['videofile'],
        ];

        if (isset($metadata['description'])) {
            $data['description'] = $metadata['description'];
        }

        if (isset($metadata['privacy'])) {
            $data['privacy'] = $metadata['privacy'];
        }

        return json_decode($this->makeRequest('POST', $url, $data), true);
    }

    public function listVideos($sort_mode = '-publishedAt', $page = 1, $page_size = 10, $find = '')
    {
        global $prefs;

        $map = [
            'created_desc'   => '-createdAt',
            'created_asc'    => 'createdAt',
            'published_desc' => '-publishedAt',
            'published_asc'  => 'publishedAt',
            'name_desc'      => '-name',
            'name_asc'       => 'name',
            'desc_createdAt'   => '-createdAt',
            'asc_createdAt'    => 'createdAt',
            'desc_publishedAt' => '-publishedAt',
            'asc_publishedAt'  => 'publishedAt',
        ];
        $apiSort = $map[$sort_mode] ?? $sort_mode;
        if (! preg_match('/^-?(name|createdAt|publishedAt|duration|views|likes|comments)$/', $apiSort)) {
            $apiSort = '-publishedAt';
        }

        $page_size = max(1, min(100, (int) $page_size));
        $start     = max(0, (int) (($page - 1) * $page_size));

        $base = rtrim($prefs['peertube_service_url'], '/');
        $query = http_build_query([
            'start' => $start,
            'count' => $page_size,
            'sort'  => $apiSort,
        ], '', '&', PHP_QUERY_RFC3986);
        $url = "$base/api/v1/videos?$query";
        if ($find !== '') {
            $url .= '&search=' . rawurlencode($find);
        }

        $response = $this->makeRequest('GET', $url);
        return json_decode($response);
    }

    public function getVideo($id)
    {
        global $prefs;

        $id = trim($id);

        if (empty($id)) {
            throw new \Exception("Video ID cannot be empty");
        }

        $url = rtrim($prefs['peertube_service_url'], '/') . "/api/v1/videos/" . urlencode($id);
        $response = $this->makeRequest('GET', $url);
        return json_decode($response, true);
    }

    public function deleteVideo($uuid)
    {
        global $prefs;

        $baseUrl = rtrim($prefs['peertube_service_url'], '/');
        $deleteUrl = "$baseUrl/api/v1/videos/$uuid";

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $deleteUrl);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

            $headers = [
                'Accept: application/json',
                'Authorization: Bearer ' . $this->getAccessToken()
            ];
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if ($httpCode === 204) {
                return ['success' => true];
            }

            throw new \Exception("HTTP error $httpCode. Response: $response");
        } catch (\Exception $e) {
            error_log("PeerTube deleteVideo error: " . $e->getMessage() . " | UUID: $uuid | URL: $deleteUrl"); // @phpstan-ignore disallowedFunctions.errorLog (PeerTube HTTP/cURL error reporting)
            throw new \Exception("Failed to delete video: " . $e->getMessage());
        }
    }

    public function updateVideo($id, array $data)
    {
        global $prefs;
        $url = rtrim($prefs['peertube_service_url'], '/') . "/api/v1/videos/$id";
        $response = $this->makeRequest('PUT', $url, $data);
        return json_decode($response, true);
    }

    public function getVideoList(array $videoIds)
    {
        $videos = [];
        foreach ($videoIds as $id) {
            $videos[] = $this->getVideo($id);
        }
        return $videos;
    }

    public function getMyAccountName(): string
    {
        global $prefs;

        $url = rtrim($prefs['peertube_service_url'], '/') . '/api/v1/users/me';

        $response = $this->makeRequest('GET', $url);
        $data = json_decode($response, true);

        if (! isset($data['account']) || ! isset($data['account']['name'])) {
            throw new \Exception('Could not retrieve account name from PeerTube');
        }

        return $data['account']['name'];
    }

    public function getMyChannels(): array
    {
        global $prefs;

        if (empty($prefs['peertube_service_url'])) {
            throw new \Exception('Missing PeerTube service URL.');
        }

        $accountName = $this->getMyAccountName();

        $url = rtrim($prefs['peertube_service_url'], '/') . '/api/v1/accounts/' . urlencode($accountName) . '/video-channels';

        return json_decode($this->makeRequest('GET', $url), true);
    }

    public function testConnection()
    {
        try {
            global $prefs;

            $required = [
                'peertube_service_url'    => tr('PeerTube service URL'),
                'peertube_username'       => tr('Username'),
                'peertube_password'       => tr('Password'),
            ];

            foreach ($required as $key => $label) {
                if (empty($prefs[$key])) {
                    return tr("PeerTube configuration '%0' is missing.", $label);
                }
            }

            $url = rtrim($prefs['peertube_service_url'], '/') . '/api/v1/config';
            $response = $this->makeRequest('GET', $url, [], false);
            $data = json_decode($response, true);
            if (empty($data['instance'])) {
                return tr("Could not retrieve PeerTube instance configuration.");
            }

            return true;
        } catch (\Exception $e) {
            return tr(
                'Failed to reach the PeerTube instance at %0. Please verify the PeerTube service URL. If the problem persists consult the <a href="%1" target="_blank">PeerTube documentation</a>.',
                $prefs['peertube_service_url'],
                'https://docs.joinpeertube.org/api/rest-getting-started'
            );
        }
    }

    /**
    * Retrieves and caches (in session) the "local" OAuth client.
     */
    private function getLocalOAuthClient(string $base): array
    {
        $cacheKey  = rtrim($base, '/');
        $sessionKey = 'peertube_local_client_' . md5($cacheKey);
        if (! empty($_SESSION[$sessionKey]['client_id']) && ! empty($_SESSION[$sessionKey]['client_secret'])) {
            return [$_SESSION[$sessionKey]['client_id'], $_SESSION[$sessionKey]['client_secret']];
        }
        $clientUrl = $cacheKey . '/api/v1/oauth-clients/local';
        $clientResponse = $this->makeRequest('GET', $clientUrl, [], false);
        $clientData = json_decode($clientResponse, true);
        if (! is_array($clientData) || empty($clientData['client_id']) || empty($clientData['client_secret'])) {
            throw new \Exception('Unable to retrieve client credentials from PeerTube');
        }
        $_SESSION[$sessionKey] = [
            'client_id'     => $clientData['client_id'],
            'client_secret' => $clientData['client_secret'],
        ];
        return [$clientData['client_id'], $clientData['client_secret']];
    }

    private function clearCachedToken(): void
    {
        global $prefs, $user;
        $base = rtrim($prefs['peertube_service_url'] ?? '', '/');
        $userKey = $user ?: 'anonymous';
        $session = "peertube_session_{$this->sessionType}_" . md5($base . '|' . $userKey);
        unset($_SESSION[$session]);
    }
}
