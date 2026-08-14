<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
require_once('tiki-setup.php');

/*
 * This script convert templates with the old preferences $truc to $pref.truc by getting the currently existing prefs keys.
 * Use with caution !
 * Copy it to the root of you're tiki, and run it with:
 * php convert_templates_prefs-2.0.php
 */


/* customize this to the directory you want to convert */
$dirtoscan = 'templates';

$src = [];
$dst = [];

foreach (array_keys($prefs) as $v) {
    $src[] = '$' . $v;
    $dst[] = '$prefs.' . $v;
}

try {
    $iterator = new FilesystemIterator($dirtoscan, FilesystemIterator::SKIP_DOTS);
} catch (Exception $e) {
    die("Could not open directory: $dirtoscan\n");
}

foreach ($iterator as $fileInfo) {
    $filename = $fileInfo->getFilename();

    if ($fileInfo->isFile() && str_ends_with($filename, '.tpl')) {
        echo "$filename... ";

        $path = $fileInfo->getPathname();
        $content_src = file_get_contents($path);
        $content_dst = str_replace($src, $dst, $content_src);

        if ($content_dst != $content_src) {
            file_put_contents($path, $content_dst);
            echo " modified\n";
        } else {
            echo " no\n";
        }
    }
}
