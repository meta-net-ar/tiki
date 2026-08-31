<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Auth;

use DateTime;
use TikiDb;
use TikiLib;

class Tokens
{
    private const LEGACY_TOKEN_LENGTH = 32;
    private const SIGNED_TOKEN_LENGTH = 64;
    private $db;
    private $table;
    private $dt;
    private $maxTimeout = 3600;
    private $maxHits = 1;
    public $ok = false;

    public static function build($prefs)
    {
        return new Tokens(
            TikiDb::get(),
            [
                'maxTimeout' => $prefs['auth_token_access_maxtimeout'],
                'maxHits' => $prefs['auth_token_access_maxhits'],
            ]
        );
    }

    public function __construct($db = null, $options = [], DateTime|null $dt = null)
    {
        if (is_null($db)) {
            $this->db = TikiDb::get();
        } else {
            $this->db = $db;
        }
        $this->table = $this->db->table('tiki_auth_tokens');

        if (is_null($dt)) {
            $this->dt = new DateTime();
        } else {
            $this->dt = $dt;
        }

        if (isset($options['maxTimeout'])) {
            $this->maxTimeout = (int) $options['maxTimeout'];
        }

        if (isset($options['maxHits'])) {
            $this->maxHits = (int) $options['maxHits'];
        }
    }

    public function getToken($token)
    {
        $data = $this->table->fetchFullRow(['token' => $token]);

        if (! $this->isTokenValid($data, $token)) {
            return null;
        }

        return $data;
    }

    public function getActiveToken($token)
    {
        $data = $this->db->query(
            'SELECT * FROM tiki_auth_tokens
                WHERE token = ?
                AND (timeout = -1 OR UNIX_TIMESTAMP(creation) + timeout >= UNIX_TIMESTAMP())
                AND (hits = -1 OR hits > 0)',
            [ $token ]
        )->fetchRow();

        if (! $this->isTokenValid($data, $token)) {
            return null;
        }

        return $data;
    }

    public function getTokens($conditions = [])
    {
        return $this->table->fetchAll([], $conditions, -1, -1, ['creation' => 'asc']);
    }

    public function rotateSigningSecret(bool $revokeExistingTokens = true): int
    {
        if (! TikiLib::lib('tiki')->set_preference('auth_token_secret', $this->generateSigningSecret())) {
            throw new \RuntimeException('Unable to rotate token signing secret.');
        }

        if (! $revokeExistingTokens) {
            return 0;
        }

        return $this->revokeAllTokens();
    }

    public function revokeAllTokens(): int
    {
        $tokenCount = (int) $this->db->getOne('SELECT COUNT(*) FROM `tiki_auth_tokens`');

        if ($tokenCount === 0) {
            return 0;
        }

        $tokensWithTempUsers = $this->db->fetchAll(
            'SELECT tokenId, userPrefix FROM `tiki_auth_tokens` WHERE `createUser` = ?',
            [ 'y' ]
        );

        foreach ($tokensWithTempUsers as $token) {
            if (! empty($token['userPrefix'])) {
                TikiLib::lib('user')->remove_temporary_user($this->buildTemporaryUsername($token['userPrefix'], $token['tokenId']));
            }
        }

        $this->db->query('DELETE FROM `tiki_auth_tokens`');

        return $tokenCount;
    }

    /**
     * @param int $limit The maximum number of tokens to delete
     *
     * @return mixed
     */
    public function deleteExpired(int $limit = -1)
    {
        return $this->db->query(
            'DELETE FROM tiki_auth_tokens
                WHERE (timeout != -1 AND UNIX_TIMESTAMP(creation) + timeout < UNIX_TIMESTAMP())
                OR (`hits` <= 0 AND `hits` != -1)',
            null,
            $limit,
        );
    }

