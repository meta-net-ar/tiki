<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Importer\Markdown\Scanner;

use Tiki\Lib\Importer\Markdown\Parser\CommonMarkPurifier;

/**
 * MarkdownFileAnalyzer
 * Pure business logic for analyzing markdown files.
 * Extracts metadata, resolves titles, detects journals, builds page names.
 * Agnostic to file source (ZIP, filesystem, etc.)
 */
class MarkdownFileAnalyzer
{
    // ========== RESULT KEYS ==========
    public const RES_TYPE = 'type';
    public const RES_PAGE_NAME = 'page_name';
    public const RES_TITLE = 'title';
    public const RES_HAS_FM = 'has_front_matter';
    public const RES_PREVIEW = 'preview';

    // ========== FILE TYPES ==========
    public const FT_PAGE = 'page';
    public const FT_JOURNAL = 'journal';

    // ========== STRATEGIES ==========
    public const TITLE_FM_H1_FILENAME = 'fm_h1_filename';

    // ========== DEFAULTS ==========
    private const DEF_TITLE_STRATEGY = self::TITLE_FM_H1_FILENAME;
    private const DEF_DETECT_JOURNAL = true;
    private const DEF_JOURNAL_LANG = 'en';
    private const DEF_JOURNAL_NS = 'Journal';
    private const DEF_NAMING_MODE = 'basename';
    private const DEF_DIR_LEVELS = 2;
    private const DEF_SEPARATOR = ' ';
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
        $titleStrategy = $options[ScannerInterface::OPT_TITLE_STRATEGY] ?? self::DEF_TITLE_STRATEGY;
        $detectJournal = $options[ScannerInterface::OPT_DETECT_JOURNAL] ?? self::DEF_DETECT_JOURNAL;
        $journalLang   = $options[ScannerInterface::OPT_JOURNAL_LANG] ?? self::DEF_JOURNAL_LANG;
        $journalNs     = $options[ScannerInterface::OPT_JOURNAL_NS] ?? self::DEF_JOURNAL_NS;
        $namingMode    = $options[ScannerInterface::OPT_NAMING_MODE] ?? self::DEF_NAMING_MODE;
        $dirLevels     = $options[ScannerInterface::OPT_DIR_LEVELS] ?? self::DEF_DIR_LEVELS;
        $separator     = $options[ScannerInterface::OPT_SEPARATOR] ?? self::DEF_SEPARATOR;
        $namespace     = trim($options[ScannerInterface::OPT_NAMESPACE] ?? '');

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
        $type = self::FT_PAGE;
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
                $type = self::FT_JOURNAL;
                $pageName = ($namespace !== '' ? $namespace . $separator : '') . $journalNs . '-' . $iso;
            }
        }

        return [
            self::RES_TYPE => $type,
            self::RES_PAGE_NAME => $pageName,
            self::RES_TITLE => $resolvedTitle,
            self::RES_HAS_FM => $hadFM,
            self::RES_PREVIEW => mb_substr(trim($this->previewText($bodyNoFm)), 0, 200),
        ];
    }

    /** Remove YAML front-matter if present, delegating to the shared implementation. */
    private function removeFrontMatter(string $raw, bool &$hadFM = false, ?string &$fmTitle = null): string
    {
        [$body, $hadFM, $fmBlock] = CommonMarkPurifier::removeFrontMatter($raw);
        $fmTitle = null;
        if ($hadFM && preg_match('/^\s*title\s*:\s*(.+)$/mi', (string)$fmBlock, $tm)) {
            $fmTitle = trim($this->trimQuotes($tm[1]));
        }
        return $body;
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

        if ($t === '') {
            return false;
        }

        // Normalize separators and remove English ordinals.
        $t2 = strtr($t, ['_' => '-', '/' => '-', '.' => '-']);
        $t2 = preg_replace('~\b(\d{1,2})(st|nd|rd|th)\b~i', '$1', $t2);

        $formats = [
            '!Y-m-d',
            '!Y-n-j',
            '!Ymd',
            '!d-m-Y',
            '!j-n-Y',
            '!M j Y',
            '!M j, Y',
            '!F j Y',
            '!F j, Y',
            '!j M Y',
            '!j F Y',
        ];

        foreach ($formats as $format) {
            $dt = \DateTime::createFromFormat($format, $t2);
            if ($dt === false) {
                continue;
            }

            $errors = \DateTime::getLastErrors();
            if (($errors['warning_count'] ?? 0) > 0 || ($errors['error_count'] ?? 0) > 0) {
                continue;
            }

            $iso = $dt->format('Y-m-d');
            return true;
        }

        // Try localized parser when month/day names are language-specific.
        $locales = strtolower($lang) === 'fr' ? ['fr_FR', 'en_US'] : ['en_US', 'fr_FR'];
        $patterns = [\IntlDateFormatter::LONG, \IntlDateFormatter::MEDIUM, \IntlDateFormatter::SHORT];

        foreach ($locales as $locale) {
            foreach ($patterns as $pattern) {
                $formatter = new \IntlDateFormatter($locale, $pattern, \IntlDateFormatter::NONE);
                $formatter->setLenient(false);
                $pos = 0;
                $ts = $formatter->parse($t2, $pos);
                if ($ts === false || $pos !== strlen($t2)) {
                    continue;
                }

                $iso = gmdate('Y-m-d', (int) $ts);
                return true;
            }
        }

        return false;
    }
}
