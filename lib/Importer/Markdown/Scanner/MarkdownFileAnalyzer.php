<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Importer\Markdown\Scanner;

/**
 * MarkdownFileAnalyzer
 * Pure business logic for analyzing markdown files.
 * Extracts metadata, resolves titles, detects journals, builds page names.
 * Agnostic to file source (ZIP, filesystem, etc.)
 */
class MarkdownFileAnalyzer
{
    /**
     * Analyze a markdown file content and metadata.
     *
     * @param string $content Raw markdown content
     * @param string $relpath Relative path from source root
     * @param array  $options Analysis options
     * @return array Analyzed file metadata
     */
    public function analyze(string $content, string $relpath, array $options = []): array
    {
        $titleStrategy = $options['title_strategy'] ?? 'fm_h1_filename';
        $detectJournal = $options['detect_journal'] ?? true;
        $journalLang   = $options['journal_lang'] ?? 'en';
        $journalNs     = $options['journal_ns'] ?? 'Journal';
        $namingMode    = $options['naming_mode'] ?? 'basename';
        $dirLevels     = $options['dir_levels'] ?? 2;
        $separator     = $options['separator'] ?? ' ';
        $namespace     = trim($options['namespace'] ?? '');

        // Extract front-matter
        $hadFM = false;
        $bodyNoFm = $this->removeFrontMatter($content, $hadFM, $fmTitle);

        // Extract H1
        $h1Title = $this->extractH1($bodyNoFm);

        // Resolve title
        $fileBase = basename($relpath, '.' . pathinfo($relpath, PATHINFO_EXTENSION));
        $resolvedTitle = $this->resolveTitle($titleStrategy, $fmTitle, $h1Title, $fileBase);

        // Build page name
        $pageName = $this->buildPageName($relpath, $namingMode, $dirLevels, $separator, $namespace);

        // Detect journal
        $type = 'page';
        if ($detectJournal) {
            $iso = null;
            $baseDash  = strtr($fileBase, ['_' => '-', ' ' => '-', '.' => '-']);
            $baseSpace = strtr($fileBase, ['_' => ' ', '-' => ' ', '.' => ' ']);
            $looksLikeJournalDir = (bool) preg_match('~^journals?/~i', $relpath);

            if (
                $this->tryParseJournalDate($resolvedTitle, $iso, $journalLang) ||
                $this->tryParseJournalDate($baseDash, $iso, $journalLang)      ||
                $this->tryParseJournalDate($baseSpace, $iso, $journalLang)     ||
                ($looksLikeJournalDir && $this->tryParseJournalDate($fileBase, $iso, $journalLang))
            ) {
                $type = 'journal';
                $pageName = ($namespace !== '' ? $namespace . $separator : '') . $journalNs . '-' . $iso;
            }
        }

        return [
            'type'             => $type,
            'page_name'        => $pageName,
            'title'            => $resolvedTitle,
            'has_front_matter' => $hadFM,
            'preview'          => mb_substr(trim($this->previewText($bodyNoFm)), 0, 200),
        ];
    }

    /** Remove YAML front-matter if present. */
    private function removeFrontMatter(string $raw, bool &$hadFM = false, ?string &$fmTitle = null): string
    {
        $hadFM = false;
        $fmTitle = null;
        if (preg_match('/^---\s*\r?\n(.*?)\r?\n---\s*\r?\n/s', $raw, $m)) {
            $hadFM = true;
            $fm    = $m[1];
            if (preg_match('/^\s*title\s*:\s*(.+)$/mi', $fm, $tm)) {
                $fmTitle = trim($this->trimQuotes($tm[1]));
            }
            return (string)substr($raw, strlen($m[0]));
        }
        return $raw;
    }

    private function trimQuotes(string $s): string
    {
        $s = trim($s);
        if ((str_starts_with($s, '"') && str_ends_with($s, '"')) || (str_starts_with($s, "'") && str_ends_with($s, "'"))) {
            return substr($s, 1, -1);
        }
        return $s;
    }

    /** Extract first H1 or H2 heading. */
    private function extractH1(string $md): ?string
    {
        if (preg_match('/^\s*#\s+(.+?)\s*$/m', $md, $m)) {
            return trim($m[1]);
        }
        if (preg_match('/^\s*##\s+(.+?)\s*$/m', $md, $m)) {
            return trim($m[1]);
        }
        return null;
    }

