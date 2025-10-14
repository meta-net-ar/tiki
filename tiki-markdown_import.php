<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

use Tiki\Lib\Importer\Markdown\Source\SourceManager;
use Tiki\Lib\Importer\Markdown\TikiMarkdownImporter;

$inputConfiguration = [[
    'staticKeyFilters' => [
        'action' => 'word',

        // Source (common)
        'source_type' => 'word', // local|ftp|repo

        // Local
        // -> file via $_FILES['local_file']

        // FTP
        'ftp_host'    => 'string',
        'ftp_port'    => 'digits',
        'ftp_user'    => 'string',
        'ftp_pass'    => 'string',
        'ftp_path'    => 'string',
        'ftp_ssl'     => 'bool',
        'ftp_passive' => 'bool',

        // Repo (GitHub/GitLab)
        'repo_provider' => 'word', // github|gitlab
        'repo_owner'    => 'string',
        'repo_name'     => 'string',
        'repo_ref'      => 'string',
        'repo_token'    => 'string',

        // Options Wiki / mapping
        'roots'          => 'string',
        'recursive'      => 'bool',
        'max_depth'      => 'digits',
        'exclude_globs'  => 'string',
        'title_strategy' => 'word',
        'naming_mode'    => 'word',
        'dir_levels'     => 'digits',
        'separator'      => 'string',
        'namespace'      => 'string',
        'detect_journal' => 'bool',
        'journal_lang'   => 'word', // en|fr
        'journal_ns'     => 'string',
        'markdown_source' => 'word', // logseq|gfm|commonmark

        // Cron tab
        'cron_name'        => 'string',
        'cron_desc'        => 'string',
        'cron_expr'        => 'string', // ex: */10 * * * *
        'cron_active'      => 'bool',
        'cron_run_if_miss' => 'bool',
        'cron_once'        => 'bool',
    ],
]];

require_once('tiki-setup.php');

$access->check_permission('tiki_p_admin_importer');

// ------------------------------------------------------------------
// Defaults in session (we keep everything here for V1 UI)
// TODO: migrate to prefs and/or DB later
if (! isset($_SESSION['md_import'])) {
    $_SESSION['md_import'] = [
        'source' => [
            'type'        => 'local', // local|ftp|repo
            // ftp
            'ftp_host'    => '',
            'ftp_port'    => 21,
            'ftp_user'    => '',
            'ftp_pass'    => '',
            'ftp_path'    => '/',
            'ftp_ssl'     => false,
            'ftp_passive' => true,
            // repo
            'repo_provider' => 'github',
            'repo_owner'    => '',
            'repo_name'     => '',
            'repo_ref'      => 'main',
            'repo_token'    => '',
        ],
        'wiki' => [
            'roots'          => '.',
            'recursive'      => true,
            'max_depth'      => 0,
            'exclude_globs'  => '',
            'title_strategy' => 'fm_h1_filename',
            'naming_mode'    => 'basename',
            'dir_levels'     => 2,
            'separator'      => ' ',
            'namespace'      => '',
            'detect_journal' => true,
            'journal_lang'   => 'en',
            'journal_ns'     => 'Journal',
            'markdown_source' => 'commonmark', // logseq|gfm|commonmark
        ],
        'cron' => [
            'name'         => 'Markdown import',
            'desc'         => '',
            'expr'         => '*/30 * * * *',
            'active'       => true,
            'run_if_miss'  => true,
            'once'         => false,
        ],
        // Last preview (dry-run)
        'last_preview' => null,
    ];
}

// ------------------------------------------------------------------
// Actions
$action = $_POST['action'] ?? null;

