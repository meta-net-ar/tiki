<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Importer\Markdown\Scanner;

/**
 * Generic scanner interface for transforming a source archive into
 * a normalized list of wiki-import candidates pages.
 */
interface ScannerInterface
{
    /**
     * Scan a ZIP file and return normalized items and counts.
     *
     * @param string $zipPath Absolute path to the ZIP file to scan
     * @param array  $options See ZipScanner for supported keys
     * @return array {
     *   files: array<array{
     *     relative:string,
     *     type:string,                // 'page' | 'journal'
     *     page_name:string,           // normalized Tiki page name (with namespace if any)
     *     title:string,               // resolved title (front-matter/H1/filename)
     *     size_bytes:int,
     *     has_front_matter:bool,
     *     preview:string,             // first ~200 chars of cleaned body
     *     raw:string                  // raw markdown (as-is); consider removing in huge imports
     *   }>,
     *   counts: array{pages:int, journals:int, total:int}
     * }
     */
    public function scan(string $zipPath, array $options = []): array;
}
