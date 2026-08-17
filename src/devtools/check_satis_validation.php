<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

$composerJsonPath = __DIR__ . '/../../vendor_bundled/composer.json';
$satisJsonPath = __DIR__ . '/../config/satis.json';

// Function to read and decode JSON from a file
function readJsonFile($filePath)
{
    if (! file_exists($filePath)) {
        throw new Exception("File not found: {$filePath}");
    }

    $jsonContent = file_get_contents($filePath);
    $jsonData = json_decode($jsonContent, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Error decoding JSON from {$filePath}: " . json_last_error_msg());
    }

    return $jsonData;
}

// Read and decode JSON data from both files
$composerData = readJsonFile($composerJsonPath);
$satisData = readJsonFile($satisJsonPath);

// Extract package names from composer.json's require and require-dev sections
$composerPackages = array_keys($composerData['require'] ?? []); // Production dependencies
$composerDevPackages = array_keys($composerData['require-dev'] ?? []); // Development dependencies
$allComposerPackages = array_merge($composerPackages, $composerDevPackages);

// Filter out system packages (those starting with ext-)
$allComposerPackages = array_filter($allComposerPackages, function ($package) {
    return strncmp('ext-', $package, 4) !== 0;
});

// Extract package names from satis.json's require section
$satisPackages = array_keys($satisData['require'] ?? []);
$lowercaseSatisPackages = array_map('strtolower', $satisPackages);

// Check each composer package is in the satis require list
$missingPackages = [];
foreach ($allComposerPackages as $package) {
    $lowercasePackage = strtolower($package);
    if (! in_array($lowercasePackage, $lowercaseSatisPackages)) {
        $missingPackages[] = $package;
    }
}

if (empty($missingPackages)) {
    echo "Success: All packages from vendor_bundled/composer.json are present in src/config/satis.json." . PHP_EOL;
    exit(0);
} else {
    echo "Fail: The following packages from vendor_bundled/composer.json are missing in src/config/satis.json." . PHP_EOL;
    foreach ($missingPackages as $missingPackage) {
        echo "- {$missingPackage}\n";
    }
    echo "All composer dependencies should be cached in Satis (https://composer.tiki.org). Make sure you add them to src/config/satis.json in the master branch." . PHP_EOL;
    exit(1); // Exit with error code to indicate failure in CI pipeline
}
