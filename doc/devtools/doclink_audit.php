<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

/**
 * Documentation Link Auditor — validate documentation links in source.
 *
 * Scans existing help links in:
 *   - lib/prefs/*.php (preference "help" metadata and hardcoded doc.tiki.org URLs)
 *   - templates/ recursively ({help url="..."} and hardcoded doc.tiki.org URLs; wizards excluded)
 *
 * Reports NON_CANONICAL slugs (+ or space) and EXTERNAL URLs. Does not HTTP-check doc.tiki.org
 * (use Restore Help Pages on doc.tiki.org for missing wiki pages).
 *
 * Skips link rows from dynamic wikiplugin/trackerfield pref modules.
 *
 * Usage (from Tiki root):
 *   php doc/devtools/doclink_audit.php [--include-missing] [--csv=PATH]
 *
 * Default CSV includes NON_CANONICAL and EXTERNAL rows only.
 * MISSING preferences (experimental) are counted in the console summary; use --include-missing to export them.
 *
 * Requires a configured Tiki instance (tiki-setup.php / database), like prefreport.php.
 * See also: doc/devtools/prefreport.php
 *
 * Preference help is parsed from lib/prefs source when possible; pref modules with
 * dynamically generated entries (wikiplugin, trackerfield, etc.) are executed for a
 * complete catalog. OCR is parsed from source only (no shell side effects).
 */

if (PHP_SAPI !== 'cli') {
    die("This script must be run from the command line.\n");
}

$options = getopt('h', ['help', 'include-missing', 'csv:']);

if (isset($options['h']) || isset($options['help'])) {
    echo <<<HELP
Documentation Link Auditor

Validates existing documentation links in lib/prefs and templates.
Reports non-canonical doc.tiki.org slugs (+ or space) and external URLs.

Usage (from Tiki root):
  php doc/devtools/doclink_audit.php [options]

Options:
  --include-missing   Include MISSING preference rows in the CSV export (experimental)
  --csv=PATH          CSV output path (default: dump/doclink_audit.csv)
  -h, --help          Show this help

Default CSV columns: source_type, location, line, preference, original_value,
resolved_url, status, suggested_slug

CSV default: NON_CANONICAL and EXTERNAL rows. Statuses: OK, MISSING, NON_CANONICAL, EXTERNAL

HELP;
    exit(0);
}

$includeMissing = isset($options['include-missing']);
$csvPath = $options['csv'] ?? 'dump/doclink_audit.csv';

chdir(dirname(__DIR__, 2));

require_once 'tiki-setup.php';

$baseHelpUrl = 'https://doc.tiki.org/';

$prefslib = TikiLib::lib('prefs');
prime_prefslib_skipped_modules(['ocr' => 'lib/prefs/ocr.php']);

$allPrefs = collect_all_preferences();
$rows = [];
$missingRows = [];
$missingCount = 0;
$unresolvedHelpCount = 0;

$skipLinkFiles = ['wikiplugin.php', 'trackerfield.php'];

foreach ($allPrefs as $prefName => $info) {
    $help = $info['help'] ?? null;
    $helpUnresolved = ! empty($info['help_unresolved']);
    $hasStringHelp = is_string($help) && $help !== '';

    if (! $hasStringHelp) {
        if ($helpUnresolved) {
            $unresolvedHelpCount++;
            continue;
        }
        $locations = $prefslib->getPreferenceLocations($prefName);
        if (! empty($locations)) {
            $prefInfo = $prefslib->getPreference($prefName, false);
            if ($prefInfo !== false && empty($prefInfo['hide'])) {
                $missingCount++;
                if ($includeMissing) {
                    $missingRows[] = make_row([
                        'source_type' => 'preference',
                        'location' => $info['file'],
                        'line' => $info['line'],
                        'preference' => $prefName,
                        'original_value' => '',
                        'resolved_url' => '',
                        'status' => 'MISSING',
                    ]);
                }
            }
        }
        continue;
    }

    if (in_array(basename($info['file']), $skipLinkFiles, true)) {
        continue;
    }

    $rows[] = preference_link_row($prefName, $info, $baseHelpUrl);
}

$rows = array_merge($rows, scan_pref_hardcoded_urls());

