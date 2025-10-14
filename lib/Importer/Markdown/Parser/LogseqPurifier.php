<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Lib\Importer\Markdown\Parser;

/**
 * LogseqPurifier
 * - Optionally removes YAML front-matter
 * - Converts [[Page]] to Tiki wiki links (Page) OR leaves them if runtime mode is enabled
 * - Detects journal-like titles & rewrites to ((<JournalNs>-YYYY-MM-DD))
 * - Converts ((block-id)) to plain inline reference (configurable strategy)
 * - Normalizes Logseq tasks to GFM checkboxes
 * - Converts "key:: value" property lines to "**key:** value"
 */
class LogseqPurifier implements MarkdownPurifierInterface
{
    public static function id(): string
    {
        return 'logseq';
    }

    public function purify(string $raw, array $context = []): array
    {
        $opts = array_merge([
            'journal_ns'         => 'Journal',
            'strip_front_matter' => true,
            'runtime_logseq'     => false,      // if true keep [[...]]/((...))
            'blockref_mode'      => 'inline',   // inline | code | keep
        ], $context);

        $notes = [];
        $out = $raw;
        $pcount = 0; // properties
        $tcount = 0; // tasks
        $brcount = 0; // block refs
        $wlcount = 0; // wikilinks
        $rmLogs = false; // removed logbook entries
        $journalLinks = 0;

        if ($opts['strip_front_matter']) {
            [$out, $had] = CommonMarkPurifier::removeFrontMatter($out);
            if ($had) {
                $notes[] = 'Front matter stripped';
            }
        }

        // 1) Properties "key:: value" to "**key:** value"
        $out = $this->convertProperties($out, $pcount);
        if ($pcount) {
            $notes[] = "Converted $pcount Logseq properties";
        }

        // 2) Task markers to GFM checkboxes
        $out = GfmPurifier::normalizeTasks($out, $tcount);
        if ($tcount) {
            $notes[] = 'Task markers normalized to GFM';
        }

        // 3) Logbook blocks and CLOCK lines
        $out = self::stripLogbookBlocks($out, $rmLogs);
        if ($rmLogs) {
            $notes[] = 'Removed Logseq :LOGBOOK: entries';
        }

        // 4) ((block-id)) handling
        if ($opts['blockref_mode'] !== 'keep') {
            $out = $this->convertBlockRefs($out, $opts['blockref_mode'], $brcount);
            if ($brcount) {
                $notes[] = 'Block references converted';
            }
        }

        // 5) [[wikilinks]]
        if (! $opts['runtime_logseq']) {
            $out = $this->convertWikilinksToMarkdown($out, $opts['journal_ns'], $wlcount, $journalLinks);
            if ($wlcount) {
                $notes[] = "Converted $wlcount wikilinks";
            }
            if ($journalLinks) {
                $notes[] = "$journalLinks journal links recognized";
            }
        } else {
            $notes[] = 'Kept [[wikilinks]] for runtime extension';
        }

        // TODO: Handle other Logseq-specific syntax?

        return ['markdown' => $out, 'notes' => $notes];
    }

    /**
     * [[...]] Markdown links.
     * - If label looks like a journal date (any EN/FR format), link to tiki-index.php?page=<JournalNs>-YYYY-MM-DD.
     * - Otherwise, emit [Label] (no URL), which Tiki’s Markdown renderer can still resolve/skin.
     *
     * @return string
     */
    private function convertWikilinksToMarkdown(string $md, string $journalNs, int &$count = 0, int &$journalLinks = 0): string
    {
        $count = 0;
        $journalLinks = 0;

        return preg_replace_callback('~\[\[([^\]]+)\]\]~u', function ($m) use ($journalNs, &$count, &$journalLinks) {
            $count++;
            $label = trim($m[1]);

            if ($this->parseJournalDate($label, $iso)) {
                $journalLinks++;
                $page = $journalNs . '-' . $iso;
                $url  = 'tiki-index.php?page=' . rawurlencode($page);
                return '[' . $label . '](' . $url . ')';
            }

            // Fallback: keep as text link with no explicit URL
            return '[' . $label . ']';
        }, $md);
    }

