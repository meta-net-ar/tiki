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

    public function purify(string $raw, array $context = []): array
    {
        $stripFM = $context['strip_front_matter'] ?? true;
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

    /** Remove YAML front-matter if present. */
    public static function removeFrontMatter(string $s): array
    {
        if (preg_match('/^---\s*(.*?)\s*---\s*(.*)$/s', $s, $m)) {
            return [$m[2], true];
        }
        return [$s, false];
    }
}
