<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Zotero;

use RuntimeException;
use Throwable;
use ZipArchive;

class CslStyleManager
{
    private const DEFAULT_CSL_STYLES_ARCHIVE_URLS = [
        'https://github.com/citation-style-language/styles/archive/refs/heads/master.zip',
        'https://codeload.github.com/citation-style-language/styles/zip/refs/heads/master',
    ];

    public function getStorageBaseDir(): string
    {
        global $tikidomainslash;

        return DEPRECATED_STORAGE_PATH . '/' . ($tikidomainslash ?? '');
    }

    public function getCslDir(): string
    {
        return $this->getStorageBaseDir() . 'csl-styles/';
    }

    public function listStyles(): array
    {
        $styles = [];
        $files = glob($this->getCslDir() . '*.csl') ?: [];

        foreach ($files as $file) {
            $styles[] = basename($file, '.csl');
        }

        sort($styles);

        return $styles;
    }

    public function searchStyles(string $searchTerm): array
    {
        return array_values(array_filter($this->listStyles(), static function (string $style) use ($searchTerm): bool {
            return stripos($style, $searchTerm) !== false;
        }));
    }

    public function resolveStylePath(string $styleName): string
    {
        $styleName = preg_replace('/\.csl$/i', '', trim($styleName));
        if ($styleName === '' || preg_match('/[^a-zA-Z0-9._-]/', $styleName)) {
            throw new RuntimeException('Invalid CSL style name.');
        }

        $stylePath = $this->resolveFilePath($styleName . '.csl', ['storage/csl-styles/', $this->getCslDir()]);
        if ($stylePath === null) {
            throw new RuntimeException('CSL style not found: ' . $styleName);
        }

        return $stylePath;
    }

    public function fileFingerprint(string $path): string
    {
        $resolvedPath = realpath($path);
        $normalizedPath = $resolvedPath !== false ? $resolvedPath : $path;

        if (! is_file($normalizedPath)) {
            return $normalizedPath . '|missing';
        }

        $mtime = @filemtime($normalizedPath) ?: 0;
        $size = @filesize($normalizedPath) ?: 0;

        return $normalizedPath . '|' . $mtime . '|' . $size;
    }

