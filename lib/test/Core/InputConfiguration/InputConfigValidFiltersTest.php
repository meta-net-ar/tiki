<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * @group unit
*/
namespace Tiki\Lib\Test\Core\InputConfiguration;

use TikiTestCase;

class InputConfigValidFiltersTest extends TikiTestCase
{
    /**
     * @return array<string, true>
     */
    private function tikiFilterShortcutNames(): array
    {
        $path = TIKI_PATH . 'lib/core/TikiFilter.php';
        $this->assertFileExists($path);

        $src = file_get_contents($path);
        $this->assertIsString($src);

        // Collect all filter shortcut names from switch cases in TikiFilter::get()
        preg_match_all("/case\\s+'([^']+)'\\s*:/", $src, $m);

        $names = [];
        foreach ($m[1] as $name) {
            $names[$name] = true;
        }

        return $names;
    }

    /**
     * Extract the first array literal assigned to $inputConfiguration: `= [ ... ]`.
     */
    private function extractInputConfigurationArrayLiteral(string $php): ?string
    {
        $needle = '$inputConfiguration';
        $pos = strpos($php, $needle);
        if ($pos === false) {
            return null;
        }

        $pos += strlen($needle);
        while ($pos < strlen($php) && ctype_space($php[$pos])) {
            $pos++;
        }
        if ($pos >= strlen($php) || $php[$pos] !== '=') {
            return null;
        }

        $pos++;
        while ($pos < strlen($php) && ctype_space($php[$pos])) {
            $pos++;
        }

        if ($pos >= strlen($php) || $php[$pos] !== '[') {
            return null;
        }

        $start = $pos;
        $depth = 0;
        $len = strlen($php);
        $inString = false;
        $stringChar = '';

        for ($i = $pos; $i < $len; $i++) {
            $ch = $php[$i];

            if ($inString) {
                if ($ch === '\\' && $stringChar === '"' && $i + 1 < $len) {
                    $i++; // Skip escaped character inside double-quoted strings
                    continue;
                }

                if ($ch === $stringChar) {
                    $inString = false;
                }
                continue;
            }

            if ($ch === '\'' || $ch === '"') {
                $inString = true;
                $stringChar = $ch;
                continue;
            }

            if ($ch === '[') {
                $depth++;
            } elseif ($ch === ']') {
                $depth--;
                if ($depth === 0) {
                    return substr($php, $start, $i - $start + 1);
                }
            }
        }

        return null;
    }

    /**
     * Collect string literals used as filter shortcuts: `=> 'filtername'`.
     *
     * @return list<string>
     */
    private function filterShortcutsUsedInArrayLiteral(string $arrayLiteral): array
    {
        // Match values in key/value arrays where the value is a single-quoted identifier
        preg_match_all("/=>\\s*'([A-Za-z_][A-Za-z0-9_]*)'\\s*(?:,|\\])/", $arrayLiteral, $m);

        return array_values(array_unique($m[1] ?? []));
    }

    /**
     * @return list<string>
     */
    private function configuredFilterShortcutsFromScript(string $path): array
    {
        $php = file_get_contents($path);
        if (! is_string($php) || strpos($php, '$inputConfiguration') === false) {
            return [];
        }

        $block = $this->extractInputConfigurationArrayLiteral($php);
        if ($block === null) {
            return [];
        }

        return $this->filterShortcutsUsedInArrayLiteral($block);
    }

    public function testRootTikiScriptsUseOnlyDefinedTikiFilterShortcuts(): void
    {
        $validShortcuts = $this->tikiFilterShortcutNames();
        $errors = [];

        $scripts = glob(TIKI_PATH . 'tiki-*.php');
        if (! is_array($scripts)) {
            $scripts = [];
        }

        foreach ($scripts as $path) {
            foreach ($this->configuredFilterShortcutsFromScript($path) as $shortcut) {
                if (! isset($validShortcuts[$shortcut])) {
                    $errors[] = basename($path) . ": unknown TikiFilter shortcut '" . $shortcut . "'";
                }
            }
        }

        sort($errors);

        $this->assertSame(
            [],
            $errors,
            'Invalid filter shortcut(s) in $inputConfiguration blocks (not defined in TikiFilter::get()):' . "\n" . implode("\n", $errors)
        );
    }
}
