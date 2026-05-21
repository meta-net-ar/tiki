<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

use Tiki\Lib\Importer\Markdown\TikiMarkdownImporter;

// ========== SESSION & CONFIG CONSTANTS ==========
const SESS_MD_IMPORT = 'md_import';
const SESS_SOURCE = 'source';
const SESS_WIKI = 'wiki';
const SESS_GC = 'gc';
const SESS_LAST_PREVIEW = 'last_preview';

const CFG_TYPE = 'type';
const CFG_SOURCE_ID = 'source_id';
const CFG_REPO_URL = 'repo_url';
const CFG_REPO_BRANCH = 'repo_branch';
const CFG_GIT_PULL = 'git_pull';
const CFG_REPO_TOKEN = 'repo_token';
const CFG_GIT_TIMEOUT = 'git_timeout';
const CFG_LOCAL_PATH = 'local_path';

const WIKI_ROOTS = 'roots';
const WIKI_RECURSIVE = 'recursive';
const WIKI_MAX_DEPTH = 'max_depth';
const WIKI_EXCLUDE_GLOBS = 'exclude_globs';
const WIKI_TITLE_STRATEGY = 'title_strategy';
const WIKI_NAMING_MODE = 'naming_mode';
const WIKI_DIR_LEVELS = 'dir_levels';
const WIKI_SEPARATOR = 'separator';
const WIKI_NAMESPACE = 'namespace';
const WIKI_DETECT_JOURNAL = 'detect_journal';
const WIKI_JOURNAL_LANG = 'journal_lang';
const WIKI_JOURNAL_NS = 'journal_ns';
const WIKI_MARKDOWN_SOURCE = 'markdown_source';

const GC_MODE = 'mode';
const GC_SAFE_NAMESPACE = 'safe_namespace';

const ACTION_SAVE = 'save_import_options';
const ACTION_DRY_RUN = 'dry_run';
const ACTION_IMPORT = 'import_now';

const SRC_TYPE_LOCAL = 'local';
const SRC_TYPE_GIT = 'git';

$inputConfiguration = [[
    'staticKeyFilters' => [
        'action' => 'word',

        // Source (common)
        'source_type' => 'word', // local|git

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

        // GC / Garbage collect deletions options
        'gc_mode'                       => 'word', // off|mark|delete
        'gc_safe_namespace'             => 'word', // ex. 'Journal'
    ],
]];

require_once('tiki-setup.php');

$access->check_permission('tiki_p_admin_importer');

// ------------------------------------------------------------------
// Defaults in session (we keep everything here for V1 UI)
// TODO: migrate to prefs and/or DB later
if (! isset($_SESSION[SESS_MD_IMPORT])) {
    $_SESSION[SESS_MD_IMPORT] = [
        SESS_SOURCE => [
            CFG_TYPE => SRC_TYPE_LOCAL, // local|git
            CFG_SOURCE_ID => '', // Stable identifier for this source
            // repo options
            CFG_REPO_URL => '',
            CFG_REPO_BRANCH => 'main',
            CFG_GIT_PULL => true,
            CFG_REPO_TOKEN => '',
            CFG_GIT_TIMEOUT => 30,
        ],
        SESS_WIKI => [
            WIKI_ROOTS => '.',
            WIKI_RECURSIVE => true,
            WIKI_MAX_DEPTH => 0,
            WIKI_EXCLUDE_GLOBS => '',
            WIKI_TITLE_STRATEGY => 'fm_h1_filename',
            WIKI_NAMING_MODE => 'basename',
            WIKI_DIR_LEVELS => 2,
            WIKI_SEPARATOR => ' ',
            WIKI_NAMESPACE => '',
            WIKI_DETECT_JOURNAL => true,
            WIKI_JOURNAL_LANG => 'en',
            WIKI_JOURNAL_NS => 'Journal',
            WIKI_MARKDOWN_SOURCE => 'commonmark', // logseq|gfm|commonmark
        ],
        SESS_GC => [
            GC_MODE => 'mark', // off | mark | delete
            GC_SAFE_NAMESPACE => '', // ex. 'Journal'
        ],
        // Last preview (dry-run)
        SESS_LAST_PREVIEW => null,
    ];
}

// ------------------------------------------------------------------
// Actions
$action = $_POST['action'] ?? null;