$templateRows = scan_templates($baseHelpUrl);
$rows = array_merge($rows, $templateRows);

foreach ($rows as $key => $row) {
    $rows[$key] = classify_row($row);
}

$rows = array_merge($rows, $missingRows);

$csvRows = filter_csv_rows($rows, $includeMissing);

if (dirname($csvPath) !== '' && ! is_dir(dirname($csvPath))) {
    mkdir(dirname($csvPath), 0755, true);
}

write_csv($csvRows, $csvPath);
print_summary($rows, $missingCount, $allPrefs, $includeMissing, $csvPath, $csvRows, $unresolvedHelpCount);

exit(0);

/**
 * @return list<string>
 */
function get_pref_php_files()
{
    return array_values(array_filter(glob('lib/prefs/*.php'), static fn ($path) => basename($path) !== 'index.php'));
}

/**
 * @return array<string, array{file: string, help: mixed, line: int, help_unresolved?: bool}>
 */
function collect_all_preferences()
{
    $all = [];
    $staticOnlyModules = ['ocr'];

    foreach (get_pref_php_files() as $file) {
        $module = substr(basename($file), 0, -4);
        $file = str_replace('\\', '/', $file);
        $staticHelp = extract_pref_help_from_source($file);

        if (in_array($module, $staticOnlyModules, true)) {
            $list = $staticHelp;
        } else {
            $function = "prefs_{$module}_list";

            if (! function_exists($function)) {
                include_once $file;
            }

            if (! function_exists($function)) {
                continue;
            }

            $list = $function();
        }

        foreach ($list as $prefName => $raw) {
            $static = $staticHelp[$prefName] ?? [];
            $help = $static['help'] ?? null;
            $helpUnresolved = ! empty($static['_help_unresolved']);

            if (($help === null || $help === '') && is_array($raw) && is_string($raw['help'] ?? null) && $raw['help'] !== '') {
                $help = $raw['help'];
                $helpUnresolved = false;
            }

            $all[$prefName] = [
                'file' => $file,
                'help' => $help,
                'line' => find_pref_line($file, $prefName),
                'help_unresolved' => $helpUnresolved,
            ];
        }
    }

    return $all;
}

/**
 * Parse preference help metadata from source without executing prefs_*_list().
 *
 * @return array<string, array{help?: string, _help_unresolved?: bool}>
 */
function extract_pref_help_from_source($file)
{
    return extract_pref_help_from_tokens(read_required_file($file));
}

/**
 * @return string
 */
function read_required_file($file)
{
    $content = file_get_contents($file);
    if ($content === false) {
        fwrite(STDERR, "Failed to read file: {$file}\n");
        exit(1);
    }

    return $content;
}

/**
 * Token-based parser for prefs_*_list() arrays.
 *
 * Supports return [...] and $var = [...] pref definitions. Handles string literals,
 * concatenated strings, simple variables assigned to string literals, and tra()/tr()
 * calls with a literal first argument. Complex expressions are flagged as unresolved.
 *
 * @return array<string, array{help?: string, _help_unresolved?: bool}>
 */
