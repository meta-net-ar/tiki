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
use Tiki\Mcp\Server as TikiMcpServer;
use Tiki\Mcp\Tools\WikiTools;

// --- Bootstrap Tiki (API mode) ---
// TIKI_API enables Bearer token auth in tiki-setup_base.php, which handles
// token validation, REDIRECT_HTTP_AUTHORIZATION, OAuth JWT, hit tracking,
// and permission context initialization via lib/setup/perms.php.
define('TIKI_API', true);

// Stateless: no session cookies, no CSRF (same as tiki-api.php)
ini_set('session.use_cookies', 0);

// Custom error handler: suppress stdout noise so MCP JSON-RPC is not corrupted.
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

require_once 'tiki-setup.php';

// --- Require API tokens feature and authenticated user ---
// Site closed check is handled by lib/setup/site_closed.php during bootstrap,
// which returns a JSON error when TIKI_API is true.
global $user, $prefs;
if ($prefs['auth_api_tokens'] !== 'y') {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'API access is not enabled.']);
    exit(1);
}

// tiki-setup_base.php sets $user from the Bearer token when TIKI_API is true.
if (empty($user)) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Authentication required. Use: Authorization: Bearer <token>']);
    exit(1);
}

// --- Build MCP server ---
// FileSessionStore persists MCP protocol sessions across PHP requests
// (required for HTTP transport). Uses Tiki's configured temp directory.
$sessionDir = $prefs['tmpDir'] . '/mcp-sessions';
$sessionStore = new FileSessionStore($sessionDir);

$clientIp = $_SERVER['REMOTE_ADDR'] ?? '';
$wikiTools = new WikiTools($user, $clientIp);
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
