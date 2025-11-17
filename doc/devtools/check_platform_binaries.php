#!/usr/bin/env php
<?php

// This script performs a spot check for an unlikely platform-specific binary
// in package-lock.json. When the npm bug occurs, only the host platform's
// binaries are present, so checking for one that is never the host
// is a reliable way to detect the issue.

require __DIR__ . '/../../path_constants.php';

$packageToCheck = '"@parcel/watcher-android-arm64":';

echo "Checking that package-lock.json contains non-host platform binaries..." . PHP_EOL;

// Performance optimization: Open a file stream instead of loading a massive JSON into memory
$fileHandle = fopen(PRIMARY_PACKAGERLOCK_FILE_PATH, 'r');
$hasBinary = false;

if ($fileHandle) {
    while (($line = fgets($fileHandle)) !== false) {
        if (str_contains($line, $packageToCheck)) {
            $hasBinary = true;
            break; // Stop reading immediately upon finding a match
        }
    }
    fclose($fileHandle);
}

// Handle the validation result
if (! $hasBinary) {
    fwrite(STDERR, PHP_EOL);
    fwrite(STDERR, "❌ ERROR: The required platform-specific binary '$packageToCheck' is missing from package-lock.json." . PHP_EOL);
    fwrite(STDERR, "This indicates the lockfile may be incomplete." . PHP_EOL);
    fwrite(STDERR, "To fix this, please try regenerating package-lock.json by deleting it and running 'npm install' again." . PHP_EOL);
    exit(1);
}

echo "✅ Success: package-lock.json appears to be complete." . PHP_EOL;
exit(0);
