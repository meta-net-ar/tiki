<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Mcp\Auth;

/**
 * Pluggable authentication interface for MCP server.
 *
 * v1: LocalAuthProvider (pass-through, CLI --as-user).
 * Future: ApiTokenAuthProvider using tiki_api_tokens table.
 */
interface AuthProviderInterface
{
    /**
     * Authenticate the request and return a Tiki username.
     *
     * @param array $metadata Request metadata (headers, tokens, etc.)
     * @return string The authenticated Tiki username
     */
    public function authenticate(array $metadata): string;
}
