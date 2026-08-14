<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\RobotsTxt;

/**
 * Robots.txt parser - parses robots.txt content into a structured tree.
 *
 * Based on Google's robots.txt specification:
 * @link https://developers.google.com/search/docs/crawling-indexing/robots/robots_txt
 *
 * Inspired by common implementations (e.g. MIT-licensed parsers like t1gor/robots-txt-parser)
 *
 * @see Matcher For matching URLs against the parsed tree
 */
class Parser
{
    /**
     * Parse robots.txt content into a structured tree keyed by user-agent token.
     *
     * The returned tree structure maps each user-agent (lowercase) to its rules:
     * ```
     * [
     *     '*' => ['allow' => ['/public/'], 'disallow' => ['/private/']],
     *     'googlebot' => ['allow' => ['/'], 'disallow' => ['/nogoogle/']],
     * ]
     * ```
     *
     * Parsing behavior:
     * - UTF-8 BOM is stripped if present
     * - Lines are split on CR, LF, or CRLF
     * - Comments (starting with #) are stripped
     * - User-agent values are lowercased for case-insensitive matching
     * - Only Allow and Disallow directives are processed (Crawl-delay, Sitemap, etc. are ignored)
     * - Rules must start with '/' to be valid
     * - Blank lines within a group are allowed for readability
     * - A new User-agent directive after rules starts a new group
     *
     * @param string $robotsContent Full robots.txt contents
     * @return array<string, array{allow: string[], disallow: string[]}> Parsed tree structure
     */
    public static function buildTree(string $robotsContent): array
    {
        // Strip UTF-8 BOM if present
        if (str_starts_with($robotsContent, "\xEF\xBB\xBF")) {
            $robotsContent = substr($robotsContent, 3);
        }

        $lines = preg_split("/\r\n|\n|\r/", $robotsContent) ?: [];
        $tree = [];

        $currentAgents = [];
        $seenRuleInGroup = false;

        foreach ($lines as $line) {
            // Check if line is blank BEFORE stripping comments
            // Only truly blank lines (not comment-only lines) should end groups
            $isBlankLine = (trim($line) === '');

            // Strip inline comments
            $hashPos = strpos($line, '#');
            if ($hashPos !== false) {
                $line = substr($line, 0, $hashPos);
            }

            $line = trim($line);
            if ($line === '') {
                // Per Google's spec, blank lines separate groups. However, many real-world
                // robots.txt files use blank lines for readability within groups.
                // We track that we've seen a blank line, and use it to determine if a new
                // User-agent directive starts a new group.
                if ($isBlankLine && $seenRuleInGroup) {
                    $seenBlankAfterRules = true;
                }
                continue;
            }

            $parts = explode(':', $line, 2);
            if (count($parts) !== 2) {
                continue;
            }

            $field = strtolower(trim($parts[0]));
            $value = trim($parts[1]);

            if ($field === 'user-agent') {
                // Start a new group if:
                // 1. We've seen rules in the current group (new UA after rules), OR
                // 2. We've seen a blank line after rules (explicit group separator)
                if ($seenRuleInGroup || (isset($seenBlankAfterRules) && $seenBlankAfterRules)) {
                    $currentAgents = [];
                    $seenRuleInGroup = false;
                }
                $seenBlankAfterRules = false;

                if ($value !== '') {
                    $currentAgents[] = strtolower($value);
                }
                continue;
            }

            if ($field !== 'allow' && $field !== 'disallow') {
                continue;
            }

            if (empty($currentAgents)) {
                // invalid group, skip
                continue;
            }

            $seenRuleInGroup = true;

            // Empty Disallow means allow-all; Empty Allow is no-op
            if ($value === '') {
                continue;
            }

            // Following common implementations: path rules should start with '/'
            if (! str_starts_with($value, '/')) {
                continue;
            }

            foreach ($currentAgents as $agent) {
                if (! isset($tree[$agent])) {
                    $tree[$agent] = ['allow' => [], 'disallow' => []];
                }
                if (! in_array($value, $tree[$agent][$field], true)) {
                    $tree[$agent][$field][] = $value;
                }
            }
        }

        return $tree;
    }
}
