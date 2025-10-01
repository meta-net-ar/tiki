<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
if (PHP_SAPI !== 'cli') {
    die('Only available through command-line.');
}

require_once __DIR__ . '/../../path_constants.php';
require dirname(__FILE__) . '/vcscommons.php';

$dir = realpath(__DIR__ . '/../../');

$excludePattern = [
    // composer related folders
    $dir . '/' . TIKI_VENDOR_NONBUNDLED_PATH,
    $dir . '/' . TIKI_VENDOR_BUNDLED_TOPLEVEL_PATH,

    // temp folder (generated files)
    $dir . '/' . TEMP_PATH,

    // folders where node modules can be installed
    $dir . '/lib/vue-mf/duration-picker/node_modules',
    $dir . '/lib/vue-mf/kanban/node_modules',
    $dir . '/lib/vue-mf/styleguide/node_modules',

    // libraries included in tiki, so taking it as is
    $dir . '/lib/openlayers/theme/default/style.tidy.css',
    $dir . '/lib/openlayers/theme/default/ie6-style.tidy.css',
    $dir . '/lib/openlayers/theme/default/google.tidy.css',
    $dir . '/lib/openlayers/theme/default/style.mobile.tidy.css',
    $dir . '/lib/vue/lib/ui-predicate-vue.css',
];

$extensions = [
    'php',
    'tpl',
    'css',
    'less',
    'htaccess',
    'config'
];

$message = '';
$paramList = $_SERVER['argv'] ?? [];

$iterator = [];
foreach ($paramList as $paramFile) {
    $file = $dir . $paramFile;
    if (file_exists($file) && basename(__FILE__) != basename($file)) {
        $iterator[] = $file;
    }
}

if (empty($iterator)) {
    $dirIterator = new RecursiveDirectoryIterator($dir);
    $iterator = new RecursiveIteratorIterator($dirIterator);
}

foreach ($iterator as $file) {
    $currentFile = $file;

    if ($file instanceof SplFileInfo) {
        $currentFile = $file->getPathname();
    }

    $fileInfo = pathinfo($currentFile);
    $excludeFile = (str_replace($excludePattern, '', $currentFile) != $currentFile);

    if ($excludeFile === false) {
        if (isset($fileInfo['extension']) && in_array($fileInfo['extension'], $extensions)) {
            $data = file($currentFile);
            if (! count($data)) {
                // empty file
                continue;
            }
            $lastLine = $data[count($data) - 1];
            $lineEnding = substr($lastLine, -1);

            if ($lineEnding !== "\n") {
                $message .= str_replace($dir . DIRECTORY_SEPARATOR, '', $currentFile) . PHP_EOL;
            }
        }
    }
}

if (! empty($message)) {
    echo color('Files that do not end with a unix style end of line:', 'yellow') . PHP_EOL;
    info($message);
    exit(1);
} else {
    important('All files OK');
}
