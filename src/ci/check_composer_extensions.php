<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

$composerJsonFile = __DIR__ . '/../../vendor_bundled/composer.json';
$composerLockFile = __DIR__ . '/../../vendor_bundled/composer.lock';

/**
 * List of core PHP extensions that are expected to be available by default.
 * These should NOT trigger an error if found in composer.lock.
 */
$baseExtensions = [
    "ext-iconv" => true,
    "ext-openssl" => true,
    "ext-curl" => true,
    "ext-dom" => true,
    "ext-libxml" => true,
    "ext-simplexml" => true,
    "ext-json" => true,
    "ext-fileinfo" => true,
    "ext-gd" => true,
    "ext-mbstring" => true,
    "ext-session" => true,
    "ext-ctype" => true,
    "ext-intl" => true,
    "ext-hash" => true,
    "ext-sodium" => true,
    "ext-xml" => true,
    "ext-filter" => true,
    "ext-pcre" => true,
    "ext-date" => true,
    "ext-spl" => true,
    "ext-xmlreader" => true,
    "ext-xmlwriter" => true,
    "ext-zlib" => true,
    "ext-bcmath" => true,
    "ext-reflection" => true,
];

/**
 * Reads and decodes a JSON file.
 *
 * @param string $filePath
 * @return array
 * @throws Exception
 */
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

// get allowed extensions from composer.json (config->platform) and base extensions
$composerJsonData = readJsonFile($composerJsonFile);
if (isset($composerJsonData['config']['platform'])) {
    foreach ($composerJsonData['config']['platform'] as $key => $value) {
        if (strncmp($key, 'ext-', 4) === 0) {
            $baseExtensions[$key] = true;
        }
    }
}

// Extract required extensions from composer.lock
$composerLockData = readJsonFile($composerLockFile);
$requiredExtensions = [];
foreach ($composerLockData['packages'] as $package) {
    foreach ($package['require'] ?? [] as $require => $version) {
        if (strncmp($require, "ext-", 4) === 0 && ! ($baseExtensions[$require] ?? false)) {
            $requiredExtensions[$require] = true;
        }
    }
}

$unexpectedExtensions = array_keys($requiredExtensions);

if (! empty($unexpectedExtensions)) {
    echo PHP_EOL;
    echo "Error: We have detected the following additional PHP extensions being needed:" . PHP_EOL;

    foreach ($unexpectedExtensions as $ext) {
        echo "  - $ext" . PHP_EOL;
    }

    echo PHP_EOL;

    echo 'Taking a dependency on new PHP extensions should be carefully analyzed.' . PHP_EOL;
    echo '  - Users updating their Tiki instance may encounter errors if their server does not have the required extensions.' . PHP_EOL;
    echo '  - New PHP extension dependencies should, in principle, only be added in the master branch.' . PHP_EOL;
    echo PHP_EOL;

    echo 'Next Steps for Handling PHP Extensions in Tiki:' . PHP_EOL;

    echo 'If this extension is required for a specific optional functionality (e.g., LDAP, SOAP, Image processing):' . PHP_EOL;
    echo '  - Add an "ext-*" override in "composer.json".' . PHP_EOL;
    echo '  - Ensure your module validates at runtime whether the extension is available before using it.' . PHP_EOL;
    echo PHP_EOL;

    echo 'If this extension is required for core Tiki functionality:' . PHP_EOL;
    echo '  - Make sure it makes sense to add a new base php module dependency.' . PHP_EOL;
    echo '  - Add it to $baseExtensions in src/ci/check_composer_extensions.php' . PHP_EOL;
    echo '  - Ensure that it is available in CI/CD images before merging.' . PHP_EOL;

    echo 'In all cases, if you are introducing a new PHP extension dependency:' . PHP_EOL;
    echo '  - Update "tiki-check.php" to validate the extension and provide meaningful guidance to users.' . PHP_EOL;
    echo PHP_EOL;

    exit(1);
}

echo 'Success: No unexpected PHP extensions found in composer.lock' . PHP_EOL;
exit(0);
