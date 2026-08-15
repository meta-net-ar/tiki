<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Zotero;

use RuntimeException;
use Tiki\FileGallery\File;
use Tiki\FileGallery\FileWrapper\WrapperInterface;

class PandocLibraryFile
{
    /**
     * Keep the wrapper alive while Pandoc reads a temporary file created for DB/Flysystem storage.
     */
    private ?WrapperInterface $wrapper;
    private ?string $temporaryJsonPath;

    private function __construct(
        private string $path,
        private string $fingerprint,
        ?WrapperInterface $wrapper = null,
        ?string $temporaryJsonPath = null
    ) {
        $this->wrapper = $wrapper;
        $this->temporaryJsonPath = $temporaryJsonPath;
    }

    public function __destruct()
    {
        if ($this->temporaryJsonPath !== null && is_file($this->temporaryJsonPath)) {
            @unlink($this->temporaryJsonPath);
        }
    }

    public static function fromPreference(string $preference): self
    {
        $preference = trim($preference);

        if ($preference === '') {
            throw new RuntimeException('No Zotero CSL JSON library file selected.');
        }

        if (ctype_digit($preference) && (int) $preference > 0) {
            return self::fromFileId((int) $preference);
        }

        // Compatibility for existing MR test instances configured with a server path.
        return self::fromPath($preference);
    }

    public static function fromFileId(int $fileId): self
    {
        $file = File::id($fileId);
        if (! $file->exists()) {
            throw new RuntimeException('Library file not found in File Gallery: fileId ' . $fileId);
        }

        $wrapper = $file->getWrapper();
        $path = $wrapper->getReadableFile();
        if (! is_file($path)) {
            throw new RuntimeException('Library file is not readable in File Gallery: fileId ' . $fileId);
        }

        $fingerprint = implode('|', [
            'fileId:' . $fileId,
            (string) ($file->lastModif ?? 0),
            (string) ($file->filesize ?? 0),
            (string) ($file->hash ?: $wrapper->getChecksum()),
        ]);

        [$pandocPath, $temporaryJsonPath] = self::ensureJsonExtension($path);

        return new self($pandocPath, $fingerprint, $wrapper, $temporaryJsonPath);
    }

    public static function fromPath(string $path): self
    {
        $resolvedPath = self::resolveFilePath($path, ['storage/zotero/', self::storageBaseDir() . 'zotero/']);
        if ($resolvedPath === null) {
            throw new RuntimeException('Library file not found: ' . $path);
        }

        [$pandocPath, $temporaryJsonPath] = self::ensureJsonExtension($resolvedPath);

        return new self($pandocPath, self::fileFingerprint($resolvedPath), null, $temporaryJsonPath);
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getFingerprint(): string
    {
        return $this->fingerprint;
    }

    private static function storageBaseDir(): string
    {
        global $tikidomainslash;

        return DEPRECATED_STORAGE_PATH . '/' . ($tikidomainslash ?? '');
    }

    private static function ensureJsonExtension(string $path): array
    {
        if (preg_match('/\.json$/i', $path)) {
            return [$path, null];
        }

        $basePath = tempnam(self::temporaryDirectory(), 'zotero_');
        if ($basePath === false) {
            throw new RuntimeException('Could not create temporary Zotero library file.');
        }

        $jsonPath = $basePath . '.json';
        if (! @copy($path, $jsonPath)) {
            @unlink($basePath);
            throw new RuntimeException('Could not prepare Zotero library file for Pandoc.');
        }

        @unlink($basePath);

        return [$jsonPath, $jsonPath];
    }

    private static function temporaryDirectory(): string
    {
        global $prefs;

        $directory = $prefs['tmpDir'] ?? sys_get_temp_dir();
        if (! is_dir($directory) || ! is_writable($directory)) {
            $directory = sys_get_temp_dir();
        }

        return $directory;
    }

    private static function resolveFilePath(string $path, array $prefixCandidates = []): ?string
    {
        $trimmed = trim($path);
        if ($trimmed === '') {
            return null;
        }

        $candidates = [$trimmed];
        foreach ($prefixCandidates as $prefix) {
            $candidates[] = rtrim($prefix, '/') . '/' . ltrim($trimmed, '/');
        }

        if (defined('TIKI_PATH')) {
            foreach ($candidates as $candidate) {
                if (! preg_match('/^(\/|[A-Za-z]:[\/\\\\])/', $candidate)) {
                    $candidates[] = rtrim(TIKI_PATH, '/') . '/' . ltrim($candidate, '/');
                }
            }
        }

        foreach ($candidates as $candidate) {
            $resolved = realpath($candidate);
            if ($resolved !== false && is_file($resolved)) {
                return $resolved;
            }
        }

        return null;
    }

    private static function fileFingerprint(string $path): string
    {
        $resolvedPath = realpath($path);
        $normalizedPath = $resolvedPath !== false ? $resolvedPath : $path;

        if (! is_file($normalizedPath)) {
            return $normalizedPath . '|missing';
        }

        $mtime = @filemtime($normalizedPath) ?: 0;
        $size = @filesize($normalizedPath) ?: 0;

        return $normalizedPath . '|' . $mtime . '|' . $size;
    }
}
