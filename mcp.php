<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

/**
 * MCP (Model Context Protocol) HTTP entry point for Tiki Wiki.
 *
 * Handles MCP requests over Streamable HTTP transport.
 * Served by Apache/Nginx — each request bootstraps Tiki, determines
 * the authenticated user, and processes the MCP JSON-RPC message.
 *
 * Authentication:
 *   Requires Authorization: Bearer <token> using Tiki's API token system.
 *   Unauthenticated requests are rejected with 401.
 */

use GuzzleHttp\Psr7\ServerRequest;
use Mcp\Server\Session\FileSessionStore;
use Mcp\Server\Transport\StreamableHttpTransport;
use Tiki\Mcp\Auth\ApiTokenAuthProvider;
use Tiki\Mcp\Auth\AuthenticationException;
use Tiki\Mcp\Server as TikiMcpServer;
use Tiki\Mcp\Tools\WikiTools;

// --- Composer autoloader ---
if (! include 'vendor_bundled/vendor/autoload.php') {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Server configuration error.']);
    exit(1);
}

// --- Custom error handler: suppress stdout noise ---
// Errors go to error_log, not the response body.
set_error_handler(function (int $number, string $message, string $file, int $line): bool {
    if (0 === error_reporting()) {
        return true;
    }
    $errorEnabled = (bool)($number & (int)ini_get('error_reporting'));
    if ($errorEnabled) {
        error_log("[MCP] $message on line $line of $file");
        if (in_array($number, [E_USER_ERROR, E_RECOVERABLE_ERROR])) {
            throw new ErrorException($message, 0, $number, $file, $line);
        }
    }
    return true;
});

// --- Bootstrap Tiki ---
$statusCode = 1100;
try {
    $bypass_siteclose_check = true;
    require_once 'tiki-setup.php';

    if (\Tiki\Installer\Installer::getInstance()->requiresUpdate()) {
        $statusCode = 1004;
    }
} catch (\Tiki\Command\ConsoleSetupException $e) {
    $statusCode = $e->getCode();
} catch (Throwable $e) {
    $statusCode = 1001;
    error_log("MCP bootstrap error: " . $e->getMessage());
}

if (! defined('DB_RUNNING')) {
    define('DB_RUNNING', $statusCode > 1001);
    define('DB_STATUS', $statusCode > 1002);
    define('DB_TIKI_SETUP', $statusCode > 1003);
    define('DB_SYNCHRONAL', $statusCode > 1004);
}

if ($statusCode < 1100) {
    http_response_code(503);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Service unavailable']);
    exit(1);
}

// --- Respect site maintenance mode (#12) ---
global $prefs;
if (! empty($prefs['site_closed']) && $prefs['site_closed'] === 'y') {
    http_response_code(503);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Site is in maintenance mode']);
    exit(1);
}

// --- Determine authenticated user ---
// Requires a valid Bearer token from the Authorization header.
// Tokens are validated against tiki_api_tokens table.

// Apache CGI/FastCGI may place the Authorization header in REDIRECT_HTTP_AUTHORIZATION.
// Replicate the same fix from tiki-setup_base.php to ensure token auth works in all configs.
if (! empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
    $_SERVER['HTTP_AUTHORIZATION'] = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
}

$authProvider = new ApiTokenAuthProvider();
try {
    $asUser = $authProvider->authenticate([
        'authorization' => $_SERVER['HTTP_AUTHORIZATION'] ?? '',
    ]);
} catch (AuthenticationException $e) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => $e->getMessage()]);
    exit(1);
}

if (! \TikiLib::lib('user')->user_exists($asUser)) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Authentication failed.']);
    exit(1);
}
$permissionContext = new \Perms_Context($asUser);

// --- Set $_SERVER defaults for TikiLib ---
$_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? '/mcp.php';
$_SERVER['SERVER_NAME'] = $_SERVER['SERVER_NAME'] ?? 'localhost';
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';

// --- Build MCP server ---
// FileSessionStore persists sessions across PHP requests (required for HTTP transport).
// Store outside the document root so sessions are never web-accessible,
// regardless of web server config (.htaccess, Nginx, AllowOverride).
$sessionDir = sys_get_temp_dir() . '/tiki-mcp-' . md5(__FILE__);
if (! is_dir($sessionDir)) {
    mkdir($sessionDir, 0700, true);
}
$sessionStore = new FileSessionStore($sessionDir);

$clientIp = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$wikiTools = new WikiTools($asUser, $clientIp);
$server = TikiMcpServer::create('tiki-wiki', '1.0.0', [$wikiTools], $sessionStore);

// --- Create PSR-7 request and run HTTP transport ---
$psrRequest = ServerRequest::fromGlobals();
$transport = new StreamableHttpTransport($psrRequest);
$response = $server->run($transport);

// --- Emit PSR-7 response ---
http_response_code($response->getStatusCode());
foreach ($response->getHeaders() as $name => $values) {
    foreach ($values as $value) {
        header("$name: $value", false);
    }
}
echo $response->getBody();
