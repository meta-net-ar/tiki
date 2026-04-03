<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace TikiDevTools;

if (PHP_SAPI !== 'cli') {
    die('Only available through command-line.');
}

require_once __DIR__ . '/../../path_constants.php';
require_once __DIR__ . '/vcscommons.php';
require_once __DIR__ . '/FileScanner.php';

/**
 * Check for external links in source code
 *
 * Detects three types of external links:
 * 1. External code (CRITICAL) - URLs that load executable code (JS/CSS)
 * 2. User visible (HIGH) - URLs visible to end users in rendered HTML
 * 3. Source code (LOW) - URLs in comments, documentation, etc.
 */
class ExternalLinksChecker
{
    private $fileScanner;
    private $severity = [
        'critical' => [], // External code (CRITICAL)
        'high' => [], // User visible (HIGH)
        'low' => [], // Source code (LOW)
    ];
    private $allUrls = []; // Complete index for full liveness audit
    private $suppressed = []; // Links with @tiki-external-link-ok comments
    private $whitelistDomains = [];
    private $whitelistPatterns = [];
    private $filesScanned = 0;
    private $ciMode = false;

    public function __construct($dir, $ciMode = false)
    {
        $this->fileScanner = new FileScanner($dir);
        $this->fileScanner->addExclusion(__FILE__);

        $this->fileScanner->setExtensions([
            'php',
            'tpl',
            'js',
            'css',
            'less',
            'sql',
            'md',
            'txt',
            'xml',
            'json',
            'htaccess',
            'config',
        ]);
        $this->ciMode = $ciMode;
        $this->setupWhitelist();
    }

    /**
     * Minimal whitelist - documentation/standards sites only.
     * For specific exceptions, use @tiki-external-link-ok: reason
     */
    private function setupWhitelist()
    {
        $this->whitelistDomains = [
            // W3C standards documentation (reference links, not code execution)
            'www.w3.org' => 'W3C standards documentation',
            'w3.org' => 'W3C standards documentation',

            // Tiki project infrastructure
            'gitlab.com/tikiwiki' => 'Tiki GitLab repository',

            // Language/framework documentation
            'php.net' => 'PHP documentation',
            'www.php.net' => 'PHP documentation',
            'developer.mozilla.org' => 'MDN web documentation',
            'mozilla.org' => 'Mozilla documentation',
            'smarty.net' => 'Smarty template engine docs',
            'www.smarty.net' => 'Smarty template engine docs',
            'summernote.org' => 'Summernote WYSIWYG editor documentation',
            'zotero.org' => 'Zotero bibliography system documentation',
            'www.zotero.org' => 'Zotero bibliography system documentation',
            'google.com' => 'Google services (Calendar, etc.) - informational links',
            'www.google.com' => 'Google services (Calendar, etc.) - informational links',
            'appspot.com' => 'Google App Engine hosted tools/documentation',

            // Open source project documentation
            'github.com' => 'GitHub repositories/documentation',
            'github.io' => 'GitHub Pages documentation sites',

            // Package repositories (required for Composer/Satis builds)
            'asset-packagist.org' => 'Composer asset repository',
        ];

        // Pattern-based whitelist for Tiki domains
        $this->whitelistPatterns = [
            '/^[^.]+\.tiki\.org$/i', // All *.tiki.org subdomains
            '/^tiki\.org$/i',
        ];
    }