//TODO: 1.validate fields more strictly 2. Save in prefs/DB
if ($action === ACTION_SAVE && $access->checkCsrf()) {
    $type = $_POST['source_type'] ?? SRC_TYPE_LOCAL;
    $type = in_array($type, [SRC_TYPE_LOCAL, SRC_TYPE_GIT], true) ? $type : SRC_TYPE_LOCAL;

    $src = $_SESSION[SESS_MD_IMPORT][SESS_SOURCE];
    $src[CFG_TYPE] = $type;
    $src[CFG_SOURCE_ID] = trim($_POST['source_id'] ?? '');

    if ($type === SRC_TYPE_GIT) {
        $src[CFG_REPO_URL] = trim($_POST['repo_url'] ?? '');
        $src[CFG_REPO_BRANCH] = trim($_POST['repo_branch'] ?? '');
        $src[CFG_GIT_PULL] = isset($_POST['git_pull']);
        $src[CFG_REPO_TOKEN] = trim($_POST['repo_token'] ?? '');
        $src[CFG_GIT_TIMEOUT] = max(5, (int)($_POST['git_timeout'] ?? 30));
    }
    $_SESSION[SESS_MD_IMPORT][SESS_SOURCE] = $src;


    $_SESSION[SESS_MD_IMPORT][SESS_WIKI] = [
        WIKI_ROOTS => trim($_POST['roots'] ?? '.'),
        WIKI_RECURSIVE => isset($_POST['recursive']),
        WIKI_MAX_DEPTH => max(0, (int)($_POST['max_depth'] ?? 0)),
        WIKI_EXCLUDE_GLOBS => trim($_POST['exclude_globs'] ?? ''),
        WIKI_TITLE_STRATEGY => $_POST['title_strategy'] ?? 'fm_h1_filename',
        WIKI_NAMING_MODE => $_POST['naming_mode'] ?? 'basename',
        WIKI_DIR_LEVELS => max(0, (int)($_POST['dir_levels'] ?? 2)),
        WIKI_SEPARATOR => (string)($_POST['separator'] ?? ' '),
        WIKI_NAMESPACE => trim($_POST['namespace'] ?? ''),
        WIKI_DETECT_JOURNAL => isset($_POST['detect_journal']),
        WIKI_JOURNAL_LANG => in_array(($_POST['journal_lang'] ?? 'en'), ['en', 'fr'], true) ? $_POST['journal_lang'] : 'en',
        WIKI_MARKDOWN_SOURCE => $_POST['markdown_source'] ?? 'commonmark',
        WIKI_JOURNAL_NS => trim($_POST['journal_ns'] ?? 'Journal'),
    ];


    $_SESSION[SESS_MD_IMPORT][SESS_GC] = [
        GC_MODE => in_array(($_POST['gc_mode'] ?? 'off'), ['off', 'mark', 'delete'], true) ? $_POST['gc_mode'] : 'off',
        GC_SAFE_NAMESPACE => trim($_POST['gc_safe_namespace'] ?? ''),
    ];
    Feedback::success(tra('Import options saved successfully.'));
}

// Dry-run (preview) — placeholder
if ($action === ACTION_DRY_RUN && $access->checkCsrf()) {
    $runtimeCfg = mdimp_build_runtime_cfg($_SESSION[SESS_MD_IMPORT] ?? []);
    $sourceCfg = $runtimeCfg[SESS_SOURCE] ?? [];
    $wikiOpts = $runtimeCfg[SESS_WIKI] ?? [];

    // Upload local ad hoc ?
    $uploadTmp  = null;
    $uploadName = null;
    if (! empty($_FILES['local_file']) && $_FILES['local_file']['error'] === UPLOAD_ERR_OK) {
        $uploadTmp  = $_FILES['local_file']['tmp_name'];
        $uploadName = $_FILES['local_file']['name'] ?? null;
        $sourceCfg[CFG_TYPE] = SRC_TYPE_LOCAL;
    }

    $importer = new TikiMarkdownImporter();
    $gc = $_SESSION[SESS_MD_IMPORT][SESS_GC] ?? [];
    $res = $importer->previewFromSource($sourceCfg, $wikiOpts, $uploadTmp, $uploadName, [SESS_GC => $gc]);

    if (! empty($res['ok'])) {
        $_SESSION[SESS_MD_IMPORT][SESS_LAST_PREVIEW] = json_encode(
            $res,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );
        Feedback::success(tra('Dry-run executed. See preview below.'));
    } else {
        Feedback::error($importer->getError() ?: ($res['error'] ?? tra('Preview failed.')));
    }
}

