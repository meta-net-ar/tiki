<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
use Tiki\Installer\Installer;

/**
 * Delete legacy serialized .infos files for newsletter attachments.
 *
 * These files were previously written using serialize() and read back with unserialize(),
 * which is a security risk (insecure deserialization). New files are written as JSON.
 * This migration removes legacy serialized .infos files and any non-array JSON metadata from tmp,
 * along with the sibling attachment file. Runtime code never calls unserialize() on these paths.
 *
 * @param Installer $installer
 */
function upgrade_20260416_delete_legacy_serialized_newsletter_infos_tiki($installer)
{
    global $prefs;

    $tmpDir = $prefs['tmpDir'] ?? 'temp';
    $pattern = $tmpDir . '/newsletterfile-*.infos';
    $files = glob($pattern);

    if (! is_array($files)) {
        return;
    }

    foreach ($files as $file) {
        $content = @file_get_contents($file);
        if ($content === false) {
            continue;
        }

        // Keep only JSON arrays (metadata shape). Legacy serialize(), invalid JSON, scalars → delete both files.
        $decoded = json_decode($content, true);
        if (! is_array($decoded)) {
            @unlink($file);
            // Also remove the associated attachment data file (same path without .infos)
            $dataFile = substr($file, 0, -6); // strip '.infos'
            if (file_exists($dataFile)) {
                @unlink($dataFile);
            }
        }
    }
}
