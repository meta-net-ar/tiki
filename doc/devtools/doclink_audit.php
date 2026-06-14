<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

/**
 * Documentation Link Auditor — audit admin-panel documentation links.
 *
 * Scans:
 *   - lib/prefs/*.php (preference "help" metadata)
 *   - templates/admin/*.tpl ({help url="..."} and hardcoded doc.tiki.org URLs)
 *
 * Excludes: admin icons, wizards, dynamic wikiplugin/trackerfield help.
 *
 * Usage (from Tiki root):
 *   php doc/devtools/doclink_audit.php [--online] [--include-missing] [--csv=PATH]
 *
 * Default CSV includes BROKEN, CHECK_FAILED, NON_CANONICAL, and EXTERNAL rows.
 * MISSING preferences are counted in the console summary; use --include-missing to export them.
 *
 * Broken link detection requires --online (HTTP checks against doc.tiki.org).
 *
 * BROKEN vs CHECK_FAILED (--online):
 *   BROKEN — doc.tiki.org responded with HTTP >= 400 (e.g. 404, 410); strong signal of a bad link.
 *   CHECK_FAILED — the HTTP request returned no status code (0): timeout, DNS, TLS, firewall,
 *     or curl unavailable. Not proof the page is missing; re-run when doc.tiki.org is reachable.
 *   Before per-URL checks, the script probes https://doc.tiki.org/; if that fails, per-URL checks
 *   are skipped entirely and a warning is printed (avoids mass false BROKEN results during outages).
 *
 * Requires a configured Tiki instance (tiki-setup.php / database), like prefreport.php.
 * See also: doc/devtools/prefreport.php
 *
 * CLI bootstrap suppresses PHP warnings from the full Tiki setup and avoids OCR
 * shell-command side effects when scanning preferences.
 */

if (PHP_SAPI !== 'cli') {
    die("This script must be run from the command line.\n");
}

$options = getopt('h', ['help', 'online', 'include-missing', 'csv:']);

if (isset($options['h']) || isset($options['help'])) {
    echo <<<HELP
Documentation Link Auditor

Usage (from Tiki root):
  php doc/devtools/doclink_audit.php [options]

Options:
  --online            HTTP-check doc.tiki.org links (see file header for BROKEN/CHECK_FAILED caveats)
  --include-missing   Include MISSING preference rows in the CSV export
  --csv=PATH          CSV output path (default: dump/doclink_audit.csv)
  -h, --help          Show this help

Default CSV columns: source_type, location, line, preference, original_value,
resolved_url, status, http_status, suggested_slug

Statuses: OK, BROKEN, CHECK_FAILED, MISSING, NON_CANONICAL, EXTERNAL

BROKEN means HTTP >= 400 from doc.tiki.org. CHECK_FAILED means no HTTP response (outage,
timeout, network). CHECK_FAILED is not proof a page is missing; re-run when doc.tiki.org
is reachable. If the doc.tiki.org probe fails, per-URL checks are skipped.

HELP;
    exit(0);
}

$online = isset($options['online']);
$includeMissing = isset($options['include-missing']);
$csvPath = $options['csv'] ?? 'dump/doclink_audit.csv';

chdir(dirname(__DIR__, 2));

define('TIKI_CONSOLE', 1);

$previousErrorHandler = set_error_handler(static function ($severity, $message, $file, $line) use (&$previousErrorHandler) {
    if (in_array($severity, [E_WARNING, E_NOTICE, E_DEPRECATED, E_USER_WARNING, E_USER_NOTICE, E_USER_DEPRECATED], true)) {
        return true;
    }
    if ($previousErrorHandler) {
        return $previousErrorHandler($severity, $message, $file, $line);
    }
    return false;
});
$displayErrors = ini_get('display_errors');
ini_set('display_errors', '0');

require_once 'tiki-setup.php';

ini_set('display_errors', $displayErrors);
restore_error_handler();

global $prefs;

$prefs['record_untranslated'] = 'n';

$prefs['feature_help'] = 'y';
$baseHelpUrl = 'https://doc.tiki.org/';
if (! empty($prefs['helpurl'])) {
    $baseHelpUrl = $prefs['helpurl'];
    if (str_starts_with($baseHelpUrl, 'http://doc.tiki.org')) {
        $baseHelpUrl = 'https://doc.tiki.org' . substr($baseHelpUrl, strlen('http://doc.tiki.org'));
    }
}
if (! str_ends_with($baseHelpUrl, '/')) {
    $baseHelpUrl .= '/';
}
$prefs['helpurl'] = $baseHelpUrl;

