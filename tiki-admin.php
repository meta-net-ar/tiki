<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

use Tiki\Installer\Installer;
use Tiki\Package\ExtensionManager;
use Tiki\Suggestion\Rules;

$section = 'admin';

require_once('tiki-setup.php');
$adminlib = TikiLib::lib('admin');

$auto_query_args = ['page'];

$access->check_permission('tiki_p_admin');
$logslib = TikiLib::lib('logs');

/**
 * Display feedback on prefs changed
 *
 * @param string $name Name of feature
 * @param string $message Other message
 * @param int $st Type of change (0=disabled, 1=enabled, 2=changed, 3=info, 4=reset)
 * @param int $num unknown
 * @return void
 * @throws Exception
 */
function add_feedback($name, $message, $st, $num = null)
{
    TikiLib::lib('prefs')->addRecent($name);

    Feedback::add(['num' => $num,
        'mes' => $message,
        'st' => $st,
        'name' => $name,
        'tpl' => 'pref',]);
}

/**
 * simple_set_toggle
 *
 * @param mixed $feature
 * @access public
 * @return void
 * @throws Exception
 */
function simple_set_toggle($feature)
{
    global $prefs;
    $logslib = TikiLib::lib('logs');
    $tikilib = TikiLib::lib('tiki');
    if (isset($_REQUEST[$feature]) && $_REQUEST[$feature] == 'on') {
        if ((! isset($prefs[$feature]) || $prefs[$feature] != 'y')) {
            // not yet set at all or not set to y
            if ($tikilib->set_preference($feature, 'y')) {
                add_feedback($feature, tr('%0 enabled', $feature), 1, 1);
                $logslib->add_action('feature', $feature, 'system', 'enabled');
            }
        }
    } else {
        if ((! isset($prefs[$feature]) || $prefs[$feature] != 'n')) {
            // not yet set at all or not set to n
            if ($tikilib->set_preference($feature, 'n')) {
                add_feedback($feature, tr('%0 disabled', $feature), 0, 1);
                $logslib->add_action('feature', $feature, 'system', 'disabled');
            }
        }
    }
    TikiLib::lib('cache')->invalidate('allperms');
}

/**
 * simple_set_value
 *
 * @param mixed $feature
 * @param string $pref
 * @param mixed $isMultiple
 * @access public
 * @return void
 * @throws Exception
 */
function simple_set_value($feature, $pref = '', $isMultiple = false)
{
    global $prefs;
    $logslib = TikiLib::lib('logs');
    $tikilib = TikiLib::lib('tiki');
    $old = $prefs[$feature];
    if (isset($_POST[$feature])) {
        if ($pref != '') {
            if ($tikilib->set_preference($pref, $_POST[$feature])) {
                $prefs[$feature] = $_POST[$feature];
            }
        } else {
            $tikilib->set_preference($feature, $_POST[$feature]);
        }
    } elseif ($isMultiple) {
        // Multiple selection controls do not exist if no item is selected.
        // We still want the value to be updated.
        if ($pref != '') {
            if ($tikilib->set_preference($pref, [])) {
                $prefs[$feature] = $_POST[$feature];
            }
        } else {
            $tikilib->set_preference($feature, []);
        }
    }
    if (isset($_POST[$feature]) && $old != $_POST[$feature]) {
        add_feedback($feature, ($_POST[$feature]) ? tr('%0 set', $feature) : tr('%0 unset', $feature), 2);
        $msg = '';
        if (is_array($_POST[$feature]) && is_array($old)) {
            $newCount = count($_POST[$feature]);
            $oldCount = count($old);
            if ($newCount > $oldCount) {
                $added = $newCount - $oldCount;
                $item = $added == 1 ? tr('item added') : tr('items added');
                $msg = $added . ' ' . $item;
            } elseif ($oldCount > $newCount) {
                $deleted = $oldCount - $newCount;
                $item = $deleted == 1 ? tr('item deleted') : tr('items deleted');
                $msg = $deleted . ' ' . $item;
            }
        } else {
            $msg = $old . ' => ' . $_POST[$feature];
        }
        $logslib->add_action('feature', $feature, 'system', $msg);
    }
    TikiLib::lib('cache')->invalidate('allperms');
}

