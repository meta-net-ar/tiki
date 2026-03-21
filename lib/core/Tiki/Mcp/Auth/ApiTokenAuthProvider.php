<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Mcp\Auth;

/**
 * API token auth provider for MCP server.
 *
 * Validates Bearer tokens from the Authorization header against
 * Tiki's tiki_api_tokens table. On success, tracks usage via hit()
 * and returns the associated Tiki username.
 *
 * A valid Bearer token is always required. Unauthenticated requests are rejected.
 */
class ApiTokenAuthProvider implements AuthProviderInterface
{
    /**
     * Authenticate via Bearer token.
     *
     * @param array $metadata Must contain 'authorization' key with the raw header value
     * @return string Authenticated Tiki username
     * @throws AuthenticationException If no token is provided, or the token is invalid
     */
    public function authenticate(array $metadata): string
    {
        $authHeader = $metadata['authorization'] ?? '';

        if (empty($authHeader)) {
            throw new AuthenticationException('Authorization header is required. Use: Authorization: Bearer <token>');
        }

        // Extract Bearer token
        if (! preg_match('/Bearer\s+(.*)/i', $authHeader, $matches)) {
            throw new AuthenticationException('Invalid Authorization header format. Expected: Bearer <token>');
        }

        $tokenString = trim($matches[1]);
        if (empty($tokenString)) {
            throw new AuthenticationException('Empty Bearer token.');
        }

        // Validate against tiki_api_tokens
        $apiTokenLib = \TikiLib::lib('api_token');
        $token = $apiTokenLib->validToken($tokenString);

        if (! $token) {
            throw new AuthenticationException('Invalid or expired API token.');
        }

        if (empty($token['user'])) {
            throw new AuthenticationException('API token has no associated user.');
        }

        // Track token usage
        $apiTokenLib->hit($token);

        return $token['user'];
    }
}
