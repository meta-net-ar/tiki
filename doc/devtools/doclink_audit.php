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
 * Default CSV includes only BROKEN, NON_CANONICAL, and EXTERNAL rows.
 * MISSING preferences are counted in the console summary; use --include-missing to export them.
 *
 * Broken link detection requires --online (HTTP checks against doc.tiki.org).
 *
 * Requires a configured Tiki instance (tiki-setup.php / database), like prefreport.php.
 * See also: doc/devtools/prefreport.php
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
  --online            HTTP-check doc.tiki.org links (not EXTERNAL or NON_CANONICAL slug checks)
  --include-missing   Include MISSING preference rows in the CSV export
  --csv=PATH          CSV output path (default: dump/doclink_audit.csv)
  -h, --help          Show this help

Default CSV columns: source_type, location, line, preference, original_value,
resolved_url, status, http_status, suggested_slug

Statuses: OK, BROKEN, MISSING, NON_CANONICAL, EXTERNAL

HELP;
    exit(0);
}

$online = isset($options['online']);
$includeMissing = isset($options['include-missing']);
$csvPath = $options['csv'] ?? 'dump/doclink_audit.csv';

chdir(dirname(__DIR__, 2));

require_once 'tiki-setup.php';

global $prefs;

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
    apply_http_checks($rows);
}

$rows = array_merge($rows, $missingRows);

$csvRows = filter_csv_rows($rows, $includeMissing);

if (dirname($csvPath) !== '' && ! is_dir(dirname($csvPath))) {
    mkdir(dirname($csvPath), 0755, true);
}

write_csv($csvRows, $csvPath);
print_summary($rows, $missingCount, $allPrefs, $online, $includeMissing, $csvPath, $csvRows);

exit(0);

/**
 * @return array<string, array{file: string, help: mixed, line: int}>
 */
function collect_all_preferences()
{
    $all = [];

    foreach (glob('lib/prefs/*.php') as $file) {
        $module = substr(basename($file), 0, -4);
        if ($module === 'index') {
            continue;
        }

        $function = "prefs_{$module}_list";

        if (! function_exists($function)) {
            include_once $file;
        }

        if (! function_exists($function)) {
            continue;
        }

        $list = $function();
        foreach ($list as $prefName => $raw) {
            $all[$prefName] = [
                'file' => str_replace('\\', '/', $file),
                'help' => $raw['help'] ?? null,
                'line' => find_pref_line($file, $prefName),
            ];
        }
    }

    return $all;
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

    if (url_path_contains_plus($resolved)) {
        $row['status'] = 'NON_CANONICAL';
        $row['suggested_slug'] = suggest_slug_without_plus($original, $resolved);
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

function url_path_contains_plus($url)
{
    $path = parse_url($url, PHP_URL_PATH) ?? '';
    $fragment = parse_url($url, PHP_URL_FRAGMENT) ?? '';
    return str_contains($path, '+') || str_contains($fragment, '+');
}

function suggest_slug_without_plus($original, $resolved)
{
    if (! preg_match('/^https?:\/\//i', $original)) {
        $fragment = '';
        if (str_contains($original, '#')) {
            [$original, $fragment] = explode('#', $original, 2);
            $fragment = '#' . $fragment;
        }
        return str_replace('+', '-', $original) . $fragment;
    }
    $parts = parse_url($resolved);
    if ($parts === false) {
        return '';
    }
    $path = $parts['path'] ?? '';
    $path = str_replace('+', '-', $path);
    $out = ($parts['scheme'] ?? 'https') . '://' . ($parts['host'] ?? 'doc.tiki.org') . $path;
    if (! empty($parts['fragment'])) {
        $out .= '#' . str_replace('+', '-', $parts['fragment']);
    }
    return $out;
}

/**
 * @param list<array<string, mixed>> $rows
 */
function apply_http_checks(array &$rows)
{
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

        if ($code === 0 || $code >= 400) {
            $rows[$key]['status'] = 'BROKEN';
        } else {
            $rows[$key]['status'] = 'OK';
        }
    }
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
    curl_close($ch);

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
        curl_close($ch);
    }

    return $code;
}

/**
 * @param list<array<string, mixed>> $rows
 * @return list<array<string, mixed>>
 */
function filter_csv_rows(array $rows, $includeMissing)
{
    $allowed = ['BROKEN', 'NON_CANONICAL', 'EXTERNAL'];
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
    array $csvRows
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
    echo 'Non-canonical links (+): ' . $counts['NON_CANONICAL'] . "\n";
    echo 'External links: ' . $counts['EXTERNAL'] . "\n";
    echo "Missing documentation (admin-visible, no help): {$missingCount}\n\n";

    if (! $includeMissing) {
        echo "(use --include-missing to export missing entries to CSV)\n\n";
    }

    if (! $online) {
        echo "Note: Broken link detection was not run. Use --online to verify doc.tiki.org pages.\n\n";
    } else {
        echo "Mode: online\n\n";
    }

    $csvNote = $includeMissing
        ? 'BROKEN, NON_CANONICAL, EXTERNAL, MISSING'
        : 'BROKEN, NON_CANONICAL, EXTERNAL only';
    echo "CSV: {$csvPath} ({$csvNote}, " . count($csvRows) . " rows)\n";

    print_detail_section($rows, 'BROKEN', 20);
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