function extract_pref_help_from_tokens($content)
{
    $tokens = token_get_all($content);
    $count = count($tokens);
    $vars = [];
    $list = [];
    $inPrefArray = false;
    $prefArrayBaseDepth = -1;
    $squareDepth = 0;
    $parenDepth = 0;

    for ($i = 0; $i < $count; $i++) {
        $token = $tokens[$i];
        $text = is_array($token) ? $token[1] : $token;
        $tokenId = is_array($token) ? $token[0] : null;

        if ($text === '[') {
            $squareDepth++;
            continue;
        }
        if ($text === ']') {
            if ($inPrefArray && $squareDepth === $prefArrayBaseDepth) {
                $inPrefArray = false;
                $prefArrayBaseDepth = -1;
            }
            $squareDepth--;
            continue;
        }
        if ($text === '(') {
            $parenDepth++;
            continue;
        }
        if ($text === ')') {
            $parenDepth--;
            continue;
        }

        if ($tokenId === T_VARIABLE && $parenDepth === 0 && ! $inPrefArray) {
            $name = ltrim($text, '$');
            $next = next_non_whitespace_token($tokens, $i + 1);
            if ($next !== null && token_text($tokens[$next]) === '=') {
                $afterEq = next_non_whitespace_token($tokens, $next + 1);
                if ($afterEq !== null && token_text($tokens[$afterEq]) === '[') {
                    $inPrefArray = true;
                    $prefArrayBaseDepth = $squareDepth + 1;
                    continue;
                }

                $expr = read_expression_tokens($tokens, $next + 1);
                $value = evaluate_help_expression($expr, $vars);
                if (is_string($value)) {
                    $vars[$name] = $value;
                }
            }
        }

        if (! $inPrefArray && $tokenId === T_RETURN) {
            $next = next_non_whitespace_token($tokens, $i + 1);
            if ($next !== null && token_text($tokens[$next]) === '[') {
                $inPrefArray = true;
                $prefArrayBaseDepth = $squareDepth + 1;
            }
            continue;
        }

        if (! $inPrefArray || $squareDepth !== $prefArrayBaseDepth) {
            continue;
        }

        // Parse top-level pref entry: 'pref_name' => [ ... ]
        if ($tokenId === T_CONSTANT_ENCAPSED_STRING) {
            $prefName = decode_php_string_literal($text);
            if ($prefName === null || ! preg_match('/^[a-zA-Z0-9_]+$/', $prefName)) {
                continue;
            }

            $arrow = next_non_whitespace_token($tokens, $i + 1);
            $open = $arrow !== null ? next_non_whitespace_token($tokens, $arrow + 1) : null;
            if (
                $arrow === null
                || token_text($tokens[$arrow]) !== '=>'
                || $open === null
                || token_text($tokens[$open]) !== '['
            ) {
                continue;
            }

            $parsed = parse_pref_entry_help($tokens, $open, $vars);
            if ($parsed['help'] !== null && $parsed['help'] !== '') {
                $list[$prefName]['help'] = $parsed['help'];
            } elseif ($parsed['unresolved']) {
                $list[$prefName]['_help_unresolved'] = true;
            } else {
                $list[$prefName] = $list[$prefName] ?? [];
            }
        }
    }

    return $list;
}

/**
 * @param array<int, mixed> $tokens
 * @param int $entryOpenBracketPos Position of "[" after pref "=>"
 * @param array<string, string> $vars
 * @return array{help: ?string, unresolved: bool}
 */
function parse_pref_entry_help(array $tokens, int $entryOpenBracketPos, array $vars): array
{
    $depth = 1;
    $help = null;
    $unresolved = false;
    $count = count($tokens);

    for ($i = $entryOpenBracketPos + 1; $i < $count; $i++) {
        $text = token_text($tokens[$i]);
        if ($text === '[') {
            $depth++;
            continue;
        }
        if ($text === ']') {
            $depth--;
            if ($depth === 0) {
                break;
            }
            continue;
        }

        if ($depth !== 1 || ! is_array($tokens[$i]) || $tokens[$i][0] !== T_CONSTANT_ENCAPSED_STRING) {
            continue;
        }

        $key = decode_php_string_literal($tokens[$i][1]);
        if ($key !== 'help') {
            continue;
        }
        $arrow = next_non_whitespace_token($tokens, $i + 1);
        if ($arrow === null || token_text($tokens[$arrow]) !== '=>') {
            continue;
        }

        $expr = read_expression_tokens($tokens, $arrow + 1);
        $resolved = evaluate_help_expression($expr, $vars);
        if (is_string($resolved) && $resolved !== '') {
            $help = $resolved;
        } else {
            $unresolved = true;
        }
        break;
    }

    return ['help' => $help, 'unresolved' => $unresolved];
}

/**
 * @param array<int, mixed> $tokens
 * @return list<mixed>
 */
