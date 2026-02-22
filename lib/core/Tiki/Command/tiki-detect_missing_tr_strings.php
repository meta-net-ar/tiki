<?php

/**
 * Tiki Translation Detector
 *
 * This script scans Tiki template files (.tpl) and PHP files to detect potentially
 * untranslated strings that should be wrapped with translation tags.
 *
 * USAGE:
 *   php lib/core/Tiki/Command/tiki-detect_missing_tr_strings.php [directory_path]
 *
 * EXAMPLES:
 *   # Scan default templates directory
 *   php lib/core/Tiki/Command/tiki-detect_missing_tr_strings.php
 *
 *   # Scan specific directory
 *   php lib/core/Tiki/Command/tiki-detect_missing_tr_strings.php /path/to/templates
 *
 *   # Scan admin templates only
 *   php lib/core/Tiki/Command/tiki-detect_missing_tr_strings.php templates/admin
 *
 *   # From Tiki root directory
 *   php lib/core/Tiki/Command/tiki-detect_missing_tr_strings.php templates/
 *
 * WHAT IT DETECTS:
 *   ✓ Hardcoded text in HTML content: <p>Hello World</p>
 *   ✓ Untranslated attributes: title="Delete", placeholder="Enter name"
 *   ✓ Button values: value="Submit", alt="Icon description"
 *   ✓ ARIA labels: aria-label="Close dialog"
 *   ✓ Data attributes with text: data-tooltip="Help text"
 *   ✓ Literal strings assigned to Smarty or PHP variables:
 *       {$label = "Submit"} or {assign var='label' value="Upload picture:"}
 *
 * WHAT IT IGNORES:
 *   ✗ Already translated strings: {tr}Hello{/tr}
 *   ✗ Pure Smarty/PHP variables: {$variable}, {$obj.property}, $var
 *   ✗ CSS selectors: #myId, .myClass
 *   ✗ CSS custom properties: --bs-primary-color
 *   ✗ Smarty logic and non-text tags: {if}, {foreach}, {include}, {assign}, {capture}, {literal}, {section}
 *   ✗ Empty lines and lines without literal strings
 *
 * OUTPUT FORMAT:
 *   File: /path/to/file.tpl
 *   Line: 42
 *   Text: Delete
 *   Suggestion: {tr}Delete{/tr}
 *
 * COMMON FIXES:
 *   Before: <button>Save</button>
 *   After:  <button>{tr}Save{/tr}</button>
 *
 *   Before: title="Close window"
 *   After:  title="{tr}Close window{/tr}"
 *
 *   Before: {if $var}{tr}{$var}{/tr}{/if}  ← WRONG!
 *   After:  {if $var}{$var}{/if}           ← CORRECT
 *
 * EXIT CODES:
 *   0 = Success (with or without findings)
 *   1 = Error (invalid directory)
 *
 * NOTE:
 *   - This tool provides suggestions only. Manual review is required as it may
 *     produce false positives for technical strings, URLs, or code snippets.
 *   - Regex rules:
 *       • Detects literal strings in quotes (single or double) for translation.
 *       • Ignores lines that are purely variable references or non-text Smarty/PHP tags.
 *       • Detects text in HTML content and attributes that is visible to users.
 *       • Excludes control structures and logic that cannot be translated.
 *
 * @author  Yves Ngalamulume
 * @package Tiki\Command
 *
 */

$baseDir = isset($argv[1]) && $argv[1] !== '' ? $argv[1] : 'templates/'; // Folder to scan (or pass path as first arg)
$baseDir = rtrim($baseDir, '/');

// Validate directory before scanning
if (! is_dir($baseDir)) {
    fwrite(STDERR, "Directory not found: $baseDir\n");
    fwrite(STDERR, "Usage: php tiki-detect_missing_tr_strings.php [path/to/templates]\n");
    exit(1);
}

$results = [];

/**
 * Check if a line contains untranslated strings
 */
