<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Lib\Test\Api;

abstract class ApiTestCase extends \TikiTestCase
{
    /**
     * @var \TikiDb The database connection used for API tests
     */
    private $db;

    /**
     * Static property to hold the admin API token for the test session
     */
    private static $adminApiToken = null;

    /**
     * Path to the tiki-api-wrapper.php file
     */
    private $tikiapiPath;

    /**
     * Set up the API test environment
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        // Ensure the API token service is enabled in the DB so subprocesses see it.
        static::setTestPreferences('auth_api_tokens', 'y');

        $tokenlib = \TikiLib::lib('api_token');
        $token = $tokenlib->createToken([
            'type' => 'manual',
            'user' => 'admin'
        ]);
        self::$adminApiToken = $token['token'];
    }

    /**
     * Write a preference or multiple preferences directly to the database, bypassing set_preference().
     * set_preference() has side-effects (cache invalidation, menu rebuilds) that dispatch PHPUnit events.
     *
     * Can be called in two ways:
     * - Single preference: setTestPreferences('name', $value)
     * - Multiple preferences: setTestPreferences(['name1' => $value1, 'name2' => $value2])
     *
     * @param string|array $name  Preference name or associative array of preferences
     * @param mixed  $value Preference value (ignored when $name is an array)
     */
    protected static function setTestPreferences($name, $value = null): void
    {
        // Handle array of preferences
        if (is_array($name)) {
            foreach ($name as $prefName => $prefValue) {
                static::setTestPreferences($prefName, $prefValue);
            }
            return;
        }

        // Handle single preference
        \TikiDb::get()->query(
            'INSERT INTO `tiki_preferences` (`name`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = ?',
            [$name, $value, $value]
        );

        // Clear the preference cache so the updated preference value is read from the database
        // in subsequent API subprocess calls
        $cachelib = \TikiLib::lib('cache');
        $cachelib->invalidate('global_preferences');
        $cachelib->invalidate('tiki_default_preferences_cache');
    }

    /**
     * Delete a set of test preferences directly via SQL.
     * Avoids TikiLib::delete_preference(), which loads preference definition files
     * and may dispatch PHPUnit events in static teardown context.
     */
    protected static function deleteTestPreferences(array $prefs): void
    {
        foreach (array_keys($prefs) as $name) {
            \TikiDb::get()->query('DELETE FROM `tiki_preferences` WHERE `name` = ?', [$name]);
        }
        $cachelib = \TikiLib::lib('cache');
        $cachelib->invalidate('global_preferences');
        $cachelib->invalidate('tiki_default_preferences_cache');
    }

    /**
     * Core user-creation logic shared by domain base classes.
     * Returns true when the user was created or already existed.
     */
    protected static function createTestUser(string $username, string $password = 'TestPass123!', string $email = ''): bool
    {
        $userlib = \TikiLib::lib('user');
        if ($userlib->user_exists($username)) {
            return true;
        }
        $email = $email ?: $username . '@api-test.tiki.org';
        return (bool) $userlib->add_user($username, $password, $email, '', false);
    }

    /**
     * Core group-creation logic shared by domain base classes.
     * For groups with extended parameters (isRole, isTplGroup, etc.) use ApiBaseGroupsTest::createGroup().
     */
    protected static function createTestGroup(string $name, string $desc = ''): bool
    {
        $userlib = \TikiLib::lib('user');
        if ($userlib->group_exists($name)) {
            return true;
        }
        return (bool) $userlib->add_group($name, $desc ?: "API integration test group: $name");
    }

