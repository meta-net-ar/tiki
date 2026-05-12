<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Lib\Importer\Markdown\Parser;

class GfmPurifier implements MarkdownPurifierInterface
{
    public static function id(): string
    {
        return 'gfm';
    }

    /**
     * Apply GFM-oriented normalization to markdown input.
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
            [$out, $had] = CommonMarkPurifier::removeFrontMatter($out);
            if ($had) {
                $notes[] = tra('Front matter stripped');
            }
        }

        $normalized = false;
        $out = self::normalizeTasks($out, $normalized);
        if ($normalized) {
            $notes[] = tra('Task markers normalized to GFM');
        }

        return ['markdown' => $out, 'notes' => $notes];
    }

    /** Convert TODO/DONE/DOING/LATER/NOW → GFM checkboxes. */
    public static function normalizeTasks(string $md, bool &$did = false): string
    {
        $did = false;
        $map = [
            'TODO' => '[ ]',
            'DOING' => '[ ]',
            'LATER' => '[ ]',
            'NOW'  => '[ ]',
            'DONE' => '[x]',
        ];
        $out = preg_replace_callback(
            '/^(\s*[-*+]\s+)(TODO|DOING|DONE|LATER|NOW)\b[: ]?/mi',
            function ($m) use ($map, &$did) {
                $did = true;
                return $m[1] . $map[$m[2]] . ' ';
            },
            $md
        );
        return $out ?? $md;
    }
}
