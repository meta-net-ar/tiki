<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

class ApiTestServer
{
    private static $serverHost = 'localhost';
    private static $serverPort = 8000;
    
    /**
     * Start the PHP built-in server for API testing
     * 
     * @param string $host Server host (default: localhost)
     * @param int $port Server port (default: 8000)
     * @return bool True if server started successfully
     */
    public static function start($host = 'localhost', $port = 8000)
    {
        self::$serverHost = $host;
        self::$serverPort = $port;

        if (self::isRunning()) {
            return true;
        }

        // Command: php -S localhost:8000 route.php
        $command = sprintf(
            'php -S %s:%d route.php > /dev/null 2>&1 & echo $!',
            self::$serverHost,
            self::$serverPort
        );

        // Execute the command and capture the process ID
        $output = shell_exec($command);
        $pid = trim($output);

        if ($pid && is_numeric($pid)) {
            // Wait a moment for the server to start
            usleep(500000); // 0.5 seconds

            if (self::isRunning()) {
                // Store the PID for later cleanup
                file_put_contents(self::getPidFile(), $pid);
                return true;
            }
        }

        return false;
    }

    /**
     * Stop the API test server
     * 
     * @return bool True if server was stopped successfully
     */
    public static function stop()
    {
        $pidFile = self::getPidFile();

        if (file_exists($pidFile)) {
            $pid = trim(file_get_contents($pidFile));

            if ($pid && is_numeric($pid)) {
                // Try to kill the process gracefully
                $result = shell_exec("kill $pid 2>/dev/null");

                usleep(200000); // 0.2 seconds
                
                // Force kill if still running
                if (self::isProcessRunning($pid)) {
                    shell_exec("kill -9 $pid 2>/dev/null");
                }
            }

            unlink($pidFile);
        }

        // Verify the server is no longer responding
        return ! self::isRunning();
    }

    /**
     * Check if the API test server is running and responding
     * 
     * @return bool True if server is running and responding
     */
    public static function isRunning()
    {
        $url = self::getBaseUrl() . '/api/version';

        // Use a simple HTTP check with short timeout
        $context = stream_context_create([
            'http' => [
                'timeout' => 1,
                'ignore_errors' => true
            ]
        ]);

        $result = @file_get_contents($url, false, $context);

        // Check if we got any response (even an error response indicates server is running)
        return $result !== false || (isset($http_response_header) && ! empty($http_response_header));
    }

    /**
     * Get the base URL for the API test server
     * 
     * @return string Base URL
     */
    public static function getBaseUrl()
    {
        return sprintf('http://%s:%d', self::$serverHost, self::$serverPort);
    }

    /**
     * Get the API base URL for the test server
     * 
     * @return string API base URL
     */
    public static function getApiBaseUrl()
    {
        return self::getBaseUrl() . '/api';
    }

    /**
     * Wait for the API server to be ready
     * 
     * @param int $maxWaitSeconds Maximum time to wait
     * @param float $checkInterval Interval between checks in seconds
     * @return bool True if server became ready within timeout
     */
    public static function waitForReady($maxWaitSeconds = 10, $checkInterval = 0.1)
    {
        $startTime = microtime(true);

        while ((microtime(true) - $startTime) < $maxWaitSeconds) {
            if (self::isRunning()) {
                return true;
            }
            usleep($checkInterval * 1000000);
        }

        return false;
    }

    /**
     * Get the path to the PID file for server process tracking
     * 
     * @return string PID file path
     */
    private static function getPidFile()
    {
        return sys_get_temp_dir() . '/tiki_api_test_server.pid';
    }

    /**
     * Check if a process is running by PID
     * 
     * @param int $pid Process ID
     * @return bool True if process is running
     */
    private static function isProcessRunning($pid)
    {
        if (! $pid || ! is_numeric($pid)) {
            return false;
        }

        // Use kill -0 to check if process exists (doesn't actually kill)
        $result = shell_exec("kill -0 $pid 2>/dev/null; echo $?");
        return trim($result) === '0';
    }

    /**
     * Clean up any leftover API server processes
     * This is useful for cleanup after failed tests
     * 
     * @return void
     */
    public static function cleanup()
    {
        $processes = shell_exec("ps aux | grep 'php -S " . self::$serverHost . ":" . self::$serverPort . "' | grep -v grep");

        if ($processes) {
            $lines = explode("\n", trim($processes));
            foreach ($lines as $line) {
                if (preg_match('/\s+(\d+)\s+/', $line, $matches)) {
                    $pid = $matches[1];
                    shell_exec("kill -9 $pid 2>/dev/null");
                }
            }
        }

        // Remove PID file
        $pidFile = self::getPidFile();
        if (file_exists($pidFile)) {
            unlink($pidFile);
        }
    }

    /**
     * Restart the API test server
     * 
     * @param string $host Server host
     * @param int $port Server port
     * @return bool True if server restarted successfully
     */
    public static function restart($host = 'localhost', $port = 8000)
    {
        self::stop();
        usleep(200000); // Wait 0.2 seconds
        return self::start($host, $port);
    }

    /**
     * Get comprehensive API server information
     * 
     * @return array Server information including URLs, paths, and status
     */
    public static function getServerInfo()
    {
        return [
            'host' => self::$serverHost,
            'port' => self::$serverPort,
            'api_base_url' => self::getApiBaseUrl(),
            'router_script' => 'route.php',
            'is_running' => self::isRunning(),
            'pid_file' => self::getPidFile()
        ];
    }
}
