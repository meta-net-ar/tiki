<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Security;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tiki\Lib\Diff\Renderer\Unified;
use Tiki\Lib\Diff\TextDiff;

/**
 * Performs integrity checks on the active webroot .htaccess compared to the shipped _htaccess reference.
 */
class HtaccessChecker
{
    public const STATUS_OK = 'ok';
    public const STATUS_MISMATCH = 'mismatch';
    public const STATUS_NOT_APPLICABLE = 'not_applicable';
    public const STATUS_UNREADABLE = 'unreadable';
    public const STATUS_MAINTENANCE = 'maintenance';

    /**
     * @param string $webRoot Absolute path to the Tiki web root (directory containing .htaccess)
     * @param string $referencePath Absolute path to the shipped reference _htaccess file
     * @param array  $options Optional overrides: server_software, htaccess_path, maintenance_contents, maintenance_paths
     *
     * @return array status payload including hashes, diff and diagnostic details
     */
    public function run(string $webRoot, string $referencePath, array $options = []): array
    {
        $htaccessPath = $options['htaccess_path'] ?? $webRoot . '/.htaccess';
        $serverSoftware = $options['server_software'] ?? ($_SERVER['SERVER_SOFTWARE'] ?? '');

        $result = [
            'status' => self::STATUS_NOT_APPLICABLE,
            'code' => 'server_not_apache',
            'details' => [
                'htaccess_path' => $htaccessPath,
                'reference_path' => $referencePath,
                'symlink_target' => null,
            ],
            'hash' => null,
            'reference_hash' => null,
            'diff' => null,
            'maintenance_source' => null,
        ];

        if (! $this->isApacheLike($serverSoftware)) {
            return $result;
        }

        if (! is_file($referencePath) || ! is_readable($referencePath)) {
            $result['code'] = 'missing_reference';
            return $result;
        }

        $referenceContents = @file_get_contents($referencePath);
        if ($referenceContents === false) {
            $result['code'] = 'reference_unreadable';
            return $result;
        }
        $referenceHash = hash('sha256', $referenceContents);
        $result['reference_hash'] = $referenceHash;

        if (! file_exists($htaccessPath)) {
            $result['code'] = 'missing_htaccess';
            return $result;
        }

        if (! is_readable($htaccessPath)) {
            $result['status'] = self::STATUS_UNREADABLE;
            $result['code'] = 'htaccess_unreadable';
            return $result;
        }

        $symlinkTarget = null;
        if (is_link($htaccessPath)) {
            $symlinkTarget = readlink($htaccessPath);
            $result['details']['symlink_target'] = $symlinkTarget;

            $resolvedTarget = $this->resolveSymlink($htaccessPath);
            $resolvedReference = realpath($referencePath);
            if ($resolvedTarget && $resolvedReference && $resolvedTarget === $resolvedReference) {
                $result['status'] = self::STATUS_OK;
                $result['code'] = 'symlink_to_reference';
                $result['hash'] = $referenceHash;
                return $result;
            }
        }

        $activeContents = @file_get_contents($htaccessPath);
        if ($activeContents === false) {
            $result['status'] = self::STATUS_UNREADABLE;
            $result['code'] = 'htaccess_unreadable';
            return $result;
        }

        $activeHash = hash('sha256', $activeContents);
        $result['hash'] = $activeHash;

        if ($activeHash === $referenceHash) {
            $result['status'] = self::STATUS_OK;
            $result['code'] = 'exact_match';
            return $result;
        }

        $maintenanceMatch = $this->matchesMaintenanceVariant($activeHash, $activeContents, $options);
        if ($maintenanceMatch) {
            $result['status'] = self::STATUS_MAINTENANCE;
            $result['code'] = 'maintenance_variant';
            $result['maintenance_source'] = $maintenanceMatch;
            return $result;
        }

        $result['status'] = self::STATUS_MISMATCH;
        $result['code'] = 'hash_mismatch';
        $result['diff'] = $this->buildUnifiedDiff($referenceContents, $activeContents);

        return $result;
    }