$crumbs[] = new Breadcrumb(tra('Control Panels'), tra('Sections'), 'tiki-admin.php', 'Admin-Home', tra('Help on Configuration Sections', '', true));
// Default values for AdminHome
$admintitle = tra('Admin Dashboard');
$helpUrl = 'Admin-Home';
$helpDescription = $description = '';
$url = 'tiki-admin.php';
$adminPage = '';

/*
 * Tiki System Suggestions
 */
$tikiShowSuggestionsPopup = false;
if ($prefs['feature_system_suggestions'] == 'y' && empty($_POST)) {
    $adminLogin = ! empty($_SESSION['u_info']['id']) ? $_SESSION['u_info']['id'] : '';
    $suggestionMessages = ! empty($_SESSION['suggestions_user_id_' . $adminLogin]) ? $_SESSION['suggestions_user_id_' . $adminLogin] : [];

    if (isset($_REQUEST['tikiSuggestionPopup'])) {
        $_SESSION['suggestions_popup_off_user_id_' . $adminLogin] = true;
        return;
    }

    if (
        ! empty($adminLogin)
        && ! isset($_SESSION['suggestions_off_user_id_' . $adminLogin])
        && TikiLib::lib('user')->user_is_in_group($user, 'Admins')
    ) {
        if (isset($_REQUEST['tikiSuggestion'])) {
            $_SESSION['suggestions_off_user_id_' . $adminLogin] = true;
            return;
        }

        if (empty($suggestionMessages)) {
            $suggestionRules = new Rules();
            $suggestionMessages = $suggestionRules->getAllMessages();
        }

        if (! empty($suggestionMessages)) {
            $feedback['title'] = tra('Tiki Suggestions');
            $feedback['mes'] = $suggestionMessages;
            Feedback::note($feedback);
            $_SESSION['suggestions_user_id_' . $adminLogin] = $suggestionMessages;
        }
    }

    if (! empty($suggestionMessages) && ! isset($_SESSION['suggestions_popup_off_user_id_' . $adminLogin])) {
        $tikiShowSuggestionsPopup = true;
    }
}
$smarty->assign('tikiShowSuggestionsPopup', $tikiShowSuggestionsPopup);

$prefslib = TikiLib::lib('prefs');

if (isset($_REQUEST['pref_filters'])) {
    $prefslib->setFilters($_REQUEST['pref_filters']);
    Feedback::success(tra('Default preference filters set'));
}

/*
 * If blacklist preferences have been updated and its also not being disabled
 * Then update the database with the selection.
 */

$blackL = TikiLib::lib('blacklist');

if (isset($_POST['pass_blacklist']) && $jitPost->offsetExists('pass_blacklist_file')) {    // if preferences were updated and blacklist feature is enabled (or is being enabled)
    $pass_blacklist_file = $jitPost->pass_blacklist_file->striptags();
    $userfile = explode('-', $pass_blacklist_file);
    $userfile = $userfile[3];
    if ($userfile) {                       // if the blacklist is a user generated file
        $passDir = 'storage/pass_blacklists/';
    } else {
        $passDir = 'lib/pass_blacklists/';
    }
    if ($pass_blacklist_file === 'auto') {
        if (
            $_POST['min_pass_length'] != $GLOBALS['prefs']['min_pass_length'] ||
            $_POST['pass_chr_num'] != $GLOBALS['prefs']['pass_chr_num']    ||
            $_POST['pass_chr_special'] != $GLOBALS['prefs']['pass_chr_special']
        ) {       // if blacklist is auto and an option is changed that could effect the selection
            $prefname = implode('-', $blackL->selectBestBlacklist($_POST['pass_chr_num'], $_POST['pass_chr_special'], $_POST['min_pass_length']));
            $filename = $passDir . $prefname . '.txt';
            $tikilib->set_preference('pass_auto_blacklist', $prefname);
            $blackL->loadBlacklist(dirname($_SERVER['SCRIPT_FILENAME']) . '/' . $filename);
        }
    } elseif ($pass_blacklist_file != $GLOBALS['prefs']['pass_blacklist_file']) {        // if manual selection mode has been changed
        $filename = $passDir . $pass_blacklist_file . '.txt';
        $blackL->loadBlacklist(dirname($_SERVER['SCRIPT_FILENAME']) . '/' . $filename);
    }
}