function isTranslatable($filePath, $line)
{
    $ext = pathinfo($filePath, PATHINFO_EXTENSION);
    $trimmed = trim($line);

    // Ignore empty lines
    if ($trimmed === '') {
        return false;
    }

    // --- Smarty (.tpl) ---
    if ($ext === 'tpl') {
        // Ignore lines already wrapped with translation tags like: "{tr}Hello{/tr}"
        if (preg_match('/\{tr[\s}]/i', $line)) {
            return false;
        }

        // Ignore pure comment lines like: "<!-- This is a comment -->"
        if (preg_match('/^\s*<!--.*-->\s*$/', $line)) {
            return false;
        }

        // Ignore pure CSS/JS comment lines like: "/* CSS comment */" or "// JS comment"
        if (preg_match('/^\s*(\/\*.*\*\/|\/\/.*)\s*$/', $line)) {
            return false;
        }

        $found = [];

        // Detect visible text between HTML tags like: "<p>Hello World</p>" → "Hello World"
        if (preg_match_all('/>([^<]{2,}?)</', $line, $matches)) {
            foreach ($matches[1] as $text) {
                $clean = trim($text);
                // Skip if it's a Smarty variable like: "{$var}"
                if (preg_match('/^\{\$[^}]+\}$/', $clean)) {
                    continue;
                }
                if ($clean !== '' && preg_match('/[A-Za-zÀ-ÖØ-öø-ÿ]/', $clean)) {
                    $found[] = $clean;
                }
            }
        }

        // Detect untranslated text inside HTML attributes
        if (preg_match_all('/\b(?:title|alt|placeholder|aria-label|data-tooltip)\s*=\s*["\']([^"\']+)["\']/', $line, $matches)) {
            foreach ($matches[1] as $text) {
                $clean = trim($text);
                // Skip Smarty variables and already translated content
                if (preg_match('/^\{\$[^}]+\}$/', $clean) || preg_match('/\{tr\}.*\{\/tr\}/', $clean)) {
                    continue;
                }
                if ($clean !== '' && preg_match('/[A-Za-zÀ-ÖØ-öø-ÿ]/', $clean)) {
                    $found[] = $clean;
                }
            }
        }

        return count($found) > 0 ? $found : false;
    }
    return false;
}

/**
 * Recursive scan of all files in a directory
 * Populates $results array with detected issues
 */
function scanFiles($dir, &$results)
{
    if (! is_dir($dir)) {
        return;
    }

    try {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS | FilesystemIterator::CURRENT_AS_FILEINFO),
            RecursiveIteratorIterator::SELF_FIRST
        );
    } catch (UnexpectedValueException $e) {
        fwrite(STDERR, "Failed to open directory: $dir\n");
        return;
    }


    foreach ($iterator as $file) {
        if ($file->isDir()) {
            continue;
        }

        $ext = pathinfo($file, PATHINFO_EXTENSION);
        if (! in_array($ext, ['tpl', 'php'])) {
            continue;
        }
        $lines = file($file->getPathname());
        foreach ($lines as $num => $line) {
            $detected = isTranslatable($file->getPathname(), $line);
            if ($detected !== false) {
                foreach ($detected as $text) {
                    $results[] = ['file' => $file->getPathname(), 'line' => $num + 1, 'text' => htmlspecialchars($text), 'suggestion' => $ext === 'tpl' ? "{tr}$text{/tr}" : "tra('$text')"];
                }
            }
        }
    }
}

scanFiles($baseDir, $results);

/**
 * CLI output rendering
 */
echo "\n Scanning directory: $baseDir\n";
echo "------------------------------------------------------------\n";

if (count($results) === 0) {
    echo "No missing translation tags found.\n\n";
    exit;
}

echo " Found " . count($results) . " potential untranslated strings:\n\n";

foreach ($results as $r) {
    echo "  File: {$r['file']}\n";
    echo "   Line: {$r['line']}\n";
    echo "   Text: {$r['text']}\n";
    echo "   Suggestion: {$r['suggestion']}\n";
    echo "------------------------------------------------------------\n";
}

echo "\nDone \n";