$prefslib = TikiLib::lib('prefs');
prime_prefslib_skipped_modules(['ocr' => 'lib/prefs/ocr.php']);

$allPrefs = collect_all_preferences();
$rows = [];
$missingRows = [];
$missingCount = 0;

$skipLinkFiles = ['wikiplugin.php', 'trackerfield.php'];

foreach ($allPrefs as $prefName => $info) {
    $help = $info['help'] ?? null;
    $hasStringHelp = is_string($help) && $help !== '';

    if (! $hasStringHelp) {
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

$adminRows = scan_admin_templates($baseHelpUrl);
$rows = array_merge($rows, $adminRows);

foreach ($rows as $key => $row) {
    $rows[$key] = classify_row($row);
}

if ($online) {
    $httpCheckResult = apply_http_checks($rows);
} else {
    $httpCheckResult = ['performed' => false, 'probe_ok' => false, 'probe_code' => 0];
}

$rows = array_merge($rows, $missingRows);

$csvRows = filter_csv_rows($rows, $includeMissing);

if (dirname($csvPath) !== '' && ! is_dir(dirname($csvPath))) {
    mkdir(dirname($csvPath), 0755, true);
}

write_csv($csvRows, $csvPath);
print_summary($rows, $missingCount, $allPrefs, $online, $includeMissing, $csvPath, $csvRows, $httpCheckResult);

exit(0);

/**
 * @return array<string, array{file: string, help: mixed, line: int}>
 */
function collect_all_preferences()
{
    $all = [];
    $skipExecuteModules = ['ocr'];

    $collectHandler = set_error_handler(static function ($severity, $message, $file, $line) {
        if (in_array($severity, [E_WARNING, E_NOTICE, E_DEPRECATED, E_USER_WARNING, E_USER_NOTICE, E_USER_DEPRECATED], true)) {
            return true;
        }
        return false;
    });

    foreach (glob('lib/prefs/*.php') as $file) {
        $module = substr(basename($file), 0, -4);
        if ($module === 'index') {
            continue;
        }

        $file = str_replace('\\', '/', $file);

        if (in_array($module, $skipExecuteModules, true)) {
            $list = extract_pref_help_from_source($file);
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
            $all[$prefName] = [
                'file' => $file,
                'help' => $raw['help'] ?? null,
                'line' => find_pref_line($file, $prefName),
            ];
        }
    }

    if ($collectHandler !== null) {
        restore_error_handler();
    }

    return $all;
}

/**
 * Parse preference metadata from source without executing prefs_*_list().
 *
 * Used for modules with heavy CLI side effects (e.g. shell commands).
 *
 * @return array<string, array{help?: string}>
 */
function extract_pref_help_from_source($file)
{
    $content = file_get_contents($file);
    if ($content === false) {
        return [];
    }

    $vars = [];
    if (preg_match('/function\s+prefs_[a-z0-9_]+_list\s*\([^)]*\)\s*\{(.*)/is', $content, $fnMatch)) {
        $beforeReturn = $fnMatch[1];
        if (preg_match('/^(.*?)(?:return\s+\[|\$prefs\s*=\s*\[)/s', $beforeReturn, $preambleMatch)) {
            if (
                    preg_match_all(
                        '/\$([a-zA-Z_][a-zA-Z0-9_]*)\s*=\s*([\'"])([^\'"]*)\2/',
                        $preambleMatch[1],
                        $varMatches,
                        PREG_SET_ORDER
                    )
            ) {
                foreach ($varMatches as $match) {
                    $vars[$match[1]] = $match[3];
                }
            }
        }
    }

    $list = [];
    if (preg_match_all("/^\s*['\"]([a-zA-Z0-9_]+)['\"]\s*=>\s*\[/m", $content, $prefMatches)) {
        foreach ($prefMatches[1] as $prefName) {
            $list[$prefName] = [];
        }
    }

    $currentPref = null;
    foreach (explode("\n", $content) as $line) {
        if (preg_match("/^\s*['\"]([a-zA-Z0-9_]+)['\"]\s*=>\s*\[/", $line, $prefMatch)) {
            $currentPref = $prefMatch[1];
            continue;
        }

        if ($currentPref === null || ! isset($list[$currentPref])) {
            continue;
        }

        if (preg_match("/^\s*['\"]help['\"]\s*=>\s*['\"]([^'\"]*)['\"]/", $line, $helpMatch)) {
            $list[$currentPref]['help'] = $helpMatch[1];
            continue;
        }

        if (preg_match("/^\s*['\"]help['\"]\s*=>\s*\$([a-zA-Z_][a-zA-Z0-9_]*)/", $line, $helpMatch)) {
            $help = $vars[$helpMatch[1]] ?? null;
            if (is_string($help) && $help !== '') {
                $list[$currentPref]['help'] = $help;
            }
            continue;
        }

        if (preg_match('/^\s*\],/', $line)) {
            $currentPref = null;
        }
    }

    return $list;
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
                'help' => $raw['help'] ?? null,
            ];
        }
        $files[$module] = $modulePrefs;
    }

    $prop->setValue($prefslib, $files);
}

