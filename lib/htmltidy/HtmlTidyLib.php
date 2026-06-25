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
    /**
     * Wraps fragments before validation so Tidy doesn't have to guess at missing
     * structure — that guesswork shifts line numbers inconsistently between libtidy builds.
     */
    private const DOCUMENT_WRAPPER = '<!DOCTYPE html><html><head><title></title></head><body>';

    private bool $tidyAvailable;

    public function __construct()
    {
        global $prefs, $tiki_p_admin;

        $this->tidyAvailable = extension_loaded('tidy');

        // Process admin session toggle from URL parameter (?tidy_html_reporting=y|n).
        if (
            ($prefs['error_tracking_enabled_tidy'] ?? 'n') === 'y'
            && ($tiki_p_admin ?? 'n') === 'y'
            && isset($_REQUEST['tidy_html_reporting'])
            && in_array($_REQUEST['tidy_html_reporting'], ['y', 'n'], true)
        ) {
            $_SESSION['tidy_html_reporting'] = $_REQUEST['tidy_html_reporting'];
        }
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
    public function cleanHtml(string $html, ?array $config = null, string $encoding = 'utf8', array $context = []): string
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
     * @return array          Parsed error entries: [['level', 'line', 'column', 'message', 'snippet'], ...]
     */
    public function reportErrors(string $html, array $context = []): array
    {
        if (! $this->tidyAvailable) {
            return [];
        }

        $wrapped = self::DOCUMENT_WRAPPER . "\n" . $html;
        $wrapperLines = substr_count(self::DOCUMENT_WRAPPER, "\n") + 1;

        $html_ = tidy_parse_string($wrapped, [], 'utf8');
        tidy_diagnose($html_);
        $errors = $this->adjustLineNumbers(
            $this->parseTidyErrors(tidy_get_error_buffer($html_)),
            $wrapperLines
        );

        if (! empty($errors)) {
            $errors = $this->attachSnippets($errors, $html);
        }

        if (! empty($errors) && $this->isTidyReportingEnabled()) {
            $this->dispatchErrors($errors, $context);
        }

        return $errors;
    }

    /**
     * @return bool True if the Tidy extension is loaded
     */
    public function isTidyLoaded(): bool
    {
        return $this->tidyAvailable;
    }

    /**
     * Returns true when all conditions are met: DSN configured, preference on,
     * Tidy extension loaded, and session not disabled by admin toggle.
     */
    public function isTidyReportingEnabled(): bool
    {
        global $prefs;

        return ! empty($prefs['error_tracking_dsn'])
            && ($prefs['error_tracking_enabled_tidy'] ?? 'n') === 'y'
            && $this->tidyAvailable
            && ($_SESSION['tidy_html_reporting'] ?? 'y') !== 'n';
    }

    /**
     * Merge caller-supplied Tidy config overrides with the standard defaults.
     */
    private function buildConfig(?array $override): array
    {
        $defaults = [
            'output-xhtml'                => false,
            'merge-divs'                  => false,
            'show-body-only'              => false,
            'new-blocklevel-tags'         => 'article aside audio bdi canvas details dialog figcaption figure footer header hgroup main menu menuitem nav section source summary template track video',
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
     * Send each parsed error to the error tracking service as a message event.
     * Includes a snippet so the message is readable without the original fragment.
     */
    private function dispatchErrors(array $errors, array|string $context): void
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

            if (! empty($error['snippet'])) {
                $message .= sprintf(' — near: "%s"', $error['snippet']);
            }

            $errorTracking->captureMessage($message);
        }
    }

    private function attachSnippets(array $errors, string $html): array
    {
        $lines = explode("\n", $html);

        foreach ($errors as &$error) {
            $line = $lines[$error['line'] - 1] ?? '';
            $error['snippet'] = $this->extractSnippet($line, $error['column']);
        }

        return $errors;
    }

    /**
     * Extract a window of text centered on $column — lines can be long, and the
     * error isn't always near the start.
     */
    private function extractSnippet(string $line, int $column, int $windowSize = 80): string
    {
        $line = trim($line);
        if ($line === '') {
            return '';
        }

        $columnIndex = $column - 1; // Tidy columns are 1-based, string offsets are 0-based
        $halfWindow = (int) ($windowSize / 2);

        $windowStart = max(0, $columnIndex - $halfWindow);
        $snippet = substr($line, $windowStart, $windowSize);

        if ($windowStart > 0) {
            $snippet = '…' . $snippet;
        }
        if ($windowStart + $windowSize < strlen($line)) {
            $snippet .= '…';
        }

        return $snippet;
    }

    /**
     * Errors with line <= 0 after the shift belong to the wrapper, not the plugin.
     */
    private function adjustLineNumbers(array $errors, int $offset): array
    {
        foreach ($errors as &$error) {
            $error['line'] -= $offset;
        }
        unset($error);

        return array_values(array_filter($errors, fn(array $error): bool => $error['line'] >= 1));
    }

    /**
     * Parse the raw Tidy error buffer string into a structured array.
     * Tidy error format: "line X column Y - Level: message"
     */
    private function parseTidyErrors(string $errorBuffer): array
    {
        $errors = [];

        foreach (explode("\n", $errorBuffer) as $line) {
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