// 1) Save source
//TODO: 1.validate fields more strictly 2. Save in prefs/DB
if ($action === 'save_source' && $access->checkCsrf()) {
    $type = $_POST['source_type'] ?? 'local';
    $type = in_array($type, ['local', 'ftp', 'repo'], true) ? $type : 'local';

    $src = $_SESSION['md_import']['source'];
    $src['type'] = $type;

    if ($type === 'ftp') {
        $src['ftp_host']    = trim($_POST['ftp_host'] ?? '');
        $src['ftp_port']    = (int)($_POST['ftp_port'] ?? 21);
        $src['ftp_user']    = trim($_POST['ftp_user'] ?? '');
        $src['ftp_pass']    = (string)($_POST['ftp_pass'] ?? '');
        $src['ftp_path']    = trim($_POST['ftp_path'] ?? '/');
        $src['ftp_ssl']     = isset($_POST['ftp_ssl']);
        $src['ftp_passive'] = isset($_POST['ftp_passive']);
    } elseif ($type === 'repo') {
        $src['repo_provider'] = in_array(($_POST['repo_provider'] ?? 'github'), ['github', 'gitlab'], true) ? $_POST['repo_provider'] : 'github';
        $src['repo_owner']    = trim($_POST['repo_owner'] ?? '');
        $src['repo_name']     = trim($_POST['repo_name'] ?? '');
        $src['repo_ref']      = trim($_POST['repo_ref'] ?? 'main');
        $src['repo_token']    = (string)($_POST['repo_token'] ?? '');
    }
    $_SESSION['md_import']['source'] = $src;
    Feedback::success(tra('Source settings saved.'));
}

// 2) Save wiki options
//TODO: 1.validate fields more strictly 2. Save in prefs/DB
if ($action === 'save_wiki' && $access->checkCsrf()) {
    $_SESSION['md_import']['wiki'] = [
        'roots' => trim($_POST['roots'] ?? '.'),
        'recursive' => isset($_POST['recursive']),
        'max_depth' => max(0, (int)($_POST['max_depth'] ?? 0)),
        'exclude_globs' => trim($_POST['exclude_globs'] ?? ''),
        'title_strategy' => $_POST['title_strategy'] ?? 'fm_h1_filename',
        'naming_mode' => $_POST['naming_mode'] ?? 'basename',
        'dir_levels' => max(0, (int)($_POST['dir_levels'] ?? 2)),
        'separator' => (string)($_POST['separator'] ?? ' '),
        'namespace' => trim($_POST['namespace'] ?? ''),
        'detect_journal' => isset($_POST['detect_journal']),
        'journal_lang' => in_array(($_POST['journal_lang'] ?? 'en'), ['en', 'fr'], true) ? $_POST['journal_lang'] : 'en',
        'markdown_source' => $_POST['markdown_source'] ?? 'commonmark',
        'journal_ns' => trim($_POST['journal_ns'] ?? 'Journal'),
    ];
    Feedback::success(tra('Import options saved.'));
}

// 3) Test source (placeholder)
if ($action === 'test_source' && $access->checkCsrf()) {
    $mgr = new SourceManager();
    $cfg = $_SESSION['md_import']['source'] ?? [];

    $zip = $mgr->fetchToTempZip($cfg);
    if ($zip) {
        $z = new ZipArchive();
        $entries = ($z->open($zip) === true) ? $z->numFiles : 0;
        if ($entries && $z->status === 0) {
            $z->close();
        }
        Feedback::success(tra(sprintf('Source OK. Downloaded ZIP (~%s bytes, %s entries).', @filesize($zip) ?: 0, (string)$entries)));
        @unlink($zip);
    } else {
        Feedback::error($mgr->getError());
    }
}

// 4) Dry-run (preview) — placeholder
if ($action === 'dry_run' && $access->checkCsrf()) {
    $sourceCfg = $_SESSION['md_import']['source'] ?? [];
    $wikiOpts = $_SESSION['md_import']['wiki'] ?? [];

    // Markdown flavor selection comes from UI (or default)
    if (! empty($_POST['markdown_source'])) {
        $wikiOpts['markdown_source'] = $_POST['markdown_source'];
    } elseif (empty($wikiOpts['markdown_source'])) {
        $wikiOpts['markdown_source'] = 'commonmark';
    }

    // Upload local ad hoc ?
    $uploadTmp  = null;
    $uploadName = null;
    if (! empty($_FILES['local_file']) && $_FILES['local_file']['error'] === UPLOAD_ERR_OK) {
        $uploadTmp  = $_FILES['local_file']['tmp_name'];
        $uploadName = $_FILES['local_file']['name'] ?? null;
        $sourceCfg['type'] = 'local';
    }

    $importer = new TikiMarkdownImporter();
    $res = $importer->previewFromSource($sourceCfg, $wikiOpts, $uploadTmp, $uploadName);

    if (! empty($res['ok'])) {
        $_SESSION['md_import']['last_preview'] = json_encode(
            $res,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );
        Feedback::success(tra('Dry-run executed. See preview below.'));
    } else {
        Feedback::error($importer->getError() ?: ($res['error'] ?? tra('Preview failed.')));
    }
}

