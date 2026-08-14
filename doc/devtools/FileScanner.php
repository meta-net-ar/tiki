<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace TikiDevTools;

require_once __DIR__ . '/../../path_constants.php';

/**
 * Common file scanner utility for CI checks
 *
 * Provides standardized directory traversal with common exclusion patterns
 * to reduce code duplication across CI scripts.
 */
class FileScanner
{
    private $dir;
    private $excludePatterns = [];
    private $extensions = [];
    private $additionalExclusions = [];

    public function __construct($dir)
    {
        $this->dir = realpath($dir);
        $this->setupCommonExclusions();
    }

    /**
     * Setup common exclusion patterns used across all CI checks
     */
    private function setupCommonExclusions()
    {
        $this->excludePatterns = [
            $this->dir . '/' . TIKI_VENDOR_NONBUNDLED_PATH,
            $this->dir . '/' . TIKI_VENDOR_BUNDLED_TOPLEVEL_PATH,
            $this->dir . '/' . TIKI_VENDOR_CUSTOM_PATH,
            $this->dir . '/' . TEMP_PATH,
            $this->dir . '/' . PUBLIC_GENERATED_PATH,
            $this->dir . '/.git',
            $this->dir . '/.gitlab-ci-local',
        ];
    }

    /**
     * Add additional exclusion patterns specific to this scanner
     */
    public function addExclusion($pattern)
    {
        $this->additionalExclusions[] = $pattern;
    }

    /**
     * Set file extensions to scan
     */
    public function setExtensions(array $extensions)
    {
        $this->extensions = $extensions;
    }

    /**
     * Check if a file or directory path should be excluded
     */
    private function shouldExclude($filePath)
    {
        $normalizedPath = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $filePath);

        $allExclusions = array_merge($this->excludePatterns, $this->additionalExclusions);

        foreach ($allExclusions as $pattern) {
            $normalizedPattern = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $pattern);
            if (
                str_starts_with($normalizedPath, $normalizedPattern) ||
                str_contains($normalizedPath, $normalizedPattern . DIRECTORY_SEPARATOR)
            ) {
                return true;
            }
        }

        if (
            preg_match('#[/\\\\]vendor_bundled[/\\\\]#', $normalizedPath) ||
            (preg_match('#[/\\\\]vendor[/\\\\]#', $normalizedPath) && ! preg_match('#[/\\\\]vendor_bundled#', $normalizedPath)) ||
            preg_match('#[/\\\\]vendor_custom[/\\\\]#', $normalizedPath) ||
            preg_match('#[/\\\\]node_modules[/\\\\]#', $normalizedPath)
        ) {
            return true;
        }

        return false;
    }

    /**
     * Check if file extension matches allowed extensions
     */
    public function matchesExtension($filePath)
    {
        if (empty($this->extensions)) {
            return true;
        }

        $fileInfo = pathinfo($filePath);
        if (! isset($fileInfo['extension'])) {
            return false;
        }

        return in_array($fileInfo['extension'], $this->extensions);
    }

    /**
     * Get iterator for files to scan
     *
     * @param array $specificFiles Optional array of specific file paths to scan
     * @return \Iterator
     */
    public function getIterator($specificFiles = [])
    {
        if (! empty($specificFiles)) {
            return new \ArrayIterator($specificFiles);
        }

        $dirIterator = new \RecursiveDirectoryIterator(
            $this->dir,
            \RecursiveDirectoryIterator::SKIP_DOTS
        );

        $filterIterator = new \RecursiveCallbackFilterIterator(
            $dirIterator,
            function ($current, $key, $iterator) {
                $filePath = $current->getPathname();

                if ($this->shouldExclude($filePath)) {
                    return false;
                }

                return true;
            }
        );

        return new \RecursiveIteratorIterator(
            $filterIterator,
            \RecursiveIteratorIterator::LEAVES_ONLY
        );
    }

    /**
     * Iterate through files and call callback for each matching file
     *
     * @param callable $callback Function to call for each file: function($filePath, $relativePath)
     * @param array $specificFiles Optional array of specific file paths to scan
     * @return int Number of files processed
     */
    public function scan(callable $callback, $specificFiles = [])
    {
        $count = 0;
        $iterator = $this->getIterator($specificFiles);

        foreach ($iterator as $item) {
            $filePath = null;

            if ($item instanceof \SplFileInfo) {
                $filePath = $item->getPathname();
            } elseif (is_string($item)) {
                $filePath = $this->dir . DIRECTORY_SEPARATOR . $item;
                if (! file_exists($filePath)) {
                    continue;
                }
            } else {
                continue;
            }

            if ($this->shouldExclude($filePath)) {
                continue;
            }

            if (! $this->matchesExtension($filePath)) {
                continue;
            }

            $relativePath = str_replace($this->dir . DIRECTORY_SEPARATOR, '', $filePath);
            $callback($filePath, $relativePath);
            $count++;
        }

        return $count;
    }

    /**
     * Get the base directory being scanned
     */
    public function getDir()
    {
        return $this->dir;
    }
}
