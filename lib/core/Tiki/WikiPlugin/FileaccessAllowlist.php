<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\WikiPlugin;

/**
 * Allowlist enforcement for filesystem wiki plugins (LSDIR, LOCALFILES).
 *
 * Initialized from preference wikiplugin_fileaccess_allowed_paths (deny-by-default).
 */
class FileaccessAllowlist
{
    /** @var list<string> Canonical absolute roots */
    private array $allowedRoots;

    /**
     * @param mixed $allowedPathsPreference Raw preference value (string CSV, serialized array, or array)
     */
    public function __construct($allowedPathsPreference = null)
    {
        $this->allowedRoots = $this->buildAllowedRoots($allowedPathsPreference);
    }

    public static function fromPreference(): self
    {
        return new self(\TikiLib::lib('tiki')->get_preference('wikiplugin_fileaccess_allowed_paths', ''));
    }

    public function isConfigured(): bool
    {
        return $this->allowedRoots !== [];
    }

    /**
     * @param string $reason no_roots|outside_path|outside_dir
     */
    public function getDeniedHtml(string $reason): string
    {
        switch ($reason) {
            case 'no_roots':
                $message = tra('Access denied: no allowed roots are configured. Ask your server administrator to set preference wikiplugin_fileaccess_allowed_paths.');
                break;
            case 'outside_path':
                $message = tra('Access denied: path is outside allowed roots. Ask your server administrator to update preference wikiplugin_fileaccess_allowed_paths.');
                break;
            case 'outside_dir':
                $message = tra('Access denied: directory is outside allowed roots. Ask your server administrator to update preference wikiplugin_fileaccess_allowed_paths.');
                break;
            default:
                $message = tra('Access denied.');
        }

        return "<span class='attention'>" . $message . '</span>';
    }

    /**
     * @return string|false Canonical path when allowed, false otherwise
     */
    public function resolvePath(string $requestedPath)
    {
        if ($this->allowedRoots === []) {
            return false;
        }

        $resolvedPath = realpath($requestedPath);
        if ($resolvedPath === false || ! $this->isWithinAllowedRoots($resolvedPath)) {
            return false;
        }

        return $resolvedPath;
    }

    /**
     * @param list<string> $candidates Paths to try (e.g. relative and DOCUMENT_ROOT-prefixed)
     * @return string|false Canonical path when allowed, false otherwise
     */
    public function resolvePathFromCandidates(array $candidates)
    {
        if ($this->allowedRoots === []) {
            return false;
        }

        foreach ($candidates as $candidate) {
            if (! is_string($candidate) || $candidate === '') {
                continue;
            }

            $resolved = $this->resolvePath($candidate);
            if ($resolved !== false) {
                return $resolved;
            }
        }

        return false;
    }

    /**
     * @return list<string> Path strings from preference (not yet canonicalized)
     */
    private function parsePreferenceValue(mixed $allowedPathsPreference): array
    {
        if (is_array($allowedPathsPreference)) {
            return $allowedPathsPreference;
        }

        if (! is_string($allowedPathsPreference) || $allowedPathsPreference === '') {
            return [];
        }

        $unserialized = @unserialize($allowedPathsPreference, ['allowed_classes' => false]);
        if (is_array($unserialized)) {
            return $unserialized;
        }

        return preg_split('/\s*,\s*/', $allowedPathsPreference, -1, PREG_SPLIT_NO_EMPTY);
    }

    /**
     * @return list<string>
     */
    private function buildAllowedRoots(mixed $allowedPathsPreference): array
    {
        $allowedRoots = [];
        foreach ($this->parsePreferenceValue($allowedPathsPreference) as $root) {
            if (! is_string($root) || $root === '') {
                continue;
            }

            $resolvedRoot = realpath($root);
            if ($resolvedRoot !== false) {
                $allowedRoots[] = rtrim($resolvedRoot, '/\\');
            }
        }

        return array_values(array_unique($allowedRoots));
    }

    private function isWithinAllowedRoots(string $path): bool
    {
        foreach ($this->allowedRoots as $root) {
            $rootWithSep = $root . DIRECTORY_SEPARATOR;
            if ($path === $root || strncmp($path, $rootWithSep, strlen($rootWithSep)) === 0) {
                return true;
            }
        }

        return false;
    }
}