// 5) Import now
if ($action === 'import_now' && $access->checkCsrf()) {
    $sourceCfg = $_SESSION['md_import']['source'] ?? [];
    $wikiOpts = $_SESSION['md_import']['wiki'] ?? [];

    // upload overrides “local” source if provided
    $uploadTmp  = null;
    $uploadName = null;
    if (! empty($_FILES['local_file']) && $_FILES['local_file']['error'] === UPLOAD_ERR_OK) {
        $uploadTmp  = $_FILES['local_file']['tmp_name'];
        $uploadName = $_FILES['local_file']['name'] ?? null;
        $sourceCfg['type'] = 'local';
    } elseif (! empty($_FILES['local_file']) && $_FILES['local_file']['error'] !== UPLOAD_ERR_NO_FILE) {
        $msg = \Tiki\Lib\Importer\Base::displayPhpUploadError($_FILES['local_file']['error']);
        Feedback::error($msg);
        // continue anyway with configured source if any
    }

    // Scanner expects concrete options (mirror what you used in dry_run)
    $scanOpts = [
        'roots'          => $wikiOpts['roots'] ?? '.',
        'recursive'      => $wikiOpts['recursive'] ?? true,
        'max_depth'      => (int)($wikiOpts['max_depth'] ?? 0),
        'exclude_globs'  => $wikiOpts['exclude_globs'] ?? '',
        'title_strategy' => $wikiOpts['title_strategy'] ?? 'fm_h1_filename',
        'naming_mode'    => $wikiOpts['naming_mode'] ?? 'basename',
        'dir_levels'     => (int)($wikiOpts['dir_levels'] ?? 2),
        'separator'      => (string)($wikiOpts['separator'] ?? ' '),
        'namespace'      => $wikiOpts['namespace'] ?? '',
        'detect_journal' => $wikiOpts['detect_journal'] ?? true,
        'journal_lang'   => $wikiOpts['journal_lang'] ?? 'en',
        'journal_ns'     => $wikiOpts['journal_ns'] ?? 'Journal',
    ];

    // Markdown flavor selection comes from UI (or default)
    $flavor = $_POST['markdown_source'] ?? ($wikiOpts['markdown_source'] ?? 'commonmark');

    $pipelineCfg = [
        'flavor'              => $flavor,
        'strip_front_matter'  => true,
        'runtime_logseq'      => false,
        'blockref_mode'       => 'inline',
    ];

    $importer = new TikiMarkdownImporter();
    $result = $importer->importFromSource($sourceCfg, $scanOpts, $pipelineCfg, $uploadTmp, $uploadName);

    if (($result['written'] ?? 0) > 0) {
        $_SESSION['md_import']['last_preview'] = json_encode(
            $result,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );
        Feedback::success(tr('%0 pages imported or updated.', (int)$result['written']));
    } else {
        Feedback::warning(tr('No pages were imported.'));
    }

    if (! empty($result['errors'])) {
        foreach ($result['errors'] as $e) {
            if ($e) {
                Feedback::error($e);
            }
        }
    }

    if (! empty($result['notes'])) {
        $cnt = count($result['notes']);
        Feedback::warning(tr('Purifier notes available for %0 page(s).', $cnt));
        // facultatif: tu peux aussi stocker en session si tu veux les afficher en bas
        $_SESSION['md_import']['last_preview'] = json_encode(
            ['notes' => $result['notes'], 'counts' => $result['counts']],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );
    }
}

// 6) Save cron settings (no real job creation here, simple persist)
// TODO: migrate to prefs/DB later + use TikiScheduler to create a real job
if ($action === 'save_cron' && $access->checkCsrf()) {
    $_SESSION['md_import']['cron'] = [
        'name'        => trim($_POST['cron_name'] ?? 'Markdown import'),
        'desc'        => trim($_POST['cron_desc'] ?? ''),
        'expr'        => trim($_POST['cron_expr'] ?? '*/30 * * * *'),
        'active'      => isset($_POST['cron_active']),
        'run_if_miss' => isset($_POST['cron_run_if_miss']),
        'once'        => isset($_POST['cron_once']),
    ];
    Feedback::success(tra('Scheduler options saved (not yet creating a real job).'));
}

