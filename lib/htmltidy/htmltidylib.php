<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

/**
 * HTML Markup Error Tracking Library
 *
 * Provides centralized HTML cleaning and error reporting.
 * Uses the Tidy extension if available to fix markup and/or log errors to error tracking services.
 *
 * @package Tiki
 * @subpackage HTMLTidy
 */
class HtmlTidyLib extends TikiDb_Bridge
{
    private $tidyAvailable;
    private $tidyReportingEnabled;

    public function __construct()
    {
        global $prefs;

        $this->tidyAvailable = extension_loaded('tidy');
        $this->tidyReportingEnabled = ($prefs['error_tracking_enabled_tidy'] ?? 'n') === 'y';
    }

    /**
     * Clean HTML with tidy or collect errors for reporting
     *
     * @param string $html The HTML to be cleaned/validated
     * @param array|null $config Optional tidy configuration
     * @param string $encoding The encoding (default: utf8)
     * @param array $context Additional context for error reporting
     * @return string Cleaned HTML or original HTML if tidy not available/enabled
     */
    public function cleanHtml($html, $config = null, $encoding = 'utf8', $context = [])
    {
        if (! $this->tidyAvailable) {
            return $html;
        }

        $default = [
            'clean' => true,
            'output-xhtml' => false,
            'merge-divs' => false,
            'show-body-only' => false,
            'new-blocklevel-tags' => 'pdfsettings pdfpage pdfinclude article aside audio bdi canvas details dialog figcaption figure footer header hgroup main menu menuitem nav section source summary template track video',
            'new-empty-tags' => 'embed keygen source track wbr',
            'new-inline-tags' => 'svg audio command datalist embed mark menuitem meter output progress source time video wbr',
            'repeated-attributes' => 'keep-first',
            'drop-proprietary-attributes' => false,
            'wrap' => 0,
            'coerce-endtags' => true,
            'quote-ampersand' => true,
            'quote-marks' => false,
            'drop-empty-elements' => false,
        ];

        $config = (is_array($config)) ? array_merge($default, $config) : $default;
        $html_ = tidy_parse_string($html, $config, $encoding);

        if ($this->tidyReportingEnabled && $html_) {
            tidy_diagnose($html_);
            $errorBuffer = tidy_get_error_buffer($html_);
            if (! empty($errorBuffer)) {
                $errors = $this->parseTidyErrors($html_);
                if (! empty($errors)) {
                    $this->reportToErrorTracking($errors, $context);
                }
            }
        }

        $html_->cleanRepair();
        return (string) $html_;
    }

    /**
     * Parse tidy errors from a tidy object into structured array
     *
     * @param tidy $tidyObject The tidy object after parsing
     * @return array Array of parsed errors
     */
    private function parseTidyErrors($tidyObject)
    {
        $errors = [];
        $errorBuffer = tidy_get_error_buffer($tidyObject);
        $lines = explode("\n", $errorBuffer);

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            // Parse tidy error format: "line X column Y - Level: message"
            if (preg_match('/line (\d+) column (\d+) - (\w+): (.+)/', $line, $matches)) {
                $errors[] = [
                    'level' => strtolower($matches[3]),
                    'line' => (int) $matches[1],
                    'column' => (int) $matches[2],
                    'message' => $matches[4],
                    'code' => 'tidy_' . strtolower($matches[3]),
                    'source' => 'tidy'
                ];
            }
        }

        return $errors;
    }

    /**
     * Report HTML markup errors to ErrorTracking
     *
     * @param array $errors Array of parsed Tidy errors
     * @param array $context Context information
     */
    private function reportToErrorTracking($errors, $context)
    {
        if (empty($errors)) {
            return;
        }

        if (! $this->isTidyReportingEnabled()) {
            return;
        }

        $errorTracking = TikiLib::lib('errortracking');
        $source = is_array($context) ? ($context['source'] ?? 'unknown') : $context;

        foreach ($errors as $error) {
            $message = sprintf(
                'HTML Tidy Error in %s: %s (Line: %d, Column: %d)',
                $source,
                $error['message'] ?? 'Unknown error',
                $error['line'] ?? 0,
                $error['column'] ?? 0
            );

            $exception = new Exception($message);
            file_put_contents('tidy-error.log', $message . "\n", FILE_APPEND);
            $errorTracking->captureException($exception);
        }
    }

    /**
     * Check if tidy is available/loaded
     *
     * @return bool
     */
    public function isTidyLoaded()
    {
        return $this->tidyAvailable;
    }

    /**
     * Check if Tidy error reporting is enabled
     * (Tidy must be loaded AND error_tracking_enabled_tidy preference must be 'y')
     *
     * @return bool
     */
    public function isTidyReportingEnabled()
    {
        return $this->tidyAvailable && $this->tidyReportingEnabled;
    }
}