    public function getGroups($token, $entry, $parameters)
    {
        // Process deletion of temporary users that are created via tokens
        $usersToDelete = $this->db->fetchAll(
            'SELECT tokenId, userPrefix FROM tiki_auth_tokens
                WHERE (timeout != -1 AND UNIX_TIMESTAMP(creation) + timeout < UNIX_TIMESTAMP())
                OR (`hits` <= 0 AND `hits` != -1)',
            null,
            2000
        );

        $userlib = TikiLib::lib('user');
        foreach ($usersToDelete as $del) {
            if (! empty($del['userPrefix'])) {
                $userlib->remove_temporary_user($this->buildTemporaryUsername($del['userPrefix'], $del['tokenId']));
            }
        }

        $this->deleteExpired(2000);

        $data = $this->getActiveToken($token);

        if (! $data) {
            return null;
        }

        global $prefs, $tikiroot;       // $full defined in route.php
        $slugmanager = TikiLib::lib('slugmanager');
        $skip_params = false;
        $convertedSefurl = '';
        $stored_entry = $data['entry'];
        $storedParams = (array) json_decode($data['parameters'], true);
        if (! empty($tikiroot) && str_starts_with($stored_entry, $tikiroot)) {
            $stored_entry = substr($stored_entry, strlen($tikiroot));
        }
        $stored_entry = ltrim($stored_entry, '/'); // Remove leading slash
        $page = $parameters['page'] ?? '';
        if ($prefs['feature_sefurl'] === 'y' && ! str_contains($entry, 'tiki-autologin.php')) {
            $sefurlTypeMap = $this->getSefurlTypeMap();
            $keys = array_keys($_GET);
            $seftype = '';

            for ($i = 0; $i < count($keys); $i++) {
                $seftype = $sefurlTypeMap[$keys[$i]];
                if ($seftype) {
                    $key = $keys[$i];
                    // $parameters is compared with the stored $data['parameters'] later
                    // but that doesn't include the 'page' or 'fileId' etc param due to sefurl
                    unset($parameters[$keys[$i]]);
                    break;
                }
            }
            if (empty($key)) {  // missing object type?
                return null;
            }
            if ($key == 'page') {
                // Decode and normalize the stored entry and current path (e.g., '/tiki28/apple' -> 'apple')
                $current_path = $GLOBALS['path'] ?? '';
                $convertedSefurl = '';
                if (! empty($current_path)) {
                    if (str_starts_with($current_path, $tikiroot)) {
                        $current_path = substr($current_path, strlen($tikiroot));
                    }
                    $convertedSefurl = $tikiroot . $current_path;
                    $current_path = \SmartyTiki\Modifier\Sefurl::apply($current_path, $seftype);
                }
                $stored_entry = \SmartyTiki\Modifier\Sefurl::apply($stored_entry, $seftype);
                // Case 1: Direct match after normalization (e.g., "apple" vs "apple")
                if ($slugmanager->normalizeToDash($stored_entry) === $slugmanager->normalizeToDash($current_path)) {
                    // Paths match; proceed
                } else { // Case 2: Handle standard mode tokens (e.g., stored entry is "tiki-index.php")
                    if (empty($current_path)) {
                        // Token belongs to SEF URL, but accessed via standard URL
                        // Generate SEF path from query parameters (e.g., 'page=apple' -> 'apple')
                        $current_sef_path = \SmartyTiki\Modifier\Sefurl::apply($page, $seftype);
                        // If the stored entry doesn't match the generated SEF path, and the token doesn't belong to standard mode, return null
                        if (
                            $slugmanager->normalizeToDash($stored_entry) !== $slugmanager->normalizeToDash($current_sef_path)
                            && ! isset($storedParams[$key])
                        ) {
                            return null; // Paths don't match
                        }
                    } elseif (isset($storedParams[$key])) {
                        // Token belongs to standard URL, but accessed via SEF URL
                        // Generate SEF path from stored parameters (e.g., 'page=apple' -> 'apple')
                        $generatedSefPath = \SmartyTiki\Modifier\Sefurl::apply($storedParams[$key], $seftype);
                        if ($slugmanager->normalizeToDash($generatedSefPath) !== $slugmanager->normalizeToDash($current_path)) {
                            return null; // Generated path doesn't match current path
                        }
                    } else {
                        return null; // No parameter to generate SEF path
                    }
                }
            }
        } elseif (! isset($data['entry']) || ! str_contains($entry, $data['entry'])) {
               // Token belongs to SEF URL, but accessed via standard URL
            if (! empty($page)) {
                // Generate SEF path from query parameters (e.g., 'page=apple' -> 'apple')
                $current_sef_path = \SmartyTiki\Modifier\Sefurl::apply($page);
                $stored_entry = \SmartyTiki\Modifier\Sefurl::apply($stored_entry);
                $path = parse_url($current_sef_path, PHP_URL_PATH);
                $script = basename($path);
                // If the stored entry doesn't match the generated SEF path, and the token doesn't belong to standard mode, return null
                if (
                    $slugmanager->normalizeToDash($stored_entry) != $slugmanager->normalizeToDash($current_sef_path)
                    || ! str_contains($entry, $script)
                ) {
                    return null; // Paths don't match
                } else {
                    $skip_params = true;
                }
            }
        }
        // If sefurl is in use, do not compare page or fileId params
        if ($prefs['feature_sefurl'] === 'y' && ! empty($key)) {
            unset($storedParams[$key]);
        }

        if (! $skip_params && (! $this->allPresent($storedParams, $parameters) || ! $this->allPresent($parameters, $storedParams))) {
            return null;
        }

        if (! $this->consumeHit($data)) {
            return null;
        }

        // Process autologin of temporary users
        if ($data['createUser'] == 'y') {
            $tempuser = $this->buildTemporaryUsername($data['userPrefix'], $data['tokenId']);
            $groups = json_decode($data['groups'], true);
            if (! $userlib->user_exists($tempuser)) {
                $randompass = $userlib->genPass();
                $userlib->add_user($tempuser, $randompass, $data['email'], '', false, null, null, $groups);
            }
            $userlib->autologin_user($tempuser);
            $url = ! empty($convertedSefurl) ? basename($convertedSefurl) : basename($data['entry']);
            if ($parameters) {
                $query = '?' . http_build_query($parameters, '', '&');
                $url .= $query;
            }
            include_once(__DIR__ . '/../../tiki-sefurl.php');
            $url = filter_out_sefurl($url);
            TikiLib::lib('access')->redirect($url);
            die;
        }

        $this->ok = true;
        return (array) json_decode($data['groups'], true);
    }

