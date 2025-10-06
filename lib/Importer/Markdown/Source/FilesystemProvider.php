<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Importer\Markdown\Source;

use ZipArchive;

/**
 * FilesystemProvider
 *
 * Handles:
 *  - 'local' : uploaded file (.zip or .md/.markdown) and pack into ZIP if needed.
 *  - 'ftp'   : connect to FTP and collect *.md/*.markdown files recursively into a ZIP.
 *
 * Constructor takes a $mode ('local'|'ftp'), a $cfg array, and a $ctx array.
 * $ctx is provided by SourceManager, carrying uploadedPath / uploadedName for 'local'.
 */
class FilesystemProvider implements SourceProviderInterface
{
    public function __construct(
        private string $mode,
        private array $cfg,
        private array $ctx
    ) {
    }

    public function fetchToTempZip(): string
    {
        if ($this->mode === 'local') {
            return $this->fromLocal();
        }
        if ($this->mode === 'ftp') {
            return $this->fromFtp();
        }
        throw new \RuntimeException("Unsupported filesystem mode: {$this->mode}");
    }

    /**
     * Local upload: if ZIP, copy to temp; if .md/.markdown, create a ZIP with one entry.
     */
    private function fromLocal(): string
    {
        $uploadedPath = $this->ctx['uploadedPath'] ?? null;
        $uploadedName = $this->ctx['uploadedName'] ?? null;

        if (! $uploadedPath || ! is_readable($uploadedPath)) {
            throw new \RuntimeException('Uploaded file is missing or not readable.');
        }

        $orig = (string)($uploadedName ?? basename($uploadedPath));
        $ext  = strtolower(pathinfo($orig, PATHINFO_EXTENSION));

        if ($ext === 'zip') {
            $tmp = $this->tmpZip();
            if (! copy($uploadedPath, $tmp)) {
                throw new \RuntimeException('Cannot copy uploaded ZIP to temp.');
            }
            return $tmp;
        }

        if (! class_exists(ZipArchive::class)) {
            throw new \RuntimeException('ZipArchive extension is required.');
        }

        $tmpZip = $this->tmpZip();
        $zip = new ZipArchive();
        if ($zip->open($tmpZip, ZipArchive::CREATE) !== true) {
            throw new \RuntimeException('Cannot create temporary ZIP.');
        }

        $data = file_get_contents($uploadedPath);
        if ($data === false) {
            $zip->close();
            @unlink($tmpZip);
            throw new \RuntimeException('Cannot read uploaded file.');
        }

        $inner = $orig ?: 'note.md';
        if (! preg_match('~\.(md|markdown)$~i', $inner)) {
            $inner .= '.md';
        }

        $zip->addFromString($inner, $data);
        $zip->close();

        return $tmpZip;
    }

    /**
     * FTP: connect, traverse, collect *.md/*.markdown into a ZIP.
     */
    private function fromFtp(): string
    {
        if (! class_exists(ZipArchive::class)) {
            throw new \RuntimeException('ZipArchive extension is required.');
        }

        $host = (string)($this->cfg['ftp_host'] ?? '');
        $port = (int)($this->cfg['ftp_port'] ?? 21);
        $user = (string)($this->cfg['ftp_user'] ?? '');
        $pass = (string)($this->cfg['ftp_pass'] ?? '');
        $path = (string)($this->cfg['ftp_path'] ?? '/');
        $ssl  = ! empty($this->cfg['ftp_ssl']);
        $passive = ! empty($this->cfg['ftp_passive']);

        $conn = $ssl ? ftp_ssl_connect($host, $port, 90) : ftp_connect($host, $port, 15);
        if (! $conn) {
            throw new \RuntimeException("Cannot connect to FTP server {$host}:{$port}. ");
        }
        if (! @ftp_login($conn, $user, $pass)) {
            @ftp_close($conn);
            throw new \RuntimeException('FTP authentication failed for user: ' . $user);
        }
        @ftp_pasv($conn, $passive);

        $root = $path ?: '/';
        if (! @ftp_chdir($conn, $root)) {
            @ftp_close($conn);
            throw new \RuntimeException('Cannot change directory on FTP.');
        }

        $tmpZip = $this->tmpZip();
        $zip = new ZipArchive();
        if ($zip->open($tmpZip, ZipArchive::CREATE) !== true) {
            @ftp_close($conn);
            throw new \RuntimeException('Cannot create temporary ZIP.');
        }

        $this->ftpAddRecursive($conn, '.', $zip);

        $zip->close();
        @ftp_close($conn);

        return $tmpZip;
    }

    private function ftpAddRecursive($conn, string $dir, ZipArchive $zip, int $depth = 0, int $max = 20): void
    {
        if ($depth > $max) {
            return;
        }
        $list = @ftp_nlist($conn, $dir);
        if (! $list) {
            return;
        }
        foreach ($list as $item) {
            $name = basename($item);
            if ($name === '.' || $name === '..') {
                continue;
            }
            $remote = ($dir === '.') ? $name : ($dir . '/' . $name);
            $size = @ftp_size($conn, $remote);
            if ($size === -1) {
                // directory
                $this->ftpAddRecursive($conn, $remote, $zip, $depth + 1, $max);
                continue;
            }
            // file
            if (! preg_match('~\.(md|markdown)$~i', $remote)) {
                continue;
            }
            $stream = fopen('php://temp', 'w+b');
            if (! $stream) {
                continue;
            }
            if (@ftp_fget($conn, $stream, $remote, FTP_BINARY)) {
                rewind($stream);
                $data = stream_get_contents($stream);
                if ($data !== false) {
                    $zip->addFromString(ltrim($remote, './'), $data);
                }
            }
            fclose($stream);
        }
    }

    private function tmpZip(): string
    {
        return tempnam(sys_get_temp_dir(), 'mdimp_') . '.zip';
    }
}