    private function isApacheLike(string $serverSoftware): bool
    {
        if ($serverSoftware === '') {
            return false;
        }

        $lower = strtolower($serverSoftware);
        return str_contains($lower, 'apache') || str_contains($lower, 'litespeed') || str_contains($lower, 'openshift');
    }

    private function resolveSymlink(string $path): ?string
    {
        $link = readlink($path);
        if ($link === false) {
            return null;
        }

        if (preg_match('#^[/\\\\]#', $link)) {
            return realpath($link) ?: null;
        }

        $dir = dirname($path);
        return realpath($dir . DIRECTORY_SEPARATOR . $link) ?: null;
    }

    /**
     * @param string $activeHash
     * @param string $activeContents
     * @param array  $options
     * @return array|null maintenance metadata if matched
     */
    private function matchesMaintenanceVariant(string $activeHash, string $activeContents, array $options): ?array
    {
        $signatures = [];

        if (! empty($options['maintenance_contents']) && is_array($options['maintenance_contents'])) {
            foreach ($options['maintenance_contents'] as $label => $content) {
                if ($content !== null) {
                    $hash = hash('sha256', $content);
                    $signatures[$hash] = ['source' => $label, 'hash' => $hash];
                }
            }
        }

        foreach ($this->findMaintenanceCandidates($options) as $path => $content) {
            $hash = hash('sha256', $content);
            $signatures[$hash] = ['source' => $path, 'hash' => $hash];
        }

        if (isset($signatures[$activeHash])) {
            return $signatures[$activeHash];
        }

        return null;
    }

    /**
     * Locate probable maintenance .htaccess templates from Tiki Manager or local overrides.
     * @param array $options
     * @return array<string,string> map of absolute path => contents
     */
    private function findMaintenanceCandidates(array $options): array
    {
        $paths = $options['maintenance_paths'] ?? [];

        if (defined('TIKI_PATH')) {
            $tikiPath = rtrim(TIKI_PATH, '/\\');
            $paths[] = $tikiPath . '/storage/tiki-manager/maintenance/.htaccess';
            $paths[] = $tikiPath . '/src/devtools/maintenance/.htaccess';

            $vendorLocations = [
                $tikiPath . '/vendor/tikiwiki/tiki-manager',
                $tikiPath . '/vendor_bundled/vendor/tikiwiki/tiki-manager',
            ];

            foreach ($vendorLocations as $location) {
                if (is_dir($location)) {
                    $paths = array_merge($paths, $this->scanMaintenanceFiles($location));
                }
            }
        }

        $candidates = [];
        foreach ($paths as $path) {
            if (is_file($path) && is_readable($path)) {
                $content = @file_get_contents($path);
                if ($content !== false) {
                    $candidates[$path] = $content;
                }
            }
        }

        return $candidates;
    }

    /**
     * Recursively scan for maintenance .htaccess files inside a directory.
     *
     * @param string $baseDir
     * @return array
     */
    private function scanMaintenanceFiles(string $baseDir): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($baseDir, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $fileInfo) {
            if (! $fileInfo->isFile()) {
                continue;
            }
            $filename = strtolower($fileInfo->getFilename());
            if (str_contains($filename, 'htaccess') && str_contains($filename, 'maint')) {
                $files[] = $fileInfo->getPathname();
            }
        }

        return $files;
    }

    /**
     * Line-based unified diff. Avoid character-level HTML (ins/del): .htaccess
     * contains Apache tags that the template must escape once for display.
     *
     * @param string $reference
     * @param string $active
     * @return array|null
     */
    private function buildUnifiedDiff(string $reference, string $active): ?array
    {
        $old = $reference === '' ? [] : explode("\n", $reference);
        $new = $active === '' ? [] : explode("\n", $active);

        $diff = new TextDiff($old, $new);
        if ($diff->isEmpty()) {
            return null;
        }

        $renderer = new class (2) extends Unified {
            // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore -- overrides Unified::_changed
            protected function _changed($orig, $final)
            {
                $this->_deleted($orig);
                $this->_added($final);
            }
        };

        $table = $renderer->render($diff);
        return empty($table) ? null : $table;
    }
}