    /** Resolve title from front-matter, H1, or filename. */
    private function resolveTitle(string $strategy, ?string $fm, ?string $h1, string $fileBase): string
    {
        $fileTitle = $this->humanizeFileBase($fileBase);
        return match ($strategy) {
            'h1_fm_filename'   => $h1 ?: ($fm ?: $fileTitle),
            'filename_only'    => $fileTitle,
            default            => $fm ?: ($h1 ?: $fileTitle), // fm_h1_filename
        };
    }

    private function humanizeFileBase(string $base): string
    {
        $s = preg_replace('/[_\-]+/', ' ', $base);
        return trim(preg_replace('/\s+/', ' ', ucwords($s)));
    }

    /** Build Tiki page name from relative path and naming options. */
    public function buildPageName(string $relpath, string $mode, int $n, string $sep, string $ns): string
    {
        [$dirs, $file] = $this->splitDirsFile($relpath);
        $base = pathinfo($file, PATHINFO_FILENAME);

        $parts = [];
        if ($mode === 'prefix') {
            $take = array_slice($dirs, -$n);
            $parts = array_merge($take, [$base]);
        } elseif ($mode === 'suffix') {
            $take = array_slice($dirs, 0, $n);
            $parts = array_merge([$base], $take);
        } else {
            $parts = [$base];
        }

        $name = $this->normalizeTikiName(implode($sep, array_filter($parts)));
        if ($ns !== '') {
            $name = $ns . $sep . $name;
        }
        return $name;
    }

    private function splitDirsFile(string $rel): array
    {
        $dir = trim(dirname($rel), '/.');
        $dirs = $dir === '' ? [] : explode('/', $dir);
        $file = basename($rel);
        return [$dirs, $file];
    }

    private function normalizeTikiName(string $s): string
    {
        $s = preg_replace('~[\\/]+~', ' ', $s);
        $s = preg_replace('~[^\\p{L}\\p{N}\\- ]+~u', ' ', $s);
        $s = preg_replace('~\\s+~', ' ', $s);
        return trim($s);
    }

    private function previewText(string $md): string
    {
        $t = preg_replace('/`{3}.*?`{3}/s', '', $md);
        $t = preg_replace('/`[^`]+`/', '', $t);
        $t = preg_replace('/!\[[^\]]*\]\([^)]+\)/', '', $t);
        $t = preg_replace('/\[[^\]]+\]\([^)]+\)/', '$1', $t);
        return strip_tags($t);
    }

    /**
     * Try to parse a human date title into ISO YYYY-MM-DD.
     * @param string $title
     * @param string|null $iso Output ISO date if detected
     * @param string $lang 'en'|'fr'
     * @return bool true if a date was detected
     */
    public function tryParseJournalDate(string $title, ?string &$iso = null, string $lang = 'en'): bool
    {
        $t = trim($title);

        // ISO-like: YYYY-MM-DD with various separators
        if (preg_match('~^(\d{4})[ \t/_\-.](\d{1,2})[ \t/_\-.](\d{1,2})$~', $t, $m)) {
            $iso = sprintf('%04d-%02d-%02d', (int)$m[1], (int)$m[2], (int)$m[3]);
            return true;
        }
        // Compact YYYYMMDD
        if (preg_match('~^(\d{4})(\d{2})(\d{2})$~', $t, $m)) {
            $iso = sprintf('%04d-%02d-%02d', (int)$m[1], (int)$m[2], (int)$m[3]);
            return true;
        }

        // Remove English ordinals
        $t2 = preg_replace('~\b(\d{1,2})(st|nd|rd|th)\b~i', '$1', $t);

        // Try IntlDateFormatter
        $locales = ['en_US', 'fr_FR'];
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

        // Fallback to strtotime
        $ts = strtotime($t2);
        if ($ts !== false) {
            $iso = date('Y-m-d', $ts);
            return true;
        }

        // European DD MM YYYY
        if (preg_match('~^(\d{1,2})[ \t./-](\d{1,2})[ \t./-](\d{4})$~', $t2, $m)) {
            $iso = sprintf('%04d-%02d-%02d', (int)$m[3], (int)$m[2], (int)$m[1]);
            return true;
        }

        return false;
    }
}