function find_pref_line($file, $prefName)
{
    $lines = file($file);
    if ($lines === false) {
        return 0;
    }
    $pattern = "/['\"]" . preg_quote($prefName, '/') . "['\"]\s*=>/";
    foreach ($lines as $num => $line) {
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
        'http_status' => '',
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

/**
 * @return list<array<string, mixed>>
 */
function scan_admin_templates($baseHelpUrl)
{
    $rows = [];

    foreach (glob('templates/admin/*.tpl') as $file) {
        $file = str_replace('\\', '/', $file);
        $content = file_get_contents($file);
        if ($content === false) {
            continue;
        }

        if (preg_match_all('/\{help\s+url="([^"]+)"/', $content, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[1] as $match) {
                $url = $match[0];
                if (str_contains($url, '$')) {
                    continue;
                }
                $rows[] = make_row([
                    'source_type' => 'admin_help',
                    'location' => $file,
                    'line' => offset_to_line($content, $match[1]),
                    'original_value' => $url,
                    'resolved_url' => resolve_url($baseHelpUrl, $url),
                ]);
            }
        }

        if (preg_match_all('#https?://doc\.tiki\.org[^\s"\'<>]+#i', $content, $urlMatches, PREG_OFFSET_CAPTURE)) {
            foreach ($urlMatches[0] as $match) {
                $url = rtrim($match[0], '.,;');
                $rows[] = make_row([
                    'source_type' => 'admin_hardcoded',
                    'location' => $file,
                    'line' => offset_to_line($content, $match[1]),
                    'original_value' => $url,
                    'resolved_url' => $url,
                ]);
            }
        }
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

function is_doc_tiki_url($url)
{
    if (! preg_match('/^https?:\/\//i', $url)) {
        return true;
    }
    $host = parse_url($url, PHP_URL_HOST);
    if ($host === null) {
        return true;
    }
    $host = strtolower($host);
    return $host === 'doc.tiki.org' || $host === 'www.doc.tiki.org';
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
 * HTTP-verify doc.tiki.org links and update row status from the response.
 *
 * Probes https://doc.tiki.org/ first; if unreachable, per-URL checks are skipped.
 * code === 0 on an individual URL → CHECK_FAILED (connectivity/check failure).
 * code >= 400 → BROKEN (likely a real broken URL).
 * NON_CANONICAL rows are checked but keep their status; EXTERNAL/MISSING are skipped.
 *
 * @param list<array<string, mixed>> $rows
 * @return array{performed: bool, probe_ok: bool, probe_code: int}
 */
function apply_http_checks(array &$rows)
{
    $probe = probe_doc_tiki_org();
    if (! $probe['ok']) {
        $code = $probe['code'];
        $msg = $code === 0
            ? 'doc.tiki.org is unreachable (no HTTP response). Skipping per-URL checks.'
            : "doc.tiki.org probe returned HTTP {$code}. Skipping per-URL checks.";
        fwrite(STDERR, "Warning: {$msg}\n");
        return ['performed' => false, 'probe_ok' => false, 'probe_code' => $code];
    }

    $httpCache = [];

    foreach ($rows as $key => $row) {
        if (in_array($row['status'], ['MISSING', 'EXTERNAL'], true)) {
            continue;
        }

        $url = $row['resolved_url'];
        if ($url === '' || ! is_doc_tiki_url($url)) {
            continue;
        }

        if (! isset($httpCache[$url])) {
            $httpCache[$url] = check_url_http($url);
        }

        $code = $httpCache[$url];
        $rows[$key]['http_status'] = $code > 0 ? (string) $code : '';

        if ($row['status'] === 'NON_CANONICAL') {
            continue;
        }

        if ($code === 0) {
            $rows[$key]['status'] = 'CHECK_FAILED';
        } elseif ($code >= 400) {
            $rows[$key]['status'] = 'BROKEN';
        } else {
            $rows[$key]['status'] = 'OK';
        }
    }

    return ['performed' => true, 'probe_ok' => true, 'probe_code' => $probe['code']];
}

/**
 * @return array{ok: bool, code: int}
 */
function probe_doc_tiki_org()
{
    $code = check_url_http('https://doc.tiki.org/');
    return ['ok' => $code > 0 && $code < 400, 'code' => $code];
}

function check_url_http($url)
{
    if (! function_exists('curl_init')) {
        fwrite(STDERR, "Warning: curl extension not available; skipping HTTP checks.\n");
        return 0;
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_NOBODY => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_USERAGENT => 'Tiki-DocLinkAudit',
    ]);

    curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($code === 405 || $code === 501 || $code === 0) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_HTTPGET => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_USERAGENT => 'Tiki-DocLinkAudit',
        ]);
        curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    }

    return $code;
}

/**
 * @param list<array<string, mixed>> $rows
 * @return list<array<string, mixed>>
 */
function filter_csv_rows(array $rows, $includeMissing)
{
    $allowed = ['BROKEN', 'CHECK_FAILED', 'NON_CANONICAL', 'EXTERNAL'];
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
        return;
    }

    $headers = [
        'source_type',
        'location',
        'line',
        'preference',
        'original_value',
        'resolved_url',
        'status',
        'http_status',
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
            $row['http_status'],
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
    $online,
    $includeMissing,
    $csvPath,
    array $csvRows,
    array $httpCheckResult
) {
    $prefsWithHelp = 0;
    foreach ($allPrefs as $info) {
        if (is_string($info['help'] ?? null) && $info['help'] !== '') {
            $prefsWithHelp++;
        }
    }

    $linkRows = array_filter($rows, static fn ($r) => ($r['status'] ?? '') !== 'MISSING');
    $adminHelp = count(array_filter($linkRows, static fn ($r) => $r['source_type'] === 'admin_help'));
    $hardcoded = count(array_filter($linkRows, static fn ($r) => $r['source_type'] === 'admin_hardcoded'));

    $counts = [
        'BROKEN' => 0,
        'CHECK_FAILED' => 0,
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
    echo "Admin help links scanned: {$adminHelp}\n";
    echo "Hardcoded doc.tiki.org URLs scanned: {$hardcoded}\n\n";
    echo 'Broken links: ' . $counts['BROKEN'] . "\n";
    echo 'Check failed (no HTTP response): ' . $counts['CHECK_FAILED'] . "\n";
    echo 'Non-canonical links (+ or space): ' . $counts['NON_CANONICAL'] . "\n";
    echo 'External links: ' . $counts['EXTERNAL'] . "\n";
    echo "Missing documentation (admin-visible, no help): {$missingCount}\n\n";

    if (! $includeMissing) {
        echo "(use --include-missing to export missing entries to CSV)\n\n";
    }

    if (! $online) {
        echo "Note: Broken link detection was not run. Use --online to verify doc.tiki.org pages.\n\n";
    } else {
        echo "Mode: online\n";
        if (! $httpCheckResult['probe_ok']) {
            echo "Note: doc.tiki.org probe failed; per-URL checks were skipped. "
                . "Re-run when doc.tiki.org is reachable.\n";
        } elseif ($counts['CHECK_FAILED'] > 0) {
            echo "Note: CHECK_FAILED means no HTTP response (timeout, network, etc.), not a confirmed "
                . "404. BROKEN with http_status 404/410 is a stronger signal of a bad link.\n";
        }
        echo "\n";
    }

    $csvNote = $includeMissing
        ? 'BROKEN, CHECK_FAILED, NON_CANONICAL, EXTERNAL, MISSING'
        : 'BROKEN, CHECK_FAILED, NON_CANONICAL, EXTERNAL only';
    echo "CSV: {$csvPath} ({$csvNote}, " . count($csvRows) . " rows)\n";

    print_detail_section($rows, 'BROKEN', 20);
    print_detail_section($rows, 'CHECK_FAILED', 20);
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
