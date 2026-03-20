<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

if (PHP_SAPI !== 'cli') {
    die("This script must be run from the command line.\n");
}

require_once dirname(__DIR__, 2) . '/tiki-setup.php';

function failUpdateCslStyles($message, $exitCode = 1)
{
    fwrite(STDERR, $message . "\n");
    exit($exitCode);
}

function acquireUpdateCslStylesLock($lockFile)
{
    $handle = @fopen($lockFile, 'c+');
    if (! $handle) {
        failUpdateCslStyles('ERROR: Cannot open lock file: ' . $lockFile);
    }

    if (! flock($handle, LOCK_EX | LOCK_NB)) {
        $existingPid = trim(stream_get_contents($handle));
        $extra = $existingPid !== '' ? ' (PID ' . $existingPid . ')' : '';
        fclose($handle);
        failUpdateCslStyles('ERROR: update_csl_styles is already running' . $extra . '.');
    }

    ftruncate($handle, 0);
    rewind($handle);
    fwrite($handle, (string) getmypid());
    fflush($handle);

    return $handle;
}

function parseUpdateCslStylesOptions($argv)
{
    return [
        'debug' => in_array('--debug', $argv, true),
    ];
}

function debugUpdateCslStyles($debugEnabled, $message)
{
    if ($debugEnabled) {
        echo '[DEBUG] ' . $message . "\n";
    }
}

function responseHeaderValue($response, $headerName)
{
    $header = $response->getHeaders()->get($headerName);
    if (! $header) {
        return '';
    }

    if (method_exists($header, 'getFieldValue')) {
        return (string) $header->getFieldValue();
    }

    if (method_exists($header, 'toString')) {
        return (string) $header->toString();
    }

    return (string) $header;
}

function downloadCslArchive($tikilib, $zipUrls, $debugEnabled = false)
{
    if (! is_array($zipUrls)) {
        $zipUrls = [$zipUrls];
    }

    $strategies = [
        [
            'label' => 'default client',
            'options' => [],
            'headers' => [],
        ],
        [
            'label' => 'curl adapter',
            'options' => ['adapter' => 'Laminas\Http\Client\Adapter\Curl'],
            'headers' => [],
        ],
        [
            'label' => 'curl adapter (no compression)',
            'options' => ['adapter' => 'Laminas\Http\Client\Adapter\Curl'],
            'headers' => ['Accept-Encoding' => 'identity'],
        ],
        [
            'label' => 'default client (no compression)',
            'options' => [],
            'headers' => ['Accept-Encoding' => 'identity'],
        ],
    ];

    $errors = [];

    foreach ($zipUrls as $zipUrl) {
        foreach ($strategies as $strategy) {
            $adapter = $strategy['options']['adapter'] ?? 'default';
            debugUpdateCslStyles($debugEnabled, 'Attempting download: url=' . $zipUrl . ' adapter=' . $adapter . ' strategy=' . $strategy['label']);

            try {
                $client = $tikilib->get_http_client($zipUrl, $strategy['options']);
                if (! empty($strategy['headers'])) {
                    $client->setHeaders($strategy['headers']);
                }

                $response = $tikilib->http_perform_request($client);
                if (! $response->isSuccess()) {
                    debugUpdateCslStyles($debugEnabled, 'HTTP failure: status=' . $response->getStatusCode());
                    $errors[] = $zipUrl . ' [' . $strategy['label'] . ']: HTTP ' . $response->getStatusCode();
                    continue;
                }

                $body = $response->getBody();
                if ($body === '') {
                    debugUpdateCslStyles($debugEnabled, 'Failure: empty response body');
                    $errors[] = $zipUrl . ' [' . $strategy['label'] . ']: empty response body';
                    continue;
                }

                debugUpdateCslStyles(
                    $debugEnabled,
                    'Success: bytes=' . strlen($body)
                    . ' content-type=' . responseHeaderValue($response, 'Content-Type')
                    . ' content-length=' . responseHeaderValue($response, 'Content-Length')
                    . ' transfer-encoding=' . responseHeaderValue($response, 'Transfer-Encoding')
                    . ' content-encoding=' . responseHeaderValue($response, 'Content-Encoding')
                );

                return $body;
            } catch (Throwable $e) {
                debugUpdateCslStyles($debugEnabled, 'Exception: ' . get_class($e) . ': ' . $e->getMessage());
                $errors[] = $zipUrl . ' [' . $strategy['label'] . ']: ' . $e->getMessage();
            }
        }
    }

    throw new RuntimeException(implode(' | ', $errors));
}

