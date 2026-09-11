<?php

/**
 * contains the hooks for Tiki's internal functionality.
 *
 * this script may only be included, it will die if called directly.
 *
 * @package TikiWiki
 * @copyright (c) Copyright by authors of the Tiki Wiki CMS Groupware Project. All Rights Reserved. See copyright.txt for details and a complete list of authors.
 * @licence Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
 */

// die if called directly.

use Tiki\Maintenance\Maintenance;
use Tiki\Package\VendorHelper;
use Tiki\Profiling\Timer;
use Tiki\Captcha\CaptchaTypeResolver;
use Tiki\Lib\CookieConsent\CookieConsentLib;

//Be careful, composer autoloading isn't available until tiki-setup_base.php is required further down

/**
 * @global array $prefs
 * @global array $tikilib
 */
global $prefs, $tikilib;

ini_set('session.cookie_httponly', 1);

if (str_contains($_SERVER['SCRIPT_NAME'], basename(__FILE__))) {
    header('location: index.php');
    exit;
}

if (! defined('TIKI_API')) {
    define('TIKI_API', false);
}

// Ensure that we clean PROXY headers
if (! empty($_SERVER['HTTP_PROXY'])) {
    $_SERVER['HTTP_PROXY_RENAMED'] = $_SERVER['HTTP_PROXY'];
    unset($_SERVER['HTTP_PROXY']);
    putenv('HTTP_PROXY');
    if (! getenv('PHP_PEAR_HTTP_PROXY')) {
        putenv('PHP_PEAR_HTTP_PROXY=http://127.0.0.1'); // fake proxy setting to avoid PEAR to use HTTP_PROXY
    }
}
require_once 'lib/setup/third_party.php';
// Enable Versioning
include_once('lib/setup/twversion.class.php');
$TWV = new TWVersion();
/** Database profiling, the number of queries executed */
$num_queries = 0;
/** Database profiling, wall clock time spend in database queries */
$elapsed_in_db = 0.0;
$server_load = '';
$area = 'tiki';
$crumbs = [];
require_once('lib/core/Tiki/TikiInit.php');
require_once('lib/setup/tikisetup.class.php');
if (isset($prefs['feature_fullscreen']) && $prefs['feature_fullscreen'] == 'y') {
    require_once('lib/setup/fullscreen.php');
}

require_once('lib/core/Tiki/Profiling/Timer.php');
$tiki_timer = new Timer();
$tiki_timer->start();

require_once('tiki-setup_base.php'); //Starting here composer autoloading is available

if (! empty($user) && isset($_SESSION['login_time'])) {
    $userInfo = $userlib->get_user_info($user);

    if (! empty($userInfo['pass_confirm']) && $_SESSION['login_time'] < $userInfo['pass_confirm']) {
        TikiLib::lib('login')->logout();
        if (! defined('TIKI_CONSOLE') && PHP_SAPI !== 'cli') {
            header('Location:tiki-login_scr.php');
        }
        exit;
    }
}

if (version_compare(PHP_VERSION, TIKI_MIN_PHP_VERSION, '<')) {
    if (PHP_SAPI !== 'cli') {                   // if not running a command line version of php, show requirements
        header('location: tiki-install.php');
        exit;
    }
    // This is command-line. No 'location' command make sense here. Let admins access what works and deal with the rest.
    echo 'Warning: Tiki expects PHP ' . TIKI_MIN_PHP_VERSION . ' and above. You are running ' . PHP_VERSION . ". Use at your own risk\n";
}

// Attempt setting locales. This code is just a start, locales should be set per-user.
// Also, different operating systems use different locale strings. en_US.utf8 is valid on POSIX systems, maybe not on Windows, feel free to add alternative locale strings.
// Getting the system default locale
$default_locale = Locale::getDefault();
if ($default_locale) {
    setlocale(LC_ALL, $default_locale); // Attempt changing the locale to the system default.
}
// Since the system default may not be UTF-8 but we may be dealing with multilingual content, attempt ensuring the collations are intelligent by forcing a general UTF-8 collation.
// This will have no effect if the locale string is not valid or if the designated locale is not generated.

foreach (['en_US.utf8'] as $UnicodeLocale) {
    if (setlocale(LC_COLLATE, $UnicodeLocale)) {
        break;
    }
}

if ($prefs['feature_tikitests'] == 'y') {
    require_once('tiki_tests/tikitestslib.php');
}
$crumbs[] = new Breadcrumb($prefs['browsertitle'], '', $prefs['tikiIndex']);
if ($prefs['site_closed'] == 'y') {
    require_once('lib/setup/site_closed.php');
}
require_once('lib/setup/error_reporting.php');
if ($prefs['use_load_threshold'] == 'y') {
    require_once('lib/setup/load_threshold.php');
}
require_once('lib/setup/sections.php');
/** @var HeaderLib $headerlib */
$headerlib = TikiLib::lib('header');

$headerlib->add_js('window.tikiroot = "' . $tikiroot . '";');

$domain_map = [];
if (isset($_SERVER['HTTP_HOST'])) {
    $host = $_SERVER['HTTP_HOST'];
} else {
    $host = "";
}
if (isset($_SERVER['REQUEST_URI'])) {
    $requestUri = $_SERVER['REQUEST_URI'];
} else {
    $requestUri = "";
}

//The value returned allows to check if the user has already enabled 2FA
$twoFactorSecret = $userlib->get_2_factor_secret($user);
//Check if user is forced to enable 2FA
$force2FA = $userlib->forceTwoFactorAuth($user);
//Check if 2FA is required for a user and if it not yet enabled
if ($prefs['twoFactorAuth'] == 'y' && empty($twoFactorSecret) && $force2FA && ! empty($user)) {
    $gracePeriod = $userlib->get2FAGracePeriod($user);
    $userGracePeriodStart = $userlib->get2FAGracePeriodStart($user);

    if (! $userGracePeriodStart) {
        if ($gracePeriod > 0) {
            $userGracePeriodStart = $userlib->reset2FAGracePeriodStart($user);
        } else {
            $userGracePeriodStart = 0;
        }
    }

    $userGracePeriodShouldEnd = $userGracePeriodStart + ($gracePeriod * 24 * 3600);

    if ($userGracePeriodShouldEnd < time()) {
        $accesslib = TikiLib::lib('access');
        //URL to send user who has not yet enabled 2FA
        $accessibleUrl = $base_url . 'tiki-user_preferences.php';
        $pageUrl = $url_scheme . "://" . $host . $requestUri;
        //Do not redirect the user if it is a logout action
        if ($accessibleUrl !== $pageUrl && $pageUrl !== $base_url . 'tiki-logout.php') {
            header('location: tiki-user_preferences.php');
            exit();
        }
    } else {
        $daysLeft = ceil(($userGracePeriodShouldEnd - time()) / (24 * 3600));
        Feedback::warning(tra(strtr('You must enable Two-Factor Authentication within the next %days% day(s) to continue using your account. Please update your <a href="%url%">user preferences</a>.', ['%days%' => $daysLeft, '%url%' => 'tiki-user_preferences.php'])));
    }
}