    private function isWhitelisted($url)
    {
        $parsed = parse_url($url);
        if (! isset($parsed['host'])) {
            return false;
        }

        $host = $parsed['host'];

        // Check exact match in whitelisted domains (using keys of associative array)
        if (array_key_exists($host, $this->whitelistDomains)) {
            return true;
        }

        // Check pattern-based whitelist
        foreach ($this->whitelistPatterns as $pattern) {
            if (preg_match($pattern, $host)) {
                return true;
            }
        }

        // Subdomain matching: check if host ends with whitelisted domain
        foreach ($this->whitelistDomains as $whitelist => $reason) {
            // Skip if whitelist contains path (handled separately)
            if (str_contains($whitelist, '/')) {
                if (str_contains($url, $whitelist)) {
                    return true;
                }
                continue;
            }
            // Check if host is the whitelisted domain or a subdomain of it
            if ($host === $whitelist || str_ends_with($host, '.' . $whitelist)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check for @tiki-external-link-ok: reason comment on current or preceding lines.
     * Checks up to 5 lines back to handle heredocs.
     */
    private function hasSuppressionComment($content, $lineNumber)
    {
        $lines = explode("\n", $content);
        $lineIndex = $lineNumber - 1;

        for ($i = max(0, $lineIndex - 5); $i <= $lineIndex && $i < count($lines); $i++) {
            if (preg_match('/@tiki-external-link-ok:\s*(.+)/', $lines[$i], $matches)) {
                return [
                    'suppressed' => true,
                    'reason' => trim($matches[1]),
                ];
            }
        }

        return ['suppressed' => false, 'reason' => null];
    }


    /**
     * Check if a file is in a test directory
     */
    private function isTestFile($filePath)
    {
        $normalizedPath = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $filePath);
        return str_contains($normalizedPath, DIRECTORY_SEPARATOR . 'test' . DIRECTORY_SEPARATOR) ||
               str_contains($normalizedPath, DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR) ||
               str_contains($normalizedPath, DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR) ||
               preg_match('#[/\\\\]lib[/\\\\]test[/\\\\]#', $normalizedPath);
    }

    /**
     * Check if a line is inside a PHP multi-line comment block
     */
    private function isInCommentBlock($content, $lineNumber)
    {
        $lines = explode("\n", $content);
        $inComment = false;

        for ($i = 0; $i <= $lineNumber && $i < count($lines); $i++) {
            $line = $lines[$i];
            $trimmed = trim($line);

            // Check for comment start
            if (preg_match('/\/\*/', $line)) {
                // Check if it's a single-line comment /* ... */
                if (preg_match('/\/\*.*\*\//', $line)) {
                    continue;
                }
                $inComment = true;
            }

            // Check for comment end
            if ($inComment && preg_match('/\*\//', $line)) {
                $inComment = false;
            }
        }

        return $inComment;
    }

    private function categorizeUrl($url, $line, $filePath, $lineNumber, $fileContent = '')
    {
        if (! str_contains($line, 'http://') && ! str_contains($line, 'https://')) {
            return null;
        }

        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        $isTestFile = $this->isTestFile($filePath);

        // Check if line is inside a multi-line comment block (PHP/JS)
        $inCommentBlock = false;
        if (($extension === 'php' || $extension === 'js') && ! empty($fileContent)) {
            $inCommentBlock = $this->isInCommentBlock($fileContent, $lineNumber - 1);
        }

        // Skip if inside a comment block
        if ($inCommentBlock) {
            if (preg_match('/(https?:\/\/[^\s"' . "'" . '<>\])]+)/i', $line, $matches)) {
                return [
                    'severity' => 'low',
                    'context' => 'URL in comment block',
                    'url' => $matches[1],
                ];
            }
            return null;
        }

        // Check for single-line comments (//) BEFORE checking HTML tags
        // This handles cases where comments contain HTML examples
        $isComment = false;
        if ($extension === 'php' || $extension === 'js') {
            $trimmed = trim($line);
            if (str_starts_with($trimmed, '//') || str_starts_with($trimmed, '/*') || str_starts_with($trimmed, '*')) {
                $isComment = true;
            }
        }

        // If it's a single-line comment, categorize as LOW severity
        if ($isComment) {
            if (preg_match('/(https?:\/\/[^\s"' . "'" . '<>\])]+)/i', $line, $matches)) {
                return [
                    'severity' => 'low',
                    'context' => 'URL in comment',
                    'url' => $matches[1],
                ];
            }
            return null;
        }

        if (preg_match('/add_jsfile_cdn\s*\([^)]*[\'"](https?:\/\/[^\'"]+)[\'"]/i', $line, $matches)) {
            // Downgrade test files to LOW severity
            $severity = ($isTestFile) ? 'low' : 'critical';
            return [
                'severity' => $severity,
                'context' => 'add_jsfile_cdn() call',
                'url' => $matches[1],
            ];
        }

        if (preg_match('/add_jsfile_external\s*\([^)]*[\'"](https?:\/\/[^\'"]+)[\'"]/i', $line, $matches)) {
            // Downgrade test files to LOW severity
            $severity = ($isTestFile) ? 'low' : 'critical';
            return [
                'severity' => $severity,
                'context' => 'add_jsfile_external() call (should use local files)',
                'url' => $matches[1],
            ];
        }

        if (preg_match('/<script[^>]+src\s*=\s*[\'"](https?:\/\/[^\'"]+)[\'"]/i', $line, $matches)) {
            // Downgrade test files to LOW severity
            $severity = ($isTestFile) ? 'low' : 'critical';
            return [
                'severity' => $severity,
                'context' => '<script> tag with external src',
                'url' => $matches[1],
            ];
        }

        if (preg_match('/<link[^>]+href\s*=\s*[\'"](https?:\/\/[^\'"]+)[\'"]/i', $line, $matches)) {
            if (preg_match('/rel\s*=\s*[\'"]stylesheet[\'"]/i', $line)) {
                // Downgrade test files to LOW severity
                $severity = ($isTestFile) ? 'low' : 'critical';
                return [
                    'severity' => $severity,
                    'context' => '<link> tag with external stylesheet',
                    'url' => $matches[1],
                ];
            }
        }

        if (preg_match('/<a[^>]+href\s*=\s*[\'"](https?:\/\/[^\'"]+)[\'"]/i', $line, $matches)) {
            // Downgrade test files to LOW severity
            $severity = ($isTestFile) ? 'low' : 'high';
            return [
                'severity' => $severity,
                'context' => '<a> tag with external href',
                'url' => $matches[1],
            ];
        }

        if (preg_match('/<img[^>]+src\s*=\s*[\'"](https?:\/\/[^\'"]+)[\'"]/i', $line, $matches)) {
            // Downgrade test files to LOW severity
            $severity = ($isTestFile) ? 'low' : 'high';
            return [
                'severity' => $severity,
                'context' => '<img> tag with external src',
                'url' => $matches[1],
            ];
        }

        if (preg_match('/<iframe[^>]+src\s*=\s*[\'"](https?:\/\/[^\'"]+)[\'"]/i', $line, $matches)) {
            // Downgrade test files to LOW severity
            $severity = ($isTestFile) ? 'low' : 'high';
            return [
                'severity' => $severity,
                'context' => '<iframe> tag with external src',
                'url' => $matches[1],
            ];
        }

        if (preg_match('/<form[^>]+action\s*=\s*[\'"](https?:\/\/[^\'"]+)[\'"]/i', $line, $matches)) {
            // Downgrade test files to LOW severity
            $severity = ($isTestFile) ? 'low' : 'high';
            return [
                'severity' => $severity,
                'context' => '<form> tag with external action',
                'url' => $matches[1],
            ];
        }

        if ($extension === 'tpl' && preg_match('/(https?:\/\/[^\s"' . "'" . '<>\])]+)/i', $line, $matches)) {
            // Downgrade test files to LOW severity
            $severity = ($isTestFile) ? 'low' : 'high';
            return [
                'severity' => $severity,
                'context' => 'URL in template file (likely user-visible)',
                'url' => $matches[1],
            ];
        }

        if (
            $extension === 'sql' || $extension === 'md' || $extension === 'txt' ||
            basename($filePath) === 'README' || basename($filePath) === 'CHANGELOG' ||
            str_starts_with(basename($filePath), 'README') || str_starts_with(basename($filePath), 'readme')
        ) {
            if (preg_match('/(https?:\/\/[^\s"' . "'" . '<>\])]+)/i', $line, $matches)) {
                return [
                    'severity' => 'low',
                    'context' => 'URL in documentation file',
                    'url' => $matches[1],
                ];
            }
        }

        if (preg_match('/(https?:\/\/[^\s"' . "'" . '<>\])]+)/i', $line, $matches)) {
            return [
                'severity' => 'low',
                'context' => 'URL in source code',
                'url' => $matches[1],
            ];
        }

        return null;
    }

    private function scanFile($filePath, $relativePath)
    {
        $this->filesScanned++;

        $content = @file_get_contents($filePath);
        if ($content === false || str_contains($content, "\0")) {
            return;
        }

        $lines = explode("\n", $content);

        foreach ($lines as $lineNumber => $line) {
            $lineNumber++;

            if (! str_contains($line, 'http://') && ! str_contains($line, 'https://')) {
                continue;
            }

            $categorized = $this->categorizeUrl('', $line, $filePath, $lineNumber, $content);

            if ($categorized) {
                $this->allUrls[] = array_merge($categorized, [
                    'file' => $relativePath,
                    'line' => $lineNumber,
                ]);

                if (! $this->isWhitelisted($categorized['url'])) {
                // Check for inline suppression comment
                    $suppression = $this->hasSuppressionComment($content, $lineNumber);
                    if ($suppression['suppressed']) {
                        $this->suppressed[] = [
                        'file' => $relativePath,
                        'line' => $lineNumber,
                        'url' => $categorized['url'],
                        'context' => $categorized['context'],
                        'reason' => $suppression['reason'],
                        ];
                        continue; // Skip flagging this link
                    }

                    $this->severity[$categorized['severity']][] = [
                    'file' => $relativePath,
                    'line' => $lineNumber,
                    'url' => $categorized['url'],
                    'context' => $categorized['context'],
                    'code' => trim($line),
                    ];
                }
            }
        }
    }

    public function scan($specificFiles = [])
    {
        $totalFiles = 0;
        $this->fileScanner->scan(function ($filePath, $relativePath) use (&$totalFiles) {
            $this->scanFile($filePath, $relativePath);
            $totalFiles++;

            if ($totalFiles % 100 === 0) {
                echo "\rScanning... " . $totalFiles . " files processed";
            }
        }, $specificFiles);

        if ($totalFiles > 0) {
            echo "\r";
        }
    }

    public function getSummary()
    {
        return [
            'files_scanned' => $this->filesScanned,
            'critical_count' => count($this->severity['critical']),
            'high_count' => count($this->severity['high']),
            'low_count' => count($this->severity['low']),
            'suppressed_count' => count($this->suppressed),
            'total' => count($this->severity['critical']) + count($this->severity['high']) + count($this->severity['low']),
        ];
    }

    public function getFindings()
    {
        return $this->severity;
    }

    public function getSuppressed()
    {
        return $this->suppressed;
    }

    public function hasIssues()
    {
        return ! empty($this->severity['critical']) || ! empty($this->severity['high']) || ! empty($this->severity['low']);
    }

    /**
     * Directories to skip when checking URL availability (test fixtures, 3rd party libs)
     */
    private const EXCLUDED_DIRECTORIES = [
        'test', 'tests', 'fixtures', 'tikihelp', 'xmpp',
        'nusoap', 'openlayers', 'metadata', 'dracula'
    ];

    /**
     * Directory paths with subdirectory structure to exclude
     */
    private const EXCLUDED_DIRECTORY_PATHS = [
        'Sheet' . DIRECTORY_SEPARATOR . 'include'
    ];

    /**
     * Placeholder/package registry domain patterns that cannot or should not be checked
     */
    private const PLACEHOLDER_DOMAINS = '#(anothersite|testuri|dummydsn|yourdomain|tiki-site)#i';

    /**
     * XML namespace URI patterns (identifiers, not fetchable URLs)
     */
    private const NAMESPACE_PATTERNS = '#/200[0-9]/|/ns/|/protocol/|/schema/|\\}#';

    /**
     * Get unique URLs for availability checking, with smart filtering.
     *
     * @param array|null $severityFilter Array of severities to include (e.g., ['critical', 'high'])
     * @return array Array of URL data for checking
     */
    public function getUrlsForAvailabilityCheck($severityFilter = null)
    {
        $urls = [];
        $seenUrls = [];

        foreach ($this->allUrls as $finding) {
            if ($severityFilter !== null && ! in_array($finding['severity'], $severityFilter)) {
                continue;
            }

            if ($this->shouldSkipUrl($finding, $seenUrls)) {
                continue;
            }

            $seenUrls[$finding['url']] = true;
            $urls[] = [
                'url' => $finding['url'],
                'file' => $finding['file'],
                'line' => $finding['line'],
                'severity' => $finding['severity'],
            ];
        }

        return $urls;
    }

    /**
     * Determine if a URL should be skipped from availability checking.
     */
    private function shouldSkipUrl(array $finding, array $seenUrls): bool
    {
        $url = $finding['url'];
        $parsed = parse_url($url);
        $host = strtolower($parsed['host'] ?? '');

        // Check in order of least to most expensive
        return isset($seenUrls[$url])
            || ! $this->hasValidHost($parsed)
            || $this->isReservedDomain($host)
            || $this->isExcludedDirectory($finding['file'])
            || $this->isNamespaceUri($url)
            || $this->isPlaceholderUrl($url, $host)
            || $this->isDevelopmentUrl($host)
            || $this->isIncompleteUrl($url);
    }

    private function hasValidHost(array|false|null $parsed): bool
    {
        if (! is_array($parsed) || ! isset($parsed['host']) || empty($parsed['host'])) {
            return false;
        }
        // Reject hosts that are only punctuation (malformed like "http://,")
        return (bool) preg_match('/[a-z0-9]/i', $parsed['host']);
    }

    private function isReservedDomain(string $host): bool
    {
        // Strip trailing dot (DNS root) for matching
        $host = rtrim($host, '.');
        // Only reserve example.org as per Tiki standard (see RFC 2606)
        return $host === 'example.org' || str_ends_with($host, '.example.org');
    }

    private function isExcludedDirectory(string $filePath): bool
    {
        // Skip lang/ directory (translation examples)
        if (str_contains($filePath, 'lang' . DIRECTORY_SEPARATOR)) {
            return true;
        }

        if (str_ends_with($filePath, 'package-lock.json')) {
            return true;
        }

        $normalizedFile = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $filePath);

        foreach (self::EXCLUDED_DIRECTORIES as $dir) {
            if (str_contains($normalizedFile, DIRECTORY_SEPARATOR . $dir . DIRECTORY_SEPARATOR)) {
                return true;
            }
        }

        foreach (self::EXCLUDED_DIRECTORY_PATHS as $path) {
            if (str_contains($normalizedFile, DIRECTORY_SEPARATOR . $path . DIRECTORY_SEPARATOR)) {
                return true;
            }
        }

        return false;
    }

    private function isNamespaceUri(string $url): bool
    {
        return (bool) preg_match(self::NAMESPACE_PATTERNS, $url);
    }

    private function isPlaceholderUrl(string $url, string $host): bool
    {
        // Template/placeholder URL patterns
        if (preg_match('#^https?://%|^https?://path_to_#', $url)) {
            return true;
        }
        // Malformed URLs (regex patterns, escape sequences, no TLD)
        if (preg_match('#\\\\|^https?://[^/]+,$|^https?://[a-z]+$#', $url)) {
            return true;
        }
        // Incomplete API base URLs (ending with / followed by concatenation)
        if (preg_match('#(repo\.packagist\.org|api\.github\.com|registry\.npmjs\.org)/[^/]*/?$#', $url)) {
            return true;
        }
        // Placeholder domains
        if (! empty($host) && preg_match(self::PLACEHOLDER_DOMAINS, $host)) {
            return true;
        }
        return false;
    }

    private function isDevelopmentUrl(string $host): bool
    {
        return $host === 'localhost'
            || str_starts_with($host, '127.')
            || $host === 'foo'
            || $host === 'foo.bar';
    }

    private function isIncompleteUrl(string $url): bool
    {
        // URLs with PHP variables
        if (preg_match('/\$[a-zA-Z_]|\{\$|%[sd]/', $url)) {
            return true;
        }
        // Search engine base URLs (query parameter will be appended at runtime)
        if (preg_match('/[?&](q|query|p|search|eq|url)=$/i', $url)) {
            return true;
        }
        // Templated/incomplete URLs
        return str_contains($url, '...') || str_contains($url, '{') || str_contains($url, '[');
    }
}



// Only run CLI entry point when executed directly
if (realpath(__FILE__) !== realpath($_SERVER['argv'][0] ?? '')) {
    return;
}

$dir = realpath(__DIR__ . '/../../');
$paramList = $_SERVER['argv'] ?? [];
$listFiles = [];
$ciMode = false;


foreach ($paramList as $param) {
    if ($param === '--ci') {
        $ciMode = true;
    } elseif (basename(__FILE__) != basename($param)) {
        $file = $dir . DIRECTORY_SEPARATOR . $param;
        if (file_exists($file)) {
            $listFiles[] = $param;
        }
    }
}

$checker = new ExternalLinksChecker($dir, $ciMode);
$checker->scan($listFiles);

$summary = $checker->getSummary();
$findings = $checker->getFindings();
$suppressed = $checker->getSuppressed();



echo PHP_EOL;
info('Scanning for external links...');
info($summary['files_scanned'] . ' files scanned' . PHP_EOL);

if ($checker->hasIssues()) {
    if (! empty($findings['critical'])) {
        echo PHP_EOL;
        echo color('CRITICAL (External Code): ' . count($findings['critical']) . ' found', 'red') . PHP_EOL;
        echo str_repeat('=', 80) . PHP_EOL;
        foreach ($findings['critical'] as $finding) {
            echo color($finding['file'] . ':' . $finding['line'], 'yellow') . PHP_EOL;
            echo '  URL: ' . color($finding['url'], 'red') . PHP_EOL;
            echo '  Context: ' . $finding['context'] . PHP_EOL;
            echo '  Code: ' . substr($finding['code'], 0, 100) . (strlen($finding['code']) > 100 ? '...' : '') . PHP_EOL;
            echo PHP_EOL;
        }
    }

    if (! empty($findings['high'])) {
        echo PHP_EOL;
        echo color('HIGH (User Visible): ' . count($findings['high']) . ' found', 'yellow') . PHP_EOL;
        echo str_repeat('=', 80) . PHP_EOL;
        foreach ($findings['high'] as $finding) {
            echo color($finding['file'] . ':' . $finding['line'], 'yellow') . PHP_EOL;
            echo '  URL: ' . $finding['url'] . PHP_EOL;
            echo '  Context: ' . $finding['context'] . PHP_EOL;
            echo '  Code: ' . substr($finding['code'], 0, 100) . (strlen($finding['code']) > 100 ? '...' : '') . PHP_EOL;
            echo PHP_EOL;
        }
    }

    if (! empty($findings['low'])) {
        echo PHP_EOL;
        echo color('LOW (Source Code): ' . count($findings['low']) . ' found', 'blue') . PHP_EOL;
        echo str_repeat('=', 80) . PHP_EOL;
        $lowDisplay = array_slice($findings['low'], 0, 20);
        foreach ($lowDisplay as $finding) {
            echo $finding['file'] . ':' . $finding['line'] . PHP_EOL;
            echo '  URL: ' . $finding['url'] . PHP_EOL;
            echo '  Context: ' . $finding['context'] . PHP_EOL;
            echo PHP_EOL;
        }
        if (count($findings['low']) > 20) {
            echo color('... and ' . (count($findings['low']) - 20) . ' more low severity findings', 'blue') . PHP_EOL;
        }
    }

    echo PHP_EOL;
    echo str_repeat('=', 80) . PHP_EOL;
    echo 'Summary:' . PHP_EOL;
    echo '  CRITICAL (External Code): ' . color($summary['critical_count'], 'red') . PHP_EOL;
    echo '  HIGH (User Visible): ' . color($summary['high_count'], 'yellow') . PHP_EOL;
    echo '  LOW (Source Code): ' . color($summary['low_count'], 'blue') . PHP_EOL;
    if ($summary['suppressed_count'] > 0) {
        echo '  SUPPRESSED (with @tiki-external-link-ok): ' . color($summary['suppressed_count'], 'green') . PHP_EOL;
    }

    echo '  Total flagged: ' . $summary['total'] . ' external links found' . PHP_EOL;
    echo PHP_EOL;

    // Exit with error if CRITICAL issues found
    if (! empty($findings['critical'])) {
        echo color('ERROR: CRITICAL external links detected. These must be fixed before merging.', 'red') . PHP_EOL;
        echo PHP_EOL;
        exit(1);
    } else {
        echo color('WARNING: External links detected (HIGH/LOW severity). Please review and consider replacing with local alternatives.', 'yellow') . PHP_EOL;
        echo PHP_EOL;
        exit(0);
    }
} else {
    echo PHP_EOL;
    if ($summary['suppressed_count'] > 0) {
        important('No external links flagged (' . $summary['suppressed_count'] . ' suppressed with @tiki-external-link-ok).');
    } else {
        important('No external links found (excluding whitelisted domains).');
    }
    echo PHP_EOL;
    exit(0);
}
