<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Importer\Markdown\Scanner;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * DirectoryScanner
 * Scans a filesystem directory for markdown files.
 * Uses MarkdownFileAnalyzer for business logic.
 */
class DirectoryScanner implements ScannerInterface
{
    private MarkdownFileAnalyzer $analyzer;

    public function __construct()
    {
        $this->analyzer = new MarkdownFileAnalyzer();
    }

    /**
     * Scan a directory and return normalized items.
     *
      * @param string $path Absolute path to directory or ZIP file
     * @param array  $options Scan options
     * @return array {files: array, counts: array}
     */
    public function scan(string $path, array $options = []): array
    {
        // Handle ZIP files (extract to temp dir first)
        if (is_file($path) && strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'zip') {
            return $this->scanZip($path, $options);
        }

        if (! is_dir($path)) {
            return [self::SCAN_FILES => [], self::SCAN_COUNTS => [self::COUNT_PAGES => 0, self::COUNT_JOURNALS => 0, self::COUNT_TOTAL => 0]];
        }

        return $this->scanDirectory($path, $options);
    }

    /**
     * Scan a directory on filesystem.
     */
    private function scanDirectory(string $dirPath, array $options): array
    {
        $out = [self::SCAN_FILES => [], self::SCAN_COUNTS => [self::COUNT_PAGES => 0, self::COUNT_JOURNALS => 0, self::COUNT_TOTAL => 0]];

        $roots = $this->listOpt($options[self::OPT_ROOTS] ?? '.');
        $recursive = (bool)($options[self::OPT_RECURSIVE] ?? true);
        $maxDepth = max(0, (int)($options[self::OPT_MAX_DEPTH] ?? 0));
        $excludeGlobs = $this->listOpt($options[self::OPT_EXCLUDE_GLOBS] ?? '');

        $iterator = $recursive
            ? new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dirPath, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            )
            : new RecursiveDirectoryIterator($dirPath, RecursiveDirectoryIterator::SKIP_DOTS);

        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $fullPath = $file->getPathname();
            $relPath = $this->makeRelative($fullPath, $dirPath);

            // Filter by extension
            if (! $this->hasMdExt(strtolower($relPath))) {
                continue;
            }

            // Roots filter
            if (! $this->matchesRoots($relPath, $roots)) {
                continue;
            }

            // Depth filter
            if (! $recursive && $this->depthFromRoots($relPath, $roots) > 0) {
                continue;
            }
            if ($maxDepth > 0 && $this->depthFromRoots($relPath, $roots) > $maxDepth) {
                continue;
            }

            // Exclude globs
            if ($this->isExcluded($relPath, $excludeGlobs)) {
                continue;
            }

            // Read file content
            if (! is_readable($fullPath)) {
                continue;
            }

            $content = file_get_contents($fullPath);
            if ($content === false) {
                continue;
            }

            // Validate UTF-8 encoding (skip binary/corrupted files)
            if (! $this->isValidUtf8($content)) {
                continue;
            }

            // Analyze
            $analyzed = $this->analyzer->analyze($content, $relPath, $options);

            $mtime = filemtime($fullPath);

            $out[self::SCAN_FILES][] = [
                'relative'         => $relPath,
                'relpath'          => $this->normRelpath($relPath),
                'type'             => $analyzed[MarkdownFileAnalyzer::RES_TYPE],
                'page_name'        => $analyzed[MarkdownFileAnalyzer::RES_PAGE_NAME],
                'title'            => $analyzed[MarkdownFileAnalyzer::RES_TITLE],
                'size_bytes'       => strlen($content),
                'mtime_utc'        => $mtime !== false ? $mtime : null,
                'has_front_matter' => $analyzed[MarkdownFileAnalyzer::RES_HAS_FM],
                'preview'          => $analyzed[MarkdownFileAnalyzer::RES_PREVIEW],
                'raw'              => $content,
            ];

