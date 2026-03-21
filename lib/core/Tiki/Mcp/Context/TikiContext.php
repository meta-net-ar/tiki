<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Mcp\Context;

/**
 * Manages Tiki runtime context for MCP tool execution.
 *
 * Provides output buffering to prevent TikiLib methods from writing
 * to stdout (which is reserved for JSON-RPC in MCP stdio transport).
 */
class TikiContext
{
    private string $user;

    public function __construct(string $user)
    {
        $this->user = $user;
    }

    public function getUser(): string
    {
        return $this->user;
    }

    /**
     * Execute a callable while capturing any stray output.
     *
     * TikiLib methods sometimes echo directly — this prevents that
     * from corrupting the JSON-RPC stdout stream. Captured output
     * is redirected to stderr for debugging.
     *
     * @template T
     * @param callable(): T $fn
     * @return T
     */
    public function captureOutput(callable $fn): mixed
    {
        ob_start();
        try {
            $result = $fn();
        } finally {
            $output = ob_get_clean();
            if ($output !== '' && $output !== false) {
                fwrite(STDERR, $output);
            }
        }
        return $result;
    }
}
