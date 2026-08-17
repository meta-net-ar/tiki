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

// Load LineEnding\Converter class
include_once $dir . '/lib/core/LineEnding/Converter.php';
// Import the class from namespace
use Tiki\LineEnding\Converter;

$excludePattern = [
    // composer related folders
    $dir . '/' . TIKI_VENDOR_NONBUNDLED_PATH,
    $dir . '/' . TIKI_VENDOR_BUNDLED_TOPLEVEL_PATH,
    $dir . '/' . PUBLIC_GENERATED_PATH,

    // temp folder (generated files)
    $dir . '/' . TEMP_PATH,

    // bin directory (executables)
    $dir . '/bin',

    // libraries included in tiki, so taking it as is
    $dir . '/lib/openlayers/theme/default/style.tidy.css',
    $dir . '/lib/openlayers/theme/default/ie6-style.tidy.css',
    $dir . '/lib/openlayers/theme/default/google.tidy.css',
    $dir . '/lib/openlayers/theme/default/style.mobile.tidy.css',
    $dir . '/lib/vue/lib/ui-predicate-vue.css',
];

// Wildcard patterns - these can appear at multiple levels
$wildcardExcludes = [
    '/node_modules/',
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

$converter = new Converter();
$filesToCheck = [];

foreach ($iterator as $file) {
    $currentFile = $file;

    if ($file instanceof SplFileInfo) {
        $currentFile = $file->getPathname();
    }

    $fileInfo = pathinfo($currentFile);
    $excludeFile = (str_replace($excludePattern, '', $currentFile) != $currentFile);

    // Check wildcard patterns (e.g., node_modules at any level)
    if (! $excludeFile) {
        foreach ($wildcardExcludes as $pattern) {
            if (strpos($currentFile, $pattern) !== false) {
                $excludeFile = true;
                break;
            }
        }
    }

    if ($excludeFile === false) {
        if (isset($fileInfo['extension']) && in_array($fileInfo['extension'], $extensions)) {
            $filesToCheck[] = $currentFile;
        }
    }
}

// Use streaming detection for efficiency (no memory issues with large files)
$affectedFiles = $converter->fix($filesToCheck, true); // report-only mode

if (! empty($affectedFiles)) {
    echo color('Files that do not have unix style line endings:', 'yellow') . PHP_EOL;
    foreach ($affectedFiles as $file) {
        $message .= str_replace($dir . DIRECTORY_SEPARATOR, '', $file) . PHP_EOL;
    }
    info($message);
    exit(1);
} else {
    important('All files OK');
}
