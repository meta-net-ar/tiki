<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Importer\Markdown\Source;

use ZipArchive;

/**
 * SourceManager
 *
 * - Holds a registry of provider factories (type => callable).
 * - Ships with built-in providers: 'local', 'ftp', 'repo' (GitHub/GitLab).
 * - Provides a light ZIP scanner for previews.
 *
 * Add a new source by creating a class that implements SourceProviderInterface and calling register('mytype', fn(...) => new MyProvider(...)).
 */
class SourceManager
{
    /** @var array<string,string|array> */
    public array $errors = [];
    public array $errorsArgs = [];

    /** @var array<string, callable(array $cfg, array $ctx): SourceProviderInterface> */
    private array $registry = [];

    public function __construct()
    {
        // Register built-in providers
        $this->register('local', function (array $cfg, array $ctx): SourceProviderInterface {
            return new FilesystemProvider('local', $cfg, $ctx);
        });
        $this->register('ftp', function (array $cfg, array $ctx): SourceProviderInterface {
            return new FilesystemProvider('ftp', $cfg, $ctx);
        });
        $this->register('repo', function (array $cfg, array $ctx): SourceProviderInterface {
            return new RepoZipProvider($cfg, $ctx);
        });
    }

    /**
     * Register a provider factory for a given type.
     * @param string $type    e.g. 'local', 'ftp', 'repo', 's3', 'webdav', 'gdrive'
     * @param callable $factory function (array $cfg, array $ctx): SourceProviderInterface
     * @return void
     */
    public function register(string $type, callable $factory): void
    {
        $this->registry[$type] = $factory;
    }

    /**
     * Build provider from config and optional runtime context (e.g. uploaded file).
     *
     * @param array $cfg  UI/Prefs config (must include 'type')
     * @param string|null $uploadedPath tmp_name when type=local (optional)
     * @param string|null $uploadedName original name when type=local (optional)
     * @return SourceProviderInterface
     */
    public function createProvider(array $cfg, ?string $uploadedPath = null, ?string $uploadedName = null): SourceProviderInterface
    {
        $type = (string)($cfg['type'] ?? 'local');
        if (! isset($this->registry[$type])) {
            throw new \RuntimeException("Unsupported source type: {$type}");
        }
        $ctx = [
            'uploadedPath' => $uploadedPath,
            'uploadedName' => $uploadedName,
        ];
        /** @var callable $factory */
        $factory = $this->registry[$type];
        return $factory($cfg, $ctx);
    }

    /**
     * High-level helper: create provider and fetch a temp ZIP path.
     * Returns null and records error on failure.
     */
    public function fetchToTempZip(array $cfg, ?string $uploadedPath = null, ?string $uploadedName = null): ?string
    {
        try {
            $provider = $this->createProvider($cfg, $uploadedPath, $uploadedName);
            return $provider->fetchToTempZip();
        } catch (\Throwable $e) {
            $this->pushError('Fetch failed', $e->getMessage());
            return null;
        }
    }

    /**
     * Light ZIP scanner for dry-run preview (markdown only).
     * Options:
     *  - roots: semicolon-separated whitelist roots ('.' / 'docs' / 'notes')
     *  - exclude_globs: semicolon or comma separated globs (fnmatch)
     */
    public function scanZipForMarkdown(string $zipPath, array $options = []): array
    {
        $out = ['total' => 0, 'files' => []];

        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            $this->pushError('Cannot open ZIP file', $zipPath);
            return $out;
        }

        $excludes = $this->explodeList($options['exclude_globs'] ?? '');
        $roots    = $this->explodeList($options['roots'] ?? '');
        if (! $roots) {
            $roots = ['.'];
        }

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $st = $zip->statIndex($i);
            if (! $st || substr($st['name'], -1) === '/') {
                continue;
            }

            $rel = $this->stripTopFolder($st['name']);
            $lower = strtolower($rel);
            if (! (str_ends_with($lower, '.md') || str_ends_with($lower, '.markdown'))) {
                continue;
            }

            if (! $this->matchesRoots($rel, $roots)) {
                continue;
            }
            if ($this->isExcluded($rel, $excludes)) {
                continue;
            }

            $out['files'][] = ['relative' => $rel, 'size' => (int)($st['size'] ?? 0)];
            $out['total']++;
        }

        $zip->close();
        return $out;
    }

    public function getError(): string
    {
        $str = '';
        foreach ($this->errors as $i => $error) {
            $str = $error;
            $arg = $this->errorsArgs[$i] ?? '';
            $str .= ': ' . (is_array($arg) ? implode(', ', $arg) : (string)$arg);
        }
        return $str;
    }

    private function pushError(string $msg, $arg = ''): void
    {
        $this->errors[] = $msg;
        $this->errorsArgs[] = $arg;
    }

    private function stripTopFolder(string $path): string
    {
        $parts = explode('/', $path, 2);
        return count($parts) >= 2 ? $parts[1] : $path;
    }

    private function explodeList(string $s): array
    {
        $out = array_filter(array_map('trim', preg_split('/[;,]+/', (string)$s)));
        return array_values(array_unique($out));
    }

    private function matchesRoots(string $rel, array $roots): bool
    {
        if (! $roots || in_array('.', $roots, true)) {
            return true;
        }
        foreach ($roots as $r) {
            $r = ltrim($r, './');
            if ($r === '' || $rel === $r || str_starts_with($rel, $r . '/')) {
                return true;
            }
        }
        return false;
    }

    private function isExcluded(string $rel, array $globs): bool
    {
        foreach ($globs as $g) {
            if (fnmatch($g, $rel, FNM_PATHNAME | FNM_CASEFOLD)) {
                return true;
            }
        }
        return false;
    }
}