// --------------------- CLI command builder (minimal) ---------------------
// TODO: Should we save this Scheduler job here directly instead of just displaying a command ?

if (! function_exists('mdimp_val_plain')) {
    function mdimp_val_plain($v): ?string
    {
        if ($v === null) {
            return null;
        }
        if (is_bool($v)) {
            return $v ? '1' : '0';
        }
        $s = (string) $v;
        if ($s === '') {
            return null;
        }
        // escape spaces to avoid quoting
        $s = preg_replace('/\s+/u', ' ', $s);
        return str_replace(' ', '\ ', $s);
    }
}

if (! function_exists('mdimp_same')) {
    function mdimp_same($v, $def): bool
    {
        // normalize types for fair compare
        if (is_bool($def)) {
            return (bool)$v === (bool)$def;
        }
        if (is_int($def)) {
            return (int)$v === (int)$def;
        }
        // strings: trim normalize spaces
        $vn = preg_replace('/\s+/u', ' ', (string)$v);
        $dn = preg_replace('/\s+/u', ' ', (string)$def);
        return $vn === $dn;
    }
}

if (! function_exists('mdimp_opt_if_changed')) {
    function mdimp_opt_if_changed(string $key, $value, $default): ?string
    {
        if ($value === null) {
            return null;
        }
        if (mdimp_same($value, $default)) {
            return null; // omit if same as default
        }
        $v = mdimp_val_plain($value);
        return $v === null ? null : "--{$key}={$v}";
    }
}

// Defaults for CLI (should match those in Command class MarkdownImporterCommand)
$cliDefaults = [
    'mode'             => 'preview',
    'source-type'      => 'local',
    'markdown-source'  => 'commonmark',
    // options wiki
    'roots'            => '.',
    'recursive'        => true,
    'max-depth'        => 0,
    'exclude-globs'    => null,
    'title-strategy'   => 'fm_h1_filename',
    'naming-mode'      => 'basename',
    'dir-levels'       => 2,
    'separator'        => ' ',
    'namespace'        => null,
    'detect-journal'   => true,
    'journal-lang'     => 'en',
    'journal-ns'       => 'Journal',
    // source local
    'local-path'       => null,
    // source ftp
    'ftp-host'         => null,
    'ftp-port'         => 21,
    'ftp-user'         => null,
    'ftp-pass'         => null,
    'ftp-path'         => '/',
    'ftp-ssl'          => false,
    'ftp-passive'      => true,
    // source repo
    'repo-provider'    => 'github',
    'repo-owner'       => null,
    'repo-name'        => null,
    'repo-ref'         => 'main',
    'repo-token'       => null,
];

$source = $_SESSION['md_import']['source'] ?? [];
$wiki = $_SESSION['md_import']['wiki'] ?? [];

$srcType = $source['type'] ?? $cliDefaults['source-type'];

/**
 * Prepare  CLI commands (preview + import)
 * - only include options that differ from defaults
 */
$srcOpts = [];
if ($srcType === 'local') {
    $localPath = $source['local_path'] ?? null;
    $srcOpts[] = mdimp_opt_if_changed('source-type', $srcType, $cliDefaults['source-type']);
    $srcOpts[] = mdimp_opt_if_changed('local-path', $localPath ?: 'path_to_your_zip_or_md', $cliDefaults['local-path']);
} elseif ($srcType === 'ftp') {
    $srcOpts[] = mdimp_opt_if_changed('source-type', $srcType, $cliDefaults['source-type']);
    $srcOpts[] = mdimp_opt_if_changed('ftp-host', $source['ftp_host'] ?? null, $cliDefaults['ftp-host']);
    $srcOpts[] = mdimp_opt_if_changed('ftp-port', (int)($source['ftp_port'] ?? 21), $cliDefaults['ftp-port']);
    $srcOpts[] = mdimp_opt_if_changed('ftp-user', $source['ftp_user'] ?? null, $cliDefaults['ftp-user']);
    $srcOpts[] = mdimp_opt_if_changed('ftp-pass', $source['ftp_pass'] ?? null, $cliDefaults['ftp-pass']);
    $srcOpts[] = mdimp_opt_if_changed('ftp-path', $source['ftp_path'] ?? '/', $cliDefaults['ftp-path']);
    $srcOpts[] = mdimp_opt_if_changed('ftp-ssl', (bool)($source['ftp_ssl'] ?? false), $cliDefaults['ftp-ssl']);
    $srcOpts[] = mdimp_opt_if_changed('ftp-passive', (bool)($source['ftp_passive'] ?? true ), $cliDefaults['ftp-passive']);
} elseif ($srcType === 'repo') {
    $srcOpts[] = mdimp_opt_if_changed('source-type', $srcType, $cliDefaults['source-type']);
    $srcOpts[] = mdimp_opt_if_changed('repo-provider', $source['repo_provider'] ?? 'github', $cliDefaults['repo-provider']);
    $srcOpts[] = mdimp_opt_if_changed('repo-owner', $source['repo_owner'] ?? null, $cliDefaults['repo-owner']);
    $srcOpts[] = mdimp_opt_if_changed('repo-name', $source['repo_name'] ?? null, $cliDefaults['repo-name']);
    $srcOpts[] = mdimp_opt_if_changed('repo-ref', $source['repo_ref'] ?? 'main', $cliDefaults['repo-ref']);
    $srcOpts[] = mdimp_opt_if_changed('repo-token', $source['repo_token'] ?? null, $cliDefaults['repo-token']);
}

