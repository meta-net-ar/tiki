<?php
/**
 * Download and update CSL styles from GitHub repository
 * Run from command line: php lib/setup/update_csl_styles.php
 */

require_once('tiki-setup.php');

echo "Updating CSL styles from GitHub...\n";

$cslDir = 'storage/csl-styles/';

// Create directory if it doesn't exist
if (!is_dir($cslDir)) {
    mkdir($cslDir, 0755, true);
    echo "Created directory: $cslDir\n";
}

// Download the styles repository as ZIP
$zipUrl = 'https://github.com/citation-style-language/styles/archive/refs/heads/master.zip';
$zipFile = $cslDir . 'styles-master.zip';

echo "Downloading styles from GitHub...\n";
$zipContent = file_get_contents($zipUrl);

if ($zipContent === false) {
    die("ERROR: Failed to download CSL styles from GitHub\n");
}

file_put_contents($zipFile, $zipContent);
echo "Downloaded: " . number_format(strlen($zipContent)) . " bytes\n";

// Extract ZIP
$zip = new ZipArchive;
if ($zip->open($zipFile) === true) {
    echo "Extracting styles...\n";
    
    // Extract only .csl files
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $filename = $zip->getNameIndex($i);
        
        if (preg_match('/\.csl$/', $filename)) {
            $basename = basename($filename);
            $content = $zip->getFromIndex($i);
            file_put_contents($cslDir . $basename, $content);
        }
    }
    
    $zip->close();
    echo "Extraction complete!\n";
} else {
    die("ERROR: Failed to open ZIP file\n");
}

// Clean up
unlink($zipFile);
if (is_dir($cslDir . 'styles-master')) {
    system("rm -rf " . escapeshellarg($cslDir . 'styles-master'));
}

// Scan and build style list
echo "Building style list...\n";
$styles = [];
$files = glob($cslDir . '*.csl');

foreach ($files as $file) {
    $basename = basename($file, '.csl');
    $displayName = ucwords(str_replace(['-', '_'], ' ', $basename));
    $styles[$basename] = $displayName;
}

asort($styles);
echo "Found " . count($styles) . " styles\n";

// Store in Tiki preference
global $prefs;
$tikilib = TikiLib::lib('tiki');

$styleList = json_encode($styles);
$tikilib->set_preference('zotero_available_csl_styles', $styleList);

echo "Saved style list to preferences\n";
echo "\nDone! CSL styles updated successfully.\n";
echo "Total styles available: " . count($styles) . "\n";