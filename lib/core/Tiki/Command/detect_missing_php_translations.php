<?php

/**
 * PHP Translation Detector
 * Detects untranslated strings in PHP files (missing tra() or _())
 */

$baseDir = isset($argv[1]) && $argv[1] !== '' ? $argv[1] : __DIR__;
$baseDir = rtrim($baseDir, '/');

if (! is_dir($baseDir)) {
    fwrite(STDERR, "Directory not found: $baseDir\n");
    fwrite(STDERR, "Usage: php detect_missing_php_translations.php [path/to/dir]\n");
    exit(1);
}

$results = [];

/**
 * Checks if a line contains an untranslated string
 */
function detectUntranslatedInPHP($filePath, $line)
{
    $trimmed = trim($line);

    // Ignore empty lines, comments, and variables
    if ($trimmed === '' || preg_match('/^\s*(\/\/|#|\/\*|\*|\?>)/', $trimmed)) {
        return false;
    }

    // Ignore if line contains a translation function
    if (preg_match('/\b(tra|_)\s*\(/', $line)) {
        return false;
    }

    // Ignore array key checks and technical comparisons
    if (preg_match('/array_key_exists\s*\(|isset\s*\(|\$\w+\s*==\s*["\']|["\']\s*==\s*\$\w+/', $line)) {
        return false;
    }

    // Ignore SQL queries
    if (preg_match('/\$query\s*=|SELECT\s+|INSERT\s+|UPDATE\s+|DELETE\s+|FROM\s+|WHERE\s+|ORDER\s+BY|GROUP\s+BY/i', $line)) {
        return false;
    }

    // Ignore lines containing SQL keywords or table names
    if (preg_match('/\b(select|insert|update|delete|from|where|order|group|by|limit|offset|join|inner|outer|left|right|distinct|count|sum|max|min|avg|having|union|exists|like|between|in|not|null|and|or|as|on|into|values|set)\b/i', $line)) {
        return false;
    }

    // Ignore table/column names with backticks
    if (preg_match('/`[^`]+`/', $line)) {
        return false;
    }

    // Ignore HTTP headers and redirections
    if (preg_match('/header\s*\(/i', $line)) {
        return false;
    }

    // Ignore configuration keys and technical identifiers
    if (preg_match('/["\'](?:preference|rules|config|setting|option|key|id|name|type|mode|status|flag)\b/i', $line)) {
        return false;
    }

    // Search for strings between quotes or apostrophes - FIXED REGEX
    if (preg_match_all('/(["\'])([A-Za-zÀ-ÖØ-öø-ÿ][^"\']*)\1/', $line, $matches)) {
        $found = [];
        foreach ($matches[2] as $text) {
            $clean = trim($text);

            // Ignore paths, variables, HTML or code
            if (preg_match('/[\$%{}<>\/\\\]/', $clean)) {
                continue;
            }

            // Ignore URLs, file paths and redirections
            if (preg_match('/^(https?:\/\/|location:\s*|index\.php|\.php|\.html|\.js|\.css)/i', $clean)) {
                continue;
            }

            // Ignore CSS classes and HTML attributes (containing hyphens, underscores, or typical CSS/HTML patterns)
            if (preg_match('/^[a-z0-9_-]+(-[a-z0-9]+)*$/i', $clean) || preg_match('/\b(form-control|btn-|text-|bg-|border-|col-|row|container|navbar|dropdown|modal|alert|card|table|list|group)\b/i', $clean)) {
                continue;
            }

            // Ignore strings too short or purely numeric
            if (strlen($clean) < 2 || is_numeric($clean)) {
                continue;
            }

            // Ignore technical keywords and configuration keys
            if (preg_match('/^(preference|rules|config|setting|option|key|id|name|type|mode|status|flag|error|warning|info|debug|true|false|null|undefined|array|object|string|int|float|bool)$/i', $clean)) {
                continue;
            }

            // Ignore database/table names and technical identifiers
            if (preg_match('/^(tiki_|db_|table_|field_|column_|index_)/', $clean)) {
                continue;
            }

            // Ignore SQL-related strings
            if (preg_match('/^(select|insert|update|delete|from|where|order|group|by|limit|offset|join|inner|outer|left|right|distinct|count|sum|max|min|avg|having|union|exists|like|between|in|not|null|and|or|as|on|into|values|set)$/i', $clean)) {
                continue;
            }

            // Ignore HTTP headers and file extensions
            if (preg_match('/^(location|content-type|content-disposition|cache-control|expires|pragma|\.php|\.html|\.js|\.css|\.png|\.jpg|\.gif)$/i', $clean)) {
                continue;
            }

            // Ignore CSS variables and properties
            if (preg_match('/^--[a-z-]+$|^[a-z-]+:[a-z-]+$/i', $clean)) {
                continue;
            }

            // Keep readable text that looks like user-facing content
            if (preg_match('/[A-Za-zÀ-ÖØ-öø-ÿ]/', $clean)) {
                $found[] = $clean;
            }
        }
        return count($found) > 0 ? $found : false;
    }

    return false;
}

/**
 * Recursively scans PHP files
 */
function scanPHPFiles($dir, &$results)
{
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if ($file->isDir() || pathinfo($file, PATHINFO_EXTENSION) !== 'php') {
            continue;
        }

        $lines = @file($file->getPathname());
        if (! $lines) {
            continue;
        }

        foreach ($lines as $num => $line) {
            $detected = detectUntranslatedInPHP($file->getPathname(), $line);
            if ($detected !== false) {
                foreach ($detected as $text) {
                    $results[] = [
                        'file' => $file->getPathname(),
                        'line' => $num + 1,
                        'text' => htmlspecialchars($text),
                        'suggestion' => "tra('$text')"
                    ];
                }
            }
        }
    }
}

/**
 * Execution
 */
echo "\n Scanning PHP files in: $baseDir\n";
echo "------------------------------------------------------------\n";

scanPHPFiles($baseDir, $results);

echo "" . count($results) . " untranslated strings found:\n\n";

foreach ($results as $r) {
    echo "  File: {$r['file']}\n";
    echo "     Line: {$r['line']}\n";
    echo "     Text: {$r['text']}\n";
    echo "      Suggestion: {$r['suggestion']}\n";
    echo "------------------------------------------------------------\n";
}

echo "\nCompleted \n";
