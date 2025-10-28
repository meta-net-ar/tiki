<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

use Tiki\Lib\Importer\Markdown\TikiMarkdownImporter;

$inputConfiguration = [[
    'staticKeyFilters' => [
        'action' => 'word',

        // Source (common)
        'source_type' => 'word', // local|repo

        // Local
        // -> file via $_FILES['local_file']

        // Repo options
        'repo_url'     => 'string',
        'repo_branch'  => 'word',
        'git_pull'      => 'bool',
        'repo_token'  => 'string',
        'git_timeout'  => 'digits',

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
            'type'        => 'local', // local|git
            // repo options
            'repo_url'   => '',
            'repo_branch' => 'main',
            'git_pull'    => true,
            'repo_token' => '',
            'git_timeout' => 30,
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
    $type = in_array($type, ['local', 'git'], true) ? $type : 'local';

    $src = $_SESSION['md_import']['source'];
    $src['type'] = $type;

    if ($type === 'git') {
        $src['repo_url']    = trim($_POST['repo_url'] ?? '');
        $src['repo_branch'] = trim($_POST['repo_branch'] ?? '');
        $src['git_pull']    = isset($_POST['git_pull']);
        $src['repo_token']  = trim($_POST['repo_token'] ?? '');
        $src['git_timeout']  = max(5, (int)($_POST['git_timeout'] ?? 30));
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
        $_SESSION['md_import']['last_preview'] = json_encode(
            $result,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );
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
    // source repo
    'repo-url'        => null,
    'repo-branch'     => 'main',
    'git-pull'         => true,
    'repo-token'    => null,
    'git-timeout'    => 30,
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
} elseif ($srcType === 'git') {
    $srcOpts[] = mdimp_opt_if_changed('source-type', $srcType, $cliDefaults['source-type']);
    $srcOpts[] = mdimp_opt_if_changed('repo-url', $source['repo_url'] ?? null, $cliDefaults['repo-path']);
    $srcOpts[] = mdimp_opt_if_changed('repo-branch', $source['repo_branch'] ?? null, $cliDefaults['repo-branch']);
    $srcOpts[] = mdimp_opt_if_changed('git-pull', $source['git_pull'] ?? null, $cliDefaults['git-pull']);
    $srcOpts[] = mdimp_opt_if_changed('repo-token', $source['repo_token'] ?? null, $cliDefaults['repo-token']);
    $srcOpts[] = mdimp_opt_if_changed('git-timeout', $source['git_timeout'] ?? null, $cliDefaults['git-timeout']);
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
$smarty->assign('md_preview', $cfg['last_preview']);

$smarty->assign('metatag_robots', 'NOINDEX, NOFOLLOW');
$smarty->assign('mid', 'tiki-markdown_import.tpl');
$smarty->display('tiki.tpl');
