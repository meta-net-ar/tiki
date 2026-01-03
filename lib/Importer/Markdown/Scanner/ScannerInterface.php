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
    /**
     * Scan a path (directory or ZIP file) and return normalized items and counts.
     *
     * @param string $path Absolute path to the directory or ZIP file to scan
     * @param array  $options Supported options:
     *   - roots (string): comma-separated root directories to scan (default: '.')
     *   - recursive (bool): scan subdirectories (default: true)
     *   - max_depth (int): maximum depth to scan (0 = unlimited, default: 0)
     *   - exclude_globs (string): comma-separated glob patterns to exclude
     *   - title_strategy (string): fm_h1_filename | h1_fm_filename | filename_only (default: fm_h1_filename)
     *   - naming_mode (string): basename | prefix | suffix (default: basename)
     *   - dir_levels (int): number of directory levels to include in page name (default: 2)
     *   - separator (string): separator for page name parts (default: ' ')
     *   - namespace (string): optional namespace prefix for all pages
     *   - detect_journal (bool): detect and convert journal pages (default: true)
     *   - journal_lang (string): language for journal date parsing (en|fr, default: en)
     *   - journal_ns (string): namespace for journal pages (default: 'Journal')
     *
     * @return array {
     *   files: array<array{
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
     *   counts: array{pages:int, journals:int, total:int}
     * }
     */
    public function scan(string $path, array $options = []): array;
}