function read_expression_tokens(array $tokens, int $startPos): array
{
    $expr = [];
    $squareDepth = 0;
    $parenDepth = 0;
    $braceDepth = 0;
    $count = count($tokens);

    for ($i = $startPos; $i < $count; $i++) {
        $text = token_text($tokens[$i]);
        if ($text === '[') {
            $squareDepth++;
        } elseif ($text === ']') {
            if ($squareDepth === 0 && $parenDepth === 0 && $braceDepth === 0) {
                break;
            }
            $squareDepth--;
        } elseif ($text === '(') {
            $parenDepth++;
        } elseif ($text === ')') {
            $parenDepth--;
        } elseif ($text === '{') {
            $braceDepth++;
        } elseif ($text === '}') {
            $braceDepth--;
        } elseif ($text === ',' && $squareDepth === 0 && $parenDepth === 0 && $braceDepth === 0) {
            break;
        } elseif ($text === ';' && $squareDepth === 0 && $parenDepth === 0 && $braceDepth === 0) {
            break;
        }

        $expr[] = $tokens[$i];
    }

    return $expr;
}

/**
 * @param list<mixed> $exprTokens
 * @param array<string, string> $vars
 */
function evaluate_help_expression(array $exprTokens, array $vars): ?string
{
    $parts = [];
    $segment = [];
    $parenDepth = 0;

    foreach ($exprTokens as $token) {
        $text = token_text($token);
        if ($text === '(') {
            $parenDepth++;
        } elseif ($text === ')') {
            $parenDepth--;
        }

        if ($text === '.' && $parenDepth === 0) {
            $parts[] = $segment;
            $segment = [];
            continue;
        }
        $segment[] = $token;
    }
    if (! empty($segment)) {
        $parts[] = $segment;
    }

    if (empty($parts)) {
        return null;
    }

    $resolved = '';
    foreach ($parts as $part) {
        $value = resolve_help_segment($part, $vars);
        if (! is_string($value)) {
            return null;
        }
        $resolved .= $value;
    }

    return $resolved;
}

/**
 * @param list<mixed> $segmentTokens
 * @param array<string, string> $vars
 */
function resolve_help_segment(array $segmentTokens, array $vars): ?string
{
    $segmentTokens = array_values(array_filter($segmentTokens, static function ($token) {
        return ! is_array($token) || ! in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true);
    }));
    if (empty($segmentTokens)) {
        return null;
    }

    if (count($segmentTokens) === 1) {
        $token = $segmentTokens[0];
        if (is_array($token) && $token[0] === T_CONSTANT_ENCAPSED_STRING) {
            return decode_php_string_literal($token[1]);
        }
        if (is_array($token) && $token[0] === T_VARIABLE) {
            return $vars[ltrim($token[1], '$')] ?? null;
        }
        return null;
    }

    // Handle tra('...') or tr('...') with a literal first argument.
    if (
        count($segmentTokens) >= 4
        && is_array($segmentTokens[0])
        && $segmentTokens[0][0] === T_STRING
        && in_array(strtolower($segmentTokens[0][1]), ['tra', 'tr'], true)
        && token_text($segmentTokens[1]) === '('
        && is_array($segmentTokens[2])
        && $segmentTokens[2][0] === T_CONSTANT_ENCAPSED_STRING
    ) {
        return decode_php_string_literal($segmentTokens[2][1]);
    }

    return null;
}

/**
 * @param array<int, mixed> $tokens
 */
