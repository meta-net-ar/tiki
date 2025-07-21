<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

require_once(dirname(__FILE__) . '/ApiTestServer.php');
use GuzzleHttp\Client;

abstract class ApiTestCase extends TikiTestCase
{
    /**
     * @var bool Whether the test server has been initialized for this class
     */
    private static $testServerInitialized = false;

    /**
     * @var \TikiDb The database connection used for API tests
     */
    private $db;

    /**
     * Static property to hold the admin API token for the test session
     */
    private static $adminApiToken = null;

    /**
     * Returns the base URL for API requests.
     * Always uses the test server for API integration tests.
     *
     * @return string The API base URL
     */
    protected function getBaseUrl()
    {
        if (ApiTestServer::isRunning()) {
            return ApiTestServer::getBaseUrl() . '/api/';  // Add /api/ with trailing slash for proper Guzzle URL combination
        }

        // Default fallback for API tests
        return 'http://localhost:8000/api/';
    }

    /**
     * Set up the API test server before running test class
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        if (! self::$testServerInitialized) {
            if (ApiTestServer::start()) {
                echo "API test server started successfully at " . ApiTestServer::getBaseUrl() . "\n";

                if (ApiTestServer::waitForReady(10)) {
                    echo "API test server is ready for requests\n";
                    self::$testServerInitialized = true;

                    // Create reusable admin API token for the test session
                    $tokenlib = \TikiLib::lib('api_token');
                    $token = $tokenlib->createToken([
                        'type' => 'manual',
                        'user' => 'admin'
                    ]);
                    self::$adminApiToken = $token['token'];
                } else {
                    throw new \RuntimeException("API test server failed to become ready within timeout");
                }
            } else {
                throw new \RuntimeException("Failed to start API test server");
            }
        }
    }

    /**
     * Clean up the API test server after running test class
     */
    public static function tearDownAfterClass(): void
    {
        if (self::$testServerInitialized) {            
            if (ApiTestServer::stop()) {
                echo "API test server stopped successfully\n";
            } else {
                echo "Warning: Failed to stop API test server cleanly\n";
                ApiTestServer::cleanup(); // Force cleanup
            }

            self::$testServerInitialized = false;
        }

        parent::tearDownAfterClass();
    }

    /**
     * Sends an HTTP request to the specified endpoint.
     *
     * @param string $method The HTTP method to use (e.g., GET, POST, PUT, DELETE).
     * @param string $endpoint The URL or endpoint to send the request to (should start with '/').
     * @param string|null $permission The required permission level for the request (null for unauthenticated requests).
     * @param array $data The data to send with the request (default: []).
     * @param string $contentType The content type of the request data (default: 'application/json').
     * @return \Psr\Http\Message\ResponseInterface The response from the HTTP request.
     */
    protected function makeHttpRequest($method, $endpoint, $permission = 'Admins', $data = [], $contentType = 'application/json')
    {
        // For API integration tests, remove leading slash from endpoint to allow proper base_uri + endpoint combination
        // Guzzle treats endpoints starting with '/' as absolute paths from host root
        if (str_starts_with($endpoint, '/')) {
            $endpoint = substr($endpoint, 1);
        }

        $baseUrl = $this->getBaseUrl();

        $clientConfig = [
            'base_uri' => $baseUrl,
            'headers' => [
                'Content-Type' => $contentType,
            ],
        ];

        // Add authentication if permission is provided
        if ($permission !== null) {
            $token = $this->getToken($permission);
            $clientConfig['headers']['Authorization'] = 'Bearer ' . $token;
        }

        $client = new Client($clientConfig);

        // Determine request method and options
        $options = [];
        if ($method === 'GET' || $method === 'DELETE') {
            $options['query'] = $data;
        } else {
            if ($contentType === 'application/json') {
                $options['json'] = $data;
            } else {
                $options['form_params'] = $data;
            }
        }

        try {
            $response = $client->request($method, $endpoint, $options);
            return $response;
        } catch (GuzzleHttp\Exception\ClientException $e) {
            throw new \RuntimeException('Failed to make request: ' . $e->getMessage());
        }
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
            return 'xxxxxxxxxxxxxxxxxxxxxxx'; // TODO: Add token for other permission levels
        }
    }

    /**
     * Cleans the specified database tables by truncating them.
     *
     * @param array $tables An array of table names to be cleaned.
     * @return void
     */
    protected function cleanDatabaseTables(array $tables): void
    {
        $this->db = TikiDb::get();
        foreach ($tables as $table) {
            $this->db->query("TRUNCATE TABLE $table");
        }
    }
}
