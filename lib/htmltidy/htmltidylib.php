<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Lib\HtmlTidy;

/**
 * Centralizes PHP Tidy integration for HTML cleaning and error reporting.
 */
class HtmlTidyLib
{
    private $tidyAvailable;

    public function __construct()
    {
        $this->tidyAvailable = extension_loaded('tidy');
    }

    /**
     * Clean and repair HTML from external sources (RSS feeds, PDF rendering, ajaxload).
     * Calls cleanRepair() — intentionally modifies the returned HTML.
     * Also reports markup errors to the error tracking service when reporting is enabled.
     *
     * Do NOT use this on internal Tiki wiki/plugin output.
     *
     * @param string $html     HTML from an external or special-case source
     * @param array|null $config  Optional Tidy config overrides
     * @param string $encoding Character encoding (default: utf8)
     * @param array $context   Context metadata for error reports (e.g. ['source' => 'rss_content'])
     * @return string Cleaned HTML, or original HTML if Tidy is unavailable
     */
    public function cleanHtml($html, $config = null, $encoding = 'utf8', $context = [])
    {
        if (! $this->tidyAvailable) {
            return $html;
        }

        $html_ = tidy_parse_string($html, $this->buildConfig($config), $encoding);

        if ($this->isTidyReportingEnabled() && $html_) {
            tidy_diagnose($html_);
            $errors = $this->parseTidyErrors(tidy_get_error_buffer($html_));
            if (! empty($errors)) {
                $this->dispatchErrors($errors, $context);
            }
        }

        $html_->cleanRepair();
        return (string) $html_;
    }

    /**
     * Validate HTML from internal Tiki sources (wiki plugins, parser output) and report errors.
     * NEVER modifies the HTML — purely diagnostic.
     *
     * Dispatches errors to the error tracking service (GlitchTip/Sentry) when reporting is enabled,
     * and always returns the parsed errors array so callers (e.g. preview mode) can surface them
     * directly to the user.
     *
     * @param string $html    HTML string to validate
     * @param array $context  Context metadata for error reports (e.g. ['source' => 'Plugin: html'])
     * @return array          Parsed error entries: [['level', 'line', 'column', 'message'], ...]
     */
    public function reportErrors($html, $context = [])
    {
        if (! $this->tidyAvailable) {
            return [];
        }

        $html_ = tidy_parse_string($html, [], 'utf8');
        tidy_diagnose($html_);
        $errors = $this->parseTidyErrors(tidy_get_error_buffer($html_));

        if (! empty($errors) && $this->isTidyReportingEnabled()) {
            $this->dispatchErrors($errors, $context);
        }

        return $errors;
    }

    /**
     * @return bool True if the Tidy extension is loaded
     */
    public function isTidyLoaded()
    {
        return $this->tidyAvailable;
    }

    /**
     * Delegates to ErrorTracking which owns all reporting state:
     * global preference, DSN, and session toggle.
     *
     * @return bool
     */
    public function isTidyReportingEnabled()
    {
        return \TikiLib::lib('errortracking')->isTidyEnabled();
    }

    /**
     * Merge caller-supplied Tidy config overrides with the standard defaults.
     */
    private function buildConfig($override)
    {
        $defaults = [
            'clean'                       => true,
            'output-xhtml'                => false,
            'merge-divs'                  => false,
            'show-body-only'              => false,
            'new-blocklevel-tags'         => 'pdfsettings pdfpage pdfinclude article aside audio bdi canvas details dialog figcaption figure footer header hgroup main menu menuitem nav section source summary template track video',
            'new-empty-tags'              => 'embed keygen source track wbr',
            'new-inline-tags'             => 'svg audio command datalist embed mark menuitem meter output progress source time video wbr',
            'repeated-attributes'         => 'keep-first',
            'drop-proprietary-attributes' => false,
            'wrap'                        => 0,
            'coerce-endtags'              => true,
            'quote-ampersand'             => true,
            'quote-marks'                 => false,
            'drop-empty-elements'         => false,
        ];

        return is_array($override) ? array_merge($defaults, $override) : $defaults;
    }

    /**
     * Send each parsed error to the error tracking service as a capturable exception.
     */
    private function dispatchErrors($errors, $context)
    {
        $errorTracking = \TikiLib::lib('errortracking');
        $source = is_array($context) ? ($context['source'] ?? 'unknown') : (string) $context;

        foreach ($errors as $error) {
            $message = sprintf(
                'HTML Tidy %s in %s: %s (line %d, col %d)',
                ucfirst($error['level']),
                $source,
                $error['message'],
                $error['line'],
                $error['column']
            );
            $errorTracking->captureException(new \Exception($message));
        }
    }

    /**
     * Parse the raw Tidy error buffer string into a structured array.
     * Tidy error format: "line X column Y - Level: message"
     */
    private function parseTidyErrors($errorBuffer)
    {
        $errors = [];

        foreach (explode("\n", (string) $errorBuffer) as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            if (preg_match('/line (\d+) column (\d+) - (\w+): (.+)/', $line, $m)) {
                $errors[] = [
                    'level'   => strtolower($m[3]),
                    'line'    => (int) $m[1],
                    'column'  => (int) $m[2],
                    'message' => $m[4],
                ];
            }
        }

        return $errors;
    }
}
