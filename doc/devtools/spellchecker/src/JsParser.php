<?php

declare(strict_types=1);

namespace TikiDevTools\Spellchecker;

final class JsParser
{
    /**
     * Extract JS identifiers from a file (functions, classes, variables).
     *
     * @return array<string>
     */
    public function parse(string $file): array
    {
        $code = file_get_contents($file);

        if ($code === false) {
            return [];
        }

        $identifiers = [];

        // Function declarations
        preg_match_all(
            '/function\s+([a-zA-Z_$][a-zA-Z0-9_$]*)/',
            $code,
            $m
        );
        $identifiers = array_merge($identifiers, $m[1] ?? []);

        // Arrow functions / const foo =
        preg_match_all(
            '/(?:const|let|var)\s+([a-zA-Z_$][a-zA-Z0-9_$]*)\s*=/',
            $code,
            $m
        );
        $identifiers = array_merge($identifiers, $m[1] ?? []);

        // Class declarations
        preg_match_all(
            '/class\s+([a-zA-Z_$][a-zA-Z0-9_$]*)/',
            $code,
            $m
        );
        $identifiers = array_merge($identifiers, $m[1] ?? []);

        // Object methods (basic)
        preg_match_all(
            '/([a-zA-Z_$][a-zA-Z0-9_$]*)\s*\(/',
            $code,
            $m
        );
        $identifiers = array_merge($identifiers, $m[1] ?? []);

        // Exported functions
        preg_match_all(
            '/export\s+(?:default\s+)?function\s+([a-zA-Z_$][a-zA-Z0-9_$]*)/',
            $code,
            $m
        );
        $identifiers = array_merge($identifiers, $m[1] ?? []);

        $identifiers = array_values(array_unique($identifiers));

        sort($identifiers, SORT_NATURAL | SORT_FLAG_CASE);

        return $identifiers;
    }

    public function parseFromString(string $code): array
    {
        $identifiers = [];

        preg_match_all(
            '/function\s+([a-zA-Z_$][a-zA-Z0-9_$]*)/',
            $code,
            $m
        );
        $identifiers = array_merge($identifiers, $m[1] ?? []);

        preg_match_all(
            '/(?:const|let|var)\s+([a-zA-Z_$][a-zA-Z0-9_$]*)\s*=/',
            $code,
            $m
        );
        $identifiers = array_merge($identifiers, $m[1] ?? []);

        preg_match_all(
            '/class\s+([a-zA-Z_$][a-zA-Z0-9_$]*)/',
            $code,
            $m
        );
        $identifiers = array_merge($identifiers, $m[1] ?? []);

        return array_values(array_unique($identifiers));
    }
}
