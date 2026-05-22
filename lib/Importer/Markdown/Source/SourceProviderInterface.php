<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Importer\Markdown\Source;

/**
 * Contract for any source provider that can materialize files
 * in a local directory ready to be scanned/imported.
 */
interface SourceProviderInterface
{
    /**
     * Download / collect files and produce a local directory path.
     * For uploaded ZIPs, extract to temp directory.
     * For Git repos, clone/pull to working directory.
     * For filesystem paths, return the path directly.
     * MUST throw \RuntimeException on error.
     *
     * @return string Absolute path to the directory containing markdown files
     */
    public function fetchToTempDir(): string;
}