    /**
     * Core wiki-page creation logic shared by domain base classes.
     * Returns true when the page was created or already existed.
     */
    protected static function createTestWikiPage(string $pageName, string $content = 'API test content', string $lang = ''): bool
    {
        $tikilib = \TikiLib::lib('tiki');
        if ($tikilib->page_exists($pageName)) {
            return true;
        }
        try {
            $result = $tikilib->create_page($pageName, 0, $content, $tikilib->now, '', 'admin', '127.0.0.1', '', $lang);
            return $result !== false;
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Remove a wiki page (all versions). Safe to call when the page does not exist.
     */
    protected static function removeTestWikiPage(string $pageName): void
    {
        try {
            $tikilib = \TikiLib::lib('tiki');
            if ($tikilib->page_exists($pageName)) {
                $tikilib->remove_all_versions($pageName);
            }
        } catch (\Exception) {
            // ignore cleanup errors
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->tikiapiPath = dirname(__FILE__) . '/tiki-api-wrapper.php';
        $this->db = \TikiDb::get();
    }

    /**
     * Execute a request against the API by calling tiki-api-wrapper.php in a subprocess
     *
     * @param string $method The HTTP method (GET, POST, PUT, DELETE)
     * @param string $endpoint The API endpoint path (e.g., '/version', '/categories')
     * @param string|null $permission The required permission level ('Admins', 'Editors', etc. or null for Anonymous)
     * @param array $data The request data
     * @param string $contentType The content type (default: 'application/json')
     * @return array Response data including status code, headers, and body
     */
    protected function makeApiRequest($method, $endpoint, $permission = null, $data = [], $contentType = 'application/json')
    {
        $env = $this->prepareRequestEnvironment($method, $endpoint, $permission, $data, $contentType);

        $response = $this->executeApiSubprocess($env);

        return $this->parseResponse($response);
    }

    /**
     * Prepare the environment variables for the API request
     *
     * @param string $method HTTP method
     * @param string $endpoint API endpoint
     * @param string|null $permission Permission level
     * @param array $data Request data
     * @param string $contentType Content type
     * @return array Environment variables
     */
    private function prepareRequestEnvironment($method, $endpoint, $permission, $data, $contentType)
    {
        // Extract the route from the endpoint (remove leading slash)
        $route = ltrim($endpoint, '/');

        // Base environment variables with all required $_SERVER keys
        $env = [
            'REQUEST_METHOD' => $method,
            'REQUEST_URI' => '/api' . $endpoint,
            'SCRIPT_NAME' => '/tiki-api.php',
            'PATH_INFO' => $endpoint,
            'CONTENT_TYPE' => $contentType,
            'HTTP_ACCEPT' => 'application/json',
            'SERVER_NAME' => 'localhost',
            'SERVER_PORT' => '80',
            'HTTPS' => '',
            'HTTP_HOST' => 'localhost',
            'HTTP_USER_AGENT' => 'TikiApiTest/1.0',
            'REDIRECT_STATUS' => '200',
            'REQUEST_SCHEME' => 'http',
            'REMOTE_ADDR' => '127.0.0.1',
            'REMOTE_HOST' => 'localhost',
        ];

        // Add authentication header if permission is provided
        if ($permission !== null) {
            $token = $this->getToken($permission);
            $env['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
        }

        // Add the route parameter to GET data (this is what JitFilter expects)
        $getData = array_merge(['route' => $route], $method === 'GET' ? $data : []);
        $queryString = http_build_query($getData);
        $env['QUERY_STRING'] = $queryString;
        $env['REQUEST_URI'] .= '?' . $queryString;

        // Handle request data based on method and content type for POST/PUT/PATCH/DELETE
        if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE']) && ! empty($data)) {
            if ($contentType === 'application/json') {
                $env['CONTENT_DATA'] = json_encode($data);
                $env['CONTENT_LENGTH'] = strlen($env['CONTENT_DATA']);
            } elseif ($contentType === 'application/x-www-form-urlencoded') {
                $env['CONTENT_DATA'] = http_build_query($data);
                $env['CONTENT_LENGTH'] = strlen($env['CONTENT_DATA']);
            } elseif ($contentType === 'multipart/form-data') {
                $env['CONTENT_TYPE'] = $contentType;
                $boundary = '----TikiTestBoundary' . md5(mt_rand());
                $env['CONTENT_TYPE'] .= "; boundary=$boundary";

                $multipart = '';

                foreach ($data as $key => $value) {
                    $multipart .= "--$boundary\r\n";
                    if (is_string($value) && file_exists($value)) {
                        $fileContent = file_get_contents($value);
                        $filename = basename($value);
                        // Send with filename so the wrapper populates $_FILES.
                        $multipart .= "Content-Disposition: form-data; name=\"$key\"; filename=\"$filename\"\r\n\r\n";
                        $multipart .= $fileContent . "\r\n";
                    } else {
                        $multipart .= "Content-Disposition: form-data; name=\"$key\"\r\n\r\n";
                        $multipart .= "$value\r\n";
                    }
                }

                $multipart .= "--$boundary--\r\n";

                $env['CONTENT_DATA'] = $multipart;
                $env['CONTENT_LENGTH'] = strlen($multipart);
            }
        }

        return $env;
    }

    /**
     * Execute tiki-api-wrapper.php in a subprocess with the prepared environment
     *
     * @param array $env Environment variables
     * @return string Raw response output
     */
    protected function executeApiSubprocess($env)
    {
        // Force Tiki to use lib/test/local.php instead of db/local.php
        $testDbEnv = [
            'TIKI_TEST_HOST'   => '127.0.0.1',
            'TIKI_TEST_HOST_A' => 'tiki_test_user',
            'TIKI_TEST_HOST_B' => 'tiki_test_db',
        ];

        $process = new \Tiki\Process\Process(
            [PHP_BINARY, $this->tikiapiPath],
            null,
            array_merge($_ENV, $env, $testDbEnv),
            $env['CONTENT_DATA'] ?? null,
            120 // 2-minute timeout per request
        );

        try {
            $process->run();
        } catch (\Symfony\Component\Process\Exception\ProcessTimedOutException $e) {
            throw new \RuntimeException(
                "API subprocess timed out after {$process->getTimeout()}s.\n" .
                "Stderr: " . $process->getErrorOutput() . "\n" .
                "Stdout: " . substr($process->getOutput(), 0, 500)
            );
        }

        if (! $process->isSuccessful()) {
            $debugInfo = "\nCommand: " . $process->getCommandLine() .
                         "\nExit code: " . $process->getExitCode() .
                         "\nStderr: " . $process->getErrorOutput() .
                         "\nStdout: " . substr($process->getOutput(), 0, 500);
            throw new \RuntimeException("API subprocess failed with exit code {$process->getExitCode()}. Debug info: $debugInfo");
        }

        return $process->getOutput();
    }

    /**
     * Parse the raw response from the subprocess
     *
     * Simplified parsing based on JSON response patterns:
     * - Scenario 1: Valid JSON without code/message/errortitle = 200 OK
     * - Scenario 2: Valid JSON with code/message/errortitle = Error response
     * - Scenario 3: Invalid JSON = 500 Internal Server Error
     *
     * @param string $rawResponse Raw response data
     * @return array Parsed response with status, headers, and body
     */
    protected function parseResponse($rawResponse)
    {
        // Clean up the response - remove any HTTP headers if present
        $body = $this->extractJsonFromResponse($rawResponse);

        // Try to parse as JSON
        $jsonDecoded = json_decode($body, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($jsonDecoded)) {
            // Scenario 1 & 2: Valid JSON response
            return $this->handleValidJsonResponse($jsonDecoded, $body);
        } else {
            // Scenario 3: Invalid JSON - unknown error
            return $this->handleInvalidJsonResponse($body);
        }
    }

    /**
     * Extract JSON content from response, handling both raw JSON and HTTP response format
     */
    private function extractJsonFromResponse($rawResponse)
    {
        $body = trim($rawResponse);

        // If response contains HTTP headers, extract just the body
        if (strpos($body, "\r\n\r\n") !== false) {
            $parts = explode("\r\n\r\n", $body, 2);
            $body = $parts[1] ?? $body;
        } elseif (strpos($body, "\n\n") !== false) {
            $parts = explode("\n\n", $body, 2);
            $body = $parts[1] ?? $body;
        }

        return trim($body);
    }

    /**
     * Handle valid JSON response (Scenarios 1 & 2)
     */
    private function handleValidJsonResponse($jsonDecoded, $body)
    {
        // Check if this is an error response (has code, message, errortitle)
        if (isset($jsonDecoded['code']) && isset($jsonDecoded['message']) && isset($jsonDecoded['errortitle'])) {
            // Scenario 2: Error response with error details
            $statusCode = is_numeric($jsonDecoded['code']) ? (int)$jsonDecoded['code'] : 500;
            return [
                'status_code' => $statusCode,
                'headers' => [],
                'body' => $body,
                'parsed_body' => $jsonDecoded,
            ];
        } else {
            // Scenario 1: Success response without error details
            return [
                'status_code' => 200,
                'headers' => [],
                'body' => $body,
                'parsed_body' => $jsonDecoded,
            ];
        }
    }

    /**
     * Handle invalid JSON response (Scenario 3)
     */
    private function handleInvalidJsonResponse($body)
    {
        return [
            'status_code' => 500,
            'headers' => [],
            'body' => $body,
            'parsed_body' => [
                'code' => 500,
                'errortitle' => 'Invalid JSON Response',
                'message' => 'The API returned a non-JSON response. This indicates an internal server error or unexpected output.',
            ],
        ];
    }

    /**
     * Recursively asserts that all expected keys exist in the data array and match expected types
     *
     * @param array $data The actual API response (part or full)
     * @param array $schema An associative array where keys match expected keys and values are types (e.g., "string", "int|null")
     * @param string $context Optional prefix for failure messages
     */
    protected function assertMatchesSchema(array $data, array $schema, string $context = ''): void
    {
        foreach ($schema as $key => $typeOrNested) {
            // Wildcard '*': validate every entry in $data against the sub-schema.
            // Usage:  ['*' => ['field' => 'string']]  or  ['*' => 'int']
            if ($key === '*') {
                foreach ($data as $dataKey => $dataValue) {
                    $msg = $context ? "$context - $dataKey" : (string) $dataKey;
                    $this->assertSchemaValue($dataValue, $typeOrNested, $msg);
                }
                continue;
            }

            $msg = $context ? "$context - $key" : $key;

            $this->assertArrayHasKey($key, $data, "$msg is missing");

            $this->assertSchemaValue($data[$key], $typeOrNested, $msg);
        }
    }

    /**
     * Validate a single value against a schema entry (type string or nested schema array).
     * Extracted so both the normal key path and the wildcard path share the same logic.
     */
    private function assertSchemaValue(mixed $value, mixed $typeOrNested, string $msg): void
    {
        if (is_array($typeOrNested)) {
            $this->assertIsArray($value, "$msg should be an array");

            // [0 => [...]] — array of objects, each validated against the inner schema.
            // The array must be non-empty: an empty array skips all per-element assertions
            if (isset($typeOrNested[0]) && is_array($typeOrNested[0])) {
                $innerSchema = $typeOrNested[0];
                $this->assertNotEmpty($value, "$msg should be a non-empty array of objects");
                foreach ($value as $idx => $item) {
                    $itemMsg = "{$msg}[{$idx}]";
                    $this->assertIsArray($item, "$itemMsg should be an array");
                    $this->assertMatchesSchema($item, $innerSchema, $itemMsg);
                }
            } else {
                // Associative nested schema
                $this->assertMatchesSchema($value, $typeOrNested, $msg);
            }

            return;
        }

        $valid = false;
        foreach (explode('|', $typeOrNested) as $type) {
            if ($this->valueMatchesType($value, trim($type))) {
                $valid = true;
                break;
            }
        }

        $this->assertTrue($valid, "$msg should be of type $typeOrNested, got " . gettype($value));
    }

    /**
     * Helper: Check if a value matches the expected PHP type
     */
    private function valueMatchesType($value, string $type): bool
    {
        return match ($type) {
            'string' => is_string($value),
            'int', 'integer' => is_int($value),
            'float' => is_float($value),
            'numeric' => is_numeric($value),
            'bool', 'boolean' => is_bool($value),
            'array' => is_array($value),
            'null' => is_null($value),
            'scalar' => is_scalar($value),
            default => false,
        };
    }

    /**
     * Get authentication token for API requests
     *
     * @param string $permission Permission level
     * @return string Authentication token
     */
    protected function getToken($permission = 'Admins'): string
    {
        if ($permission === 'Admins') {
            if (self::$adminApiToken === null) {
                throw new \RuntimeException('Admin API token not initialized.');
            }
            return self::$adminApiToken;
        } else {
            // TODO: Add token creation for other permission levels
            throw new \RuntimeException("Token creation for permission level '$permission' not implemented yet.");
        }
    }

    /**
     * Assert that the response has the expected status code
     *
     * @param int $expectedCode Expected status code
     * @param array $response Response array
     * @param string $message Optional assertion message
     */
    protected function assertResponseStatus($expectedCode, $response, $message = '')
    {
        $this->assertEquals($expectedCode, $response['status_code'], $message ?: "Expected status code $expectedCode, got {$response['status_code']}");
    }

    /**
     * Get the parsed response body (as array if JSON)
     *
     * @param array $response Response array
     * @return mixed Parsed response body
     */
    protected function getResponseBody($response)
    {
        return $response['parsed_body'];
    }

    /**
     * Assert that response contains a valid error structure
     */
    protected function assertValidErrorResponse($body, $expectedCode = null, $expectedMessage = null)
    {
        $this->assertIsArray($body, 'Error response should have valid structure');

        // Required error fields
        $this->assertArrayHasKey('code', $body, 'Error response should have code');
        $this->assertArrayHasKey('message', $body, 'Error response should have message');
        if ($expectedMessage !== null) {
            $this->assertStringContainsString($expectedMessage, $body['message'], 'Error message should be contains expected text');
        }

        // Data type validation
        $this->assertIsInt($body['code'], 'Error code should be integer');
        $this->assertIsString($body['message'], 'Error message should be string');
        $this->assertNotEmpty($body['message'], 'Error message should not be empty');

        // Optional specific code check
        if ($expectedCode !== null) {
            $this->assertEquals($expectedCode, $body['code'], "Error code should be {$expectedCode}");
        }

        if (isset($body['errortitle'])) {
            $this->assertIsString($body['errortitle'], 'Error title should be string');
        }
    }
}
