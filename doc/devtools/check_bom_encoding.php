<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
if (PHP_SAPI !== 'cli') {
    die('Only available through command-line.');
}

require dirname(__FILE__) . '/vcscommons.php';
require dirname(__DIR__) . '/../lib/core/BOMChecker/Scanner.php';

$dir = realpath(__DIR__ . '/../../') ;

$excludeFolders = [
    $dir . '/vendor',
    $dir . '/vendor_bundled',
    $dir . '/temp',
    $dir . '/.git',
];

$extensions = [
    'php',
    'tpl',
    'sql',
    'css',
    'less',
    'js',
    'htaccess',
    'config',
    'xml'
];

$paramList = $_SERVER['argv'] ?? [];
$listFiles = [];
foreach ($paramList as $paramFile) {
    $file = $dir . '/' . ltrim($paramFile, '/');

    if (file_exists($file) && basename(__FILE__) != basename($file)) {
        $listFiles[] = $file;
    }
}

$BOMScanner = new BOMChecker_Scanner($dir, $extensions, $excludeFolders, $listFiles);
$BOMFiles = $BOMScanner->scan();
$totalFilesScanned = $BOMScanner->getScannedFiles();

echo PHP_EOL;
info($totalFilesScanned . ' files scanned...');

if ($BOMScanner->bomFilesFound()) {
    foreach ($BOMScanner->getBomFilesByType() as $type => $listBOMFiles) {
        if (! count($listBOMFiles)) {
            continue;
        }
        echo PHP_EOL;
        echo color('=> Found ' . $type . ' in ' . count($listBOMFiles) . ' files:', 'red') . PHP_EOL . PHP_EOL;
        foreach ($listBOMFiles as $files) {
            echo color($files, 'red') . PHP_EOL;
        }
    }
} else {
    echo PHP_EOL;
    important('No problem found in the files.');
}

echo PHP_EOL;
exit($BOMScanner->bomFilesFound() ? 1 : 0);
