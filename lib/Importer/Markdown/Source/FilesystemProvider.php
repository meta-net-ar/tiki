<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Importer\Markdown\Source;

use ZipArchive;

/**
 * FilesystemProvider
 * - mode 'upload' : upload via HTTP (single .md/.zip file)
 * - mode 'path'   : local filesystem path (file or dir)
 */
class FilesystemProvider implements SourceProviderInterface
{
    public function __construct(
        private string $mode, // 'upload' | 'path'
        private array $cfg,   // ['local_path' => '/srv/notes' (optional for mode=path)]
        private array $ctx    // ['uploadedPath','uploadedName'] for mode=upload
    ) {
    }

    public function fetchToTempDir(): string
    {
        return $this->mode === 'upload' ? $this->fromUpload() : $this->fromPath();
    }

    private function fromUpload(): string
    {
        $uploadedPath = $this->ctx['uploadedPath'] ?? null;
        $uploadedName = $this->ctx['uploadedName'] ?? null;

        if (! $uploadedPath || ! is_readable($uploadedPath)) {
            throw new \RuntimeException(tra('Uploaded file is missing or not readable.'));
        }

        $ext = strtolower(pathinfo($uploadedName ?: $uploadedPath, PATHINFO_EXTENSION));

        // If ZIP, extract to temp directory
        if ($ext === 'zip') {
            if (! class_exists(ZipArchive::class)) {
                throw new \RuntimeException(tra('ZipArchive extension required to extract ZIP files.'));
            }

            $tempDir = sys_get_temp_dir() . '/mdimp_upload_' . uniqid();
            @mkdir($tempDir, 0755, true);

            $zip = new ZipArchive();
            if ($zip->open($uploadedPath) !== true) {
                throw new \RuntimeException(tra('Failed to open uploaded ZIP file.'));
            }

            if (! $zip->extractTo($tempDir)) {
                $zip->close();
                throw new \RuntimeException(tra('Failed to extract ZIP file.'));
            }
            $zip->close();

            // Auto-detect and unwrap single top-level folder
            return $this->detectAndUnwrapTopFolder($tempDir);
        }

        // Single .md file: create temp dir with single file
        if (! preg_match('~\.(md|markdown)$~i', $uploadedName ?: $uploadedPath)) {
            throw new \RuntimeException(tra('Uploaded file must be .md, .markdown, or .zip'));
        }

        $tempDir = sys_get_temp_dir() . '/mdimp_upload_' . uniqid();
        @mkdir($tempDir, 0755, true);

        $targetFile = $tempDir . '/' . ($uploadedName ?: 'note.md');
        if (! copy($uploadedPath, $targetFile)) {
            throw new \RuntimeException(tra('Failed to copy uploaded file.'));
        }

        return $tempDir;
    }

    private function fromPath(): string
    {
        $path = (string)($this->cfg['local_path'] ?? '');
        if ($path === '') {
            throw new \RuntimeException(tra('Local path is not specified.'));
        }
        if (! file_exists($path)) {
            throw new \RuntimeException(tra('Local path does not exist:') . ' ' . $path);
        }

        // If it's a directory, return it directly
        if (is_dir($path)) {
            return realpath($path);
        }

        // If it's a ZIP file, extract to temp directory
        if (is_file($path) && strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'zip') {
            if (! class_exists(ZipArchive::class)) {
                throw new \RuntimeException(tra('ZipArchive extension required to extract ZIP files.'));
            }

            $tempDir = sys_get_temp_dir() . '/mdimp_local_' . uniqid();
            @mkdir($tempDir, 0755, true);

            $zip = new ZipArchive();
            if ($zip->open($path) !== true) {
                throw new \RuntimeException(tra('Failed to open ZIP file.'));
            }

            if (! $zip->extractTo($tempDir)) {
                $zip->close();
                throw new \RuntimeException(tra('Failed to extract ZIP file.'));
            }
            $zip->close();

            // Auto-detect and unwrap single top-level folder
            return $this->detectAndUnwrapTopFolder($tempDir);
        }

        // Single .md file: create temp dir with single file
        $lower = strtolower($path);
        if (! str_ends_with($lower, '.md') && ! str_ends_with($lower, '.markdown')) {
            throw new \RuntimeException(tra('Local file must be .md, .markdown, .zip, or a directory'));
        }

        $tempDir = sys_get_temp_dir() . '/mdimp_local_' . uniqid();
        @mkdir($tempDir, 0755, true);

        $targetFile = $tempDir . '/' . basename($path);
        if (! copy($path, $targetFile)) {
            throw new \RuntimeException(tra('Failed to copy file.'));
        }

        return $tempDir;
    }

    /**
     * Detect if extracted ZIP has a single top-level folder and return its path.
     * Many ZIP files wrap content in a folder named after the ZIP.
     * E.g., Archive.zip contains Archive/file1.md instead of file1.md directly.
     */
    private function detectAndUnwrapTopFolder(string $dir): string
    {
        $items = @scandir($dir);
        if ($items === false) {
            return $dir;
        }

        // Filter out . and ..
        $realItems = array_filter($items, fn($i) => $i !== '.' && $i !== '..');

        // If there's exactly one item and it's a directory, use it as the base
        if (count($realItems) === 1) {
            $single = reset($realItems);
            $singlePath = $dir . '/' . $single;
            if (is_dir($singlePath)) {
                return $singlePath;
            }
        }

        // Otherwise, use the original directory
        return $dir;
    }
}
