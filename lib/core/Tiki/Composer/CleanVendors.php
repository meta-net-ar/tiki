<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Composer;

require_once __DIR__ . '/../../../../path_constants.php';

use Composer\Script\Event;
use Composer\Util\FileSystem;
use Exception;

class CleanVendors
{
    /**
     * @var array Files or directories to remove anywhere in vendor files. Case-insensitive. Must specify as lower case.
     */
    private static array $standardFiles = [
        '.coveralls.yml',
        '.editorconfig',
        '.empty',
        '.env.local',
        '.eslintignore',
        '.eslintrc',
        '.eslintrc.json',
        '.gitattributes',
        '.github',
        '.github/workflows',
        '.gitignore',
        '.gitmodules',
        '.hound.yml',
        '.jscsrc',
        '.jshintignore',
        '.jshintrc',
        '.mailmap',
        '.npmignore',
        '.php_cs',
        '.php_cs.dist',
        '.scrutinizer.yml',
        '.styleci.yml',
        '.travis.install.sh',
        '.travis.yml',
        '_translationstatus.txt',
        'appveyor.yml',
        'authors',
        'authors.txt',
        'bower.json',
        'building.md',
        'changelog',
        'changelog.md',
        'changelog.txt',
        'changes.md',
        'changes.md~',
        'changes.txt',
        'cname',
        'code_of_conduct.md',
        'composer.json',
        'composer.lock',
        'conduct.md',
        'contributing.md',
        'credits.md',
        'demo',
        'demo.html',
        'demo.js',
        'demo1',
        'demo2',
        'demos',
        'demos.html',
        'development',
        'devtools',
        'doc',
        'docs',
        'documentation',
        'docker-compose.yml',
        'example',
        'example.html',
        'example.md',
        'examples',
        'gemfile',
        'gemfile.lock',
        'gruntfile.coffee',
        'gruntfile.js',
        'history.md',
        'index.html',
        'info.txt',
        'install',
        'makefile',
        'news',
        'notice',
        'package.json',
        'phpunit.xml.dist',
        'psalm.xml',
        'readme',
        'readme.md',
        'readme.markdown',
        'readme.mdown',
        'readme.php',
        'readme.rst',
        'readme.textile',
        'readme.txt',
        'robots.txt',
        'sample',
        'samples',
        'security.md',
        'support.md',
        'test.html',
        'tests',
        'todo',
        'todo.md',
        'upgrading.md',
        'www',
    ];

    private static int $deletedFiles = 0;
    private static float $deletedSize = 0.0;

    /**
     * Unique affected packages set: ["vendor/package" => true]
     */
    private static array $affectedPackages = [];

    private static array $deletedPaths = [];
    private static float $startTime = 0.0;

    private static bool $enableLog = false;
    private static string $logFile = '';
    private static string $jsonFile = '';
    private static string $logDir = '';

    public static function clean(Event $event): void
    {
        self::resetCounters();
        self::$startTime = microtime(true);

        $io = $event->getIO();

        $vendorsRoot = rtrim(
            (string) $event->getComposer()->getConfig()->get('vendor-dir'),
            DIRECTORY_SEPARATOR
        ) . DIRECTORY_SEPARATOR;

        // Parse options like --log, --log-file=..., --json-file=...
        self::parseLogOptions($event);

        $io->write('<info>Cleaning vendor directories...</info>');
        self::logMessage('Starting vendor directories cleanup');

        self::removeStandard($vendorsRoot, $vendorsRoot, $event);
        self::addIndexFiles($vendorsRoot);

        $executionTime = round(microtime(true) - self::$startTime, 2);
        $deletedSizeMb = round(self::$deletedSize / (1024 * 1024), 2);
        $depsCount = count(self::$affectedPackages);

        $summary = sprintf(
            '%d files (%.2f MB) removed from %d dependencies in %.2f seconds',
            self::$deletedFiles,
            $deletedSizeMb,
            $depsCount,
            $executionTime
        );

        // Console output
        $io->write("<info>$summary</info>");
        self::logMessage($summary);

        if ($io->isVerbose()) {
            $io->write('<comment>Deleted files:</comment>');
            foreach (self::$deletedPaths as $path) {
                $io->write("  $path");
            }
        }

        // Log + JSON reports if enabled
        if (self::$enableLog) {
            self::writeLogDetails();
            self::writeJsonReport($vendorsRoot, $summary, $executionTime, $deletedSizeMb, $depsCount);

            $io->write("<info>Full report logged to: " . self::$logFile . "</info>");
            $io->write("<info>JSON report saved to: " . self::$jsonFile . "</info>");
        }
    }