if ($prefs['tiki_domain_prefix'] == 'strip' && str_starts_with($host, 'www.')) {
    $domain_map[$host] = substr($host, 4);
} elseif ($prefs['tiki_domain_prefix'] == 'force' && ! str_starts_with($host, 'www.')) {
    $domain_map[$host] = 'www.' . $host;
}

if (str_contains($prefs['tiki_domain_redirects'], ',')) {
    foreach (explode("\n", $prefs['tiki_domain_redirects']) as $row) {
        list($old, $new) = array_map('trim', explode(',', $row, 2));
        $domain_map[$old] = $new;
    }
    unset($old);
    unset($new);
}

if (isset($domain_map[$host]) && ! defined('TIKI_CONSOLE')) {
    $prefix = $tikilib->httpPrefix();
    $prefix = str_replace("://$host", "://{$domain_map[$host]}", $prefix);
    $url = $prefix . $requestUri;

    $access->redirect($url, null, 301);
    exit;
}

if (isset($_REQUEST['PHPSESSID'])) {
    $tikilib->setSessionId($_REQUEST['PHPSESSID']);
} elseif (function_exists('session_id')) {
    $tikilib->setSessionId(session_id());
}

// Session info needs to be kept up to date if pref login_multiple_forbidden is set
if ($prefs['login_multiple_forbidden'] == 'y') {
    $tikilib->update_session();
}

require_once('lib/setup/cookies.php');
require_once('lib/setup/user_prefs.php');
require_once('lib/core/Sections.php');
use Tiki\Sections;
// Initialize language settings after section is set and autoloader is available
Language::setSectionLanguage();

// Register callback to update language when section changes
// This ensures that when admin files call Sections::setCurrentSection(),
// the language is automatically updated (e.g., admin section gets admin language)
Sections::onSectionChange(function ($section) {
    Language::setSectionLanguage();
});

Sections::onSectionChange(function ($section) use ($prefs) {
    if (($prefs['feature_freetags'] ?? 'n') !== 'y') {
        return;
    }

    $freetaglib = TikiLib::lib('freetag');
    $freetaglib->handleCurrentObjectTagRequest();
    $freetaglib->assignCurrentObjectTagsToSmarty();
});

require_once('lib/setup/wiki.php');

$user_groups = $userlib->get_user_groups($user);
$assigned_modules = TikiLib::lib('mod')->get_assigned_modules();
$prefs['switch_color_module_assigned'] = 'n';
foreach ($assigned_modules as $position => $modules) {
    foreach ($modules as $module) {
        if ($module['name'] == 'switch_color_mode') {
            $prefs['switch_color_module_assigned'] = 'y';
            break;
        }
    }
}

$tables = TikiDb::get()->listTables();
$color_mode_table_exists = in_array("tiki_custom_color_modes", $tables);
if ($color_mode_table_exists) {
    try {
        $custom_color_mode = TikiDb::get()->fetchAll("SELECT css_variables FROM tiki_custom_color_modes WHERE custom='y'", null, -1, -1, 'exception');
        $color_mode_names = TikiDb::get()->fetchAll("SELECT name FROM tiki_custom_color_modes WHERE 1", null, -1, -1, 'exception');
        $prefs['custom_color_mode'] = $custom_color_mode;
        $prefs['color_modes_names'] = [];
        foreach ($color_mode_names as $mode) {
            $prefs['color_modes_names'][$mode['name']] = $mode['name'];
        };
    } catch (Exception $e) {
        //failed to pull css variables from DB
        $prefs['custom_color_mode'] = [];
        $prefs['color_modes_names'] = [];
    }
} else {
    $prefs['custom_color_mode'] = [];
    $prefs['color_modes_names'] = [];
}

require_once('lib/setup/theme.php');

if (! TIKI_API) {
    require_once('lib/setup/javascript.php');
}

/* Cookie consent setup, has to be after the JS decision and wiki setup */
if (! TIKI_API) {
    $cookie_consent_html = '';
    if (
        $prefs['cookie_consent_feature'] === 'y' &&
        (
            ! str_contains($_SERVER['PHP_SELF'], 'tiki-cookie-jar.php') && http_response_code() !== false ||
            $jitRequest->offsetExists('cookie_consent')
        )
    ) {
        $cookie_consent_name = CookieConsentLib::COOKIE_CONSENT_NAME;
        if (
            ! empty($_REQUEST[$cookie_consent_name]) ||
            $prefs['site_closed'] === 'y'
        ) {
            // js disabled, so we need to set the cookies server-side
            // DRY, this appears to be the default value of initializeConsentPreferences why do we have another copy here?  benoitg - 2026-03-25
            $consent = [
                'consentGiven' => true,
                'categories' => [
                    'essential' => true,
                    'analytics' => false,
                    'marketing' => false,
                    'functional' => false,
                ],
            ];
            CookieConsentLib::setConsentPreferences($consent);
            $feature_no_cookie = false;  //This isn't a global here, so this set will have no effect! - benoitg - 2026-03-25
        }
        // Retrieve the full consent object
        $consent_preferences = CookieConsentLib::initializeConsentPreferences();

        /*
        I don't understand why CookieConsentLib doesn't deal with this at init.
        Especially since it's not clearing the cookies for a category if one withdraws consent from
        tiki-user_preferences.php, so tiki essentially lies in that situation -  benoitg - 2026-03-25
        */
        if (! $consent_preferences['consentGiven'] || $jitRequest->offsetExists('cookie_consent')) {
            if (! $jitRequest->offsetExists('cookie_consent')) {
                foreach ($_COOKIE as $k => $v) {
                    if (! str_contains($k, session_name())) {
                        setcookie($k, '', time() - 3600);        // unset any previously existing cookies except the session and js detect
                    }
                }
            }
            // Get cookie categories
            $smarty->assign('cookie_categories', CookieConsentLib::getRequestedCookieCategories());
            $cookie_consent_html = $smarty->fetch('cookie_consent.tpl');
        }
    }
    $smarty->assign('cookie_consent_html', $cookie_consent_html);
}

if ($prefs['feature_polls'] == 'y') {
    require_once('lib/setup/polls.php');
}
if ($prefs['feature_mailin'] == 'y') {
    require_once('lib/setup/mailin.php');
}
require_once('lib/setup/tikiIndex.php');
if ($prefs['useGroupHome'] == 'y') {
    require_once('lib/setup/default_homepage.php');
}
if ($prefs['user_force_avatar_upload'] === 'y') {
        require_once('lib/setup/avatar_force_upload.php');
}
if ($prefs['tracker_force_fill'] == 'y') {
    require_once('lib/setup/tracker_force_fill.php');
}
// change $prefs['tikiIndex'] if feature_sefurl is enabled (e.g. tiki-index.php?page=HomePage becomes HomePage)
if ($prefs['feature_sefurl'] == 'y' && ! defined('TIKI_CONSOLE')) {
    //TODO: need a better way to know which is the type of the tikiIndex URL (wiki page, blog, file gallery etc)
    //TODO: implement support for types other than wiki page and blog
    if ($prefs['tikiIndex'] == 'tiki-index.php' && $prefs['wikiHomePage']) {
        $wikilib = TikiLib::lib('wiki');
        $prefs['tikiIndex'] = $wikilib->sefurl($userlib->best_multilingual_page($prefs['wikiHomePage']));
    } elseif (str_starts_with($prefs['tikiIndex'], 'tiki-view_blog.php')) {
        include_once('tiki-sefurl.php');
        $prefs['tikiIndex'] = filter_out_sefurl($prefs['tikiIndex'], 'blog');
    }
}

