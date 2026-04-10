<?php

require_once __DIR__ . '/../../path_constants.php';

/**
 * Returns colored text for console output.
 */

function colorText(string $text, string $color): string
{
    static $colors = [
        'red'    => "\033[31m",
        'green'  => "\033[32m",
        'yellow' => "\033[33m",
        'blue'   => "\033[34m",
        'white'  => "\033[1;37m",
        'reset'  => "\033[0m",
    ];

    return ($colors[$color] ?? '') . $text . $colors['reset'];
}

/**
 * Loads and decodes a JSON file.
 */

function getJsonData(string $path): array
{
    $content = file_get_contents($path);
    if ($content === false) {
        throw new RuntimeException("Failed to read $path");
    }

    $data = json_decode($content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new RuntimeException("Invalid JSON in $path: " . json_last_error_msg());
    }

    return $data;
}

/**
 * Detects sections that appear to describe dependencies
 * (based on key name patterns like "require" or "dependency").
 */
function detectDependencySections(array $data): array
{
    $sections = [];

    foreach ($data as $key => $value) {
        if (! is_array($value)) {
            continue;
        }

        // Detect keys that look like dependency sections
        if (preg_match('/(require|dependenc(y|ies)|deps)$/i', $key)) {
            // Must be associative (package => version)
            if (isAssociativeArray($value) && looksLikeDependencyMap($value)) {
                $sections[] = $key;
            }
        }
    }

    return $sections;
}

/**
 * Checks if array is associative.
 */
function isAssociativeArray(array $arr): bool
{
    return array_keys($arr) !== range(0, count($arr) - 1);
}

/**
 * Heuristic: determine if a section looks like dependencies
 * (keys are strings, values are strings or numbers)
 */
function looksLikeDependencyMap(array $section): bool
{
    $sample = array_slice($section, 0, 3, true);
    foreach ($sample as $k => $v) {
        if (! is_string($k)) {
            return false;
        }
        if (! is_string($v) && ! is_numeric($v)) {
            return false;
        }
    }
    return true;
}

function findFirstUnordered(array $keys): ?string
{
    $sorted = $keys;
    sort($sorted, SORT_STRING);
    for ($i = 1; $i < count($sorted); $i++) {
        if ($keys[$i] !== $sorted[$i]) {
            return $keys[$i]; // Returns the first out-of-order item
        }
    }
    return null; // All items are in order
}

/**
 * Checks if dependencies in a given section are alphabetically sorted.
 */
function checkDependenciesOrder(array $data, string $section, string $file): bool
{
    $deps = $data[$section] ?? null;
    if (! is_array($deps) || empty($deps)) {
        return true;
    }

    $keys = array_keys($deps);

    if ($firstUnordered = findFirstUnordered($keys)) {
        echo colorText("⚠️  Dependencies in '$section' of '$file' are not sorted alphabetically: $firstUnordered is out of order\n", 'yellow');
        return false;
    }

    return true;
}

/**
 * Main validation runner.
 */
function main(): void
{
    $files = [
        PRIMARY_COMPOSERJSON_FILE_PATH,
        PRIMARY_PACKAGEJSON_FILE_PATH,
        PRIMARY_JQUERYTIKI_PACKAGEJSON_FILE_PATH,
        PRIMARY_EXTERNAL_PACKAGEJSON_FILE_PATH,
    ];

    $hasError = false;

    foreach ($files as $file) {
        $data = getJsonData($file);
        $sections = detectDependencySections($data);

        if (empty($sections)) {
            echo colorText("ℹ️  No dependency sections found in $file\n", 'blue');
            continue;
        }

        $fileHasError = false;
        foreach ($sections as $section) {
            if (! checkDependenciesOrder($data, $section, $file)) {
                $fileHasError = true;
                $hasError = true;
            }
        }

        if (! $fileHasError) {
            echo colorText("✅ $file dependencies are properly sorted.\n", 'green');
        }
    }

    if ($hasError) {
        echo colorText("\n❌ CI FAILURE: Some dependencies are unsorted.\n", 'red');
        exit(1);
    }

    echo colorText("\nAll dependency sections are sorted correctly.\n", 'green');
    exit(0);
}

main();