$temp_filters = isset($_REQUEST['filters']) ? explode(' ', $_REQUEST['filters']) : null;
$smarty->assign('pref_filters', $prefslib->getFilters($temp_filters));

if (isset($_POST['lm_preference']) && $access->checkCsrf()) {
    $changes = $prefslib->applyChanges((array) $_POST['lm_preference'], $_POST);
    foreach ($changes as $pref => $val) {
        if ($val['type'] == 'reset') {
            add_feedback($pref, tr('%0 reset', $pref), 4);
            $logslib->add_action('feature', $pref, 'system', 'reset');
        } else {
            $value = $val['new'];
            if ($value == 'y') {
                add_feedback($pref, tr('%0 enabled', $pref), 1, 1);
                $logslib->add_action('feature', $pref, 'system', 'enabled');
            } elseif ($value == 'n') {
                add_feedback($pref, tr('%0 disabled', $pref), 0, 1);
                $logslib->add_action('feature', $pref, 'system', 'disabled');
            } else {
                add_feedback($pref, tr('%0 set', $pref), 1, 1);
                $logslib->add_action('feature', $pref, 'system', (is_array($val['old']) ? implode(',', $val['old']) : $val['old']) . '=>' . (is_array($value) ? implode(',', $value) : $value));
            }
            /*
                Enable/disable addreference/showreference plugins alognwith references feature.
            */
            if ($pref == 'feature_references') {
                $tikilib->set_preference('wikiplugin_addreference', $value);
                $tikilib->set_preference('wikiplugin_showreference', $value);

                /* Add/Remove the plugin toolbars from the editor */
                $toolbars = ['wikiplugin_addreference', 'wikiplugin_showreference'];
                $t_action = ($value == 'y') ? 'add' : 'remove';
                $tikilib->saveEditorToolbars($toolbars, 'global', $t_action);
            }
        }
    }
}

if (isset($_REQUEST['lm_criteria'])) {
    set_time_limit(0);
    try {
        $smarty->assign('lm_criteria', $_REQUEST['lm_criteria']);
        $results = $prefslib->getMatchingPreferences($_REQUEST['lm_criteria']);
        $results = array_slice($results, 0, 50);
        $results = $prefslib->unsetHiddenPreferences($results);
        $smarty->assign('lm_searchresults', $results);
    } catch (\Exception $e) {
        Feedback::warning(['mes' => $e->getMessage(), 'title' => tr('Search error')]);
        $smarty->assign('lm_criteria', '');
        $smarty->assign('lm_searchresults', '');
    }
} else {
    $smarty->assign('lm_criteria', '');
    $smarty->assign('lm_searchresults', '');
}

if (isset($_REQUEST['prefrebuild'])) {
    $prefslib->rebuildIndex();
    header('Location: ' . $base_url . 'tiki-admin.php');
}

global $admin_icons;
include_once 'admin/define_admin_icons.php';
$smarty->assign('template_not_found', 'n');

