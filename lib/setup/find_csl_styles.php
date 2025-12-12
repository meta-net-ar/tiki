<?php
/**
 * Helper script to search for CSL styles
 * Usage: php lib/setup/find_csl_styles.php [search_term]
 */

$searchTerm = $argv[1] ?? '';

$cslDir = 'storage/csl-styles/';

if (!is_dir($cslDir)) {
    echo "ERROR: Directory $cslDir not found\n";
    echo "Run: php lib/setup/update_csl_styles.php first\n";
    exit(1);
}

$files = glob($cslDir . '*.csl');

if (empty($searchTerm)) {
    echo "Usage: php lib/setup/find_csl_styles.php [search_term]\n\n";
    echo "Examples:\n";
    echo "  php lib/setup/find_csl_styles.php chicago\n";
    echo "  php lib/setup/find_csl_styles.php harvard\n";
    echo "  php lib/setup/find_csl_styles.php apa\n";
    echo "  php lib/setup/find_csl_styles.php medical\n\n";
    echo "Total styles available: " . count($files) . "\n";
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
echo "Found " . count($matches) . " matching styles\n";
echo "\nUsage in Tiki: {zoterobibliography style=" . ($matches[0] ?? 'style-name') . "}\n";