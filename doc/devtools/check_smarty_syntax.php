<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE.

require_once __DIR__ . '/../../path_constants.php';
use Tiki\Smarty\SmartyTiki;

define('ROOT', realpath(__DIR__ . '/../..'));

/**
 * Fake function required by Smarty
 */
function zone_is_empty()
{
}

/**
 * Entry point for the Smarty syntax checker
 */
function check_smarty_syntax(): int
{
    global $tikidomain, $prefs;

    $tikidomain = '';

    // Minimal prefs required for Smarty initialization
    $prefs = [
        'lang_use_db' => 'n',
        'language' => 'en',
        'site_language' => 'en',
        'feature_ajax' => 'n',
        'smarty_compilation' => 'always',
        'smarty_security' => 'y',
        'maxRecords' => 25,
        'log_tpl' => 'y',
        'feature_sefurl_filter' => 'y',
        'site_layout' => 'basic',
    ];

    // Tell TikiDb that database access is not required
    if (! defined('DB_TIKI_SETUP')) {
        define('DB_TIKI_SETUP', 0);
    }

    require_once ROOT . '/lib/init/initlib.php';

    define('TIKI_PATH', getcwd());

    require_once ROOT . '/lib/smarty_tiki/prefilter.tr.php';
    require_once ROOT . '/lib/smarty_tiki/prefilter.jq.php';
    require_once ROOT . '/lib/smarty_tiki/prefilter.log_tpl.php';

    $smarty = new SmartyTiki();

    set_error_handler('check_smarty_syntax_error_handler');

    $templates_dir = TIKI_PATH . '/' . SMARTY_TEMPLATES_PATH;

    $entries = [];
    get_files_list($templates_dir, $entries, '/\.tpl$/');

    $errors_found = false;
    $total = count($entries);

    foreach ($entries as $index => $entry) {
        display_progress_percentage($index, $total, '%d%% of templates checked');
        if (str_contains($entry, 'tiki-mods.tpl')) {
            continue;
        }

        $template_file = substr($entry, strlen($templates_dir) + 1);

        try {
            $template = $smarty->createTemplate($template_file);
            $template->compileTemplateSource();
        } catch (Throwable $e) {
            echo color("\n[ERROR] $template_file: " . $e->getMessage(), 'red') . PHP_EOL;
            $errors_found = true;
        }
    }

    restore_error_handler();

    echo PHP_EOL;

    if ($errors_found) {
        echo color("Smarty syntax check failed.", 'red') . PHP_EOL;
        return 1;
    }

    echo color("Smarty syntax check passed successfully.", 'green') . PHP_EOL;
    return 0;
}

/**
 * Custom error handler for Smarty compilation
 */
function check_smarty_syntax_error_handler($errno, $errstr): bool
{
    if (! str_contains($errstr, 'filemtime(): stat failed for')) {
        echo color("\n[WARNING] $errstr", 'yellow') . PHP_EOL;
    }

    return true;
}

/**
 * Display progress during template compilation
 */
function display_progress_percentage(int $done, int $total, string $message): void
{
    if ($total === 0) {
        return;
    }

    $step = max(1, ceil($total / 100));

    if ($done % $step === 0 || $done === $total) {
        $percentage = min(100, (int) (($done / $total) * 100));
        printf("\r$message", $percentage);
    }
}

/**
 * @param string $dir
 * @param array  $entries
 * @param string $regexp_pattern
 *
 * @return bool
 */
function get_files_list(string $dir, array &$entries, string $regexp_pattern): bool
{
    $d = dir($dir);
    while (false !== ($e = $d->read())) {
        $entry = $dir . '/' . $e;
        if (is_dir($entry)) {
            // do not descend and no git files
            if ($e != '..' && $e != '.' && $e != '.git' && $e != '.gitignore' && $entry != './' . SMARTY_COMPILED_TEMPLATES_PATH && $entry != './' . TIKI_VENDOR_BUNDLED_PATH) {
                if (! get_files_list($entry, $entries, $regexp_pattern)) {
                    return false;
                }
            }
        } elseif (preg_match($regexp_pattern, $e) && realpath($entry) != __FILE__) {
            $entries[] = $entry;
        }
    }
    $d->close();
    return true;
}

function color(string $text, string $color): string
{
    $colors = [
        'red' => "\033[31m",
        'green' => "\033[32m",
        'yellow' => "\033[33m",
        'blue' => "\033[34m",
        'reset' => "\033[0m"
    ];

    return $colors[$color] . $text . $colors['reset'];
}

require_once ROOT . '/lib/core/TikiDb.php';
require_once ROOT . '/lib/core/TikiDb/Bridge.php';
require_once ROOT . '/lib/language/Language.php';

exit(check_smarty_syntax());