if (! empty($varcheck_errors) && ! TIKI_API) {
    if (
        isset($_SERVER['HTTP_X_REQUESTED_WITH'])
        && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest'
    ) {
        Feedback::error($varcheck_errors, true);
        exit(1);
    } else {
        $varcheck_errors = [
            'tpl' => 'error_raw.tpl',
            'mes' => $varcheck_errors,
        ];
        Feedback::errorPage($varcheck_errors);
    }
}
if ($prefs['feature_usermenu'] == 'y') {
    require_once('lib/setup/usermenu.php');
}
if ($prefs['feature_live_support'] == 'y') {
    require_once('lib/setup/live_support.php');
}
if ($prefs['feature_referer_stats'] == 'y' || $prefs['feature_stats'] == 'y') {
    require_once('lib/setup/stats.php');
}
require_once('lib/setup/dynamic_variables.php');
require_once('lib/setup/output_compression.php');
if ($prefs['feature_debug_console'] == 'y') {
    // Include debugger class declaration. So use loggin facility in php files become much easier :)
    include_once('lib/debug/debugger.php');
}
if ($prefs['feature_integrator'] == 'y') {
    require_once('lib/setup/integrator.php');
}
if (isset($_REQUEST['comzone'])) {
    require_once('lib/setup/comments_zone.php');
}
if (! empty($_SESSION['interactive_translation_mode']) && ($_SESSION['interactive_translation_mode'] == 'on')) {
    $cachelib->empty_cache('templates_c');
}
if ($prefs['feature_freetags'] == 'y') {
    require_once('lib/setup/freetags.php');
}
if ($prefs['feature_categories'] == 'y') {
    require_once('lib/setup/categories.php');
}
if ($prefs['feature_userlevels'] == 'y') {
    require_once('lib/setup/userlevels.php');
}
if ($prefs['feature_wysiwyg'] == 'y') {
    if (! isset($_SESSION['wysiwyg'])) {
        $_SESSION['wysiwyg'] = 'n';
    }
    $smarty->assign_by_ref('wysiwyg', $_SESSION['wysiwyg']);
    // this may no longer be needed
    $headerlib->add_css('.ui-front {z-index: 9999;}');  // so the plugin edit dialogs show up
} else {
    $_SESSION['wysiwyg'] = 'n';
    $smarty->assign('wysiwyg', 'n');
}

if ($prefs['pwa_feature'] == 'y') { //pwa test propose, pages to cache
    $headerlib->add_jsfile(VendorHelper::getAvailableVendorPath('dexie', 'npm-asset/dexie/dist/dexie.min.js'), true);
    $headerlib->add_jsfile("lib/pwa/app.js");
    $pages = ['trackers' => [], 'wiki' => []];

    if (isset($user)) {
        $trackerlib = TikiLib::lib('trk');

        $trackers = $trackerlib->list_trackers();
        foreach ($trackers['data'] as $tracker) {
            $items = $trackerlib->get_all_tracker_items($tracker['trackerId']);
            $pages['trackers'] = array_merge($pages['trackers'], array_map(function ($item) use ($tracker) {
                return ['id' => $tracker['trackerId'], 'itemId' => $item];
            }, $items));
        }

        $pagesAll = $tikilib->get_all_pages(['pageName']);
        $pages['wiki'] = array_map(function ($m) {
            return str_replace(' ', '-', $m['pageName']);
        }, $pagesAll);
    }
    $urls = explode(PHP_EOL, $prefs['pwa_cache_links']);
    $pages['urls'] = $urls;
    $smarty->assign('pagespwa', json_encode($pages));
}


Sections::onSectionChange(function ($section) use ($prefs, $headerlib, $smarty) {
    $customRobots = null;
    if ($prefs['metatag_robotscustom'] == 'y') {
        $object = current_object();
        if ($object && $object['type'] == 'wiki page') {
            $wikilib = TikiLib::lib('wiki');
            $customRobots = $wikilib->getPageMetatagRobotscustom($object['object']);
            if (! empty($customRobots)) {
                $smarty->assign('metatag_robotscustom', $customRobots);
                $headerlib->setXRobotsTag($customRobots);
            }
        }
    }

    // Apply robots directives from robots.txt as meta tags and X-Robots-Tag header.
    if (($prefs['metatag_robots_txt_apply_directives'] ?? 'n') === 'y') {
        $robotsFromTxt = $headerlib->getRobots();
        if (! empty($robotsFromTxt)) {
            $smarty->assign('metatag_robots', $robotsFromTxt);
            if (empty($customRobots)) {
                $headerlib->setXRobotsTag($robotsFromTxt);
            }
        }
    }
});

if ($prefs['feature_antibot'] == 'y' && empty($user)) {
    $captchaType = CaptchaTypeResolver::getConfiguredType($prefs);
    if (in_array($captchaType, ['recaptcha', 'recaptcha20', 'recaptcha30'])) {
        if ($captchaType == 'recaptcha20') {
            if (! empty($prefs['language'])) {
                $headerlib->add_jsfile_cdn("$url_scheme://www.google.com/recaptcha/api.js?hl=" . $prefs['language']);
            } else {
                $headerlib->add_jsfile_cdn("$url_scheme://www.google.com/recaptcha/api.js");
            }
        } else {
            $headerlib->add_jsfile_cdn("$url_scheme://www.google.com/recaptcha/api.js?render=" . $prefs['recaptcha_pubkey']);
        }
    }
    $captchalib = TikiLib::lib('captcha');
    $smarty->assign('captchalib', $captchalib);
}

if ($prefs['feature_credits'] == 'y') {
    require_once('lib/setup/credits.php');
}

if ($prefs['https_external_links_for_users'] == 'y') {
    $base_url_canonical_default = $base_url_https;
} else {
    $base_url_canonical_default = $base_url_http;
}

if (! empty($prefs['feature_canonical_domain'])) {
    $base_url_canonical = $prefs['feature_canonical_domain'];
} else {
    $base_url_canonical = $base_url_canonical_default;
}
// Since it's easier to be error-resistant than train users, ensure base_url_canonical ends with '/'
if (! str_ends_with($base_url_canonical, '/')) {
    $base_url_canonical .= '/';
}

