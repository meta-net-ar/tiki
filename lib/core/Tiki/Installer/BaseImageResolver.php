<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Installer;

use Exception;

class BaseImageResolver
{
    private string $tikiRoot;
    private string $dbDirectory;

    public function __construct(string $tikiRoot)
    {
        $this->tikiRoot = rtrim($tikiRoot, DIRECTORY_SEPARATOR);
        $dbDirectory = realpath($this->tikiRoot . DIRECTORY_SEPARATOR . 'db');

        if ($dbDirectory === false || ! is_dir($dbDirectory)) {
            throw new Exception('Fatal: Cannot access installer db directory');
        }

        $this->dbDirectory = $dbDirectory;
    }

    public function resolve(): ?string
    {
        $config = $this->loadConfiguration();

        if ($config === null) {
            return null;
        }

        return match ($config['type']) {
            'local' => $this->resolveLocalSource($config['file'] ?? ''),
            'http', 'https' => throw new Exception(
                'Fatal: Remote install base images are no longer supported. Download the SQL dump into db/ and use source.type=local.'
            ),
            default => throw new Exception('Fatal: Unsupported install base image source type "' . $config['type'] . '".'),
        };
    }

    private function loadConfiguration(): ?array
    {
        $iniFile = $this->dbDirectory . DIRECTORY_SEPARATOR . 'install.ini';

        if (! is_readable($iniFile)) {
            return null;
        }

        $ini = parse_ini_file($iniFile);

        if ($ini === false) {
            throw new Exception('Fatal: Cannot parse ' . $iniFile);
        }

        if (empty($ini['source.type'])) {
            return null;
        }

        return [
            'type' => strtolower(trim($ini['source.type'])),
            'file' => $ini['source.file'] ?? '',
        ];
    }

    private function resolveLocalSource(string $file): string
    {
        $file = trim($file);

        if ($file === '') {
            $file = 'custom_tiki.sql';
        }

        if (str_contains($file, '://')) {
            throw new Exception('Fatal: Local install base image path is invalid.');
        }

        $resolved = $this->resolvePath($file);

        if ($resolved === null || ! is_file($resolved) || ! is_readable($resolved)) {
            throw new Exception('Fatal: Cannot open ' . $file);
        }

        if (! $this->pathIsWithinDirectory($resolved, $this->dbDirectory)) {
            throw new Exception('Fatal: Local install base images must be stored inside db/.');
        }

        return $resolved;
    }

    private function resolvePath(string $file): ?string
    {
        $candidate = $this->isAbsolutePath($file)
            ? $file
            : $this->buildRelativeCandidate($file);

        $resolved = realpath($candidate);

        return $resolved === false ? null : $resolved;
    }

    private function buildRelativeCandidate(string $file): string
    {
        $normalized = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, ltrim($file, '/\\'));

        if ($normalized === 'db' || str_starts_with($normalized, 'db' . DIRECTORY_SEPARATOR)) {
            return $this->tikiRoot . DIRECTORY_SEPARATOR . $normalized;
        }

        return $this->dbDirectory . DIRECTORY_SEPARATOR . $normalized;
    }

    private function pathIsWithinDirectory(string $path, string $directory): bool
    {
        $normalizedPath = $this->normalizePath($path);
        $normalizedDirectory = rtrim($this->normalizePath($directory), '/');

        return $normalizedPath === $normalizedDirectory
            || str_starts_with($normalizedPath, $normalizedDirectory . '/');
    }

    private function normalizePath(string $path): string
    {
        $normalized = str_replace('\\', '/', $path);

        if (DIRECTORY_SEPARATOR === '\\') {
            $normalized = strtolower($normalized);
        }

        return $normalized;
    }

    private function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, DIRECTORY_SEPARATOR)
            || preg_match('/^[A-Za-z]:[\\\\\\/]/', $path) === 1;
    }
}