    private function allPresent($a, $b)
    {
        $slugmanager = TikiLib::lib('slugmanager');
        foreach ($a as $key => $value) {
            $value2 = $b[$key] ?? '';
            if ($key == 'view_as_visitor') {
                continue;
            }
            if ((empty($value2) || $value != $value2)) {
                if ($key == 'page' && $slugmanager->normalizeToDash($value) == $slugmanager->normalizeToDash($value2)) {
                    continue;
                }
                return false;
            }
        }

        return true;
    }

    /**
     * Provide mapping between item key and object type
     * TODO centralise this info in objectlib.php (?) and decide on one word or two for each
     *
     * @return array
     */
    private function getSefurlTypeMap()
    {
        return [
            'page'      => 'wiki page',
            'articleId' => 'article',
            'blogId'    => 'blog',
            'postId'    => 'blog post',
            'parentId'  => 'category',
            'fileId'    => 'file',
            'galleryId' => 'file gallery',
            'forumId'   => 'forum',
            'nlId'      => 'newsletter',
            'trackerId' => 'tracker',
            'itemId'    => 'trackeritem',
            'sheetId'   => 'sheet',
            'userId'    => 'user',
            'calIds'    => 'calendar',
        ];
    }

    private function getSefurlPatterns(): array
    {
        return  [
            '/^blog\d+/' => 'blog',
            '/^blogpost\d+/' => 'blogpost',
            '/^cal\d+/' => 'calendar',
            '/^calevent\d+/' => 'calendar event',
            '/^article\d+/' => 'article',
            '/^(display|thumbnail|preview)\d+/' => 'file',
            '/^forum\d+/' => 'forum',
            '/^forumthread\d+/' => 'forumthread',
            '/^sheet\d+/' => 'sheet',
            '/^cat\d+/' => 'category',
        ];
    }

    public function convertToStandardUrl(string $url): ?string
    {
        global $prefs;

        if ($prefs['feature_sefurl'] === 'y') {
            return $url;
        }

        $parsedUrl = parse_url($url, PHP_URL_PATH);
        $pathSegments = explode('/', trim($parsedUrl, '/'));

        // Extract the last part of the URL path (e.g., '/blogpost45-New-Tech-Updates' -> 'blogpost45')
        if (! empty($pathSegments)) {
            $lastSegment = $pathSegments[count($pathSegments) - 1];
            if (str_contains($lastSegment, 'tiki')) {
                return $url;
            }
            if (str_contains($lastSegment, '-')) {
                $lastSegment = explode('-', $lastSegment, 2)[0];
            }

            // Match the segment against regex patterns to determine the type
            foreach ($this->getSefurlPatterns() as $pattern => $type) {
                if (preg_match($pattern, $lastSegment, $matches)) {
                    // Extract numeric ID (e.g., 'blogpost45' -> '45')
                    $id = preg_replace('/[^0-9]/', '', $lastSegment);

                    return \SmartyTiki\Modifier\Sefurl::apply($id, $type);
                }
            }
            return \SmartyTiki\Modifier\Sefurl::apply($lastSegment); // Return 'wiki' type and page name (last segment)
        }
        return $url;
    }