$smarty->assign_by_ref('phpErrors', $phpErrors);
$smarty->assign_by_ref('num_queries', $num_queries);
// Assigned by ref because there is no place to assigne it where no additional queries would occur
$smarty->assign_by_ref('elapsed_in_db', $elapsed_in_db);
$smarty->assign_by_ref('crumbs', $crumbs);
$smarty->assign('lock', false);
$smarty->assign('edit_page', 'n');
$smarty->assign('forum_mode', 'n');
$smarty->assign('wiki_extras', 'n');
$smarty->assign('tikipath', $tikipath);
$smarty->assign('tikiroot', $tikiroot);
$smarty->assign('url_scheme', $url_scheme);
$smarty->assign('url_host', $url_host);
$smarty->assign('url_port', $url_port);
$smarty->assign('url_path', $url_path);
$dir_level = (! empty($dir_level)) ? $dir_level : '';
$smarty->assign('dir_level', $dir_level);
$smarty->assign('base_host', $base_host);
$smarty->assign('base_url', $base_url);
$smarty->assign('base_url_http', $base_url_http);
$smarty->assign('base_url_https', $base_url_https);
$smarty->assign('base_url_canonical', $base_url_canonical);
$smarty->assign('base_url_canonical_default', $base_url_canonical_default);
$smarty->assign('show_stay_in_ssl_mode', $show_stay_in_ssl_mode);
$smarty->assign('stay_in_ssl_mode', $stay_in_ssl_mode);
$smarty->assign('tiki_version', $TWV->version);
$smarty->assign('tiki_branch', $TWV->branch);
$smarty->assign('tiki_star', $TWV->getStar());
$smarty->assign('symbols', TikiLib::symbols());

$maintenance = new Maintenance();
if ($maintenance->isTime()) {
    TikiLib::lib('access')->showSiteClosed('maintenance', $maintenance->getMessage());
} elseif ($maintenance->isPreMaintenanceTime()) {
    $smarty->assign('maintenanceNotification', $maintenance->getPreMaintenanceMessage());
} elseif ($maintenance->isIndexing()) {
    $smarty->assign('maintenanceNotification', $maintenance->getIndexingMessage());
}

// Used by TikiAccessLib::redirect()
if (isset($_GET['msg'])) {
    Feedback::add(['mes' => htmlspecialchars($_GET['msg']), 'type' => htmlspecialchars($_GET['msgtype'] ?? '')]);
} elseif (isset($_SESSION['msg'])) {
    Feedback::add(['mes' => $_SESSION['msg'], 'type' => $_SESSION['msgtype']]);
    unset($_SESSION['msg']);
    unset($_SESSION['msgtype']);
}

require_once 'lib/setup/events.php';

if ($prefs['rating_advanced'] == 'y' && $prefs['rating_recalculation'] == 'randomload') {
    $ratinglib = TikiLib::lib('rating');
    $ratinglib->attempt_refresh();
}

// using jquery-migrate-1.3.0.js plugin for tiki 11, still required in tiki 12 LTS to support some 3rd party plugins

// jQuery 4 also ships an ES module build (dist-module/jquery.module.js), but Tiki still
// loads the classic one here: module scripts are deferred, so every classic script runs
// first, and anything classic using $ at load time would break if jQuery were only
// reachable as a module. jQuery also stays on the global window object for the many
// plugins that expect it there. The headerlib refactor lifts that ordering constraint,
// and jQuery 4's own exports map is meant to give import and require the same copy, so
// the switch should not need a shim on our side.
// See https://github.com/jquery/jquery/wiki/jQuery-4-exports-explainer

// Google's CDN has no jQuery 4.x (its newest is 3.7.1), so 'google' falls through to
// the local copies below instead of 404ing, as jquery-migrate already had to.
if (isset($prefs['javascript_cdn']) && $prefs['javascript_cdn'] == 'jquery') {
    $headerlib->add_jsfile_cdn("$url_scheme://code.jquery.com/jquery-$headerlib->jquery_version.min.js");
    $headerlib->add_jsfile_cdn("$url_scheme://code.jquery.com/jquery-migrate-$headerlib->jquerymigrate_version.min.js");
} else {
    if (isset($prefs['tiki_minify_javascript']) && $prefs['tiki_minify_javascript'] === 'y') {
        $headerlib->add_jsfile_dependency(NODE_PUBLIC_DIST_PATH . "/jquery/dist/jquery.min.js", true);
        $headerlib->add_jsfile_dependency(NODE_PUBLIC_DIST_PATH . "/jquery-migrate/dist/jquery-migrate.min.js", true);
    } else {
        $headerlib->add_jsfile_dependency(NODE_PUBLIC_DIST_PATH . "/jquery/dist/jquery.js", true);
        $headerlib->add_jsfile_dependency(NODE_PUBLIC_DIST_PATH . "/jquery-migrate/dist/jquery-migrate.js", true);
    }
}

if (isset($prefs['fgal_elfinder_feature']) && $prefs['fgal_elfinder_feature'] === 'y') {
    //Loading javascript which is huge, has been moved in tikiElFinder::loadJSCSS to load on demand.
    //Loading CSS hasn't been moved yet, as there are css load order issues if we blindly move it there.  benoitg - 2023-04-20
    $headerlib->add_cssfile('vendor_bundled/vendor/studio-42/elfinder/css/elfinder.min.css')->add_cssfile('vendor_bundled/vendor/studio-42/elfinder/css/theme.css');
}

$headerlib->add_jsfile('lib/jquery_tiki/tiki-jquery.js');

if ($prefs['error_tracking_enabled_js'] == 'y' && ! empty($prefs['error_tracking_dsn'])) {
    // Sentry configuration passed to ES module
    $sentryConfig = json_encode([
        'dsn' => $prefs['error_tracking_dsn'],
        'sampleRate' => isset($prefs['error_tracking_sample_rate']) && is_numeric($prefs['error_tracking_sample_rate'])
            ? (float) $prefs['error_tracking_sample_rate']
            : 1,
        'tracingEnabled' => ($prefs['error_tracking_tracing_enabled_js'] ?? 'n') === 'y',
        'tracesSampleRate' => isset($prefs['error_tracking_traces_sample_rate']) && is_numeric($prefs['error_tracking_traces_sample_rate'])
            ? (float) $prefs['error_tracking_traces_sample_rate']
            : 0.1,
    ]);

    // Sentry initialization via ES module
    // Loaded with priority -10 to ensure it initializes before other modules
    $headerlib->add_js_module("import { initSentry } from '@tiki-modules/sentryBrowser'; initSentry($sentryConfig);", -10);
}

//This is applied globally so all menus can open upward when space is limited.
//Since menus appear in many places in Tiki, we can’t handle them individually, so we load it once on page load to make it available site-wide.
//templates/menu/clone.tpl
//templates/menu/preview.tpl
//templates/menu/quicklinks.tpl
//templates/tiki-view_sheets_menu.tpl
$headerlib->add_jsfile(JS_ASSETS_PATH . '/jquery-tiki/tiki-menu.js');
$headerlib->add_jsfile('lib/tiki-js.js'); //This depends on tiki-jquery.js in at least one place, so must load after - benoitg - 2023-11-21