    public function updateStyles(?callable $progress = null, ?callable $debug = null): int
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('PHP ZipArchive extension is required.');
        }

        $cslDir = $this->getCslDir();
        $zipFile = $cslDir . 'styles-master.zip';

        if (! is_dir(TEMP_CACHE_PATH) && ! mkdir(TEMP_CACHE_PATH, 0755, true)) {
            throw new RuntimeException('Cannot create cache directory for lock file: ' . TEMP_CACHE_PATH);
        }

        $lockFile = TEMP_CACHE_PATH . '/zotero_csl_update.lock';
        $lockHandle = $this->acquireLock($lockFile);

        try {
            $this->debug($debug, 'Acquired lock: ' . $lockFile . ' pid=' . getmypid());

            if (! is_dir($cslDir)) {
                if (! mkdir($cslDir, 0755, true)) {
                    throw new RuntimeException("Cannot create directory $cslDir");
                }
                $this->progress($progress, "Created directory: $cslDir");
            }

            $tikilib = \TikiLib::lib('tiki');
            $zipContent = $this->downloadCslArchive($tikilib, $debug);
            if (file_put_contents($zipFile, $zipContent) === false) {
                throw new RuntimeException("Cannot write downloaded archive to $zipFile");
            }

            $this->progress($progress, 'Downloaded: ' . number_format(strlen($zipContent)) . ' bytes');
            $this->progress($progress, 'Extracting styles...');

            try {
                $extractedCount = $this->extractCslFiles($zipFile, $cslDir, $debug);
            } finally {
                @unlink($zipFile);
            }

            if ($extractedCount === 0) {
                throw new RuntimeException('No CSL files were extracted from archive.');
            }

            $this->progress($progress, "Extraction complete ($extractedCount files).");

            return count($this->listStyles());
        } finally {
            if (is_resource($lockHandle)) {
                flock($lockHandle, LOCK_UN);
                fclose($lockHandle);
            }
        }
    }

    /**
     * @return resource
     */
    private function acquireLock(string $lockFile): mixed
    {
        $handle = @fopen($lockFile, 'c+');
        if (! $handle) {
            throw new RuntimeException('Cannot open lock file: ' . $lockFile);
        }

        if (! flock($handle, LOCK_EX | LOCK_NB)) {
            $existingPid = trim(stream_get_contents($handle));
            $extra = $existingPid !== '' ? ' (PID ' . $existingPid . ')' : '';
            fclose($handle);
            throw new RuntimeException('zotero:csl:update is already running' . $extra . '.');
        }

        ftruncate($handle, 0);
        rewind($handle);
        fwrite($handle, (string) getmypid());
        fflush($handle);

        return $handle;
    }

    private function downloadCslArchive(\TikiLib $tikilib, ?callable $debug = null): string
    {
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

        foreach ($this->cslArchiveUrls() as $zipUrl) {
            foreach ($strategies as $strategy) {
                $adapter = $strategy['options']['adapter'] ?? 'default';
                $this->debug($debug, 'Attempting download: url=' . $zipUrl . ' adapter=' . $adapter . ' strategy=' . $strategy['label']);

                try {
                    $client = $tikilib->get_http_client($zipUrl, $strategy['options']);
                    if (! empty($strategy['headers'])) {
                        $client->setHeaders($strategy['headers']);
                    }

                    $response = $tikilib->http_perform_request($client);
                    if (! $response->isSuccess()) {
                        $this->debug($debug, 'HTTP failure: status=' . $response->getStatusCode());
                        $errors[] = $zipUrl . ' [' . $strategy['label'] . ']: HTTP ' . $response->getStatusCode();
                        continue;
                    }

                    $body = $response->getBody();
                    if ($body === '') {
                        $this->debug($debug, 'Failure: empty response body');
                        $errors[] = $zipUrl . ' [' . $strategy['label'] . ']: empty response body';
                        continue;
                    }

                    $this->debug(
                        $debug,
                        'Success: bytes=' . strlen($body)
                        . ' content-type=' . $this->responseHeaderValue($response, 'Content-Type')
                        . ' content-length=' . $this->responseHeaderValue($response, 'Content-Length')
                        . ' transfer-encoding=' . $this->responseHeaderValue($response, 'Transfer-Encoding')
                        . ' content-encoding=' . $this->responseHeaderValue($response, 'Content-Encoding')
                    );

                    return $body;
                } catch (Throwable $e) {
                    $this->debug($debug, 'Exception: ' . get_class($e) . ': ' . $e->getMessage());
                    $errors[] = $zipUrl . ' [' . $strategy['label'] . ']: ' . $e->getMessage();
                }
            }
        }

        throw new RuntimeException(implode(' | ', $errors));
    }

    private function cslArchiveUrls(): array
    {
        global $prefs;

        $configured = trim((string) ($prefs['zotero_pandoc_csl_archive_url'] ?? ''));
        if ($configured === '') {
            return self::DEFAULT_CSL_STYLES_ARCHIVE_URLS;
        }

        if (! preg_match('/^https?:\/\//i', $configured) || filter_var($configured, FILTER_VALIDATE_URL) === false) {
            throw new RuntimeException('Invalid CSL style archive URL: ' . $configured);
        }

        return [$configured];
    }

    private function extractCslFiles(string $zipFile, string $cslDir, ?callable $debug = null): int
    {
        $zip = new ZipArchive();
        if ($zip->open($zipFile) !== true) {
            throw new RuntimeException('Failed to open downloaded ZIP archive.');
        }

        $this->debug($debug, 'ZIP entries total: ' . $zip->numFiles);

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
                throw new RuntimeException("Cannot write extracted style file $basename");
            }

            $extractedCount++;
            if ($extractedCount % 2000 === 0) {
                $this->debug($debug, 'Extraction progress: ' . $extractedCount . ' files');
            }
        }

        $zip->close();

        return $extractedCount;
    }

    private function responseHeaderValue(object $response, string $headerName): string
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

    private function resolveFilePath(string $path, array $prefixCandidates = []): ?string
    {
        $trimmed = trim($path);
        if ($trimmed === '') {
            return null;
        }

        $candidates = [$trimmed];
        foreach ($prefixCandidates as $prefix) {
            $candidates[] = rtrim($prefix, '/') . '/' . ltrim($trimmed, '/');
        }

        if (defined('TIKI_PATH')) {
            foreach ($candidates as $candidate) {
                if (! preg_match('/^(\/|[A-Za-z]:[\/\\\\])/', $candidate)) {
                    $candidates[] = rtrim(TIKI_PATH, '/') . '/' . ltrim($candidate, '/');
                }
            }
        }

        foreach ($candidates as $candidate) {
            $resolved = realpath($candidate);
            if ($resolved !== false && is_file($resolved)) {
                return $resolved;
            }
        }

        return null;
    }

    private function progress(?callable $progress = null, string $message = ''): void
    {
        if ($progress) {
            $progress($message);
        }
    }

    private function debug(?callable $debug = null, string $message = ''): void
    {
        if ($debug) {
            $debug($message);
        }
    }
}
