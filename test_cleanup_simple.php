<?php
/**
 * Simple test to verify CleanVendors functionality
 * Creates test files, runs composer install, and checks if cleanup works
 */

chdir(__DIR__);

echo "=== VENDOR CLEANUP TEST ===\n\n";

// Step 1: Find all vendor packages
echo "[1/4] Discovering vendor packages...\n";
$packages = glob('vendor_bundled/vendor/*/*', GLOB_ONLYDIR);
$packageCount = count($packages);
echo "Found $packageCount packages\n\n";

// Step 2: Create test marker files
echo "[2/4] Creating test marker files in all packages...\n";
$testFiles = [
    'TEST_CLEANUP_MARKER_1.txt',
    'TEST_CLEANUP_MARKER_2.txt',
];

$filesCreated = 0;
foreach ($packages as $package) {
    foreach ($testFiles as $file) {
        $path = $package . '/' . $file;
        if (!file_exists($path)) {
            @file_put_contents($path, 'TEST MARKER - SHOULD BE REMOVED');
            $filesCreated++;
        }
    }
}
echo "Created $filesCreated test marker files\n\n";

// Step 3: Count before cleanup
echo "[3/4] Counting test files before cleanup...\n";
$beforeCount = 0;
foreach ($packages as $package) {
    foreach ($testFiles as $file) {
        $path = $package . '/' . $file;
        if (file_exists($path)) {
            $beforeCount++;
        }
    }
}
echo "Test files before cleanup: $beforeCount\n";

// Step 4: Run composer install to trigger cleanup
echo "\n[4/4] Running composer install (this triggers the cleanup hook)...\n";
echo "Running: cd vendor_bundled && composer install\n";
echo "This may take a minute or two...\n\n";

$output = [];
$exitCode = 0;
$startTime = microtime(true);

// Run composer install in vendor_bundled directory
exec('cd vendor_bundled && composer install 2>&1', $output, $exitCode);

$elapsed = round(microtime(true) - $startTime, 2);

// Show relevant output lines
$foundCleaningMsg = false;
foreach ($output as $line) {
    if (stripos($line, 'cleaning') !== false || 
        stripos($line, 'removed') !== false ||
        stripos($line, 'loading') !== false ||
        stripos($line, 'error') !== false) {
        echo "  " . trim($line) . "\n";
        if (stripos($line, 'cleaning') !== false) {
            $foundCleaningMsg = true;
        }
    }
}

if (!$foundCleaningMsg && $exitCode !== 0) {
    echo "  Exit code: $exitCode\n";
    echo "  Last few lines of output:\n";
    foreach (array_slice($output, -3) as $line) {
        echo "    " . trim($line) . "\n";
    }
}

echo "\nComposer completed in {$elapsed}s\n\n";

// Step 5: Count after cleanup
echo "=== RESULTS ===\n";
$afterCount = 0;
foreach ($packages as $package) {
    foreach ($testFiles as $file) {
        $path = $package . '/' . $file;
        if (file_exists($path)) {
            $afterCount++;
        }
    }
}

$removed = $beforeCount - $afterCount;
$rate = $beforeCount > 0 ? round(100 * $removed / $beforeCount, 1) : 0;

echo "Test files before: $beforeCount\n";
echo "Test files after:  $afterCount\n";
echo "Test files removed: $removed\n";
echo "Success rate: $rate%\n\n";

if ($removed >= $beforeCount * 0.9) {
    echo "✓ CLEANUP WORKING: At least 90% of test files were removed\n";
} else if ($removed > 0) {
    echo "⚠ PARTIAL CLEANUP: Some test files were removed but not all\n";
} else {
    echo "✗ CLEANUP NOT WORKING: No test files were removed\n";
}

// Check for log files
echo "\n=== LOG FILES ===\n";
$logFile = 'temp/dev/composer_clean.log';
$jsonFile = 'temp/dev/composer_clean.json';

if (file_exists($logFile)) {
    echo "Log file found at: $logFile\n";
    $logContent = file_get_contents($logFile);
    $lines = explode("\n", $logContent);
    echo "Last few log lines:\n";
    foreach (array_slice(array_filter($lines), -5) as $line) {
        echo "  " . trim($line) . "\n";
    }
} else {
    echo "Log file not found at: $logFile\n";
}

if (file_exists($jsonFile)) {
    echo "\nJSON report found at: $jsonFile\n";
    $json = json_decode(file_get_contents($jsonFile), true);
    if ($json) {
        echo "  Files deleted: " . $json['deletedFiles'] . "\n";
        echo "  Size freed: " . $json['deletedSizeMb'] . " MB\n";
        echo "  Packages affected: " . $json['affectedDependencies'] . "\n";
    }
} else {
    echo "JSON report not found at: $jsonFile\n";
}

echo "\n=== TEST COMPLETE ===\n";
?>