if (isset($_REQUEST['page'])) {
    $adminPage = $_REQUEST['page'];
    // Check if the associated incude_*.php file exists. If not, check to see if it might exist in the Addons.
    // If it exists, include the associated file
    $utilities = new \Tiki\Package\Extension\Utilities();
    if (file_exists("admin/include_$adminPage.php")) {
        include_once("admin/include_$adminPage.php");
    } elseif ($filepath = $utilities->getExtensionFilePath("admin/include_$adminPage.php")) {
        include_once($filepath);
    }
    $url = 'tiki-admin.php' . '?page=' . $adminPage;

    if ($prefs['theme_unified_admin_backend'] === 'y') {
        foreach ($admin_icons as & $admin_icon) {
            foreach ($admin_icon['children'] as & $child) {
                $child = array_merge(['disabled' => false, 'description' => ''], $child);
            }

            if (isset($admin_icon['children'][$adminPage])) {
                $admin_icon['selected'] = true;
                $admin_icon['children'][$adminPage]['selected'] = true;
                $admintitle = $admin_icon['children'][$adminPage]['title'];
                $description = $admin_icon['children'][$adminPage]['description'] ?? '';
                $helpUrl = $admin_icon['children'][$adminPage]['help'] ?? '';
            }
        }
    } else {
        foreach ($admin_icons as &$admin_icon) {
            $admin_icon = array_merge([ 'disabled' => false, 'description' => ''], $admin_icon);
        }
        if (isset($admin_icons[$adminPage])) {
            $admin_icon = $admin_icons[$adminPage];

            $admintitle = $admin_icon['title'];
            $description = isset($admin_icon['description']) ? $admin_icon['description'] : '';
            $helpUrl = isset($admin_icon['help']) ? $admin_icon['help'] : '';
        }
    }
    $helpDescription = tr("Help on %0 Config", $admintitle);

    $smarty->assign('include', $adminPage);
    if (substr($adminPage, 0, 3) == 'tp_' && ! file_exists("admin/include_$adminPage.tpl")) {
        $packageAdminTplFile = $utilities->getExtensionFilePath("templates/admin/include_$adminPage.tpl");
        if (! file_exists($packageAdminTplFile)) {
            $smarty->assign('include', 'extension_package_missing_page');
        }
        if (! ExtensionManager::isExtensionEnabled(str_replace("_", "/", substr($adminPage, 3)))) {
            $smarty->assign('include', 'extension_package_inactive');
        }
    } elseif (! file_exists("templates/admin/include_$adminPage.tpl")) {
        // First, try cleaning the url to see if it matches an existing page.
        TikiLib::lib('wiki')->clean_url_suffix_and_redirect($adminPage, 'tpl', getcwd() . '/templates/admin/', 'include_');

        // Graceful error management when URL is wrong for admin panel
        $smarty->assign('template_not_found', 'y');
    } else {
        $smarty->assign('template_not_found', 'n');
    }

    //for most admin include page forms, need to redirect as changes to one pref can affect display of others
    //however other forms that perform actions other than changing preferences should not redirect to avoid infinite loops
    //for these add a hidden input named redirect with a value of 0
    if (
        $access->csrfResult() && (! isset($_POST['redirect']) || strval($_POST['redirect']) === '1')
        && ! isset($_POST['saveblacklist']) && ! isset($_POST['viewblacklist'])
    ) {
        $access->redirect($_SERVER['REQUEST_URI'], '', 200);
    }
} else {
    $smarty->assign('include', 'list_sections');
}

if (isset($_POST["gobackto"])) {
    $access->redirect($_POST["gobackto"]);
}

if ($prefs['activity_custom_events'] === 'y') {
    $activity = TikiLib::lib("activity");
    $total = count($activity->getRules());
    $smarty->assign('activity_custom_events', $prefs['activity_custom_events']);
    $smarty->assign('total_activity_custom_events', $total);
}

if ($prefs['theme_unified_admin_backend'] === 'y') {
    $headerlib->add_cssfile('themes/base_files/css/feature/adminui.css');
} else {
    $headerlib->add_cssfile('themes/base_files/feature_css/admin.css');
}
$crumbs[] = new Breadcrumb($admintitle, $description, $url, $helpUrl, $helpDescription);
$smarty->assign('admintitle', $admintitle);
$headtitle = breadcrumb_buildHeadTitle($crumbs);
$smarty->assign('headtitle', $headtitle);
$smarty->assign('helpUrl', $helpUrl);
$smarty->assign('description', $description);

// VERSION TRACKING
$forcecheck = ! empty($_GET['forcecheck']);

// Versioning feature has been enabled, so if the time is right, do a live
// check, otherwise display the stored data.
if ($prefs['feature_version_checks'] == 'y' || $forcecheck) {
    $versionUtils = new Tiki_Version_Utils();
    $upgrades = $versionUtils->checkUpdatesForVersion($TWV->version);

    $smarty->assign('upgrade_messages', $upgrades);
} else {
    $smarty->assign('upgrade_messages', []);
}

// SSL setup
$haveMySQLSSL = $tikilib->haveMySQLSSL();
$smarty->assign('haveMySQLSSL', $haveMySQLSSL);
if ($haveMySQLSSL) {
    $isSSL = $tikilib->isMySQLConnSSL();
} else {
    $isSSL = false;
}
$smarty->assign('mysqlSSL', $isSSL);

$smarty->assign('admin_icons', $admin_icons);

