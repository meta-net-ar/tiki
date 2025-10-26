<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
use Tiki\Installer\Installer;

/**
 * Move h5p assets to the new dir if it doesn't exist
 *
 * @param Installer $installer
 */
function upgrade_20190123_h5p_move_storage_assets_to_new_dir_tiki($installer)
{
    $newH5Pdir = STORAGE_PUBLIC_H5P_PATH;

    if (! is_dir($newH5Pdir)) {
        if (@mkdir($newH5Pdir, 0775, true)) {
            foreach (['cachedassets', 'content', 'exports','libraries','temp'] as $dir) {
                $old = STORAGE_PUBLIC_PATH . '/' . $dir;
                $new = $newH5Pdir . '/' . $dir;
                if (is_dir($old) && ! is_dir($new)) {
                    rename($old, $new);
                }
            }
        }
    }
}
