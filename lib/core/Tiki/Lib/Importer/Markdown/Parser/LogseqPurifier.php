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
    // ========== LOGSEQ-SPECIFIC CONTEXT KEYS ==========
    public const CTX_JOURNAL_NS = 'journal_ns';
    public const CTX_JOURNAL_LANG = 'journal_lang';
    public const CTX_RUNTIME_LOGSEQ = 'runtime_logseq';
    public const CTX_BLOCKREF_MODE = 'blockref_mode';

    // ========== BLOCKREF MODES ==========
    public const MODE_INLINE = 'inline';
    public const MODE_CODE = 'code';
    public const MODE_KEEP = 'keep';

    // ========== LOGSEQ-SPECIFIC DEFAULTS ==========
    public const DEF_JOURNAL_NS = 'Journal';
    public const DEF_JOURNAL_LANG = 'en';
    public const DEF_RUNTIME_LOGSEQ = false;
    public const DEF_BLOCKREF_MODE = self::MODE_INLINE;

    public static function id(): string
    {
        return 'logseq';
    }

    /**
     * Apply Logseq-specific markdown normalization.
     *
     * Supported context keys:
     * - strip_front_matter (bool, default: true): remove YAML front-matter block when present.
     * - journal_ns (string, default: 'Journal'): namespace prefix used for detected journal links.
     * - journal_lang ('en'|'fr', default: 'en'): locale order used to parse journal-like [[wikilink]] dates.
     * - runtime_logseq (bool, default: false): keep Logseq wikilinks/blockrefs for runtime handling.
     * - blockref_mode ('inline'|'code'|'keep', default: 'inline'): output strategy for ((block-id)).
     *
     * @param string $raw Raw markdown source content.
     * @param array{
     *   strip_front_matter?: bool,
     *   journal_ns?: string,
     *   journal_lang?: string,
     *   runtime_logseq?: bool,
     *   blockref_mode?: string
     * } $context Optional purifier options.
     * @return array{markdown: string, notes: string[]} Purified markdown and informational notes.
     */
    public function purify(string $raw, array $context = []): array
    {
        $opts = array_merge([
            self::CTX_JOURNAL_NS => self::DEF_JOURNAL_NS,
            self::CTX_JOURNAL_LANG => self::DEF_JOURNAL_LANG,
            self::CTX_STRIP_FM => true,
            self::CTX_RUNTIME_LOGSEQ => self::DEF_RUNTIME_LOGSEQ,      // if true keep [[...]]/((...))
            self::CTX_BLOCKREF_MODE => self::DEF_BLOCKREF_MODE,   // inline | code | keep
        ], $context);

        $notes = [];
        $out = $raw;
        $pcount = 0; // properties
        $tcount = 0; // tasks
        $brcount = 0; // block refs
        $wlcount = 0; // wikilinks
        $rmLogs = false; // removed logbook entries
        $journalLinks = 0;

        if ($opts[self::CTX_STRIP_FM]) {
            [$out, $had] = CommonMarkPurifier::removeFrontMatter($out);
            if ($had) {
                $notes[] = tra('Front matter stripped');
            }
        }

        // 0) Restore Logseq's sometimes-missing root block marker
        $rootFixCount = 0;
        $out = $this->fixOrphanedListRoots($out, $rootFixCount);
        if ($rootFixCount) {
            $notes[] = tr('Restored %0 missing list marker(s)', $rootFixCount);
        }

        // 1) Properties "key:: value" to "**key:** value"
        $out = $this->convertProperties($out, $pcount);
        if ($pcount) {
            $notes[] = tr('Converted %0 Logseq properties', $pcount);
        }

        // 2) Task markers to GFM checkboxes
        $out = GfmPurifier::normalizeTasks($out, $tcount);
        if ($tcount) {
            $notes[] = tra('Task markers normalized to GFM');
        }

        // 3) Logbook blocks and CLOCK lines
        $out = self::stripLogbookBlocks($out, $rmLogs);
        if ($rmLogs) {
            $notes[] = tra('Removed Logseq :LOGBOOK: entries');
        }

        // 4) ((block-id)) handling
        if ($opts[self::CTX_BLOCKREF_MODE] !== self::MODE_KEEP) {
            $out = $this->convertBlockRefs($out, $opts[self::CTX_BLOCKREF_MODE], $brcount);
            if ($brcount) {
                $notes[] = tra('Block references converted');
            }
        }

        // 5) [[wikilinks]]
        if (! $opts[self::CTX_RUNTIME_LOGSEQ]) {
            $out = $this->convertWikilinksToMarkdown($out, $opts[self::CTX_JOURNAL_NS], $opts[self::CTX_JOURNAL_LANG], $wlcount, $journalLinks);
            if ($wlcount) {
                $notes[] = tr('Converted %0 wikilinks', $wlcount);
            }
            if ($journalLinks) {
                $notes[] = tr('%0 journal links recognized', $journalLinks);
            }
        } else {
            $notes[] = tra('Kept [[wikilinks]] for runtime extension');
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
    private function convertWikilinksToMarkdown(string $md, string $journalNs, string $journalLang, int &$count = 0, int &$journalLinks = 0): string
    {
        $count = 0;
        $journalLinks = 0;

        $result = preg_replace_callback('~\[\[([^\]]+)\]\]~u', function ($m) use ($journalNs, $journalLang, &$count, &$journalLinks) {
            $count++;
            $label = trim($m[1]);

            if ($this->parseJournalDate($label, $iso, $journalLang)) {
                $journalLinks++;
                $page = $journalNs . '-' . $iso;
                $url  = 'tiki-index.php?page=' . rawurlencode($page);
                return '[' . $label . '](' . $url . ')';
            }

            // Fallback: keep as text link with no explicit URL
            return '[' . $label . ']';
        }, $md);

        return $result ?? $md;
    }

    /**
     * Try to interpret a Logseq journal title into ISO date (YYYY-MM-DD).
     * Handles: "Month Xth, Year", "X Month Year", "X Month Year", "Year_Month_X", etc.
     * Supports multiple languages via IntlDateFormatter.
     */
    private function parseJournalDate(string $title, ?string &$iso = null, string $lang = self::DEF_JOURNAL_LANG): bool
    {
        $t = trim($title);

        // Already ISO-ish (YYYY[-_./]MM[-_./]DD)
        if (preg_match('~^(\d{4})[\/_\-.](\d{1,2})[\/_\-.](\d{1,2})$~', $t, $m)) {
            $iso = sprintf('%04d-%02d-%02d', (int)$m[1], (int)$m[2], (int)$m[3]);
            return true;
        }

        // Strip English ordinals: 6th to 6
        $t2 = preg_replace('~\b(\d{1,2})(st|nd|rd|th)\b~i', '$1', $t);

        // Try parsing with IntlDateFormatter for multiple locales and patterns.
        // Locale order follows the configured journal_lang, same as
        // MarkdownFileAnalyzer::tryParseJournalDate(), so an in-body [[date]]
        // wikilink resolves consistently with the journal page name itself.
        $locales = strtolower($lang) === 'fr' ? ['fr_FR', 'en_US'] : ['en_US', 'fr_FR'];
        $patterns = [
            \IntlDateFormatter::LONG,
            \IntlDateFormatter::MEDIUM,
            \IntlDateFormatter::SHORT,
        ];

        foreach ($locales as $locale) {
            foreach ($patterns as $pattern) {
                $formatter = new \IntlDateFormatter(
                    $locale,
                    $pattern,
                    \IntlDateFormatter::NONE,
                    'UTC'
                );
                $ts = $formatter->parse($t2);
                if ($ts !== false) {
                    $iso = date('Y-m-d', $ts);
                    return true;
                }
            }
        }

        // Fallback to strtotime for other formats
        $ts = strtotime($t2);
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

    /**
     * Logseq sometimes exports a page's leading block (often a plain heading
     * used as the page title/subject) without its own "- " marker, even though
     * its child blocks are still tab/space-indented as if nested under it.
     * Left as-is, CommonMark has no open list item to attach the indented
     * children to, so it reads them as an indented code block instead of a
     * nested list, silently mangling the rest of the page.
     *
     * Restore the missing marker whenever a non-indented, non-list line is
     * immediately followed by an indented list line.
     */
    private function fixOrphanedListRoots(string $md, int &$count = 0): string
    {
        $count = 0;
        $lines = explode("\n", $md);
        $last = count($lines) - 1;

        for ($i = 0; $i < $last; $i++) {
            $line = $lines[$i];

            if (trim($line) === '') {
                continue;
            }
            // Already indented, or already a list item: nothing to fix.
            if (preg_match('/^[ \t]/', $line) || preg_match('/^(?:[-*+]|\d+[.)])\s/', $line)) {
                continue;
            }

            if (preg_match('/^[ \t]+(?:[-*+]|\d+[.)])\s/', $lines[$i + 1])) {
                $lines[$i] = '- ' . $line;
                $count++;
            }
        }

        return implode("\n", $lines);
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
