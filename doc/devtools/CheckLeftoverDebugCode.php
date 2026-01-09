<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace TikiDevTools;

/**
 * Detect leftover debug code introduced in a merge request.
 * The script scans ONLY:
 *  - files added or modified
 *  - lines added in the diff (git diff -U0)
 */

class CheckLeftoverDebugCode
{
    /**
     * Entry point
     */
    public function execute()
    {
        echo "\033[0;32mChecking for leftover debug code...\033[0m" . PHP_EOL;

        $diff = $this->getDiff();
        $filesWithLines = $this->parseAddedLines($diff);

        if (empty($filesWithLines)) {
            echo "\033[0;32mNo changed files detected\033[0m" . PHP_EOL;
        } else {
            $errorCount = $this->scanAddedLines($filesWithLines);

            echo "\033[0;31m{$errorCount} debug issues found\033[0m" . PHP_EOL;

            if ($errorCount > 0) {
                exit(1);
            }
        }

        echo "\033[0;32mCompleted\033[0m" . PHP_EOL;
    }

    /**
     * Get git diff with no context (added lines only)
     */
    protected function getDiff(): array
    {
        $baseSha = getenv('CI_MERGE_REQUEST_DIFF_BASE_SHA');
        $cmd = sprintf(
            'git diff -U0 %s...HEAD',
            escapeshellarg($baseSha)
        );

        exec($cmd, $output, $returnCode);

        if ($returnCode !== 0) {
            echo "\033[0;31mUnable to retrieve git diff\033[0m" . PHP_EOL;
            exit(1);
        }

        return $output;
    }

    /**
     * Extract added lines per file from git diff
     *
     * @return array [file => [lines]]
     */
    protected function parseAddedLines(array $diff): array
    {
        $files = [];
        $currentFile = null;

        foreach ($diff as $line) {
            if (preg_match('#^\+\+\+ b/(.+)$#', $line, $m)) {
                $currentFile = $m[1];
                continue;
            }

            if (
                $currentFile &&
                str_starts_with($line, '+') &&
                ! str_starts_with($line, '+++')
            ) {
                $files[$currentFile][] = substr($line, 1);
            }
        }

        return $files;
    }

    /**
     * Scan only added lines for debug patterns
     */
    protected function scanAddedLines(array $filesWithLines): int
    {
        $errorCount = 0;

        foreach ($filesWithLines as $file => $lines) {
            if (! is_file($file)) {
                continue;
            }

            $ext = pathinfo($file, PATHINFO_EXTENSION);
            $patterns = $this->getPatternsForExtension($ext);

            if (empty($patterns)) {
                continue;
            }

            foreach ($lines as $line) {
                foreach ($patterns as $pattern) {
                    if (preg_match($pattern, $line)) {
                        $this->printMessage(
                            "$file: leftover debug code detected → $line"
                        );
                        $errorCount++;
                        break 2;
                    }
                }
            }
        }

        return $errorCount;
    }

    /**
     * Debug patterns per language
     */
    protected function getPatternsForExtension(string $ext): array
    {
        $patterns = [
            'php' => [
                '/\bvar_dump\s*\(/',
                '/\bprint_r\s*\(/',
                '/\berror_log\s*\(/',
            ],
            'js' => [
                '/console\.log\s*\(/',
            ],
        ];

        return $patterns[$ext] ?? [];
    }

    /**
     * Print error message
     */
    protected function printMessage(string $message): void
    {
        echo "\033[0;31m" . $message . "\033[0m" . PHP_EOL;
    }
}

if (PHP_SAPI !== 'cli') {
    die('Please run this script from the command line');
}

$checker = new CheckLeftoverDebugCode();
$checker->execute();
