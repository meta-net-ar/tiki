<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\RobotsTxt;

use TikiLib;

/**
 * Robots.txt matcher - matches URLs against a parsed robots.txt tree.
 *
 * Instance-based design for easier testing and flexibility.
 * Use factory methods fromFile() or fromString() to create instances.
 *
 * Features:
 * - User-agent aware matching (most specific UA token wins, '*' fallback)
 * - Allow/Disallow matching with wildcards '*' and end anchor '$'
 * - Longest matching rule wins; ties prefer Allow
 * - Built-in caching support via Tiki's cache system
 *
 * Based on Google's robots.txt specification:
 * @link https://developers.google.com/search/docs/crawling-indexing/robots/robots_txt
 *
 * @see Parser For parsing robots.txt content into a tree
 */
class Matcher
{
    /**
     * @var array The parsed robots.txt tree structure
     */
    private array $tree;

    /**
     * @param array $tree The parsed robots.txt tree
     */
    private function __construct(array $tree)
    {
        $this->tree = $tree;
    }

    /**
     * Create a Matcher instance from a robots.txt file.
     *
     * Handles file loading and optional caching. If the file doesn't exist,
     * returns a matcher that allows everything by default.
     *
     * @param string $robotsFile Path to the robots.txt file
     * @param bool $useCache Whether to use Tiki's cache system (default: true)
     * @return self A Matcher instance
     */
    public static function fromFile(string $robotsFile, bool $useCache = true): self
    {
        // If file doesn't exist, return a matcher that allows everything
        if (! file_exists($robotsFile)) {
            return new self([]);
        }

        if ($useCache) {
            $tree = self::loadTreeWithCache($robotsFile);
        } else {
            $robotsContent = file_get_contents($robotsFile);
            $tree = ($robotsContent !== false && $robotsContent !== '')
                ? Parser::buildTree($robotsContent)
                : [];
        }

        return new self($tree);
    }

    /**
     * Create a Matcher instance from robots.txt content string.
     *
     * Useful for testing or when content is already in memory.
     *
     * @param string $robotsContent The robots.txt content
     * @return self A Matcher instance
     */
    public static function fromString(string $robotsContent): self
    {
        if (empty($robotsContent)) {
            return new self([]);
        }

        $tree = Parser::buildTree($robotsContent);
        return new self($tree);
    }

    /**
     * Check if a URL is allowed for a given user agent.
     *
     * @param string $uri The request URI (path + query string)
     * @param string $userAgent The HTTP User-Agent string (defaults to '*' if empty)
     * @return bool true if allowed, false if disallowed
     */
    public function isAllowed(string $uri, string $userAgent = ''): bool
    {
        // Empty URI is allowed by default (nothing to match against)
        if (empty($uri)) {
            return true;
        }

        // Empty tree means no rules - allow everything
        if (empty($this->tree)) {
            return true;
        }

        // Parse the URI to get path + query (strip fragment)
        $pathQuery = $this->parseUri($uri);
        if (empty($pathQuery)) {
            return true;
        }

        // Empty or whitespace-only user agent defaults to '*'
        $ua = trim($userAgent);
        if (empty($ua)) {
            $ua = '*';
        }

        // Find the matching user-agent group
        $uaKey = $this->matchUserAgentKey($ua, array_keys($this->tree));
        if (empty($uaKey) || empty($this->tree[$uaKey])) {
            // No matching rules for this user agent - allow by default
            return true;
        }

        return $this->applyAllowDisallow($this->tree[$uaKey], $pathQuery);
    }

    /**
     * Check if a URL is disallowed for a given user agent.
     *
     * This is the inverse of isAllowed() - provided for semantic clarity.
     *
     * @param string $uri The request URI (path + query string)
     * @param string $userAgent The HTTP User-Agent string (defaults to '*' if empty)
     * @return bool true if disallowed (blocked), false if allowed
     */
    public function isDisallowed(string $uri, string $userAgent = ''): bool
    {
        return ! $this->isAllowed($uri, $userAgent);
    }

    /**
     * Get the parsed tree
     *
     * @return array The parsed robots.txt tree
     */
    public function getTree(): array
    {
        return $this->tree;
    }

    /**
     * Load the parsed robots.txt tree using Tiki's cache system.
     *
     * Cache invalidation is based on file modification time (filemtime).
     * The tree persists indefinitely until robots.txt is modified.
     *
     * @param string $robotsFile Path to the robots.txt file
     * @return array The parsed tree structure
     */
    private static function loadTreeWithCache(string $robotsFile): array
    {
        $cachelib = TikiLib::lib('cache');
        $cacheKey = 'robots_txt_tree';
        $cacheType = 'robots';

        // Use file modification time for cache invalidation
        $robotsMtime = filemtime($robotsFile);

        // Try to get cached tree (invalidated only when robots.txt is newer)
        $tree = $cachelib->getSerialized($cacheKey, $cacheType, $robotsMtime);

        if ($tree !== null && is_array($tree)) {
            return $tree;
        }

        // Cache miss or stale - parse the file and cache the result
        $robotsContent = file_get_contents($robotsFile);
        if ($robotsContent === false || empty($robotsContent)) {
            return [];
        }

        $tree = Parser::buildTree($robotsContent);
        $cachelib->cacheItem($cacheKey, serialize($tree), $cacheType);

        return $tree;
    }