if (! class_exists('ZipArchive')) {
    failUpdateCslStyles('ERROR: PHP ZipArchive extension is required.');
}

$options = parseUpdateCslStylesOptions($argv);
$debugEnabled = $options['debug'];

global $tikidomainslash;
$storageBaseDir = DEPRECATED_STORAGE_PATH . '/' . ($tikidomainslash ?? '');
$cslDir = $storageBaseDir . 'csl-styles/';
$zipUrls = [
    'https://github.com/citation-style-language/styles/archive/refs/heads/master.zip',
    'https://codeload.github.com/citation-style-language/styles/zip/refs/heads/master',
];
$zipFile = $cslDir . 'styles-master.zip';

if (! is_dir(TEMP_CACHE_PATH) && ! mkdir(TEMP_CACHE_PATH, 0755, true)) {
    failUpdateCslStyles('ERROR: Cannot create cache directory for lock file: ' . TEMP_CACHE_PATH);
}

$lockFile = TEMP_CACHE_PATH . '/update_csl_styles.lock';
$lockHandle = acquireUpdateCslStylesLock($lockFile);
register_shutdown_function(static function () use ($lockHandle) {
    if (is_resource($lockHandle)) {
        flock($lockHandle, LOCK_UN);
        fclose($lockHandle);
    }
});
debugUpdateCslStyles($debugEnabled, 'Acquired lock: ' . $lockFile . ' pid=' . getmypid());

echo "Updating CSL styles from GitHub...\n";
echo "Target directory: $cslDir\n";
debugUpdateCslStyles($debugEnabled, 'Using multitiki domain prefix: ' . ($tikidomainslash ?? '(none)'));

if (! is_dir($cslDir)) {
    if (! mkdir($cslDir, 0755, true)) {
        failUpdateCslStyles("ERROR: Cannot create directory $cslDir");
    }
    echo "Created directory: $cslDir\n";
}

$tikilib = TikiLib::lib('tiki');
if (! $tikilib) {
    failUpdateCslStyles('ERROR: Could not initialize Tiki library.');
}

echo "Downloading styles archive...\n";

try {
    $zipContent = downloadCslArchive($tikilib, $zipUrls, $debugEnabled);
} catch (Throwable $e) {
    failUpdateCslStyles('ERROR: Failed to download CSL styles: ' . $e->getMessage());
}

if (file_put_contents($zipFile, $zipContent) === false) {
    failUpdateCslStyles("ERROR: Cannot write downloaded archive to $zipFile");
}

echo 'Downloaded: ' . number_format(strlen($zipContent)) . " bytes\n";
echo "Extracting styles...\n";

$zip = new ZipArchive();
if ($zip->open($zipFile) !== true) {
    @unlink($zipFile);
    failUpdateCslStyles('ERROR: Failed to open downloaded ZIP archive.');
}
debugUpdateCslStyles($debugEnabled, 'ZIP entries total: ' . $zip->numFiles);

$extractedCount = 0;
for ($i = 0; $i < $zip->numFiles; $i++) {
    $filename = $zip->getNameIndex($i);
    if (! $filename || ! preg_match('/\.csl$/i', $filename)) {
        continue;
    }

    $basename = basename($filename);
    if ($basename === '' || $basename === '.' || $basename === '..') {
        continue;
    }

    $content = $zip->getFromIndex($i);
    if ($content === false) {
        continue;
    }

    if (file_put_contents($cslDir . $basename, $content) === false) {
        $zip->close();
        @unlink($zipFile);
        failUpdateCslStyles("ERROR: Cannot write extracted style file $basename");
    }

    $extractedCount++;
    if ($debugEnabled && $extractedCount % 2000 === 0) {
        debugUpdateCslStyles($debugEnabled, 'Extraction progress: ' . $extractedCount . ' files');
    }
}

$zip->close();
@unlink($zipFile);

if ($extractedCount === 0) {
    failUpdateCslStyles('ERROR: No CSL files were extracted from archive.');
}

echo "Extraction complete ($extractedCount files).\n";

echo "Building style index...\n";
$styles = [];
$files = glob($cslDir . '*.csl') ?: [];

foreach ($files as $file) {
    $basename = basename($file, '.csl');
    $styles[$basename] = ucwords(str_replace(['-', '_'], ' ', $basename));
}

asort($styles);
echo 'Found ' . count($styles) . " styles\n";
echo "\nDone! CSL styles updated successfully.\n";
echo 'Total styles available: ' . count($styles) . "\n";