function next_non_whitespace_token(array $tokens, int $startPos): ?int
{
    $count = count($tokens);
    for ($i = $startPos; $i < $count; $i++) {
        if (! is_array($tokens[$i])) {
            return $i;
        }
        if (! in_array($tokens[$i][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
            return $i;
        }
    }
    return null;
}

function token_text($token): string
{
    return is_array($token) ? $token[1] : $token;
}

function decode_php_string_literal(string $literal): ?string
{
    $len = strlen($literal);
    if ($len < 2) {
        return null;
    }
    $quote = $literal[0];
    if (($quote !== "'" && $quote !== '"') || $literal[$len - 1] !== $quote) {
        return null;
    }

    $inner = substr($literal, 1, -1);
    if ($quote === "'") {
        return str_replace(["\\'", '\\\\'], ["'", '\\'], $inner);
    }

    $decoded = stripcslashes($inner);
    return $decoded === '' && $inner !== '' ? null : $decoded;
}

/**
 * Preload statically parsed pref modules into PrefsLib cache (avoids shell side effects).
 *
 * @param array<string, string> $modules module name => pref file path
 */
function prime_prefslib_skipped_modules(array $modules)
{
    $prefslib = TikiLib::lib('prefs');
    $ref = new ReflectionClass($prefslib);
    $prop = $ref->getProperty('files');
    $prop->setAccessible(true);
    $files = $prop->getValue($prefslib);

    foreach ($modules as $module => $path) {
        if (isset($files[$module])) {
            continue;
        }
        $parsed = extract_pref_help_from_source($path);
        $modulePrefs = [];
        foreach ($parsed as $name => $raw) {
            $modulePrefs[$name] = [
                'name' => $name,
                'type' => 'text',
                'default' => '',
                'help' => $raw['help'] ?? null,
            ];
        }
        $files[$module] = $modulePrefs;
    }

    $prop->setValue($prefslib, $files);
}

function find_pref_line($file, $prefName)
{
    static $linesByFile = [];

    if (! isset($linesByFile[$file])) {
        $linesByFile[$file] = explode("\n", read_required_file($file));
    }

    $pattern = "/['\"]" . preg_quote($prefName, '/') . "['\"]\s*=>/";
    foreach ($linesByFile[$file] as $num => $line) {
        if (preg_match($pattern, $line) && ! preg_match('/^\s*\/\//', $line)) {
            return $num + 1;
        }
    }
    return 0;
}

/**
 * @param array<string, mixed> $fields
 * @return array<string, mixed>
 */
function make_row(array $fields)
{
    return array_merge([
        'source_type' => '',
        'location' => '',
        'line' => 0,
        'preference' => '',
        'original_value' => '',
        'resolved_url' => '',
        'status' => '',
        'suggested_slug' => '',
    ], $fields);
}

/**
 * @param array{file: string, help: string, line: int} $info
 */
function preference_link_row($prefName, array $info, $baseHelpUrl)
{
    $help = $info['help'];
    return make_row([
        'source_type' => 'preference',
        'location' => $info['file'],
        'line' => $info['line'],
        'preference' => $prefName,
        'original_value' => $help,
        'resolved_url' => resolve_url($baseHelpUrl, $help),
    ]);
}

function resolve_url($baseHelpUrl, $value)
{
    $value = trim($value);
    if (preg_match('/^https?:\/\//i', $value)) {
        return $value;
    }
    return $baseHelpUrl . $value;
}

function is_auditable_template_help_url($url)
{
    $url = trim($url);

    return $url !== '' && $url !== '#' && ! str_contains($url, '$');
}

/**
 * @return list<array<string, mixed>>
 */
function scan_pref_hardcoded_urls()
{
    $rows = [];

    foreach (get_pref_php_files() as $file) {
        $file = str_replace('\\', '/', $file);
        $rows = array_merge($rows, collect_hardcoded_doc_tiki_url_rows(read_required_file($file), $file, 'preference_hardcoded'));
    }

    return $rows;
}

/**
 * @return list<array<string, mixed>>
 */
function collect_hardcoded_doc_tiki_url_rows($content, $file, $sourceType)
{
    $rows = [];

    if (! preg_match_all('#https?://doc\.tiki\.org[^\s"\'<>|]+#i', $content, $urlMatches, PREG_OFFSET_CAPTURE)) {
        return $rows;
    }

    foreach ($urlMatches[0] as $match) {
        $url = rtrim($match[0], '.,;');
        $rows[] = make_row([
            'source_type' => $sourceType,
            'location' => $file,
            'line' => offset_to_line($content, $match[1]),
            'original_value' => $url,
            'resolved_url' => $url,
        ]);
    }

    return $rows;
}

/**
 * @return list<string>
 */
function collect_template_files()
{
    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator('templates', FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $fileInfo) {
        if (! $fileInfo->isFile() || $fileInfo->getExtension() !== 'tpl') {
            continue;
        }
        $path = str_replace('\\', '/', $fileInfo->getPathname());
        if (str_starts_with($path, 'templates/wizard/')) {
            continue;
        }
        $files[] = $path;
    }

    sort($files);

    return $files;
}

/**
 * @return list<array<string, mixed>>
 */
function scan_templates($baseHelpUrl)
{
    $rows = [];

    foreach (collect_template_files() as $file) {
        $content = read_required_file($file);

        $helpTagCount = preg_match_all(
            '/\{help\b\s+url\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s}]+))/is',
            $content,
            $matches,
            PREG_OFFSET_CAPTURE
        );
        if ($helpTagCount) {
            foreach ($matches[0] as $i => $fullMatch) {
                $url = $matches[1][$i][0] !== '' ? $matches[1][$i][0]
                    : ($matches[2][$i][0] !== '' ? $matches[2][$i][0] : $matches[3][$i][0]);
                if (! is_auditable_template_help_url($url)) {
                    continue;
                }
                $rows[] = make_row([
                    'source_type' => 'template_help',
                    'location' => $file,
                    'line' => offset_to_line($content, $fullMatch[1]),
                    'original_value' => $url,
                    'resolved_url' => resolve_url($baseHelpUrl, $url),
                ]);
            }
        }

        $rows = array_merge($rows, collect_hardcoded_doc_tiki_url_rows($content, $file, 'template_hardcoded'));
    }

    return $rows;
}

function offset_to_line($content, $offset)
{
    return substr_count($content, "\n", 0, $offset) + 1;
}

/**
 * @param array<string, mixed> $row
 * @return array<string, mixed>
 */
function classify_row(array $row)
{
    if (($row['status'] ?? '') === 'MISSING') {
        return $row;
    }

    $original = $row['original_value'];
    $resolved = $row['resolved_url'];

    if (is_doc_external_url($resolved)) {
        $row['status'] = 'EXTERNAL';
        return $row;
    }

    if (url_has_non_canonical_slug($resolved)) {
        $row['status'] = 'NON_CANONICAL';
        $row['suggested_slug'] = suggest_canonical_slug($original, $resolved);
        return $row;
    }

    $row['status'] = 'OK';
    return $row;
}

function is_doc_external_url($url)
{
    if (! preg_match('/^https?:\/\//i', $url)) {
        return false;
    }
    $host = parse_url($url, PHP_URL_HOST);
    if ($host === null) {
        return false;
    }
    $host = strtolower($host);
    return $host !== 'doc.tiki.org' && $host !== 'www.doc.tiki.org';
}

function url_has_non_canonical_slug($url)
{
    $path = urldecode(parse_url($url, PHP_URL_PATH) ?? '');
    $fragment = urldecode(parse_url($url, PHP_URL_FRAGMENT) ?? '');
    return slug_contains_non_canonical_chars($path) || slug_contains_non_canonical_chars($fragment);
}

function slug_contains_non_canonical_chars($segment)
{
    return str_contains($segment, '+') || str_contains($segment, ' ');
}

function suggest_canonical_slug($original, $resolved)
{
    if (! preg_match('/^https?:\/\//i', $original)) {
        $fragment = '';
        if (str_contains($original, '#')) {
            [$original, $fragment] = explode('#', $original, 2);
            $fragment = '#' . canonicalize_slug_chars($fragment);
        }
        return canonicalize_slug_chars($original) . $fragment;
    }
    $parts = parse_url($resolved);
    if ($parts === false) {
        return '';
    }
    $path = canonicalize_slug_chars($parts['path'] ?? '');
    $out = ($parts['scheme'] ?? 'https') . '://' . ($parts['host'] ?? 'doc.tiki.org') . $path;
    if (! empty($parts['fragment'])) {
        $out .= '#' . canonicalize_slug_chars(urldecode($parts['fragment']));
    }
    return $out;
}

function canonicalize_slug_chars($text)
{
    return str_replace(['+', ' '], '-', $text);
}

/**
 * @param list<array<string, mixed>> $rows
 * @return list<array<string, mixed>>
 */
function filter_csv_rows(array $rows, $includeMissing)
{
    $allowed = ['NON_CANONICAL', 'EXTERNAL'];
    if ($includeMissing) {
        $allowed[] = 'MISSING';
    }

    return array_values(array_filter($rows, static function ($row) use ($allowed) {
        return in_array($row['status'], $allowed, true);
    }));
}

/**
 * @param list<array<string, mixed>> $rows
 */
function write_csv(array $rows, $path)
{
    $handle = fopen($path, 'w');
    if ($handle === false) {
        fwrite(STDERR, "Could not write CSV to {$path}\n");
        exit(1);
    }

    $headers = [
        'source_type',
        'location',
        'line',
        'preference',
        'original_value',
        'resolved_url',
        'status',
        'suggested_slug',
    ];
    fputcsv($handle, $headers, escape: TikiLib::TIKI_GLOBAL_CSV_ESCAPE_CHAR);

    foreach ($rows as $row) {
        fputcsv($handle, [
            $row['source_type'],
            $row['location'],
            $row['line'],
            $row['preference'],
            $row['original_value'],
            $row['resolved_url'],
            $row['status'],
            $row['suggested_slug'],
        ], escape: TikiLib::TIKI_GLOBAL_CSV_ESCAPE_CHAR);
    }

    fclose($handle);
}

/**
 * @param list<array<string, mixed>> $rows
 * @param list<array<string, mixed>> $csvRows
 */
function print_summary(
    array $rows,
    $missingCount,
    array $allPrefs,
    $includeMissing,
    $csvPath,
    array $csvRows,
    int $unresolvedHelpCount
) {
    $prefsWithHelp = 0;
    foreach ($allPrefs as $info) {
        if (is_string($info['help'] ?? null) && $info['help'] !== '') {
            $prefsWithHelp++;
        }
    }

    $linkRows = array_filter($rows, static fn ($r) => ($r['status'] ?? '') !== 'MISSING');
    $templateHelp = count(array_filter($linkRows, static fn ($r) => $r['source_type'] === 'template_help'));
    $hardcoded = count(array_filter(
        $linkRows,
        static fn ($r) => in_array($r['source_type'], ['template_hardcoded', 'preference_hardcoded'], true)
    ));

    $counts = [
        'NON_CANONICAL' => 0,
        'EXTERNAL' => 0,
        'OK' => 0,
    ];

    foreach ($rows as $row) {
        $status = $row['status'] ?? '';
        if ($status === 'MISSING') {
            continue;
        }
        if (isset($counts[$status])) {
            $counts[$status]++;
        }
    }

    echo "\n# Documentation Link Audit\n\n";
    echo 'Preferences defined: ' . count($allPrefs) . "\n";
    echo 'Preferences with help (links scanned): ' . $prefsWithHelp . "\n";
    echo "Template {help} links scanned: {$templateHelp}\n";
    echo "Hardcoded doc.tiki.org URLs scanned: {$hardcoded}\n\n";
    echo 'Non-canonical links (+ or space): ' . $counts['NON_CANONICAL'] . "\n";
    echo 'External links: ' . $counts['EXTERNAL'] . "\n";
    echo "Missing documentation (admin-visible, no help): {$missingCount}\n\n";

    if (! $includeMissing) {
        echo "(use --include-missing to export missing entries to CSV)\n\n";
    }
    if ($unresolvedHelpCount > 0) {
        echo "Note: {$unresolvedHelpCount} preference help values could not be statically resolved "
            . "(e.g., computed expressions); they were not counted as missing.\n\n";
    }

    $csvNote = $includeMissing
        ? 'NON_CANONICAL, EXTERNAL, MISSING'
        : 'NON_CANONICAL, EXTERNAL only';
    echo "CSV: {$csvPath} ({$csvNote}, " . count($csvRows) . " rows)\n";

    print_detail_section($rows, 'NON_CANONICAL', 20);
    print_detail_section($rows, 'EXTERNAL', 10);

    if ($includeMissing) {
        print_detail_section($rows, 'MISSING', 20);
    }
}

/**
 * @param list<array<string, mixed>> $rows
 */
function print_detail_section(array $rows, $status, $limit)
{
    $matching = array_values(array_filter($rows, static fn ($r) => ($r['status'] ?? '') === $status));
    if (count($matching) === 0) {
        return;
    }

    echo "\n--- {$status} (" . count($matching) . ") ---\n";
    $shown = array_slice($matching, 0, $limit);
    foreach ($shown as $row) {
        $loc = $row['location'];
        if (! empty($row['line'])) {
            $loc .= ':' . $row['line'];
        }
        $pref = $row['preference'] !== '' ? $row['preference'] . ' | ' : '';
        $val = $row['resolved_url'] !== '' ? $row['resolved_url'] : $row['original_value'];
        echo "  {$row['source_type']} | {$pref}{$loc} | {$val}\n";
    }
    if (count($matching) > $limit) {
        $more = count($matching) - $limit;
        echo "  ... and {$more} more\n";
    }
}
