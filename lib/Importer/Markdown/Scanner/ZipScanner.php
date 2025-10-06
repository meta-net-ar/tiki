<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Importer\Markdown\Scanner;

use ZipArchive;

/**
 * ZipScanner
 *
 * Full-featured scanner that:
 *  - filters entries by roots / depth / exclude globs;
 *  - extracts front-matter and first H1;
 *  - resolves a title via strategy: fm_h1_filename | h1_fm_filename | filename_only;
 *  - normalizes Tiki page names with naming_mode (basename|prefix|suffix), dir_levels and separator, and optional namespace;
 *  - detects "journal" pages from human dates (en/fr) and maps to Journal-YYYY-MM-DD (Specific to LogSeq style journals) : We need to implement a more generic mapping later.
 */
class ZipScanner implements ScannerInterface
{
    public function scan(string $zipPath, array $options = []): array
    {
        $out = ['files' => [], 'counts' => ['pages' => 0, 'journals' => 0, 'total' => 0]];

        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return $out;
        }

        // Options (defaults aligned with your UI)
        $roots = $this->listOpt($options['roots'] ?? '.');
        $recursive = (bool)($options['recursive'] ?? true);
        $maxDepth = max(0, (int)($options['max_depth'] ?? 0)); // 0 = unlimited
        $excludeGlobs = $this->listOpt($options['exclude_globs'] ?? '');
        $titleStrategy = (string)($options['title_strategy'] ?? 'fm_h1_filename');
        $namingMode = (string)($options['naming_mode'] ?? 'basename'); // basename|prefix|suffix
        $dirLevels = max(0, (int)($options['dir_levels'] ?? 2));
        $separator = (string)($options['separator'] ?? ' ');
        $namespace = trim((string)($options['namespace'] ?? ''));
        $detectJournal = (bool)($options['detect_journal'] ?? true);
        $journalLang = (string)($options['journal_lang'] ?? 'en'); // 'en'|'fr'
        $journalNs = trim((string)($options['journal_ns'] ?? 'Journal'));

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $st = $zip->statIndex($i);
            if (! $st || substr($st['name'], -1) === '/') {
                continue; // directories
            }

            $full = $st['name'];
            $rel  = $this->stripTopFolder($full);
            $lower = strtolower($rel);
            if (! ($this->hasMdExt($lower))) {
                continue;
            }

            // roots/depth filters
            if (! $this->matchesRoots($rel, $roots)) {
                continue;
            }
            if (! $recursive && $this->depthFromRoots($rel, $roots) > 0) {
                continue; // only root level
            }
            if ($maxDepth > 0 && $this->depthFromRoots($rel, $roots) > $maxDepth) {
                continue;
            }

            // excludes
            if ($this->isExcluded($rel, $excludeGlobs)) {
                continue;
            }

            $raw = $zip->getFromIndex($i);
            if ($raw === false) {
                continue;
            }

            $hadFM = false;
            $bodyNoFm = $this->removeFrontMatter($raw, $hadFM, $fmTitle);

            $h1Title = $this->extractH1($bodyNoFm);

            $fileBase = basename($rel, '.' . pathinfo($rel, PATHINFO_EXTENSION));
            $resolvedTitle = $this->resolveTitle($titleStrategy, $fmTitle, $h1Title, $fileBase);

            // detect journal from title, if enabled
            $type = 'page';
            $pageName = $this->buildPageName($rel, $namingMode, $dirLevels, $separator, $namespace);

            if ($detectJournal) {
                $iso = null;

                $fileBase = basename($rel, '.' . pathinfo($rel, PATHINFO_EXTENSION));
                // Prepare alternatives from base name
                $baseAsIs   = $fileBase;
                $baseDash   = strtr($fileBase, ['_' => '-', ' ' => '-', '.' => '-']); // 2025_09_13 -> 2025-09-13
                $baseSpace  = strtr($fileBase, ['_' => ' ', '-' => ' ', '.' => ' ']); // 2025_09_13 -> 2025 09 13

                $looksLikeJournalDir = (bool) preg_match('~^journals?/~i', $rel);

                if (
                    $this->tryParseJournalDate($resolvedTitle, $iso, $journalLang) ||
                    $this->tryParseJournalDate($baseDash, $iso, $journalLang)     ||
                    $this->tryParseJournalDate($baseSpace, $iso, $journalLang)    ||
                    ($looksLikeJournalDir && $this->tryParseJournalDate($baseAsIs, $iso, $journalLang))
                ) {
                    $type = 'journal';
                    $pageName = ($namespace !== '' ? $namespace . $separator : '') . $journalNs . '-' . $iso;
                }
            }

