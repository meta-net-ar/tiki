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
        private array $cfg,   // ['local_path' => '/srv/notes' (optionnel for mode=path)]
        private array $ctx    // ['uploadedPath','uploadedName'] for mode=upload
    ) {
    }

    public function fetchToTempZip(): string
    {
        return $this->mode === 'upload' ? $this->fromUpload() : $this->fromPath();
    }

    private function fromUpload(): string
    {
        $uploadedPath = $this->ctx['uploadedPath'] ?? null;
        $uploadedName = $this->ctx['uploadedName'] ?? null;

        if (! $uploadedPath || ! is_readable($uploadedPath)) {
            throw new \RuntimeException('Uploaded file is missing or not readable.');
        }

        $ext = strtolower(pathinfo($uploadedName ?: $uploadedPath, PATHINFO_EXTENSION));

        if ($ext === 'zip') {
            $dst = $this->tmpZip();
            if (! copy($uploadedPath, $dst)) {
                throw new \RuntimeException('Cannot copy uploaded ZIP to temp.');
            }
            return $dst;
        }

        if (! class_exists(ZipArchive::class)) {
            throw new \RuntimeException('ZipArchive extension is required.');
        }
        $zipPath = $this->tmpZip();
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
            throw new \RuntimeException('Cannot create temporary ZIP.');
        }
        $name = $uploadedName ?: 'note.md';
        if (! preg_match('~\.(md|markdown)$~i', $name)) {
            $name .= '.md';
        }
        $data = file_get_contents($uploadedPath);
        if ($data === false) {
            $zip->close();
            @unlink($zipPath);
            throw new \RuntimeException('Cannot read uploaded file.');
        }
        $zip->addFromString($name, $data);
        $zip->close();
        return $zipPath;
    }

    private function fromPath(): string
    {
        $path = (string)($this->cfg['local_path'] ?? '');
        if ($path === '') {
            throw new \RuntimeException('Missing local_path.');
        }
        if (! file_exists($path)) {
            throw new \RuntimeException('Path does not exist: ' . $path);
        }

        // If it's already a ZIP file, just copy it to temp
        if (is_file($path) && strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'zip') {
            $dst = $this->tmpZip();
            if (! copy($path, $dst)) {
                throw new \RuntimeException('Cannot copy ZIP to temp.');
            }
            return $dst;
        }

        if (! class_exists(ZipArchive::class)) {
            throw new \RuntimeException('ZipArchive extension is required.');
        }

        $zipPath = $this->tmpZip();
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
            throw new \RuntimeException('Cannot create temporary ZIP.');
        }

        if (is_file($path)) {
            $this->addOneFile($zip, $path, basename($path));
        } else {
            $this->addDir($zip, rtrim($path, '/'));
        }

        $zip->close();
        return $zipPath;
    }

    private function addOneFile(ZipArchive $zip, string $full, string $rel): void
    {
        $lower = strtolower($full);
        if (! str_ends_with($lower, '.md') && ! str_ends_with($lower, '.markdown')) {
            return;
        }
        $data = @file_get_contents($full);
        if ($data !== false) {
            $zip->addFromString($rel, $data);
        }
    }

    private function addDir(ZipArchive $zip, string $root, string $rel = ''): void
    {
        $dir = $rel ? ($root . '/' . $rel) : $root;
        $it = @scandir($dir);
        if ($it === false) {
            // Permission denied → ignore
            return;
        }
        foreach ($it as $n) {
            if ($n === '.' || $n === '..') {
                continue;
            }
            $full = $dir . '/' . $n;
            $relPath = ltrim($rel . '/' . $n, '/');
            if (is_dir($full)) {
                if ($n === '.git') {
                    continue;
                }
                $this->addDir($zip, $root, $relPath);
            } else {
                $this->addOneFile($zip, $full, $relPath);
            }
        }
    }

    private function tmpZip(): string
    {
        return tempnam(sys_get_temp_dir(), 'mdimp_') . '.zip';
    }
}
