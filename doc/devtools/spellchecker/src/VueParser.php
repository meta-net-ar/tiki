<?php

declare(strict_types=1);

namespace TikiDevTools\Spellchecker;

final class VueParser
{
    private JsParser $jsParser;

    public function __construct()
    {
        $this->jsParser = new JsParser();
    }

    /**
     * Extract identifiers from Vue SFC files.
     *
     * @return array<string>
     */
    public function parse(string $file): array
    {
        $content = file_get_contents($file);

        if ($content === false) {
            return [];
        }

        $identifiers = [];

        // Extract <script> block only
        if (preg_match('/<script[^>]*>(.*?)<\/script>/s', $content, $m)) {
            $script = $m[1];

            $identifiers = array_merge(
                $identifiers,
                $this->jsParser->parseFromString($script)
            );
        }

        // Extract Vue component name: name: 'X'
        preg_match_all(
            '/name\s*:\s*[\'"]([a-zA-Z0-9_-]+)[\'"]/i',
            $content,
            $m
        );
        $identifiers = array_merge($identifiers, $m[1] ?? []);

        // Extract props
        preg_match_all(
            '/props\s*:\s*\{([^}]*)\}/s',
            $content,
            $m
        );
        foreach ($m[1] ?? [] as $block) {
            preg_match_all(
                '/([a-zA-Z_][a-zA-Z0-9_]*)\s*:/',
                $block,
                $pm
            );
            $identifiers = array_merge($identifiers, $pm[1] ?? []);
        }

        // Extract methods names
        preg_match_all(
            '/methods\s*:\s*\{([^}]*)\}/s',
            $content,
            $m
        );
        foreach ($m[1] ?? [] as $block) {
            preg_match_all(
                '/([a-zA-Z_][a-zA-Z0-9_]*)\s*\(/',
                $block,
                $mm
            );
            $identifiers = array_merge($identifiers, $mm[1] ?? []);
        }

        $identifiers = array_values(array_unique($identifiers));

        sort($identifiers, SORT_NATURAL | SORT_FLAG_CASE);

        return $identifiers;
    }
}
