<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

if (PHP_SAPI !== 'cli') {
    die("This script must be run from the command line.\n");
}

require_once dirname(__DIR__, 2) . '/tiki-setup.php';

$debugEnabled = in_array('--debug', $argv, true);

$searchTerm = '';
foreach (array_slice($argv, 1) as $arg) {
    if ($arg !== '--debug') {
        $searchTerm = trim($arg);
        break;
    }
}

global $tikidomainslash;
$storageBaseDir = DEPRECATED_STORAGE_PATH . '/' . ($tikidomainslash ?? '');
$cslDir = $storageBaseDir . 'csl-styles/';

if ($debugEnabled) {
    echo '[DEBUG] storageBaseDir=' . $storageBaseDir . "\n";
    echo '[DEBUG] cslDir=' . $cslDir . "\n";
}

if (! is_dir($cslDir)) {
    fwrite(STDERR, "ERROR: Directory $cslDir not found\n");
    fwrite(STDERR, "Run: php lib/setup/update_csl_styles.php first\n");
    exit(1);
}

$files = glob($cslDir . '*.csl') ?: [];

if ($searchTerm === '') {
    echo "Usage: php lib/setup/find_csl_styles.php [search_term]\n\n";
    echo "Examples:\n";
    echo "  php lib/setup/find_csl_styles.php chicago\n";
    echo "  php lib/setup/find_csl_styles.php harvard\n";
    echo "  php lib/setup/find_csl_styles.php apa\n";
    echo "  php lib/setup/find_csl_styles.php medical\n\n";
    echo 'Total styles available: ' . count($files) . "\n";
    exit(0);
}

echo "Searching for: $searchTerm\n\n";
echo "Matching styles:\n";
echo "================\n\n";

$matches = [];
foreach ($files as $file) {
    $basename = basename($file, '.csl');
    if (stripos($basename, $searchTerm) !== false) {
        $matches[] = $basename;
    }
}

sort($matches);

foreach ($matches as $match) {
    echo $match . "\n";
}

echo "\n================\n";
echo 'Found ' . count($matches) . " matching styles\n";
echo "\nUsage in Tiki: {zoterobibliography style=" . ($matches[0] ?? 'style-name') . "}\n";
