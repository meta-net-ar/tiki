<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Importer\Markdown\Source;

/**
 * Contract for any source provider that can materialize a temp ZIP file
 * containing markdown files ready to be scanned/imported.
 */
interface SourceProviderInterface
{
    /**
     * Download / collect files and produce a temporary ZIP file path.
     * MUST throw \RuntimeException on error.
     *
     * @return string Absolute path to the temporary .zip
     */
    public function fetchToTempZip(): string;
}
