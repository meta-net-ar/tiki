<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

/**
 * Standalone URL Availability Checker — Release Prep Tool
 *
 * Checks if external URLs found in the codebase are still reachable.
 * Run manually by the Release Manager before a release.
 *
 * This is NOT part of CI. CI handles link governance via check_external_links.php.
 *
 * Usage:
 *   php src/devtools/check_url_availability.php
 *   php src/devtools/check_url_availability.php --severity=critical,high
 */

namespace TikiDevTools;

if (PHP_SAPI !== 'cli') {
    die('Only available through command-line.' . PHP_EOL);
}

require_once __DIR__ . '/../ci/vcscommons.php';
require_once __DIR__ . '/../ci/check_external_links.php';

/**
 * URL Availability Checker using curl_multi for parallel requests.
 * Designed for performance: checks URLs concurrently with configurable batch size.
 */
class UrlAvailabilityChecker
{
    /** @var int Total request timeout in seconds (balance between detection and CI speed) */
    private $timeout = 5;

    /** @var int Connection timeout in seconds (detect unreachable hosts quickly) */
    private $connectTimeout = 3;

    /** @var int URLs to check concurrently (too high may trigger rate limiting) */
    private $batchSize = 50;

    /** @var int Max retries for transient failures (timeouts, 429, 5xx) */
    private $maxRetries = 2;

    /** @var string Browser-like User-Agent to reduce bot detection false positives */
    private $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';

    private $brokenUrls = [];
    private $activeUrls = [];
    private $uncertainUrls = [];

    /**
     * Check multiple URLs for availability using parallel requests
     * @param array $urlData Array of ['url' => ..., 'file' => ..., 'line' => ..., 'severity' => ...]
     * @return array Results with 'broken', 'active', and 'uncertain' arrays
     */
    public function checkUrls($urlData)
    {
        $this->brokenUrls = [];
        $this->activeUrls = [];
        $this->uncertainUrls = [];

        $totalUrls = count($urlData);
        $processed = 0;

        $queue = array_map(function ($data) {
            $data['attempts'] = 0;
            return $data;
        }, $urlData);

        while (! empty($queue)) {
            $batchSize = min(count($queue), $this->batchSize);
            $batch = array_splice($queue, 0, $batchSize);

            $retries = $this->processBatch($batch);

            if (! empty($retries)) {
                $queue = array_merge($queue, $retries);
            }

            $processed += (count($batch) - count($retries));
            echo "\rChecking URL availability... " . $processed . "/" . $totalUrls . " URLs checked";
        }

        echo "\r" . str_repeat(' ', 60) . "\r";

        return [
            'broken'    => $this->brokenUrls,
            'active'    => $this->activeUrls,
            'uncertain' => $this->uncertainUrls,
        ];
    }

    private function processBatch($batch)
    {
        $multiHandle = curl_multi_init();
        $handles = [];
        $retries = [];

        foreach ($batch as $index => $data) {
            // Clean URL: remove trailing punctuation and Tiki wiki syntax (|text)
            $cleanUrl = rtrim($data['url'], '.,;:!?)');
            // Strip Tiki wiki syntax: [http://example.com|display text] - remove everything after pipe
            if (str_contains($cleanUrl, '|')) {
                $cleanUrl = explode('|', $cleanUrl)[0];
            }

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => $cleanUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => $this->timeout,
                CURLOPT_CONNECTTIMEOUT => $this->connectTimeout,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS      => 5,
                CURLOPT_NOBODY         => true,
                CURLOPT_USERAGENT      => $this->userAgent,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_HTTPHEADER     => [
                    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language: en-US,en;q=0.5',
                ],
            ]);

            curl_multi_add_handle($multiHandle, $ch);
            $handles[$index] = ['handle' => $ch, 'data' => $data];
        }

        $this->runMulti($multiHandle);

        $fallbackActive = false;
        foreach ($handles as $index => $item) {
            $ch = $item['handle'];
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            // Retry with GET in parallel if HEAD fails with 403/404/405 (common with bot protection)
            if ($httpCode === 403 || $httpCode === 404 || $httpCode === 405) {
                curl_multi_remove_handle($multiHandle, $ch);
                curl_setopt($ch, CURLOPT_NOBODY, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
                curl_multi_add_handle($multiHandle, $ch);
                $fallbackActive = true;
            }
        }

        if ($fallbackActive) {
            $this->runMulti($multiHandle);
        }

        foreach ($handles as $index => $item) {
            $ch = $item['handle'];
            $data = $item['data'];

            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            $errno = curl_errno($ch);

            // Transient issues (Timeout, 429, 5xx)
            $isTransient = $errno === CURLE_OPERATION_TIMEDOUT
                || $httpCode === 429
                || ($httpCode >= 500 && $httpCode < 600);

            $resultData = $data;
            unset($resultData['attempts']);

            if ($isTransient && $data['attempts'] < $this->maxRetries) {
                $data['attempts']++;
                $retries[] = $data;
            } elseif ($httpCode >= 200 && $httpCode < 400) {
                $this->activeUrls[] = $resultData;
            } elseif ($isTransient) {
                // Exhausted retries -> Uncertain
                $reason = $errno === CURLE_OPERATION_TIMEDOUT
                    ? 'Timeout (after ' . $this->maxRetries . ' retries)'
                    : "HTTP $httpCode (after " . $this->maxRetries . ' retries)';
                $this->uncertainUrls[] = array_merge($resultData, ['reason' => $reason, 'http_code' => $httpCode]);
            } elseif (in_array($httpCode, [103, 400, 401, 403, 405])) {
                // Uncertain: bot protection, auth required, method not allowed
                $reasons = [
                    103 => 'HTTP 103 (Early Hints - provisional)',
                    400 => 'HTTP 400 (Possible bot protection)',
                    401 => 'HTTP 401 (Requires authentication)',
                    403 => 'HTTP 403 (Possible bot protection)',
                    405 => 'HTTP 405 (Method not allowed)',
                ];
                $reason = $reasons[$httpCode] ?? "HTTP $httpCode";
                $this->uncertainUrls[] = array_merge($resultData, ['reason' => $reason, 'http_code' => $httpCode]);
            } else {
                $reason = $error ?: "HTTP $httpCode";
                if ($httpCode === 0) {
                    $reason = $error ?: 'Connection failed';
                } elseif ($httpCode === 404) {
                    $reason = 'HTTP 404 (Not Found)';
                } elseif ($httpCode >= 500) {
                    $reason = "HTTP $httpCode (Server Error)";
                }
                $this->brokenUrls[] = array_merge($resultData, ['reason' => $reason, 'http_code' => $httpCode]);
            }

            curl_multi_remove_handle($multiHandle, $ch);
            curl_close($ch);
        }

        curl_multi_close($multiHandle);

        return $retries;
    }

    /**
     * Execute multi-handle requests and wait for completion
     */
    private function runMulti($multiHandle)
    {
        $running = null;
        do {
            curl_multi_exec($multiHandle, $running);
            if ($running) {
                curl_multi_select($multiHandle);
            }
        } while ($running > 0);
    }
}