// Import now
if ($action === ACTION_IMPORT && $access->checkCsrf()) {
    $runtimeCfg = mdimp_build_runtime_cfg($_SESSION[SESS_MD_IMPORT] ?? []);
    $sourceCfg = $runtimeCfg[SESS_SOURCE] ?? [];
    $wikiOpts = $runtimeCfg[SESS_WIKI] ?? [];

    // upload overrides “local” source if provided
    $uploadTmp  = null;
    $uploadName = null;
    if (! empty($_FILES['local_file']) && $_FILES['local_file']['error'] === UPLOAD_ERR_OK) {
        $uploadTmp  = $_FILES['local_file']['tmp_name'];
        $uploadName = $_FILES['local_file']['name'] ?? null;
        $sourceCfg[CFG_TYPE] = SRC_TYPE_LOCAL;
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
    $flavor = $_POST['markdown_source'] ?? ($wikiOpts[WIKI_MARKDOWN_SOURCE] ?? 'commonmark');
    $gc = $runtimeCfg[SESS_GC] ?? [];

    $pipelineCfg = [
        'flavor'              => $flavor,
        'strip_front_matter'  => true,
        'runtime_logseq'      => false,
        'blockref_mode'       => 'inline',
        'gc'                  => $gc,
    ];

    $importer = new TikiMarkdownImporter();
    $result = $importer->importFromSource($sourceCfg, $scanOpts, $pipelineCfg, $uploadTmp, $uploadName);

    if (($result['written'] ?? 0) > 0) {
        $_SESSION[SESS_MD_IMPORT][SESS_LAST_PREVIEW] = json_encode(
            $result,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );
        Feedback::success(tr('%0 pages imported or updated.', (int)$result['written']));
    } else {
        $_SESSION[SESS_MD_IMPORT][SESS_LAST_PREVIEW] = json_encode(
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
        $_SESSION[SESS_MD_IMPORT][SESS_LAST_PREVIEW] = json_encode(
            ['notes' => $result['notes'], 'counts' => $result['counts']],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );
    }
}

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

function mdimp_build_runtime_cfg(array $sessionCfg): array
{
    $source = $sessionCfg[SESS_SOURCE] ?? [];
    $wiki = $sessionCfg[SESS_WIKI] ?? [];
    $gc = $sessionCfg[SESS_GC] ?? [];

    if (! empty($_POST['source_type'])) {
        $type = $_POST['source_type'];
        if (in_array($type, [SRC_TYPE_LOCAL, SRC_TYPE_GIT], true)) {
            $source[CFG_TYPE] = $type;
        }
    }

    if (array_key_exists('source_id', $_POST)) {
        $source[CFG_SOURCE_ID] = trim((string)$_POST['source_id']);
    }

    if (array_key_exists('repo_url', $_POST)) {
        $source[CFG_REPO_URL] = trim((string)$_POST['repo_url']);
    }
    if (array_key_exists('repo_branch', $_POST)) {
        $source[CFG_REPO_BRANCH] = trim((string)$_POST['repo_branch']);
    }
    if (array_key_exists('git_pull', $_POST)) {
        $source[CFG_GIT_PULL] = true;
    } elseif (($source[CFG_TYPE] ?? '') === SRC_TYPE_GIT) {
        $source[CFG_GIT_PULL] = false;
    }
    if (array_key_exists('repo_token', $_POST)) {
        $source[CFG_REPO_TOKEN] = trim((string)$_POST['repo_token']);
    }
    if (array_key_exists('git_timeout', $_POST)) {
        $source[CFG_GIT_TIMEOUT] = max(5, (int)$_POST['git_timeout']);
    }

    if (array_key_exists('markdown_source', $_POST) && $_POST['markdown_source'] !== '') {
        $wiki[WIKI_MARKDOWN_SOURCE] = $_POST['markdown_source'];
    } elseif (empty($wiki[WIKI_MARKDOWN_SOURCE])) {
        $wiki[WIKI_MARKDOWN_SOURCE] = 'commonmark';
    }

    if (array_key_exists('roots', $_POST)) {
        $wiki[WIKI_ROOTS] = trim((string)$_POST['roots']);
    }
    if (array_key_exists('recursive', $_POST)) {
        $wiki[WIKI_RECURSIVE] = true;
    } elseif (array_key_exists('action', $_POST)) {
        $wiki[WIKI_RECURSIVE] = false;
    }
    if (array_key_exists('max_depth', $_POST)) {
        $wiki[WIKI_MAX_DEPTH] = max(0, (int)$_POST['max_depth']);
    }
    if (array_key_exists('exclude_globs', $_POST)) {
        $wiki[WIKI_EXCLUDE_GLOBS] = trim((string)$_POST['exclude_globs']);
    }
    if (array_key_exists('title_strategy', $_POST)) {
        $wiki[WIKI_TITLE_STRATEGY] = (string)$_POST['title_strategy'];
    }
    if (array_key_exists('naming_mode', $_POST)) {
        $wiki[WIKI_NAMING_MODE] = (string)$_POST['naming_mode'];
    }
    if (array_key_exists('dir_levels', $_POST)) {
        $wiki[WIKI_DIR_LEVELS] = max(0, (int)$_POST['dir_levels']);
    }
    if (array_key_exists('separator', $_POST)) {
        $wiki[WIKI_SEPARATOR] = (string)$_POST['separator'];
    }
    if (array_key_exists('namespace', $_POST)) {
        $wiki[WIKI_NAMESPACE] = trim((string)$_POST['namespace']);
    }
    if (array_key_exists('detect_journal', $_POST)) {
        $wiki[WIKI_DETECT_JOURNAL] = true;
    } elseif (array_key_exists('action', $_POST)) {
        $wiki[WIKI_DETECT_JOURNAL] = false;
    }
    if (array_key_exists('journal_lang', $_POST)) {
        $wiki[WIKI_JOURNAL_LANG] = in_array($_POST['journal_lang'], ['en', 'fr'], true) ? $_POST['journal_lang'] : 'en';
    }
    if (array_key_exists('journal_ns', $_POST)) {
        $wiki[WIKI_JOURNAL_NS] = trim((string)$_POST['journal_ns']);
    }

    if (array_key_exists('gc_mode', $_POST)) {
        $gc[GC_MODE] = in_array($_POST['gc_mode'], ['off', 'mark', 'delete'], true) ? $_POST['gc_mode'] : 'off';
    }
    if (array_key_exists('gc_safe_namespace', $_POST)) {
        $gc[GC_SAFE_NAMESPACE] = trim((string)$_POST['gc_safe_namespace']);
    }

    return [
        SESS_SOURCE => $source,
        SESS_WIKI => $wiki,
        SESS_GC => $gc,
    ];
}

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
    // Garbage Collector options
    'gc-mode'                   => 'off',
    'gc-safe-namespace'         => null,
];

$source = $_SESSION[SESS_MD_IMPORT][SESS_SOURCE] ?? [];
$wiki = $_SESSION[SESS_MD_IMPORT][SESS_WIKI] ?? [];

$srcType = $source[CFG_TYPE] ?? $cliDefaults['source-type'];

/**
 * Prepare  CLI commands (preview + import)
 * - only include options that differ from defaults
 */
$srcOpts = [];
if ($srcType === SRC_TYPE_LOCAL) {
    $localPath = $source[CFG_LOCAL_PATH] ?? null;
    $srcOpts[] = mdimp_opt_if_changed('source-type', $srcType, $cliDefaults['source-type']);
    $srcOpts[] = mdimp_opt_if_changed('local-path', $localPath ?: 'path_to_your_zip_or_md', $cliDefaults['local-path']);
} elseif ($srcType === SRC_TYPE_GIT) {
    $srcOpts[] = mdimp_opt_if_changed('source-type', $srcType, $cliDefaults['source-type']);
    $srcOpts[] = mdimp_opt_if_changed('repo-url', $source[CFG_REPO_URL] ?? null, $cliDefaults['repo-url']);
    $srcOpts[] = mdimp_opt_if_changed('repo-branch', $source[CFG_REPO_BRANCH] ?? null, $cliDefaults['repo-branch']);
    $srcOpts[] = mdimp_opt_if_changed('git-pull', $source[CFG_GIT_PULL] ?? null, $cliDefaults['git-pull']);
    $srcOpts[] = mdimp_opt_if_changed('git-timeout', $source[CFG_GIT_TIMEOUT] ?? null, $cliDefaults['git-timeout']);
}

// Options wiki (common)
$common = [];
$common[] = mdimp_opt_if_changed('markdown-source', $wiki[WIKI_MARKDOWN_SOURCE] ?? 'commonmark', $cliDefaults['markdown-source']);
$common[] = mdimp_opt_if_changed('roots', $wiki[WIKI_ROOTS] ?? '.', $cliDefaults['roots']);
$common[] = mdimp_opt_if_changed('recursive', (bool)($wiki[WIKI_RECURSIVE] ?? true), $cliDefaults['recursive']);
$common[] = mdimp_opt_if_changed('max-depth', (int)($wiki[WIKI_MAX_DEPTH] ?? 0), $cliDefaults['max-depth']);
$common[] = mdimp_opt_if_changed('exclude-globs', $wiki[WIKI_EXCLUDE_GLOBS] ?? null, $cliDefaults['exclude-globs']);
$common[] = mdimp_opt_if_changed('title-strategy', $wiki[WIKI_TITLE_STRATEGY] ?? 'fm_h1_filename', $cliDefaults['title-strategy']);
$common[] = mdimp_opt_if_changed('naming-mode', $wiki[WIKI_NAMING_MODE] ?? 'basename', $cliDefaults['naming-mode']);
$common[] = mdimp_opt_if_changed('dir-levels', (int)($wiki[WIKI_DIR_LEVELS] ?? 2), $cliDefaults['dir-levels']);
$common[] = mdimp_opt_if_changed('separator', $wiki[WIKI_SEPARATOR] ?? ' ', $cliDefaults['separator']);
$common[] = mdimp_opt_if_changed('namespace', $wiki[WIKI_NAMESPACE] ?? null, $cliDefaults['namespace']);
$common[] = mdimp_opt_if_changed('detect-journal', (bool)($wiki[WIKI_DETECT_JOURNAL] ?? true), $cliDefaults['detect-journal']);
$common[] = mdimp_opt_if_changed('journal-lang', $wiki[WIKI_JOURNAL_LANG] ?? 'en', $cliDefaults['journal-lang']);
$common[] = mdimp_opt_if_changed('journal-ns', $wiki[WIKI_JOURNAL_NS] ?? 'Journal', $cliDefaults['journal-ns']);

$gc = $_SESSION[SESS_MD_IMPORT][SESS_GC] ?? [];

$gcOpts = [];
$gcOpts[] = mdimp_opt_if_changed('gc-mode', $gc[GC_MODE] ?? 'off', $cliDefaults['gc-mode']);
$gcOpts[] = mdimp_opt_if_changed('gc-safe-namespace', $gc[GC_SAFE_NAMESPACE] ?? null, $cliDefaults['gc-safe-namespace']);

// cleanup
$srcOpts = array_values(array_filter($srcOpts));
$common  = array_values(array_filter($common));
$gcOpts = array_values(array_filter($gcOpts));

//Build commands
$base = 'markdown:importer';
$cmdPreview = trim($base . ' --mode=preview ' . implode(' ', $srcOpts) . ' ' . implode(' ', $common) . ' ' . implode(' ', $gcOpts) . ' --json');
$cmdImport  = trim($base . ' --mode=import ' . implode(' ', $srcOpts) . ' ' . implode(' ', $common) . ' ' . implode(' ', $gcOpts) . ' --json');

$smarty->assign('md_cli_preview', $cmdPreview);
$smarty->assign('md_cli_import', $cmdImport);
$smarty->assign('md_cli_sourcetype', $srcType);
$smarty->assign('md_gc', $_SESSION[SESS_MD_IMPORT][SESS_GC]);

$cfg = $_SESSION[SESS_MD_IMPORT];
$smarty->assign('md_source', $cfg[SESS_SOURCE]);
$smarty->assign('md_wiki', $cfg[SESS_WIKI]);
$smarty->assign('md_preview', $cfg[SESS_LAST_PREVIEW]);

$smarty->assign('metatag_robots', 'NOINDEX, NOFOLLOW');
$smarty->assign('mid', 'tiki-markdown_import.tpl');
$smarty->display('tiki.tpl');
