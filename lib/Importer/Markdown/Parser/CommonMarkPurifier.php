<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Lib\Importer\Markdown\Parser;

class CommonMarkPurifier implements MarkdownPurifierInterface
{
    public static function id(): string
    {
        return 'commonmark';
    }

    /**
     * Apply minimal CommonMark-oriented cleanup to markdown input.
     *
     * Supported context keys:
     * - strip_front_matter (bool, default: true): remove YAML front-matter block when present.
     *
     * @param string $raw Raw markdown source content.
     * @param array{strip_front_matter?: bool} $context Optional purifier options.
     * @return array{markdown: string, notes: string[]} Purified markdown and informational notes.
     */
    public function purify(string $raw, array $context = []): array
    {
        $stripFM = $context[self::CTX_STRIP_FM] ?? true;
        $out = $raw;
        $notes = [];

        if ($stripFM) {
            [$out, $had] = self::removeFrontMatter($out);
            if ($had) {
                $notes[] = tra('Front matter stripped');
            }
        }

        // No other transformation: rely on Tiki’s CommonMark pipeline.
        return ['markdown' => $out, 'notes' => $notes];
    }

    /**
     * Remove YAML front-matter if present. The single implementation shared by
     * every purifier and by MarkdownFileAnalyzer's title resolution, so the two
     * can never disagree on what counts as front-matter.
     *
     * Delimiters must each be alone on their own line (per the YAML front-matter
     * convention), not merely appear anywhere in the text — otherwise an
     * ordinary document that happens to contain two "---" sequences (e.g. a
     * Markdown horizontal rule) could be misread as having front-matter.
     *
     * @return array{0: string, 1: bool, 2: ?string} [body without front-matter, had front-matter, raw front-matter block]
     */
    public static function removeFrontMatter(string $s): array
    {
        if (preg_match('/^---\s*\r?\n(.*?)\r?\n---\s*(?:\r?\n|$)/s', $s, $m)) {
            return [(string)substr($s, \strlen($m[0])), true, $m[1]];
        }
        return [$s, false, null];
    }
}