            $out[self::SCAN_COUNTS][self::COUNT_TOTAL]++;
            if ($analyzed[MarkdownFileAnalyzer::RES_TYPE] === MarkdownFileAnalyzer::FT_JOURNAL) {
                $out[self::SCAN_COUNTS][self::COUNT_JOURNALS]++;
            } else {
                $out[self::SCAN_COUNTS][self::COUNT_PAGES]++;
            }
        }

        return $out;
    }

    /**
     * Scan a ZIP file by extracting it to a temp directory, then scan the extracted directory.
     */
    private function scanZip(string $zipPath, array $options): array
    {
        if (! class_exists(\ZipArchive::class)) {
            throw new \RuntimeException('ZipArchive extension required to scan ZIP files.');
        }

        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return [self::SCAN_FILES => [], self::SCAN_COUNTS => [self::COUNT_PAGES => 0, self::COUNT_JOURNALS => 0, self::COUNT_TOTAL => 0]];
        }

        // Extract to temp directory
        $tempDir = sys_get_temp_dir() . '/mdimp_extract_' . uniqid();
        if (! mkdir($tempDir, 0755, true) && ! is_dir($tempDir)) {
            $zip->close();
            return [self::SCAN_FILES => [], self::SCAN_COUNTS => [self::COUNT_PAGES => 0, self::COUNT_JOURNALS => 0, self::COUNT_TOTAL => 0]];
        }

        if (! $zip->extractTo($tempDir)) {
            $zip->close();
            return [self::SCAN_FILES => [], self::SCAN_COUNTS => [self::COUNT_PAGES => 0, self::COUNT_JOURNALS => 0, self::COUNT_TOTAL => 0]];
        }
        $zip->close();

        try {
            // Check if ZIP has a single top-level directory wrapping everything
            $actualScanDir = $this->detectAndUnwrapTopFolder($tempDir);
            $result = $this->scanDirectory($actualScanDir, $options);
        } finally {
            // Cleanup temp directory
            $this->rrmdir($tempDir);
        }

        return $result;
    }

    /**
     * Detect if extracted ZIP has a single top-level folder and return its path.
     * Many ZIP files wrap content in a folder named after the ZIP.
     * E.g., Archive.zip contains Archive/file1.md instead of file1.md directly.
     */
    private function detectAndUnwrapTopFolder(string $dir): string
    {
        if (! is_dir($dir)) {
            return $dir;
        }

        $items = scandir($dir);
        if ($items === false) {
            return $dir;
        }

        // Filter out . and ..
        $realItems = array_filter($items, fn($i) => $i !== '.' && $i !== '..');

        // If there's exactly one item and it's a directory, use it as the base
        if (count($realItems) === 1) {
            $single = reset($realItems);
            $singlePath = $dir . '/' . $single;
            if (is_dir($singlePath)) {
                return $singlePath;
            }
        }

        // Otherwise, use the original directory
        return $dir;
    }

    /** Recursive directory removal. */
    private function rrmdir(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($it as $file) {
            if ($file->isDir()) {
                if (! rmdir($file->getPathname())) {
                    trigger_error(tra('Failed to remove directory: %0', $file->getPathname()), E_USER_WARNING);
                }
            } else {
                if (! unlink($file->getPathname())) {
                    trigger_error(tra('Failed to remove file: %0', $file->getPathname()), E_USER_WARNING);
                }
            }
        }
        if (! rmdir($dir)) {
            trigger_error(tra('Failed to remove directory: %0', $dir), E_USER_WARNING);
        }
    }

    private function makeRelative(string $fullPath, string $basePath): string
    {
        // Normalize paths (handle Windows backslashes)
        $fullPath = str_replace('\\', '/', $fullPath);
        $basePath = str_replace('\\', '/', $basePath);
        $base = rtrim($basePath, '/') . '/';
        if (str_starts_with($fullPath, $base)) {
            $rel = substr($fullPath, strlen($base));
            // Ensure forward slashes
            return str_replace('\\', '/', $rel);
        }
        return basename($fullPath);
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

    /**
     * Validate that content is valid UTF-8 text (not binary data).
     */
    private function isValidUtf8(string $content): bool
    {
        // Empty content is valid
        if ($content === '') {
            return true;
        }

        // Check for null bytes (strong indicator of binary data)
        if (str_contains($content, "\0")) {
            return false;
        }

        // Validate UTF-8 encoding
        return mb_check_encoding($content, 'UTF-8');
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

    private function normRelpath(string $p): string
    {
        $p = str_replace('\\', '/', $p);
        $p = preg_replace('~^(\./)+~', '', $p);
        return ltrim($p, '/');
    }
}
