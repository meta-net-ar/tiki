<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Lib\Importer\Markdown\Parser;

/**
 * Contract for Markdown source "purifiers".
 * Implementations receive raw Markdown and return normalized Markdown (string)
 * along with metadata if needed.
 */
interface MarkdownPurifierInterface
{
    /**
     * Purify/normalize a Markdown document coming from a specific source.
     *
     * @param string $raw Raw Markdown input
     * @param array  $context Contextual options:
     *   - 'journal_ns' (string)  Namespace for journals (default 'Journal')
     *   - 'strip_front_matter' (bool) Remove YAML front-matter (default true)
     *   - 'runtime_logseq' (bool) Keep Logseq markers to be handled at runtime (default false)
     *   - any other source-specific flags…
     *
     * @return array { 'markdown' => string, 'notes' => string[] }
     */
    public function purify(string $raw, array $context = []): array;

    /**
     * Identifier of the source type handled by this purifier
     * (e.g. 'commonmark', 'gfm', 'logseq').
     */
    public static function id(): string;
}