// Options wiki (common)
$common = [];
$common[] = mdimp_opt_if_changed('markdown-source', $wiki['markdown_source'] ?? 'commonmark', $cliDefaults['markdown-source']);
$common[] = mdimp_opt_if_changed('roots', $wiki['roots'] ?? '.', $cliDefaults['roots']);
$common[] = mdimp_opt_if_changed('recursive', (bool)($wiki['recursive'] ?? true), $cliDefaults['recursive']);
$common[] = mdimp_opt_if_changed('max-depth', (int)($wiki['max_depth'] ?? 0), $cliDefaults['max-depth']);
$common[] = mdimp_opt_if_changed('exclude-globs', $wiki['exclude_globs'] ?? null, $cliDefaults['exclude-globs']);
$common[] = mdimp_opt_if_changed('title-strategy', $wiki['title_strategy'] ?? 'fm_h1_filename', $cliDefaults['title-strategy']);
$common[] = mdimp_opt_if_changed('naming-mode', $wiki['naming_mode'] ?? 'basename', $cliDefaults['naming-mode']);
$common[] = mdimp_opt_if_changed('dir-levels', (int)($wiki['dir_levels'] ?? 2), $cliDefaults['dir-levels']);
$common[] = mdimp_opt_if_changed('separator', $wiki['separator'] ?? ' ', $cliDefaults['separator']);
$common[] = mdimp_opt_if_changed('namespace', $wiki['namespace'] ?? null, $cliDefaults['namespace']);
$common[] = mdimp_opt_if_changed('detect-journal', (bool)($wiki['detect_journal'] ?? true), $cliDefaults['detect-journal']);
$common[] = mdimp_opt_if_changed('journal-lang', $wiki['journal_lang'] ?? 'en', $cliDefaults['journal-lang']);
$common[] = mdimp_opt_if_changed('journal-ns', $wiki['journal_ns'] ?? 'Journal', $cliDefaults['journal-ns']);

// cleanup
$srcOpts = array_values(array_filter($srcOpts));
$common  = array_values(array_filter($common));

//Build commands
$base = 'markdown:importer';
$cmdPreview = trim($base . ' --mode=preview ' . implode(' ', $srcOpts) . ' ' . implode(' ', $common) . ' --json');
$cmdImport  = trim($base . ' --mode=import  ' . implode(' ', $srcOpts) . ' ' . implode(' ', $common) . ' --json');

$smarty->assign('md_cli_preview', $cmdPreview);
$smarty->assign('md_cli_import', $cmdImport);
$smarty->assign('md_cli_sourcetype', $srcType);

$cfg = $_SESSION['md_import'];
$smarty->assign('md_source', $cfg['source']);
$smarty->assign('md_wiki', $cfg['wiki']);
$smarty->assign('md_cron', $cfg['cron']);
$smarty->assign('md_preview', $cfg['last_preview']);

$smarty->assign('metatag_robots', 'NOINDEX, NOFOLLOW');
$smarty->assign('mid', 'tiki-markdown_import.tpl');
$smarty->display('tiki.tpl');