    public function createToken($entry, array $parameters, array $groups, array $arguments = [], array $data = [])
    {
        if (! empty($arguments['timeout'])) {
            $timeout = min($this->maxTimeout, $arguments['timeout']);
        } else {
            $timeout = $this->maxTimeout;
        }

        if (! empty($arguments['hits'])) {
            $hits = $arguments['hits'];
        } else {
            $hits = $this->maxHits;
        }

        if (isset($arguments['email'])) {
            $email = $arguments['email'];
        } else {
            $email = '';
        }

        if (! empty($arguments['createUser']) && $arguments['createUser'] !== 'n') {
            $createUser = 'y';
        } else {
            $createUser = 'n';
        }

        if (isset($arguments['userPrefix'])) {
            $userPrefix = $arguments['userPrefix'];
        } else {
            $userPrefix = '';
        }

        global $user;

        $this->db->query(
            'INSERT INTO tiki_auth_tokens ( timeout, maxhits, hits, entry, parameters, data, `groups`, email, createUser, userPrefix, user ) VALUES( ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ? )',
            [
                (int) $timeout,
                (int) $hits,
                (int) $hits,
                $entry,
                json_encode($parameters),
                json_encode($data),
                json_encode($groups),
                $email,
                $createUser,
                $userPrefix,
                $arguments['user'] ?? $user,

            ]
        );
        $tokenId = $this->db->lastInsertId();

        $tokenData = $this->table->fetchFullRow(['tokenId' => $tokenId]);
        $token = $this->generateToken($tokenData);

        $this->table->update(['token' => $token], ['tokenId' => $tokenId]);

        return $token;
    }

    /**
     * This is a function that includes a security token into a provided URL
     * @param string $url The URL where the token is valid.
     * @param array $groups The groups from which the person using the token will have permissions for.
     * @param string $email The email that the token was sent to, for recording purpose. If there are multiple
     * emails, it currently saves as comma-separated list but exploding will need to trim spaces.
     * @param int $timeout Timeout to set in seconds. If not included, will use default as set in prefs.
     * @param int $hits Number of hits allowed before token expires. If not included, will use default as set in prefs.
     * @param boolean $createUser Login token user as temporary user if set to true
     * @param string $userPrefix Username of the created users will be a 6 digit number based on the token ID prefixed with this (default is 'guest')
     * @param string $creatorUser The user that created the token. If not included, will use the current user.
    * @param array $data The extra data/params that should not be included in the URL but are associated with the token.
     * @return string A URL that has the security token included.
     */
    public function includeToken($url, array $groups = [], $email = '', $timeout = 0, $hits = 0, $createUser = false, $userPrefix = 'guest', $creatorUser = null, array $data = [])
    {
        global $user;
        $urlData = parse_url($url);
        $longurl = '';

        if (isset($urlData['query'])) {
            parse_str($urlData['query'], $args);
            unset($args['TOKEN']);
        } else {
            global $prefs, $sefurl_regex_out;
            include_once __DIR__ . '/../../tiki-sefurl.php';
            if ($prefs['feature_sefurl'] === 'y' && ! empty($sefurl_regex_out)) {
                global $base_url;

                $short = substr($url, strlen($base_url));
                $is_numeric = preg_match('/\d+/', $short);

                foreach (array_reverse($sefurl_regex_out) as $regex) {  // wiki is the first one and will match anything
                    if ($is_numeric) {
                        $replace = '(\d+)'; // match digits
                    } else {
                        $replace = '(.+)';  // or anything (for wiki pages)
                    }
                    $pattern = str_replace('$1', $replace, $regex['right']);

                    if (preg_match('/' . $pattern . '/', $short, $matches)) {
                        $longurl = preg_replace('/' . preg_quote($replace) . '/', $matches[1], $regex['left']);
                        $longurl = $base_url . stripcslashes($longurl); // add back the beginning and get rid of the \ infront of the ?
                        break;
                    }
                }

                if ($longurl) {
                    $longdata = parse_url($longurl);
                    parse_str($longdata['query'], $args);
                } else {
                    $args = [];
                }
            } else {
                $args = [];
            }
        }

        $settings = ['email' => $email];
        if (! empty($timeout)) {
            $settings['timeout'] = $timeout;
        }
        if (! empty($hits)) {
            $settings['hits'] = $hits;
        }
        $settings['createUser'] = $createUser;
        $settings['userPrefix'] = $userPrefix;
        $settings['user'] = $creatorUser ?? $user;

        $token = $this->createToken($urlData['path'], $args, $groups, $settings, $data);
        if ($longurl) { // sefurl was used so the args should be reset now the token has been created
            $args = [];
        }
        $args['TOKEN'] = $token;

        $query = '?' . http_build_query($args, '', '&');

        if (! isset($urlData['fragment'])) {
            $anchor = '';
        } else {
            $anchor = "#{$urlData['fragment']}";
        }

        return "{$urlData['scheme']}://{$urlData['host']}{$urlData['path']}$query$anchor";
    }