    /**
     * Try to interpret a Logseq journal title into ISO date (YYYY-MM-DD).
     * Handles: "Month Xth, Year", "X Month Year", "X Month Year", "Year_Month_X", etc.
     * Do we need to handle other languages?
     */
    private function parseJournalDate(string $title, ?string &$iso = null): bool
    {
        $t = trim($title);

        // Already ISO-ish (YYYY[-_./]MM[-_./]DD)
        if (preg_match('~^(\d{4})[\/_\-.](\d{1,2})[\/_\-.](\d{1,2})$~', $t, $m)) {
            $iso = sprintf('%04d-%02d-%02d', (int)$m[1], (int)$m[2], (int)$m[3]);
            return true;
        }

        // Strip English ordinals: 6th to 6
        $t2 = preg_replace('~\b(\d{1,2})(st|nd|rd|th)\b~i', '$1', $t);

        // FR to EN month normalization for strtotime()
        $fr = [
            'janv.' => 'jan',
            'févr.' => 'feb',
            'fevr.' => 'feb',
            'mars'  => 'mar',
            'avr.'  => 'apr',
            'mai'   => 'may',
            'juin'  => 'jun',
            'juil.' => 'jul',
            'août'  => 'aug',
            'aout' => 'aug',
            'sept.' => 'sep',
            'oct.'  => 'oct',
            'nov.'  => 'nov',
            'déc.'  => 'dec',
            'dec.'  => 'dec',
            'janvier' => 'january',
            'février' => 'february',
            'fevrier' => 'february',
            'avril'   => 'april',
            'juillet' => 'july',
            'septembre' => 'september',
            'octobre' => 'october',
            'novembre' => 'november',
            'décembre' => 'december',
            'decembre' => 'december',
        ];
        $t2_low = $t2;
        foreach ($fr as $k => $v) {
            $t2_low = preg_replace('~' . preg_quote($k, '~') . '~iu', $v, $t2_low);
        }

        $ts = strtotime($t2_low);
        if ($ts !== false) {
            $iso = date('Y-m-d', $ts);
            return true;
        }

        // Explicit D M Y like "06 10 2025" to European preference (d/m/Y)
        if (preg_match('~^(\d{1,2})[ \t./-](\d{1,2})[ \t./-](\d{4})$~', $t2, $m)) {
            $iso = sprintf('%04d-%02d-%02d', (int)$m[3], (int)$m[2], (int)$m[1]);
            return true;
        }

        return false;
    }

    /** key:: value (line-start) > **key:** value */
    private function convertProperties(string $md, int &$count = 0): string
    {
        $count = 0;
        $out = preg_replace_callback('/^(?:\s*)([^\s:#][^:\n#]*?)::\s*(.+)$/m', function ($m) use (&$count) {
            $count++;
            $k = trim($m[1]);
            $v = rtrim($m[2]);
            return '**' . $k . ':** ' . $v;
        }, $md);
        return $out ?? $md;
    }

    /** ((block-id)) choice of inline representation */
    private function convertBlockRefs(string $md, string $mode, bool &$did = false): string
    {
        $did = false;
        return preg_replace_callback('/\(\(([a-zA-Z0-9_-]{6,})\)\)/', function ($m) use ($mode, &$did) {
            $did = true;
            switch ($mode) {
                case 'code':
                    return '`#' . $m[1] . '`';
                case 'inline':
                    return '<span class="ls-block-ref">#' . htmlspecialchars($m[1]) . '</span>';
                default:
                    return $m[0];
            }
        }, $md) ?? $md;
    }

    /** Strip Logseq time tracking drawers (:LOGBOOK: ... :END:) and standalone CLOCK: lines. */
    private static function stripLogbookBlocks(string $md, bool &$removed = false): string
    {
        $removed = false;
        $before  = $md;

        // 1) Remove :LOGBOOK: ... :END: blocks (multi-line)
        $md = preg_replace(
            '/^[ \t]*:LOGBOOK:\s*\R.*?^[ \t]*:END:\s*\R?/mis',
            '',
            $md
        );

        // 2) Remove standalone CLOCK: lines
        $md = preg_replace(
            '/^[ \t]*CLOCK:\s*\[[^\]]+\]\s*--\s*\[[^\]]+\].*$\R?/mi',
            '',
            $md
        );

        if ($md !== $before) {
            $removed = true;
        }

        return $md ?? $before;
    }
}