    private static function resetCounters(): void
    {
        self::$deletedFiles = 0;
        self::$deletedSize = 0.0;
        self::$affectedPackages = [];
        self::$deletedPaths = [];
        self::$enableLog = false;
        self::$logFile = '';
        self::$jsonFile = '';
        self::$logDir = '';
        self::$startTime = 0.0;
    }

    /**
    *Logging is enabled automatically when Composer runs in verbose mode:
    *  - -v / --verbose / -vv / -vvv (enables logging)
    *  - --log-file=/path/to/file.log
    *  - --json-file=/path/to/file.json
    */
    private static function parseLogOptions(Event $event): void
    {
        $io = $event->getIO();

        $isVerbose = $io->isVerbose();          // -v / --verbose
        $isVeryVerbose = $io->isVeryVerbose();  // -vv
        $isDebug = $io->isDebug();              // -vvv

        if (! ($isVerbose || $isVeryVerbose || $isDebug)) {
            return;
        }

        self::$enableLog = true;

        $tikiRoot = dirname(__DIR__, 4);
        self::$logDir = self::getDefaultLogDirectory($tikiRoot);

        $logFile = self::$logDir . '/composer_clean.log';
        $jsonFile = self::$logDir . '/composer_clean.json';

        self::$logFile = self::normalizePath($logFile);
        self::$jsonFile = self::normalizePath($jsonFile);

        self::ensureDirectory(dirname(self::$logFile));
        self::ensureDirectory(dirname(self::$jsonFile));

        self::initLogFile();
    }

    private static function ensureDirectory(string $dir): void
    {
        if (is_dir($dir)) {
            return;
        }

        if (! mkdir($dir, 0755, true) && ! is_dir($dir)) {
            throw new \RuntimeException("Failed to create directory: {$dir}");
        }
    }

    private static function initLogFile(): void
    {
        file_put_contents(
            self::$logFile,
            "=== Vendor Cleanup Log ===\n" . "Date: " . date('Y-m-d H:i:s') . "\n" . "\n\n"
        );
    }

    /**
     * Determine the default directory used to store Composer vendor cleanup reports and diagnostics.
     *
     * @param string $tikiRoot The root directory of the Tiki installation
     * @return string The path to the temp directory
     */
    private static function getDefaultLogDirectory(string $tikiRoot): string
    {

        $logDir = $tikiRoot . DIRECTORY_SEPARATOR . CLEAN_VENDOR_LOG_PATH;

        if (! is_dir($logDir)) {
            if (! mkdir($logDir, 0755, true) && ! is_dir($logDir)) {
                throw new \RuntimeException('Failed to create log directory: ' . $logDir);
            }
        }

        return $logDir;
    }

    private static function normalizePath(string $path): string
    {
        // Keep as-is if absolute, otherwise resolve relative to current working directory
        if ($path === '') {
            return $path;
        }
        $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);

        // absolute on Unix
        if (str_starts_with($path, DIRECTORY_SEPARATOR)) {
            return $path;
        }

        // absolute on Windows (e.g. C:\)
        if (preg_match('/^[A-Za-z]:\\\\/', $path) === 1) {
            return $path;
        }

