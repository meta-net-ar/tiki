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
    $routeLib = TikiLib::lib('custom_route');
    $tikipath = rtrim($routeLib->getTikiPath(''), '/');

    // construct new path with possible domain subdir
    global $tikidomainslash;
    $base = 'storage';
    if (! empty($tikidomainslash)) {
        $base .= '/' . rtrim($tikidomainslash, '/');
    }
    $absNew = $tikipath . '/' . $base . '/public/h5p';

    // create new dirs if not present
    if (! is_dir($absNew)) {
        @mkdir($absNew, 0775, true);
    }
    foreach (['cachedassets','content','exports','libraries','temp'] as $dir) {
        $sub = $absNew . '/' . $dir;
        if (! is_dir($sub)) {
            @mkdir($sub, 0775, true);
        }
    }

    $absOldBase = $tikipath . '/' . 'storage/public';

    // move old dirs to new location
    foreach (['cachedassets','content','exports','libraries','temp'] as $dir) {
        $old = $absOldBase . '/' . $dir;
        $new = $absNew . '/' . $dir;
        if (is_dir($old) && ! is_dir($new)) {
            @rename($old, $new);
        }
    }
}