    /**
     * Retrieves the token from a given URL.
     *
     * This function parses the query parameters of the provided URL and checks if a 'TOKEN' parameter is present.
     * If the 'TOKEN' parameter is found, it retrieves the token using the `getToken` method and returns it.
     * If the 'TOKEN' parameter is not found, it returns null.
     *
     * @param string $url The URL to extract the token from.
     * @return string|null The token if found, or null if not found.
     */
    public function getTokenFromUrl($url)
    {
        $data = parse_url($url);
        if (isset($data['query'])) {
            parse_str($data['query'], $args);
            if (isset($args['TOKEN'])) {
                return $this->getToken($args['TOKEN']);
            }
        }

        return null;
    }

    public function deleteToken($tokenId)
    {
        $userPrefix = $this->table->fetchOne(
            'userPrefix',
            ['tokenId' => $tokenId, 'createUser' => 'y']
        );
        if ($userPrefix) {
            TikiLib::lib('user')->remove_temporary_user($this->buildTemporaryUsername($userPrefix, $tokenId));
        }
        $this->table->delete(['tokenId' => $tokenId]);
    }

    private function consumeHit(array $data): bool
    {
        if ((int) $data['hits'] === -1) {
            return true;
        }

        $result = $this->db->query(
            'UPDATE `tiki_auth_tokens` SET `hits` = `hits` - 1 WHERE `tokenId` = ? AND `hits` > 0',
            [ $data['tokenId'] ]
        );

        return $result && $result->numRows() === 1;
    }

    private function buildTemporaryUsername(string $userPrefix, int|string $tokenId): string
    {
        return $userPrefix . TikiLib::lib('user')->autogenerate_login($tokenId, 6);
    }

    private function buildTokenPayload(array $data): string
    {
        return implode(
            '',
            [
                (string) ($data['tokenId'] ?? ''),
                (string) ($data['creation'] ?? ''),
                (string) ($data['timeout'] ?? ''),
                (string) ($data['entry'] ?? ''),
                (string) ($data['parameters'] ?? ''),
                (string) ($data['groups'] ?? ''),
            ]
        );
    }

    private function generateToken(array $data): string
    {
        return hash_hmac('sha256', $this->buildTokenPayload($data), $this->getSigningSecret());
    }

    private function generateSigningSecret(): string
    {
        return bin2hex(random_bytes(32));
    }

    private function getSigningSecret(): string
    {
        global $prefs;

        if (! empty($prefs['auth_token_secret'])) {
            return (string) $prefs['auth_token_secret'];
        }

        throw new \RuntimeException('No token signing secret is configured.');
    }

    private function isTokenValid(?array $data, string $token): bool
    {
        if (! $data || empty($data['token'])) {
            return false;
        }

        $length = strlen($token);
        if ($length !== strlen((string) $data['token'])) {
            return false;
        }

        if ($length === self::LEGACY_TOKEN_LENGTH) {
            $expected = md5($this->buildTokenPayload($data));
            return hash_equals($expected, $token);
        }

        if ($length === self::SIGNED_TOKEN_LENGTH) {
            $expected = hash_hmac('sha256', $this->buildTokenPayload($data), $this->getSigningSecret());
            return hash_equals($expected, $token);
        }

        return false;
    }
}