        return getcwd() . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR);
    }

    private static function logMessage(string $message): void
    {
        if (! self::$enableLog || self::$logFile === '') {
            return;
        }
        @file_put_contents(self::$logFile, $message . "\n", FILE_APPEND);
    }

    private static function writeLogDetails(): void
    {
        if (! self::$enableLog || self::$logFile === '') {
            return;
        }

        $logContent = "\nDeleted files:\n";
        foreach (self::$deletedPaths as $path) {
            $logContent .= "- $path\n";
        }

        @file_put_contents(self::$logFile, $logContent, FILE_APPEND);
    }

    private static function writeJsonReport(
        string $vendorsRoot,
        string $summary,
        float $executionTime,
        float $deletedSizeMb,
        int $depsCount
    ): void {
        if (! self::$enableLog || self::$jsonFile === '') {
            return;
        }

        $data = [
            'timestamp' => date('c'),
            'vendorDir' => $vendorsRoot,
            'summary' => $summary,
            'deletedFiles' => self::$deletedFiles,
            'deletedSizeMb' => $deletedSizeMb,
            'affectedDependencies' => $depsCount,
            'durationSeconds' => $executionTime,
            'deletedPaths' => self::$deletedPaths,
            'affectedPackages' => array_keys(self::$affectedPackages),
        ];

        @file_put_contents(self::$jsonFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    private static function addIndexFiles(string $path): void
    {
        $excludeDirs = [
            'phpseclib/phpseclib/phpseclib/Crypt',
            'phpunit/phpunit/schema',
            'rector/rector',
        ];

        $path = rtrim(str_replace('/', DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        // If directory is empty (including hidden files), add index.php
        if (self::isDirEmpty($path)) {
            $indexPath = $path . 'index.php';
            if (! file_exists($indexPath)) {
                @file_put_contents($indexPath, "<?php\nexit;\n");
            }
            return;
        }

        $dirs = glob($path . '{,.}*[!.]', GLOB_MARK | GLOB_BRACE | GLOB_ONLYDIR);
        if (! $dirs) {
            return;
        }

        foreach ($dirs as $dir) {
            $dir = rtrim(str_replace('/', DIRECTORY_SEPARATOR, $dir), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
            if (! array_filter($excludeDirs, fn($item) => str_contains(str_replace(DIRECTORY_SEPARATOR, '/', $dir), $item))) {
                self::addIndexFiles($dir);
            }
        }
    }

    private static function isDirEmpty(string $dir): bool
    {
        return ! is_dir($dir) || ! (new \FilesystemIterator($dir, \FilesystemIterator::SKIP_DOTS))->valid();
    }

    private static function removeStandard(string $vendorsRoot, string $vendorsRootBase, Event $event): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($vendorsRoot, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        $fs = new FileSystem();

        foreach ($iterator as $file) {
            $path = $file->getPathname();

            $normalized = strtolower(str_replace('\\', '/', $path));

            if (self::isStandardPath($path)) {
                try {
                    $size = $file->isDir()
                        ? self::getDirectorySize($path)
                        : (float) $file->getSize();

                    $fs->remove($path);

                    self::$deletedFiles++;
                    self::$deletedSize += $size;
                    self::$deletedPaths[] = $path;

                    $packageName = self::extractPackageName($path, $vendorsRootBase);
                    if ($packageName) {
                        self::$affectedPackages[$packageName] = true;
                    }
                } catch (Exception $e) {
                    $event->getIO()->writeError("Error deleting $path: " . $e->getMessage());
                }
            }
        }
    }

    private static function isStandardPath(string $path): bool
    {
        $basename = strtolower(basename($path));
        if (in_array($basename, self::$standardFiles, true)) {
            return true;
        }

        // also match entries like ".github/workflows" by checking path tail
        $norm = strtolower(str_replace(DIRECTORY_SEPARATOR, '/', $path));

        foreach (self::$standardFiles as $entry) {
            // exact tail match:
            // ".../.github/workflows" or ".../docs"
            if (str_ends_with($norm, '/' . $entry) || $norm === $entry) {
                return true;
            }
        }

        return false;
    }

    /**
     * Extract "vendor/package" from an absolute path under vendor-dir.
     */
    private static function extractPackageName(string $path, string $vendorsRoot): ?string
    {
        $path = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $vendorsRoot = rtrim($vendorsRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        if (strpos($path, $vendorsRoot) !== 0) {
            return null;
        }

        $relative = substr($path, strlen($vendorsRoot)); // e.g. symfony/console/...
        $relative = trim($relative, DIRECTORY_SEPARATOR);

        if ($relative === '') {
            return null;
        }

        $parts = array_values(array_filter(explode(DIRECTORY_SEPARATOR, $relative)));
        if (count($parts) >= 2) {
            return $parts[0] . '/' . $parts[1];
        }

        return null;
    }

    private static function getDirectorySize(string $directory): float
    {
        $size = 0.0;

        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                /** @var \SplFileInfo $file */
                if ($file->isFile()) {
                    $size += (float) $file->getSize();
                }
            }
        } catch (Exception $e) {
            // If size calculation fails, don't block cleanup
            return 0.0;
        }

        return $size;
    }
}
