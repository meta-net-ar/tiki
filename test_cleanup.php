<?php
// Test script to verify CleanVendors is working
// Creates temporary test files in vendor packages and validates cleanup

chdir(__DIR__);

// Load Composer autoloader (includes Tiki namespace mapping)
require_once 'vendor_bundled/vendor/autoload.php';

echo "=== SETUP: Creating test files in vendor packages ===\n";

// Find all vendor packages
$packages = glob('vendor_bundled/vendor/*/*', GLOB_ONLYDIR);
echo "Found " . count($packages) . " vendor packages\n\n";

// Test files to create in each package (unique marker files)
$testFilesByType = [
    'file' => [
        'TEST_CLEANUP_MARKER_GIT.txt',
        'TEST_CLEANUP_MARKER_ENV.txt',
        'TEST_CLEANUP_MARKER_TESTS.txt',
        'TEST_CLEANUP_MARKER_DOCS.txt',
        'TEST_CLEANUP_MARKER_CHANGELOG.txt',
    ],
    'dir' => [
        'TEST_CLEANUP_DIR_GITHUB',
        'TEST_CLEANUP_DIR_GIT',
        'TEST_CLEANUP_DIR_HIDDEN',
    ]
];

$createdCount = 0;

// Create test files in packages
foreach ($packages as $package) {
    // Create test marker files
    foreach ($testFilesByType['file'] as $file) {
        $fullPath = $package . '/' . $file;
        if (!file_exists($fullPath)) {
            @file_put_contents($fullPath, 'TEST MARKER');
            $createdCount++;
        }
    }
    // Create test marker directories
    foreach ($testFilesByType['dir'] as $dir) {
        $fullPath = $package . '/' . $dir;
        if (!is_dir($fullPath)) {
            @mkdir($fullPath, 0777, true);
            @file_put_contents($fullPath . '/marker.txt', 'TEST');
            $createdCount++;
        }
    }
}

echo "✓ Created test marker files in $createdCount locations\n";

// Count before cleanup
echo "\n=== BEFORE CLEANUP ===\n";
$beforeCount = 0;
$beforeFiles = [];
foreach ($packages as $package) {
    foreach ($testFilesByType['file'] as $file) {
        $fullPath = $package . '/' . $file;
        if (file_exists($fullPath)) {
            $beforeCount++;
            $beforeFiles[] = $fullPath;
        }
    }
    foreach ($testFilesByType['dir'] as $dir) {
        $fullPath = $package . '/' . $dir;
        if (is_dir($fullPath)) {
            $beforeCount++;
            $beforeFiles[] = $fullPath;
        }
    }
}
echo "Test files/dirs existing: $beforeCount\n";

// Create a mock Event object to pass --log to the cleanup
echo "\n=== RUNNING CLEANUP ===\n";

// Run composer install which automatically triggers the post-install-cmd hooks
// The cleanup will run regardless, and we can check if files were removed
echo "Command: composer install (in vendor_bundled)\n\n";
$output = [];
$exitCode = 0;
exec('cd vendor_bundled && composer install 2>&1', $output, $exitCode);
foreach ($output as $line) {
    if (strpos($line, 'Cleaning vendor') !== false || 
        strpos($line, 'removed') !== false || 
        strpos($line, 'ERROR') !== false) {
        echo $line . "\n";
    }
}

// Count after cleanup
echo "\n=== AFTER CLEANUP ===\n";
$afterCount = 0;
$afterFiles = [];
foreach ($packages as $package) {
    foreach ($testFilesByType['file'] as $file) {
        $fullPath = $package . '/' . $file;
        if (file_exists($fullPath)) {
            $afterCount++;
            $afterFiles[] = $fullPath;
        }
    }
    foreach ($testFilesByType['dir'] as $dir) {
        $fullPath = $package . '/' . $dir;
        if (is_dir($fullPath)) {
            $afterCount++;
            $afterFiles[] = $fullPath;
        }
    }
}
echo "Test files/dirs remaining: $afterCount\n";
echo "Test files/dirs removed: " . ($beforeCount - $afterCount) . "\n";

if ($beforeCount > 0) {
    $removalRate = round(100 * ($beforeCount - $afterCount) / $beforeCount, 1);
    echo "Removal success rate: $removalRate%\n";
}

// Show logs
echo "\n=== CLEANUP LOGS ===\n";
$logFile = 'temp/dev/composer_clean.log';
if (file_exists($logFile)) {
    echo file_get_contents($logFile);
} else {
    echo "Log file not found at: $logFile\n";
}

$jsonFile = 'temp/dev/composer_clean.json';
if (file_exists($jsonFile)) {
    echo "\n=== JSON REPORT ===\n";
    $json = json_decode(file_get_contents($jsonFile), true);
    if ($json) {
        echo "Files deleted: " . $json['deletedFiles'] . "\n";
        echo "Size freed: " . $json['deletedSizeMb'] . " MB\n";
        echo "Packages affected: " . $json['affectedDependencies'] . "\n";
        if (!empty($json['affectedPackages'])) {
            echo "Sample affected packages:\n";
            foreach (array_slice($json['affectedPackages'], 0, 5) as $pkg) {
                echo "  - $pkg\n";
            }
            if (count($json['affectedPackages']) > 5) {
                echo "  ... and " . (count($json['affectedPackages']) - 5) . " more\n";
            }
        }
    }
} else {
    echo "JSON file not found at: $jsonFile\n";
}

echo "\n=== TEST COMPLETE ===\n";
?>