    /**
     * Parse URI to extract path + query string (strips fragment).
     *
     * @param string $uri The request URI
     * @return string The path with optional query string
     */
    private function parseUri(string $uri): string
    {
        $parsedUrl = parse_url($uri);
        $path = $parsedUrl['path'] ?? '';
        $query = $parsedUrl['query'] ?? '';

        return ! empty($query) ? ($path . '?' . $query) : $path;
    }

    /**
     * Match a user agent string to the most specific available user-agent token.
     *
     * Per Google's specification, user-agent matching uses case-insensitive substring
     * matching. The most specific (longest) matching token wins. If no specific match
     * is found, falls back to '*' if available.
     *
     * @param string   $userAgent     The full user agent string from the HTTP request
     * @param string[] $availableKeys Array of user-agent tokens defined in robots.txt
     * @return string|null The matched token, '*' as fallback, or null if no match
     */
    private function matchUserAgentKey(string $userAgent, array $availableKeys): ?string
    {
        // Normalize user agent - tree keys are already lowercase from Parser::buildTree()
        $ua = strtolower(trim($userAgent));
        if (empty($ua)) {
            $ua = '*';
        }

        // Exact match wins immediately
        if (in_array($ua, $availableKeys, true)) {
            return $ua;
        }

        $best = null;
        $bestLen = -1;
        $hasWildcard = false;

        foreach ($availableKeys as $token) {
            if ($token === '*') {
                $hasWildcard = true;
                continue;
            }

            if (str_contains($ua, $token)) {
                $len = strlen($token);
                if ($len > $bestLen) {
                    $bestLen = $len;
                    $best = $token;
                }
            }
        }

        return $best ?? ($hasWildcard ? '*' : null);
    }

    /**
     * Apply Allow/Disallow rules to determine if a path is allowed.
     *
     * Per Google's specification:
     * 1. The most specific (longest) matching rule wins
     * 2. If multiple rules have the same specificity, Allow takes precedence
     * 3. If no rules match, the path is allowed by default
     *
     * @param array{allow: string[], disallow: string[]} $rules The rules for the matched user-agent
     * @param string $pathQuery The URL path with optional query string to check
     * @return bool true if allowed, false if disallowed
     */
    private function applyAllowDisallow(array $rules, string $pathQuery): bool
    {
        $bestLen = -1;
        $bestType = null; // 'allow'|'disallow'

        foreach (['disallow', 'allow'] as $type) {
            foreach ($rules[$type] ?? [] as $rule) {
                $pattern = $this->ruleToRegex($rule);
                if (! preg_match($pattern, $pathQuery)) {
                    continue;
                }

                $len = $this->specificityLength($rule);

                if ($len > $bestLen) {
                    $bestLen = $len;
                    $bestType = $type;
                } elseif ($len === $bestLen) {
                    // Tie-breaker: Allow wins
                    if ($bestType !== 'allow' && $type === 'allow') {
                        $bestType = 'allow';
                    }
                }
            }
        }

        if ($bestType === null) {
            return true;
        }

        return $bestType === 'allow';
    }

    /**
     * Convert a robots.txt rule pattern to a regex.
     *
     * Per Google's specification:
     * - '*' matches any sequence of characters (including empty)
     * - '$' at the end anchors the pattern to end of URL
     * - All other special regex characters are escaped
     * - Rules are prefix-matched (implicitly match anything after)
     *
     * @param string $rule The rule pattern from robots.txt
     * @return string A regex pattern
     */
    private function ruleToRegex(string $rule): string
    {
        // Escape all regex special characters except * and $
        $pattern = preg_quote($rule, '/');

        // Restore * as wildcard (matches any sequence including empty)
        $pattern = str_replace('\*', '.*', $pattern);

        // Handle end anchor: $ at end of rule means exact end match
        if (str_ends_with($pattern, '\$')) {
            $pattern = substr($pattern, 0, -2) . '$';
        }

        // Anchor to start of string
        return '/^' . $pattern . '/';
    }

    /**
     * Calculate the specificity length of a rule for precedence comparison.
     *
     * @param string $rule The rule pattern
     * @return int The specificity length
     */
    private function specificityLength(string $rule): int
    {
        return strlen(str_replace(['*', '$'], '', $rule));
    }
}
