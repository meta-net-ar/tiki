<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Importer\Markdown\Scanner;

/**
 * Generic scanner interface for transforming a source into
 * a normalized list of wiki-import candidate pages.
 * Implementations can scan directories, ZIP files, or other sources.
 */
interface ScannerInterface
{
    // ========== SCAN OUTPUT KEYS ==========
    public const SCAN_FILES = 'files';
    public const SCAN_COUNTS = 'counts';

    // ========== COUNT KEYS ==========
    public const COUNT_PAGES = 'pages';
    public const COUNT_JOURNALS = 'journals';
    public const COUNT_TOTAL = 'total';

    // ========== OPTION KEYS ==========
    public const OPT_ROOTS = 'roots';
    public const OPT_RECURSIVE = 'recursive';
    public const OPT_MAX_DEPTH = 'max_depth';
    public const OPT_EXCLUDE_GLOBS = 'exclude_globs';
    public const OPT_TITLE_STRATEGY = 'title_strategy';
    public const OPT_NAMING_MODE = 'naming_mode';
    public const OPT_DIR_LEVELS = 'dir_levels';
    public const OPT_SEPARATOR = 'separator';
    public const OPT_NAMESPACE = 'namespace';
    public const OPT_DETECT_JOURNAL = 'detect_journal';
    public const OPT_JOURNAL_LANG = 'journal_lang';
    public const OPT_JOURNAL_NS = 'journal_ns';

    /**
     * Scan a path (directory or ZIP file) and return normalized items and counts.
     *
     * @param string $path Absolute path to the directory or ZIP file to scan
     * @param array  $options Supported options (use class constants):
     *   - OPT_ROOTS (string): comma-separated root directories to scan (default: '.')
     *   - OPT_RECURSIVE (bool): scan subdirectories (default: true)
     *   - OPT_MAX_DEPTH (int): maximum depth to scan (0 = unlimited, default: 0)
     *   - OPT_EXCLUDE_GLOBS (string): comma-separated glob patterns to exclude
     *   - OPT_TITLE_STRATEGY (string): fm_h1_filename | h1_fm_filename | filename_only (default: fm_h1_filename)
     *   - OPT_NAMING_MODE (string): basename | prefix | suffix (default: basename)
     *   - OPT_DIR_LEVELS (int): number of directory levels to include in page name (default: 2)
     *   - OPT_SEPARATOR (string): separator for page name parts (default: ' ')
     *   - OPT_NAMESPACE (string): optional namespace prefix for all pages
     *   - OPT_DETECT_JOURNAL (bool): detect and convert journal pages (default: true)
     *   - OPT_JOURNAL_LANG (string): language for journal date parsing (en|fr, default: en)
     *   - OPT_JOURNAL_NS (string): namespace for journal pages (default: 'Journal')
     *
     * @return array {
     *   SCAN_FILES: array<array{
     *     relative:string,
     *     relpath:string,
     *     type:string,                // 'page' | 'journal'
     *     page_name:string,           // normalized Tiki page name (with namespace if any)
     *     title:string,               // resolved title (front-matter/H1/filename)
     *     size_bytes:int,
     *     mtime_utc:int|null,
     *     has_front_matter:bool,
     *     preview:string,             // first ~200 chars of cleaned body
     *     raw:string                  // raw markdown (as-is)
     *   }>,
     *   SCAN_COUNTS: array{COUNT_PAGES:int, COUNT_JOURNALS:int, COUNT_TOTAL:int}
     * }
     */
    public function scan(string $path, array $options = []): array;
}