            $out['files'][] = [
                'relative'         => $rel,
                'type'             => $type,
                'page_name'        => $pageName,
                'title'            => $resolvedTitle,
                'size_bytes'       => (int)($st['size'] ?? strlen($raw)),
                'has_front_matter' => $hadFM,
                'preview'          => mb_substr(trim($this->previewText($bodyNoFm)), 0, 200),
                'raw'              => $raw,
            ];

            $out['counts']['total']++;
            if ($type === 'journal') {
                $out['counts']['journals']++;
            } else {
                $out['counts']['pages']++;
            }
        }

        $zip->close();
        return $out;
    }

    private function listOpt(string $s): array
    {
        $out = array_filter(array_map('trim', preg_split('/[;,\r\n]+/', $s)));
        return array_values(array_unique($out));
    }

    private function hasMdExt(string $lower): bool
    {
        return str_ends_with($lower, '.md') || str_ends_with($lower, '.markdown');
    }

    private function stripTopFolder(string $path): string
    {
        $parts = explode('/', $path, 2);
        return count($parts) >= 2 ? $parts[1] : $path;
    }

    private function matchesRoots(string $rel, array $roots): bool
    {
        if (! $roots || in_array('.', $roots, true)) {
            return true;
        }
        foreach ($roots as $r) {
            $r = ltrim($r, './');
            if ($r === '' || $rel === $r || str_starts_with($rel, $r . '/')) {
                return true;
            }
        }
        return false;
    }

    private function depthFromRoots(string $rel, array $roots): int
    {
        // Count slashes AFTER the matching root prefix
        $cand = [$rel];
        foreach ($roots as $r) {
            $r = ltrim($r, './');
            if ($r !== '' && str_starts_with($rel, $r . '/')) {
                $cand[] = substr($rel, strlen($r) + 1);
            }
        }
        $min = PHP_INT_MAX;
        foreach ($cand as $p) {
            $min = min($min, substr_count($p, '/'));
        }
        return $min === PHP_INT_MAX ? substr_count($rel, '/') : $min;
    }

    private function isExcluded(string $rel, array $globs): bool
    {
        foreach ($globs as $g) {
            if ($g !== '' && fnmatch($g, $rel, FNM_PATHNAME | FNM_CASEFOLD)) {
                return true;
            }
        }
        return false;
    }

    private function removeFrontMatter(string $raw, bool &$hadFM = false, ?string &$fmTitle = null): string
    {
        $hadFM = false;
        $fmTitle = null;
        // YAML front-matter at file start: ---\n ... \n---\n
        if (preg_match('/^---\s*\r?\n(.*?)\r?\n---\s*\r?\n/s', $raw, $m)) {
            $hadFM = true;
            $fm    = $m[1];
            // naive title extraction (title: "...")
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

    private function extractH1(string $md): ?string
    {
        // Match first ATX H1..H6, prefer H1/H2
        if (preg_match('/^\s*#\s+(.+?)\s*$/m', $md, $m)) {
            return trim($m[1]);
        }
        if (preg_match('/^\s*##\s+(.+?)\s*$/m', $md, $m)) {
            return trim($m[1]);
        }
        return null;
    }

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
        // Title case light
        return trim(preg_replace('/\s+/', ' ', ucwords($s)));
    }

    private function buildPageName(string $rel, string $mode, int $n, string $sep, string $ns): string
    {
        [$dirs, $file] = $this->splitDirsFile($rel);
        $base = pathinfo($file, PATHINFO_FILENAME);

        $parts = [];
        if ($mode === 'prefix') {
            $take = array_slice($dirs, -$n); // N parent folders from left? Using last N to be closest context
            $parts = array_merge($take, [$base]);
        } elseif ($mode === 'suffix') {
            $take = array_slice($dirs, 0, $n); // N child folders from root
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
        // Keep letters/digits/space/hyphen; collapse others to space; no slashes
        $s = preg_replace('~[\\/]+~', ' ', $s);
        $s = preg_replace('~[^\\p{L}\\p{N}\\- ]+~u', ' ', $s);
        $s = preg_replace('~\\s+~', ' ', $s);
        return trim($s);
    }

    private function previewText(string $md): string
    {
        // Remove code fences/links quickly for preview legibility
        $t = preg_replace('/`{3}.*?`{3}/s', '', $md);
        $t = preg_replace('/`[^`]+`/', '', $t);
        $t = preg_replace('/!\[[^\]]*\]\([^)]+\)/', '', $t); // images
        $t = preg_replace('/\[[^\]]+\]\([^)]+\)/', '$1', $t); // links -> label
        return strip_tags($t);
    }

    /**
     * Try to parse a human date title into ISO YYYY-MM-DD (en/fr)
     * ######### Specific to LogSeq style journals for now. We need to implement a more generic mapping later. #########
     * ######### Do we want to support other languages than en/fr?  #########
     * @param string $title
     * @param string|null $iso output ISO date if detected
     * @param string $lang 'en'|'fr' (for month names)
     * @return bool true if a date was detected
     */
    private function tryParseJournalDate(string $title, ?string &$iso = null, string $lang = 'en'): bool
    {
        $t = trim($title);

        // 1) ISO-like with many separators: Y + [space/_/.-] + m + [space/_/.-] + d
        if (preg_match('~^(\d{4})[ \t/_\-.](\d{1,2})[ \t/_\-.](\d{1,2})$~', $t, $m)) {
            $iso = sprintf('%04d-%02d-%02d', (int)$m[1], (int)$m[2], (int)$m[3]);
            return true;
        }
        // 1b) Compact YYYYMMDD
        if (preg_match('~^(\d{4})(\d{2})(\d{2})$~', $t, $m)) {
            $iso = sprintf('%04d-%02d-%02d', (int)$m[1], (int)$m[2], (int)$m[3]);
            return true;
        }

        // 2) Remove English ordinals (1st, 2nd, 3rd, 4th…)
        $t2 = preg_replace('~\b(\d{1,2})(st|nd|rd|th)\b~i', '$1', $t);

        // 3) FR → EN month normalization (for strtotime)
        $fr = [
            'janv.' => 'jan',
            'févr.' => 'feb',
            'fevr.' => 'feb',
            'mars' => 'mar',
            'avr.' => 'apr',
            'mai' => 'may',
            'juin' => 'jun',
            'juil.' => 'jul',
            'août' => 'aug',
            'aout' => 'aug',
            'sept.' => 'sep',
            'oct.' => 'oct',
            'nov.' => 'nov',
            'déc.' => 'dec',
            'dec.' => 'dec',
            'janvier' => 'january',
            'février' => 'february',
            'fevrier' => 'february',
            'avril' => 'april',
            'juillet' => 'july',
            'septembre' => 'september',
            'octobre' => 'october',
            'novembre' => 'november',
            'décembre' => 'december',
            'decembre' => 'december',
        ];
        $t2low = $t2;
        foreach ($fr as $k => $v) {
            $t2low = preg_replace('~' . preg_quote($k, '~') . '~iu', $v, $t2low);
        }

        // 4) Try strtotime directly
        $ts = strtotime($t2low);
        if ($ts !== false) {
            $iso = date('Y-m-d', $ts);
            return true;
        }

        // 5) Normalize separators to '-' and try explicit patterns (d-m-Y)
        $norm = preg_replace('~[ \t/_\.]+~', '-', $t2low);
        if (preg_match('~^(\d{4})-(\d{1,2})-(\d{1,2})$~', $norm, $m)) {
            $iso = sprintf('%04d-%02d-%02d', (int)$m[1], (int)$m[2], (int)$m[3]);
            return true;
        }
        if (preg_match('~^(\d{1,2})-(\d{1,2})-(\d{4})$~', $norm, $m)) { // assume d-m-Y
            $iso = sprintf('%04d-%02d-%02d', (int)$m[3], (int)$m[2], (int)$m[1]);
            return true;
        }

        return false;
    }
}