$show_warning = $adminlib->checkSystemConfigurationFile();
$smarty->assign('show_system_configuration_warning', $show_warning);

if (isset($system_configuration_file)) {
    $config_file_errors = $adminlib->checkConfigurationFileErrors();
    $smarty->assign('config_file_errors', $config_file_errors);
}

// disallow robots to index page:
$smarty->assign('metatag_robots', 'NOINDEX, NOFOLLOW');
// Display the template
$smarty->assign('adminpage', $adminPage);
$smarty->assign('mid', 'tiki-admin.tpl');
$smarty->assign('trail', $crumbs);
$smarty->assign('crumb', count($crumbs) - 1);

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    if (
        file_exists(__DIR__ . '/vendor/do_not_clean.txt')
        || ! ( // check the existence of critical files denoting a legacy vendor folder
            (file_exists(__DIR__ . '/vendor/zendframework/zend-config/src/Config.php') //ZF2
                || file_exists(__DIR__ . '/vendor/bombayworks/zendframework1/library/Zend/Config.php')) //ZF1
            && (file_exists(__DIR__ . '/vendor/smarty/smarty/libs/Smarty.class.php') //Smarty
                || file_exists(__DIR__ . '/vendor/smarty/smarty/distribution/libs/Smarty.class.php')) //Smarty
            && file_exists(__DIR__ . '/vendor/adodb/adodb/adodb.inc.php') //Adodb
        )
    ) {
        $vendorAutoloadIgnored = false;
    } else {
        $vendorAutoloadIgnored = true;
    }
} else {
    $vendorAutoloadIgnored = false;
}

if (file_exists(__DIR__ . '/vendor/autoload-disabled.php')) {
    $vendorAutoloadDisabled = true;
} else {
    $vendorAutoloadDisabled = false;
}

$smarty->assign('fgal_web_accessible', false);
if ($prefs['fgal_use_dir'] && $prefs['fgal_use_db'] === 'n') {
    $smarty->assign('fgal_web_accessible', $access->isFileWebAccessible($prefs['fgal_use_dir'] . 'index.php'));
}
$smarty->assign('vendor_autoload_ignored', $vendorAutoloadIgnored);
$smarty->assign('vendor_autoload_disabled', $vendorAutoloadDisabled);

include_once('installer/installlib.php');
$installer = Installer::getInstance();
$smarty->assign('db_requires_update', $installer->requiresUpdate());
$smarty->assign('missing_patches', $installer->missingPatches());
$smarty->assign('installer_not_locked', $installer->checkInstallerLocked());
$smarty->assign('db_engine_type', getCurrentEngine());

$smarty->assign('wiki_attachments_migrated', true);
if ($prefs['feature_wiki_attachments'] === 'y' && $prefs['feature_use_fgal_for_wiki_attachments'] === 'y') {
    $smarty->assign('wiki_attachments_migrated', TikiLib::lib('wiki')->attachmentsCount() == 0);
}

$unifiedsearch = \TikiLib::lib('unifiedsearch');
$smarty->assign('search_index_outdated', $unifiedsearch->isOutdated());

if (! $unifiedsearch->rebuildInProgress()) {
    $searchIndex = [
        'error' => false,
        'feedback' => ''
    ];

    $lastLogItems = [];

    if ($prefs['unified_engine'] === 'elastic') {
        $searchIndex = $unifiedsearch->checkElasticsearch();
        $removeIndexErrorsCallback = function ($item) {
            if ($item['type'] == 'error') {
                foreach ($item['mes'] as $me) {
                    if (strpos($me, 'stream_socket_client') !== false) {
                        return true;
                    }
                }
            }
            return false;
        };
        Feedback::removeIf($removeIndexErrorsCallback);
    }

    if ($prefs['unified_engine'] === 'mysql') {
        $searchIndex = $unifiedsearch->checkMySql();
    }

    if (! $searchIndex['error'] && ! $unifiedsearch->getIndex()->exists()) {
        $searchIndex['error'] = true;
    }

    if ($searchIndex['error']) {
        $lastLogItems = $unifiedsearch->getLogItems(5, $needle = 'ERR');
    }

    $smarty->assign('searchIndex', $searchIndex);
    $smarty->assign('lastLogItems', $lastLogItems);
}

$smarty->display('tiki.tpl');