if ($prefs['feature_jquery_zoom'] === 'y') {
    $headerlib->add_jsfile(NODE_PUBLIC_DIST_PATH . '/jquery-zoom/jquery.zoom.js')
        ->add_css('
.img_zoom {
    display:inline-block;
}
.img_zoom:after {
    content:"";
    display:block;
    width:33px;
    height:33px;
    position:absolute;
    top:0;
    right:0;
    background:url(' . NODE_PUBLIC_DIST_PATH . '/jquery-zoom/icon.png);
}
.img_zoom img {
    display:block;
}
');
}
// Load CodeMirror CSS early.
// Adding it from Smarty/Ajax is too late and breaks the editor.
// Therefore, we load these CSS files during setup (tiki-setup.php / early stage)
// to ensure they are present in the <head> before any rendering occurs.
if ($prefs['feature_syntax_highlighter'] == 'y') {
    $headerlib->add_cssfile(CODEMIRROR_DIST_PATH . '/lib/codemirror.css')
        ->add_cssfile('themes/base_files/feature_css/codemirror_tiki.css');
}
if ($prefs['feature_ajax'] === 'y') {
    $headerlib->add_jsfile('lib/jquery_tiki/tiki-confirm.js');
    $headerlib->add_jsfile('lib/ajax/autosave.js'); // Note that this file is needed even if ajax_autosave is off otherwise wysiwyg won't load.
}

if ($prefs['cookie_consent_feature'] == 'y') {
    $headerlib->add_js_module('import "@jquery-tiki/tiki-cookie-handler";');
}

// $url_scheme is 'http' or 'https' depending on request type condsidering already a reverse proxy
// $https_mode is true / false depending on request type condsidering already a reverse proxy
if ($prefs['feature_jquery_ui'] == 'y') {
    // Google's CDN does not carry the jQuery UI version Tiki pins (its newest is
    // 1.14.1), so 'google' falls through to the local copy below rather than 404ing.
    // Same approach already used for jquery-migrate, which Google has never hosted,
    // and for the jQuery UI theme CSS further down.
    if (isset($prefs['javascript_cdn']) && $prefs['javascript_cdn'] == 'jquery') {
        $headerlib->add_jsfile_cdn("$url_scheme://code.jquery.com/ui/$headerlib->jqueryui_version/jquery-ui.min.js");
    } else {
        if ($prefs['tiki_minify_javascript'] === 'y') {
            $headerlib->add_jsfile_dependency(NODE_PUBLIC_DIST_PATH . "/jquery-ui/dist/jquery-ui.min.js", true);
        } else {
            $headerlib->add_jsfile_dependency(NODE_PUBLIC_DIST_PATH . "/jquery-ui/dist/jquery-ui.js");
        }
    }

    // restore jquery-ui buttons function, thanks to http://stackoverflow.com/a/23428433/2459703
    $headerlib->add_js('
var bootstrapButton;
if (typeof $.fn.button.noConflict === "function") {
    bootstrapButton = $.fn.button.noConflict() // return $.fn.button to previously assigned value
    $.fn.bootstrapBtn = bootstrapButton            // give $().bootstrapBtn the Bootstrap functionality
}
');

    if ($prefs['feature_jquery_ui_theme'] !== 'none') {
        // cdn for css not working - this is the only css from a cdn anyway - so use local version
        //if ( isset($prefs['javascript_cdn']) && $prefs['javascript_cdn'] == 'jquery' ) {
            // $headerlib->add_cssfile("$url_scheme://code.jquery.com/ui/$headerlib->jqueryui_version/themes/{$prefs['feature_jquery_ui_theme']}/jquery-ui.css");
            $headerlib->add_cssfile(NODE_PUBLIC_DIST_PATH . '/jquery-ui/dist/themes/' . $prefs['feature_jquery_ui_theme'] . '/jquery-ui.css');
    //  } else {
    //      $headerlib->add_cssfile('vendor_bundled/vendor/jquery/jquery-ui-themes/themes/' . $prefs['feature_jquery_ui_theme'] . '/jquery-ui.css');
    //  }
    }
}
if ($prefs['jquery_fitvidjs'] == 'y') {
    $customSelectors = \Tiki\Lib\FitVidJs\FitVidJs::getCustomSelector();
    $headerlib->add_jsfile(NODE_PUBLIC_DIST_PATH . '/fitvids/dist/fitvids.js')
        ->add_jq_onready("fitvids('article', $customSelectors);");
}
if ($prefs['jquery_smartmenus_enable'] == 'y') {
    Sections::onSectionChange(function ($section) use ($headerlib) {
        $headerlib->add_cssfile(NODE_PUBLIC_DIST_PATH . '/smartmenus/dist/css/smartmenus-only-layout.css', 10);
        $headerlib->add_js_module(
            'import SmartMenus from "smartmenus";
const nav = document.querySelectorAll("nav");
let collapsible_behavior = jqueryTiki.smartmenus_collapsible_behavior == "default" ? false : true;
let noMouseoverBehavior = jqueryTiki.smartmenus_open_close_click ? "click" : "mouseover";
let hideOnClick = jqueryTiki.smartmenus_open_close_click ? "click" : "mouseout";

for (let indexCurrentNav = 0; indexCurrentNav < nav.length; indexCurrentNav++) {
    if (nav[indexCurrentNav].querySelector("div.collapse") !== null) {
        nav[indexCurrentNav].classList.add("sm-navbar");
        nav[indexCurrentNav].querySelector("div.collapse").classList.add("sm-collapse");
        const navigation = new SmartMenus(nav[indexCurrentNav], {collapsibleBehaviorAccordion: collapsible_behavior, dropdownsShowTrigger: noMouseoverBehavior, dropdownsHideTrigger: hideOnClick, dropdownsHideTimeout: 250, dropdownsShowTimeout: 200});

        // Enable navigation on parent menu items with children
        // SmartMenus uses .sm-sub-toggler class for parent items, which by default only toggle submenus
        // This handler allows navigation when parent items have valid hrefs (not just "#" or empty)
        // Note: Relies on SmartMenus internal class that may change in future versions
        nav[indexCurrentNav].addEventListener("click", function(e) {
            const link = e.target.closest("a.sm-sub-toggler");
            if (link && nav[indexCurrentNav].contains(link)) {
                const href = link.getAttribute("href");
                if (0 && href && href !== "#" && href !== "") {
                    e.preventDefault();
                    e.stopPropagation();
                    window.location.href = href;
                }
            }
        });
    }
}
'
        );
    });
}

if ($prefs['feature_jquery_tablesorter'] == 'y') {
    $headerlib->add_jsfile(NODE_PUBLIC_DIST_PATH . '/tablesorter/dist/js/jquery.tablesorter.combined.js');
    $headerlib->add_jsfile(NODE_PUBLIC_DIST_PATH . '/tablesorter/dist/js/parsers/parser-input-select.min.js');
    $headerlib->add_jsfile(NODE_PUBLIC_DIST_PATH . '/tablesorter/dist/js/widgets/widget-columnSelector.min.js');
    $headerlib->add_jsfile(NODE_PUBLIC_DIST_PATH . '/tablesorter/dist/js/widgets/widget-filter-formatter-jui.min.js');
    $headerlib->add_jsfile(NODE_PUBLIC_DIST_PATH . '/tablesorter/dist/js/widgets/widget-grouping.min.js');
    $headerlib->add_jsfile(NODE_PUBLIC_DIST_PATH . '/tablesorter/dist/js/widgets/widget-math.min.js');
    $headerlib->add_jsfile(NODE_PUBLIC_DIST_PATH . '/tablesorter/dist/js/widgets/widget-pager.min.js');
    $headerlib->add_jsfile(NODE_PUBLIC_DIST_PATH . '/tablesorter/dist/js/widgets/widget-output.min.js'); // APTITUDE NEEDS THIS
    //currently only working when ajax is not used
    $headerlib->add_jsfile(NODE_PUBLIC_DIST_PATH . '/tablesorter/dist/js/widgets/widget-sort2Hash.min.js');
    $headerlib->add_jsfile('lib/jquery_tiki/tablesorter.js');
}

if ($prefs['feature_jquery_tagcanvas'] == 'y') {
    $headerlib->add_jsfile(NODE_PUBLIC_DIST_PATH . '/jquery-tagcanvas/jquery.tagcanvas.min.js');
}

if ($prefs['feature_shadowbox'] == 'y') {
    $headerlib->add_cssfile(NODE_PUBLIC_DIST_PATH . '/glightbox/dist/css/glightbox.min.css');
    $headerlib->add_css('
.gcounter {
    position: absolute;
    bottom: 15px;
    right: 20px;
    font-size: 14px;
    color: white;
    z-index: 9999;
}
.gcontainer .ginner-container {
    max-width: 90vw;
    max-height: 90vh;
}
.gnext.disabled,
.gprev.disabled{
    display: none !important;
}
');
    $localPlyrCSS = NODE_PUBLIC_DIST_PATH . '/plyr/dist/plyr.css';
    $localPlyrJS = NODE_PUBLIC_DIST_PATH . '/plyr/dist/plyr.min.js';
    $headerlib->add_js_module(<<<JS
    import initGlightbox from "@tiki-glightbox";
    window.applyGlightbox = initGlightbox({
         plyr: {
            css: "$localPlyrCSS",
            js: "$localPlyrJS"
        }
    });
    JS);
}

if ($prefs['jquery_timeago'] === 'y') {
    $language = Language::getCurrentLanguage();
    $locale_file = '';

    $full_locale_path = NODE_PUBLIC_DIST_PATH . "/timeago/dist/locales/jquery.timeago.{$language}.js";

    $language_short = substr($language, 0, 2);
    $short_locale_path = NODE_PUBLIC_DIST_PATH . "/timeago/dist/locales/jquery.timeago.{$language_short}.js";

    $headerlib->add_jsfile_dependency(NODE_PUBLIC_DIST_PATH . '/timeago/dist/jquery.timeago.js');

    if (is_readable($full_locale_path)) {
        $headerlib->add_jsfile($full_locale_path);
    } elseif (is_readable($short_locale_path)) {
        $headerlib->add_jsfile($short_locale_path);
    }

    $headerlib->add_jsfile(JS_ASSETS_PATH . "/jquery-tiki/timeago.js");
    $headerlib->add_cssfile('themes/base_files/feature_css/timeago.css');
}

if ($prefs['jquery_jqdoublescroll'] == 'y') {
    $headerlib
        ->add_jsfile(NODE_PUBLIC_DIST_PATH . "/jqdoublescroll/jquery.doubleScroll.js")
        ->add_jq_onready('$(".table-responsive").doubleScroll({resetOnWindowResize: true});');
}

if ($prefs['feature_jquery_validation'] == 'y') {
    $headerlib->add_jsfile(NODE_PUBLIC_DIST_PATH . '/jquery-validation/dist/jquery.validate.js');
    $headerlib->add_jsfile('lib/validators/validator_tiki.js');
}

// note: @jquery-tiki/asyncLoop automatically loads a copy of jQuery
$headerlib->add_js_module('import "@jquery-tiki/asyncLoop";');

$headerlib->add_jsfile(NODE_PUBLIC_DIST_PATH . '/jquery-treetable/jquery.treetable.js');
$headerlib->add_cssfile(NODE_PUBLIC_DIST_PATH . '/jquery-treetable/css/jquery.treetable.css');

//This must always be loaded (early), as it's curently used by any Vue3 module using single-spa to make onDOMElementRemoved() available
$headerlib->add_jsfile('lib/jquery_tiki/tiki-vue.js');

if (empty($user) && $prefs['feature_antibot'] == 'y') {
    $headerlib->add_jsfile_late('lib/captcha/captchalib.js');
    if (CaptchaTypeResolver::getConfiguredType($prefs) === 'altcha') {
        $headerlib->add_js_module('import {Altcha} from "altcha";');
    }
}

if (! empty($prefs['header_custom_css'])) {
    $headerlib->add_css($prefs['header_custom_css']);
}

if (! empty($prefs['header_custom_js'])) {
    $headerlib->add_js($prefs['header_custom_js']);
}

if ($prefs['feature_file_galleries'] == 'y') {
    $headerlib->add_jsfile('lib/jquery_tiki/files.js');
}

if ($prefs['feature_trackers'] == 'y') {
    $headerlib->add_jsfile('lib/jquery_tiki/tiki-trackers.js');

    if ($prefs['feed_tracker'] === 'y') {
        $opts = TikiLib::lib('trk')->get_trackers_options(null, 'publishRSS', 'y');
        foreach ($opts as & $o) {
            $o = $o['trackerId'];
        }
        $trackers = TikiLib::lib('trk')->list_trackers();

        $rss_trackers = [];
        foreach ($trackers['data'] as $trk) {
            if (in_array($trk['trackerId'], $opts)) {
                $rss_trackers[] = [
                    'trackerId' => $trk['trackerId'],
                    'name' => $trk['name'],
                ];
            }
        }
        TikiLib::lib('smarty')->assign('rsslist_trackers', $rss_trackers);
    }
}

if ($prefs['geo_enabled'] === 'y') {
    $headerlib->add_map();
}

if ($prefs['xmpp_conversejs_always_load'] === 'y') {
    require_once 'lib/xmpp/ConverseJS.php';
    $xmppclient = new ConverseJS();
    array_map([$headerlib, 'add_jsfile'], $xmppclient->get_js_dependencies());
    array_map([$headerlib, 'add_cssfile'], $xmppclient->get_css_dependencies());
}
if ($prefs['markdown_enabled'] === 'y' && $prefs['feature_wysiwyg'] === 'y') {
    $str = $prefs['tiki_minify_javascript'] === 'y' ? '.min' : '';

    $headerlib ->add_cssfile(NODE_PUBLIC_DIST_PATH . "/@toast-ui/editor/dist/toastui-editor.css");
}

if ($prefs['workspace_ui'] == 'y') {
    $headerlib->add_jsfile('lib/jquery_tiki/tiki-workspace-ui.js');
}

if ($prefs['feature_sefurl'] != 'y') {
    $headerlib->add_js(
        '$.service = function (controller, action, query) {
        if (! query) {
            query = {};
        }
        query.controller = controller;

        if (action) {
            query.action = action;
        }

        return "tiki-ajax_services.php?" + $.buildParams(query);
    };'
    );
}

if ($prefs['feature_friends'] == 'y' || $prefs['monitor_enabled'] == 'y') {
    $headerlib->add_jsfile('lib/jquery_tiki/social.js');
}

if ($prefs['ajax_inline_edit'] == 'y') {
    $headerlib->add_jsfile('lib/jquery_tiki/inline_edit.js');
}

if ($prefs['mustread_enabled'] == 'y') {
    $headerlib->add_jsfile('lib/jquery_tiki/mustread.js');
}

if ($prefs['feature_tasks'] == 'y') {
    $headerlib->add_jsfile('lib/jquery_tiki/tiki-tasks.js');
}

Sections::onSectionChange(function ($section) {
    global $headerlib, $prefs, $user;
    if ($prefs['feature_inline_comments'] === 'y' && $prefs['comments_inline_annotator'] === 'y') {
        if (empty($object)) {
            $object = current_object();
        }
        $commentController = new Services_Comment_Controller();

        if (
            $object &&
            $commentController->isEnabled($object['type'], $object['object']) &&
            $commentController->canView($object['type'], $object['object'])
        ) {
            $canPost = $commentController->canPost($object['type'], $object['object']);
            // spoof a URI from type and id
            $objectIdentifier = urlencode($object['type']) . ':' . urlencode($object['object']);

            $headerlib
                ->add_js_module('import moment from "moment"; window.moment = moment;')
                ->add_jsfile('vendor_bundled/vendor/openannotation/annotator/annotator-full.min.js')
                ->add_cssfile('vendor_bundled/vendor/openannotation/annotator/annotator.min.css')
                // language=JavaScript
                ->add_jq_onready('
var annotatorContent = $("#top").annotator({readOnly: ' . ($canPost ? 'false' : 'true') . '});
annotatorContent.annotator("addPlugin", "Store", {
    prefix: "tiki-ajax_services.php?controller=annotation&action=",

    urls: {
        create:  "create",
        update:  "update&threadId=:id",
        destroy: "destroy&threadId=:id",
        search:  "search"
    },

    annotationData: {
        "uri": "' . $objectIdentifier . '"
    },

    loadFromSearch: {
        "limit": 20,
        "uri": "' . $objectIdentifier . '"
    },

    emulateJSON: true,    // send the data in a form request so we can get it later
    emulateHTTP: true    // tiki services need GET or POST

});
annotatorContent.annotator("addPlugin", "Permissions", {
    user: "' . $user . '",
    showViewPermissionsCheckbox: false,    // TODO for private comments
    showEditPermissionsCheckbox: false,
    userAuthorize: function(action, annotation, user) {
        return annotation.permissions[action];
    }
});');
            // handling for extra info
            if ($prefs['comments_inline_annotator_with_info'] === 'y') {
                $headerlib->add_jq_onready(
                    '// a little glue to show the author name and date
$(".annotator-outer.annotator-viewer").on("load", function (event, annotations) {
    $(this).find(".annotator-user").text(annotations[0].realName + " - " + annotations[0].commentDate);
});
// update the annotaion object with the user name and date for newly created annotations
$("#top").on("annotationCreated", function (e, annotation) {
    const momentDate = moment();
    annotation.commentDate = momentDate.format(jqueryTiki.shortDateFormat) + " " +
        momentDate.format(jqueryTiki.shortTimeFormat);
    annotation.realName = jqueryTiki.userRealName || jqueryTiki.username;
});'
                );
            }
        }
    }
});

$headerlib->add_jsfile('lib/jquery_tiki/pluginedit.js');
$headerlib->add_js_module('import "@jquery-tiki/coverpage-form";');

if ($prefs['feature_machine_learning'] == 'y') {
    $headerlib->add_jsfile('lib/jquery_tiki/tiki-machine_learning.js');
}

if (session_id()) {
    if ($prefs['tiki_cachecontrol_session']) {
        header('Cache-Control: ' . $prefs['tiki_cachecontrol_session']);
    }
} else {
    if ($prefs['tiki_cachecontrol_nosession']) {
        header('Cache-Control: ' . $prefs['tiki_cachecontrol_nosession']);
    }
}

if (! empty($prefs['access_control_allow_origin']) && ! empty($_SERVER['HTTP_ORIGIN']) && $base_host !== $_SERVER['HTTP_ORIGIN']) {
    $http_origin = $_SERVER['HTTP_ORIGIN'];

    if (in_array($http_origin, preg_split('/[\s,]+/', $prefs['access_control_allow_origin']))) {
        header("Access-Control-Allow-Origin: $http_origin");
    }
}

if (isset($token_error)) {
    $smarty->assign('token_error', $token_error);
    $access->display_error('', $token_error);
}

require_once('lib/setup/plugins_actions.php');

if ($tiki_p_admin == 'y') {
    $headerlib->add_jsfile_late('lib/jquery_tiki/tiki-admin.js');
}

if ($prefs['wikiplugin_addtocart'] == 'y') {
    $headerlib->add_jsfile('lib/payment/cartlib.js');
}

if (! Sections::getCurrentSection()) {
    // if nothing else has set the section, set it to global as not all files do
    Sections::setCurrentSection(Sections::SECTION_GLOBAL);
}

//////////////////////////////////////////////////////////////////////////
// ******************************************************************** //
// ** IMPORTANT NOTE:                                                ** //
// ** USE THE GLOBAL VARIABLE BELOW TO CONTROL THE VERSION OF EMAIL  ** //
// ** WHICH IS USED                                                  ** //
// **   $prefs['openpgp_gpg_pgpmimemail'] == 'y'                     ** //
// **       USE TIKI OpenPGP Enabled PGP/MIME-standard mail          ** //
// **   $prefs['openpgp_gpg_pgpmimemail'] == 'n'                     ** //
// **       USE TIKI normal mail functionality                       ** //
// **                                                                ** //
// ** SETTING THIS PREFERENCES VARIABLE TO "y" NEED PROPER           ** //
// ** CONFIGURATION OF gnupg AND RELATED KEYRING WITH PROPERLY       ** //
// ** CONFIGURED TIKI-SENDER KEYPAIR (PRIVATE/PUBLIC) AND ALL USER   ** //
// ** ACCOUNT-RELATED PUBLIC KEYS                                    ** //
// **                                                                ** //
// ** DO NOT SWITCH THIS VARIABLE TO TRUE FOR THIS EXPERIMENTAL      ** //
// ** FULLY PGP/MIME-ENCRYPTION COMPLIANT EMAIL FUNCTIONALITY, IF    ** //
// ** YOU ARE **NOT ABSOLUTE SURE HOW TO CONFIGURE IT**!             ** //
// **                                                                ** //
// ** ONCE PROPERLY CONFIGURED, SUCH 100% OPAQUE FUNCTIONALITY       ** //
// ** DELIVERS ROBUST END-TO-END PRIVACY WITH HIGH DEGREE OF TESTED  ** //
// ** ROBUSTNESS FOR THE FOLLOWING MAIL TRAFFIC:                     ** //
// **                                                                ** //
// **   - all webmail-based messaging from messu-compose.php         ** //
// **   - all admin notifications                                    ** //
// **   - all newsletters                                            ** //
// **                                                                ** //
// ** PLEASE NOTE THAT ALL SITE ACCOUNTS **MUST** HAVE PROPERLY      ** //
// ** CONFIGURED OpenPGP-COMPLIANT PUBLIC-KEY IN THE SYSTEM's        ** //
// ** KEYRING, SO IT IS NOT THEN WISE/POSSIBLE TO ALLOW ANONYMOUS    ** //
// ** SUBSCRIPTIONS TO NEWSLETTERS ETC, OR USE NOT FULLY PGP/MIME    ** //
// ** READY ACCOUNTS IN SUCH SYSTEM.                                 ** //
// **                                                                ** //
// ** IT IS ASSUMED, THAT IF AND WHEN YOU TURN SUCH PGP/MIME ON      ** //
// ** YOU ARE FULLY AWARE OF THE REQUIREMENTS AND CONSEQUENCES.      ** //
// **                                                                ** //
if ($prefs['openpgp_gpg_pgpmimemail'] == 'y') {
    // hollmeer 2012-11-03:
    // TURNED ON openPGP support from a lib based class
    require_once('lib/openpgp/openpgplib.php');
}
// **                                                                ** //
// ******************************************************************** //
//////////////////////////////////////////////////////////////////////////

//adding pdf creation javascript, used to integrate plugins like tablesorter, trackerfilter with mpdf.
if ($prefs['print_pdf_from_url'] != 'none') {
    $headerlib->add_jsfile('lib/jquery_tiki/pdf.js');
}

if (file_exists(TIKI_CUSTOMIZATIONS_SETUP_PHP_FILE)) {
    require_once(TIKI_CUSTOMIZATIONS_SETUP_PHP_FILE);
}

// any furher $headerlib->add_js() call not using rank = 'external' will be put into rank 'late'
// this should separate the overall JS from page specific JS
$headerlib->forceJsRankLate();

if ($prefs['conditions_enabled'] == 'y' && ! TIKI_API) {
    if (! Services_User_ConditionsController::hasRequiredAge($user)) {
        $servicelib = TikiLib::lib('service');
        $broker = $servicelib->getBroker();
        $broker->process('user_conditions', 'age_validation', $jitRequest);
        exit;
    }
    if (Services_User_ConditionsController::requiresApproval($user)) {
        $servicelib = TikiLib::lib('service');
        $broker = $servicelib->getBroker();
        $broker->process('user_conditions', 'approval', $jitRequest);
        exit;
    }
}

if ($prefs['feature_tiki_manager'] == 'y') {
    $headerlib->add_jsfile('lib/jquery_tiki/tiki-manager.js');
}

if ($prefs['feature_realtime'] == 'y') {
    $headerlib->add_jsfile('lib/jquery_tiki/tiki-websockets.js');
}

if ($prefs['feature_calendar'] === 'y') {
    $headerlib->add_cssfile(NODE_PUBLIC_DIST_PATH . "/@event-calendar/core/dist/index.css");
    $headerlib->add_js_module('import "@jquery-tiki/tiki-calendar"; window.moduleCalendar = [];');
    $headerlib->add_js_module('import "@jquery-tiki/eventcalendar_to_pdf";');
}

// Using boomerang for performance monitoring
if ($prefs['tiki_monitor_performance'] == 'y') {
    $headerlib->add_jsfile_dependency(NODE_PUBLIC_DIST_PATH . "/boomerangjs/boomerang.js");
    $headerlib->add_jsfile_dependency(NODE_PUBLIC_DIST_PATH . "/boomerangjs/plugins/rt.js");
}

$headerlib->add_js_module('import Sortable from "sortablejs"; window.Sortable = Sortable;');

// Shoelace color picker
// Why are these in global setup, and unconditional?  color picking is not a frequent user operation - benoitg - 2026-03-09
$headerlib->add_js_module("import '@shoelace/color-picker';");
$headerlib->add_cssfile(JS_ASSETS_PATH . '/vendor_dist/@shoelace-style/shoelace/dist/themes/light.css');
$headerlib->add_js_module("import { textareaColorpicker } from '@tiki/ui-utils'; textareaColorpicker();");

// element-plus-ui
if ($prefs['feature_elementplus'] == 'y') {
    $headerlib->add_cssfile(JS_ASSETS_PATH . '/vendor_dist/element-plus/css/base.css');
    $headerlib->add_cssfile(JS_ASSETS_PATH . '/vendor_dist/element-plus/css/el-message.css');
    $headerlib->add_cssfile(JS_ASSETS_PATH . '/vendor_dist/element-plus/css/el-message-box.css');
    $headerlib->add_cssfile(JS_ASSETS_PATH . '/vendor_dist/element-plus/css/el-popper.css');
    $headerlib->add_cssfile(JS_ASSETS_PATH . '/element-plus-ui/input-root-css.css');
    $headerlib->add_js_module("import '@vue-widgets/el-file-input';");
    $headerlib->add_js_module("import '@vue-widgets/el-input';");
    $headerlib->add_js_module("import { showMessage } from '@vue-widgets/el-message'; window.showMessage = showMessage;");
    if ($prefs['elementplus_select'] == 'y') {
        $headerlib->add_js_module("import { applySelect } from '@vue-widgets/el-select'; applySelect();");
    }
    if ($prefs['elementplus_autocomplete'] == 'y') {
        $headerlib->add_js_module("import { autocomplete } from '@tiki/ui-utils'; window.autocomplete = autocomplete;");
    }
} else {
    $headerlib->add_jq_onready("window.showMessage = function (message) { alert(message); };");
}

$headerlib->add_js_module('import "@jquery-tiki/constants";');
$headerlib->add_js_module('import "@jquery-tiki/tiki-password";');

$headerlib->add_cssfile(NODE_PUBLIC_DIST_PATH . '/summernote/dist/summernote-bs5.min.css');

if ($prefs['fgal_use_record_rtc_screen'] == 'y') {
    $headerlib->add_jsfile(NODE_PUBLIC_DIST_PATH . '/moment/min/moment.min.js', true);
    $headerlib->add_jsfile(NODE_PUBLIC_DIST_PATH . '/recordrtc/RecordRTC.js', true);
    $headerlib->add_jsfile('lib/jquery_tiki/recordrtc.js', true);
    $headerlib->add_cssfile('themes/base_files/feature_css/recordrtc.css');
}
// use this to distinguish if tiki-setup has completed, e.g. in smarty lib when including tiki-modules and determining if a redirect must be served or not
define('TIKI_SETUP_FINISHED', true);