// ── CLI Entry Point

$dir = realpath(__DIR__ . '/../../');
$paramList = $_SERVER['argv'] ?? [];
$listFiles = [];
$severityFilter = null;

foreach ($paramList as $param) {
    if (str_starts_with($param, '--severity=')) {
        $severityValue = substr($param, strlen('--severity='));
        $severityFilter = array_map('strtolower', array_map('trim', explode(',', $severityValue)));
    } elseif (basename(__FILE__) !== basename($param)) {
        $file = $dir . DIRECTORY_SEPARATOR . $param;
        if (file_exists($file)) {
            $listFiles[] = $param;
        }
    }
}

// Phase 1: Scan codebase for URLs using the existing ExternalLinksChecker
info('Phase 1: Scanning for external URLs' . (empty($listFiles) ? ' (full project)...' : ' in specified files...'));
$checker = new ExternalLinksChecker($dir);
$checker->scan($listFiles);

$urlsToCheck = $checker->getUrlsForAvailabilityCheck($severityFilter);
$summary = $checker->getSummary();
info($summary['files_scanned'] . ' files scanned, ' . count($urlsToCheck) . ' unique URLs to check');

if (empty($urlsToCheck)) {
    important('No URLs to check for availability (all filtered out).');
    exit(0);
}

// Phase 2: Check availability
echo PHP_EOL;
info('Phase 2: Checking URL availability...');
$availabilityChecker = new UrlAvailabilityChecker();
$results = $availabilityChecker->checkUrls($urlsToCheck);

$brokenUrls = $results['broken'];
$uncertainUrls = $results['uncertain'];
$activeUrls = $results['active'];

// ── Output Results

echo PHP_EOL;
echo str_repeat('=', 70) . PHP_EOL;

if (! empty($brokenUrls)) {
    echo color('BROKEN URLs: ' . count($brokenUrls) . ' found', 'red') . PHP_EOL;
    echo str_repeat('-', 70) . PHP_EOL;
    foreach ($brokenUrls as $broken) {
        echo color($broken['file'] . ':' . $broken['line'], 'yellow') . PHP_EOL;
        echo '  URL:    ' . color($broken['url'], 'red') . PHP_EOL;
        echo '  Reason: ' . $broken['reason'] . PHP_EOL;
        echo PHP_EOL;
    }
}

if (! empty($uncertainUrls)) {
    echo color('UNCERTAIN URLs: ' . count($uncertainUrls) . ' (may need manual check)', 'yellow') . PHP_EOL;
    echo str_repeat('-', 70) . PHP_EOL;
    foreach ($uncertainUrls as $uncertain) {
        echo $uncertain['file'] . ':' . $uncertain['line'] . PHP_EOL;
        echo '  URL:    ' . $uncertain['url'] . PHP_EOL;
        echo '  Reason: ' . $uncertain['reason'] . PHP_EOL;
        echo PHP_EOL;
    }
}

echo str_repeat('=', 70) . PHP_EOL;
echo 'Summary:' . PHP_EOL;
echo '  Active:    ' . color(count($activeUrls), 'green') . PHP_EOL;
echo '  Broken:    ' . color(count($brokenUrls), 'red') . PHP_EOL;
echo '  Uncertain: ' . color(count($uncertainUrls), 'yellow') . PHP_EOL;
echo '  Total:     ' . count($urlsToCheck) . ' URLs checked' . PHP_EOL;
echo str_repeat('=', 70) . PHP_EOL;

if (empty($brokenUrls)) {
    important('All URLs are reachable. Ready for release!');
} else {
    echo color(count($brokenUrls) . ' broken URL(s) detected. Please review before release.', 'red') . PHP_EOL;
}

echo PHP_EOL;
exit(0);
