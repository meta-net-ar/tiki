<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace TikiDevTools;

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

        $batches = array_chunk($urlData, $this->batchSize);

        foreach ($batches as $batch) {
            $this->processBatch($batch);
            $processed += count($batch);
            echo "\rChecking URL availability... " . $processed . "/" . $totalUrls . " URLs checked";
        }

        echo "\r" . str_repeat(' ', 60) . "\r";

        return [
            'broken' => $this->brokenUrls,
            'active' => $this->activeUrls,
            'uncertain' => $this->uncertainUrls,
        ];
    }

    private function processBatch($batch)
    {
        $multiHandle = curl_multi_init();
        $handles = [];

        foreach ($batch as $index => $data) {
            // Clean URL: remove trailing punctuation and Tiki wiki syntax (|text)
            $cleanUrl = rtrim($data['url'], '.,;:!?)');
            // Strip Tiki wiki syntax: [http://example.com|display text] - remove everything after pipe
            if (str_contains($cleanUrl, '|')) {
                $cleanUrl = explode('|', $cleanUrl)[0];
            }

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $cleanUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => $this->timeout,
                CURLOPT_CONNECTTIMEOUT => $this->connectTimeout,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 5,
                CURLOPT_NOBODY => true,
                CURLOPT_USERAGENT => $this->userAgent,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_HTTPHEADER => [
                    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language: en-US,en;q=0.5',
                ],
            ]);

            curl_multi_add_handle($multiHandle, $ch);
            $handles[$index] = ['handle' => $ch, 'data' => $data];
        }

        $running = null;
        do {
            curl_multi_exec($multiHandle, $running);
            if ($running) {
                curl_multi_select($multiHandle);
            }
        } while ($running > 0);

        foreach ($handles as $index => $item) {
            $ch = $item['handle'];
            $data = $item['data'];

            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);

            // Some sites don't support HEAD requests properly and return 404 or 405
            // Retry with GET to confirm if the resource actually exists
            if ($httpCode === 404 || $httpCode === 405) {
                curl_multi_remove_handle($multiHandle, $ch);
                curl_setopt($ch, CURLOPT_NOBODY, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
                curl_exec($ch);
                $newHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $error = curl_error($ch);
                if ($newHttpCode >= 200 && $newHttpCode < 400) {
                    $httpCode = $newHttpCode;
                }
                curl_multi_add_handle($multiHandle, $ch);
            }

            if ($httpCode >= 200 && $httpCode < 400) {
                $this->activeUrls[] = $data;
            } elseif (in_array($httpCode, [103, 400, 401, 403, 405, 429])) {
                // Uncertain: bot protection, auth required, method not allowed, rate limited
                $reasons = [
                    103 => 'HTTP 103 (Early Hints - provisional)',
                    400 => 'HTTP 400 (Possible bot protection)',
                    401 => 'HTTP 401 (Requires authentication)',
                    403 => 'HTTP 403 (Possible bot protection)',
                    405 => 'HTTP 405 (Method not allowed)',
                    429 => 'HTTP 429 (Rate limited)',
                ];
                $reason = $reasons[$httpCode] ?? "HTTP $httpCode";
                $this->uncertainUrls[] = array_merge($data, ['reason' => $reason, 'http_code' => $httpCode]);
            } else {
                $reason = $error ?: "HTTP $httpCode";
                if ($httpCode === 0) {
                    $reason = $error ?: 'Connection failed';
                } elseif ($httpCode === 404) {
                    $reason = 'HTTP 404 (Not Found)';
                } elseif ($httpCode >= 500) {
                    $reason = "HTTP $httpCode (Server Error)";
                }
                $this->brokenUrls[] = array_merge($data, ['reason' => $reason, 'http_code' => $httpCode]);
            }

            curl_multi_remove_handle($multiHandle, $ch);
        }

        curl_multi_close($multiHandle);
    }
}
