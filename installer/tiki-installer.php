<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// To (re-)enable this script the file has to be named tiki-installer.php and the following four lines
// must start with two '/' and 'stopinstall:'. (Make sure there are no spaces inbetween // and stopinstall: !)

use Tiki\Installer\Installer;
use Tiki\Installer\Patch;
use Tiki\Installer\ProgressBar;

//this script may only be included - so its better to die if called directly.
if (strpos($_SERVER["SCRIPT_NAME"], basename(__FILE__)) !== false) {
    header("location: index.php");
    exit;
}

$inputConfiguration = [
    [ 'staticKeyFilters' =>
        [
            'admin_account' => 'striptags',
            'admin_email' => 'striptags',
            'browsertitle' => 'striptags',
            'server_domain' => 'striptags',
            'convert_to_utf8' => 'xss',
            'db' => 'alpha',
            'dbinfo' => 'alpha',
            'email_test_cc' => 'digits',
            'email_test_to' => 'email',
            'use_proxy' => 'alpha',
            'proxy_host' => 'striptags',
            'proxy_port' => 'digits',
            'proxy_user' => 'striptags',
            'proxy_pass' => 'striptags',
            'error_reporting_adminonly' => 'alpha',
            'error_reporting_level' => 'int',
            'feature_switch_ssl_mode' => 'alpha',
            'feature_show_stay_in_ssl_mode' => 'alpha',
            'fix_disable_accounts' => 'alpha',
            'fix_double_encoding' => 'xss',
            'force_utf8' => 'alpha',
            'general_settings' => 'alpha',
            'host' => 'text',
            'https_login' => 'word',
            'https_port' => 'digits',
            'install_step' => 'digits',
            'install_type' => 'word',
            'lang' => 'lang',
            'log_tpl' => 'alpha',
            'multi' => 'striptags',
            'name' => 'text',
            'pass' => 'text',
            'perform_mail_test' => 'alpha',
            'previous_encoding' => 'word',
            'reset' => 'alpha',
            'resetdb' => 'alpha',
            'scratch' => 'word',
            'sender_email' => 'striptags',
            'setdbversion' => 'text',
            'smarty_notice_reporting' => 'alpha',
            'test' => 'alnum',
            'test2' => 'digits',
            'test3' => 'int',
            'test4' => 'word',
            'update' => 'word',
            'useInnoDB' => 'digits',
            'user' => 'text',
//          'validPatches' => '',   //paramterized in sql
        ]
    ]
];

$errors = '';


try {
    $inputFilter = DeclFilter::fromConfiguration($inputConfiguration);
    $_GET = $inputFilter->filter($_GET);
    $_POST = $inputFilter->filter($_POST);
    $_REQUEST = array_merge($_GET, $_POST);
} catch (Exception $e) {
    $errors .= '<strong>' . $e->getMessage() . '</strong><br>' . tr('Check') . '<a href="tiki-check.php">tiki-check.php</a>' . tr('to ensure your system is ready for Tiki or refer to') . '<a href="https://doc.tiki.org/Requirements">https://doc.tiki.org/Requirements</a>' . tr('for more information.');
    error_and_exit();
}

require_once('tiki-filter-base.php');

// Define and load Smarty components
global $prefs;
$prefs = [];
$prefs['smarty_notice_reporting'] = 'n';
$prefs['smarty_compilation'] = 'always';
$prefs['smarty_security'] = 'y';
require_once 'lib/init/initlib.php';
require_once 'lib/tikilib.php';
set_error_handler("tiki_error_handling", error_reporting());
require_once('lib/init/smarty.php');
require_once('installer/installlib.php');
include_once('lib/setup/twversion.class.php');

$dbTiki = false;
$commands = [];

// tra() should not use $tikilib because this lib is not available in every steps of the installer
//  and because we want to be sure that translations of the installer are the original ones, even for an upgrade
$prefs['lang_use_db'] = 'n';

// Which step of the installer
if (empty($_POST['install_step'])) {
    $install_step = '0';

    if (isset($_REQUEST['setdbversion'])) {
        // Sets dbversion_tiki when installing the WebDeploy package
        $db = fopen('db/' . $tikidomainslash . 'local.php', 'a');
        require_once 'lib/setup/twversion.class.php';
        $TWV = new TWVersion();
        fwrite($db, "\n\$dbversion_tiki='" . $TWV->getBaseVersion() . "';\n");
        fclose($db);
    }
} else {
    $install_step = $_POST['install_step'];

    if ($install_step == 3) {   // clear caches after system requirements page
        $cachelib = TikiLib::lib('cache');
        $cachelib->empty_cache();
    }
}

// define the language to use, either from user-setting or default
if (! empty($_POST['lang'])) {
    $language = $prefs['site_language'] = $prefs['language'] = $_POST['lang'];
} else {
    $language = $prefs['site_language'] = $prefs['language'] = 'en';
}
include_once('lib/init/tra.php');


// -----------------------------------------------------------------------------
// end of functions .. now starts the processing

// If using multiple Tikis
if (is_file('db/virtuals.inc')) {
    $virtuals = array_map('trim', file('db/virtuals.inc'));
    foreach ($virtuals as $v) {
        if ($v) {
            if (is_file("db/$v/local.php") && is_readable("db/$v/local.php")) {
                $virt[$v] = 'y';
            } else {
                $virt[$v] = 'n';
            }
        }
    }
} else {
    $virt = false;
    $virtuals = false;
}

$serverFilter = new DeclFilter();
if (
    ( isset($prefs['tiki_allow_trust_input']) && $prefs['tiki_allow_trust_input'] ) !== 'y'
    || $tiki_p_trust_input != 'y'
) {
    $serverFilter->addStaticKeyFilters(
        [
            'TIKI_VIRTUAL' => 'striptags',
            'SERVER_NAME' => 'striptags',
            'HTTP_HOST' => 'striptags',
        ]
    );
}
$jitServer = new JitFilter($_SERVER);
$_SERVER = $serverFilter->filter($_SERVER);

$multi = '';
// If using multiple Tiki installations (MultiTiki)
if ($virtuals) {
    if (isset($_POST['multi']) && in_array($_POST['multi'], $virtuals)) {
        $multi = $_POST['multi'];
    } else {
        if (isset($_SERVER['TIKI_VIRTUAL']) && is_file('db/' . $_SERVER['TIKI_VIRTUAL'] . '/local.php')) {
            $multi = $_SERVER['TIKI_VIRTUAL'];
        } elseif (isset($_SERVER['SERVER_NAME']) && is_file('db/' . $_SERVER['SERVER_NAME'] . '/local.php')) {
            $multi = $_SERVER['SERVER_NAME'];
        } elseif (isset($_SERVER['HTTP_HOST']) && is_file('db/' . $_SERVER['HTTP_HOST'] . '/local.php')) {
            $multi = $_SERVER['HTTP_HOST'];
        }
    }
}
if (! empty($multi)) {
    $local = "db/$multi/local.php";
    $preconfiguration = "db/$multi/preconfiguration.php";
} else {
    $local = "db/local.php";
    $preconfiguration = 'db/preconfiguration.php';
}

$tikidomain = $multi;
$tikidomainslash = (! empty($tikidomain) ? $tikidomain . '/' : '');

$title = tra('Tiki Installer');

$_SESSION["install-logged-$multi"] = 'y';

// Init smarty
global $tikidomain;

try {
    $smarty = TikiLib::lib('smarty');
} catch (Exception $e) {
    $errors .= '<strong>' . $e->getMessage() . '</strong><br>' . tr('Check') . '<a href="tiki-check.php">tiki-check.php</a>' . tr('to ensure your system is ready for Tiki or refer to') . '<a href="https://doc.tiki.org/Requirements">https://doc.tiki.org/Requirements</a>' . tr('for more information.');
    error_and_exit();
}

$smarty->assign('mid', 'tiki-install.tpl');
$smarty->assign('virt', isset($virt) ? $virt : null);
$smarty->assign('multi', isset($multi) ? $multi : null);
$smarty->assign('lang', $language);
$smarty->assign('default_server_domain_name', isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME']);

// Try to set a longer execution time for the installer
@ini_set('max_execution_time', '0');
$max_execution_time = ini_get('max_execution_time');
$smarty->assign('max_exec_set_failed', 'n');
if ($max_execution_time != 0) {
    $smarty->assign('max_exec_set_failed', 'y');
}

// Tiki Database schema version
$TWV = new TWVersion();
$tikiVersionShort = preg_replace('/^(\d+)\..*$/', '\1', $TWV->version);
$smarty->assign('tiki_version_name', preg_replace('/^(\d+\.\d+)([^\d])/', '\1 \2', $TWV->version));
$smarty->assign('tiki_version_short', $tikiVersionShort);

check_session_save_path();

get_webserver_uid();

$errors .= create_dirs($multi);

if ($errors) {
    error_and_exit();
}

// Second check try to connect to the database
// if no local.php => no con
// if local then build dsn and try to connect
//   then get con or nocon

//adodb settings

if (! defined('ADODB_FORCE_NULLS')) {
    define('ADODB_FORCE_NULLS', 1);
}

if (! defined('ADODB_ASSOC_CASE')) {
    define('ADODB_ASSOC_CASE', 2);
}

if (! defined('ADODB_CASE_ASSOC')) { // typo in adodb's driver for sybase? // so do we even need this without sybase? What's this?
    define('ADODB_CASE_ASSOC', 2);
}

require_once('lib/tikilib.php');

// Get list of available languages
$langLib = TikiLib::lib('language');
$languages = $langLib->list_languages(false, null, true);
$smarty->assignByRef("languages", $languages);

$logslib = TikiLib::lib('logs');

$client_charset = '';

// next block checks if there is a local.php and if we can connect through this.
// sets $dbcon to false if there is no valid local.php
$dbcon = (bool) TikiDb::get();
$installer = null;
if (file_exists($local)) {
    // include the file to get the variables
    $default_api_tiki = $api_tiki;
    $api_tiki = '';
    include $local;
    if (! $client_charset_forced = isset($client_charset)) {
        $client_charset = '';
    }
    $previousDbApi = $api_tiki;
    if (empty($api_tiki)) {
        $api_tiki_forced = false;
        $api_tiki = $default_api_tiki;
        if (! empty($dbversion_tiki) && $dbversion_tiki[0] < 4) {
            $previousDbApi = 'adodb'; // AdoDB was the default DB abstraction layer before 4.0
        }
    } else {
        $api_tiki_forced = true;
    }

    unset($default_api_tiki);

    // In case of replication, ignore it during installer.
    unset($shadow_dbs, $shadow_user, $shadow_pass, $shadow_host);
    if ($dbversion_tiki == '1.10') {
        $dbversion_tiki = '2.0';
    }

    $dbcon = false;
    $smarty->assign('resetdb', 'n');
    if ($dbcon = initTikiDB($api_tiki, $host_tiki, $user_tiki, $pass_tiki, $dbs_tiki, $client_charset, $dbTiki)) {
        $smarty->assign('resetdb', isset($_POST['reset']) ? 'y' : 'n');
        $installer = Installer::getInstance();
        if (! $client_charset_forced) {
            write_local_php($host_tiki, $user_tiki, $pass_tiki, $dbs_tiki, $client_charset, ($api_tiki_forced ? $api_tiki : ''), $dbversion_tiki);
            $logslib->add_log('install', 'database credentials written to file with hostname=' . $host_tiki
                . '; dbname=' . $dbs_tiki . '; dbuser=' . $user_tiki);
        }
    }
} elseif ($dbcon) {
    $installer = Installer::getInstance();
    TikiDb::get()->setErrorHandler(new \Tiki\Installer\InstallerDatabaseErrorHandler());
} else {
    // If there is no local.php we check if there is a db/preconfiguration.php preconfiguration file with database connection values which we can prefill the installer with
    if (file_exists($preconfiguration)) {
        include $preconfiguration;
        if (isset($host_tiki_preconfig)) {
            $smarty->assign('preconfighost', $host_tiki_preconfig);
        }
        if (isset($user_tiki_preconfig)) {
            $smarty->assign('preconfiguser', $user_tiki_preconfig);
        }
        if (isset($pass_tiki_preconfig)) {
            $smarty->assign('preconfigpass', $pass_tiki_preconfig);
        }
        if (isset($dbs_tiki_preconfig)) {
            $smarty->assign('preconfigname', $dbs_tiki_preconfig);
        }
    }
}

if ($dbcon) {
    $admin_acc = has_admin($api_tiki);
}

if ($admin_acc == 'n') {
    $smarty->assign('noadmin', 'y');
} else {
    $smarty->assign('noadmin', 'n');
}


// We won't update database info unless we can't connect to the database.
// We won't reset the db connection if there is an admin account set
// and the admin is not logged
if (
    (
        ! $dbcon
        || (
            isset($_POST['resetdb'])
            && $_POST['resetdb'] == 'y'
            && (
                $admin_acc == 'n'
                || (isset($_SESSION["install-logged-$multi"])
                && $_SESSION["install-logged-$multi"] == 'y')
            )
        )
    ) && isset($_POST['dbinfo'])
) {
    if (! empty($_POST['user']) && strlen($_POST['user']) > 80) {
        $dbcon = false;
        Feedback::error(tra('Invalid database user.'));
    } elseif (empty($_POST['name'])) {
        $dbcon = false;
        Feedback::error(tra('No database name specified'));
    } else {
        if (isset($_POST['force_utf8'])) {
            $client_charset = 'utf8mb4';
        } else {
            $client_charset = '';
        }

        if (
            ! empty($_POST['create_new_user'])
            && ! empty($_POST['root_user'])
            && ! empty($_POST['root_pass'])
        ) {
            $dbcon = initTikiDB(
                $api_tiki,
                $_POST['host'],
                $_POST['root_user'],
                $_POST['root_pass'],
                $_POST['name'],
                $client_charset,
                $dbTiki
            );
        } else {
            $dbcon = initTikiDB(
                $api_tiki,
                $_POST['host'],
                $_POST['user'],
                $_POST['pass'],
                $_POST['name'],
                $client_charset,
                $dbTiki
            );
        }

        if ($dbcon) {
            if (
                ! empty($_POST['create_new_user'])
                && ! empty($_POST['root_user'])
                && ! empty($_POST['root_pass'])
            ) {
                createTikiDBUser($dbTiki, $_POST['host'], $_POST['user'], $_POST['pass'], $_POST['name']);
            }
            write_local_php($_POST['host'], $_POST['user'], $_POST['pass'], $_POST['name'], $client_charset);

            // TODO: it is not possible to add_log if we don't have tables created
            //$logslib->add_log('install', 'database credentials updated with hostname=' . $_POST['host'] . '; dbname='
            //  . $_POST['name'] .'; dbuser=' . $_POST['user']);

            include $local;
            // In case of replication, ignore it during installer.
            unset($shadow_dbs, $shadow_user, $shadow_pass, $shadow_host);
            $installer = Installer::getInstance();
        }
    }
}
// Mark what db type to use if selected
if (isset($_POST['useInnoDB'])) {
    if ($installer != null) {
        if ((int)$_POST['useInnoDB'] > 0) {
            $installer->useInnoDB = true;
        } else {
            $installer->useInnoDB = false;
        }
    }
}

if ($dbcon) {
    $smarty->assign('dbcon', 'y');
    $smarty->assign('dbname', isset($dbs_tiki) ? $dbs_tiki : null);
} else {
    $smarty->assign('dbcon', 'n');
}

// Some initializations to avoid PHP error messages
$smarty->assign('tikidb_created', false);
$smarty->assign('tikidb_is20', false);

if ($dbcon) {
    $has_tiki_db = has_tiki_db();
    $smarty->assign('tikidb_created', $has_tiki_db);
    $oldPerms = $installer->getOne('SELECT COUNT(*) FROM `users_permissions` WHERE `permDesc` = \'Can view categorized items\'');
    $smarty->assign('tikidb_oldPerms', $oldPerms);

    if ($install_step == '6' && $has_tiki_db) {
        if (isset($_POST['install_type']) && $_POST['install_type'] === 'scratch') {
            require_once('lib/setup/prefs.php');
            // fix some prefs thwt get reset here
            $prefs['language'] = $language;
            $prefs['javascript_enabled'] = null;    // seems the check is for $prefs['javascript_enabled'] == 'n'
        }
        update_preferences($prefs);
        $smarty->assign('admin_email', get_admin_email());
        $smarty->assign('upgradefix', (empty($dbversion_tiki) || $dbversion_tiki[0] < 4) ? 'y' : 'n');
    }
    $smarty->assign('tikidb_is20', has_tiki_db_20());
}

if (isset($_POST['restart'])) {
    $_SESSION["install-logged-$multi"] = '';
}

$smarty->assign('admin_acc', $admin_acc);

// If no admin account then we are logged
if ($admin_acc == 'n') {
    $_SESSION["install-logged-$multi"] = 'y';
}

$smarty->assign('dbdone', 'n');
$smarty->assign('logged', $logged);
$smarty->assign('installer', $installer);
// Installation steps
if (
    $dbcon
    && isset($_SESSION["install-logged-$multi"])
    && $_SESSION["install-logged-$multi"] == 'y'
) {
    $smarty->assign('logged', 'y');

    if (isset($_POST['scratch'])) {
        $installer->attach(new ProgressBar());

        $installer->cleanInstall();
        if ($has_tiki_db) {
            $logmsg = 'database "' . $dbs_tiki . '" destroyed and reinstalled';
        } else {
            $logmsg = 'clean install of new database "' . $dbs_tiki . '"';
        }
        $logslib->add_log('install', $logmsg);
        $smarty->assign('dbdone', 'y');
        $install_type = 'scratch';
        require_once 'lib/tikilib.php';
        $tikilib = new TikiLib();
        $userlib = TikiLib::lib('user');
        $tikidate = TikiLib::lib('tikidate');
    }

    if (isset($_POST['update'])) {
        $installer->update();
        $logslib->add_log('install', 'database "' . $dbs_tiki . '" upgraded to latest version');
        $smarty->assign('dbdone', 'y');
        $install_type = 'update';
    }

    // Try to activate Apache htaccess file by making a symlink or copying _htaccess into .htaccess
    // Do nothing (but warn the user to do it manually) if:
    //   - there is no  _htaccess file,
    //   - there is already an existing .htaccess (that is not necessarily the one that comes from Tiki),
    //   - the copy does not work (e.g. due to filesystem permissions)
    //
    // TODO: Equivalent for IIS


    if ($install_step == '6' || $install_step == '7') {
        if (strpos($_SERVER['SERVER_SOFTWARE'], 'Apache') !== false) {
            if (! file_exists('.htaccess')) {
                if (! isset($_REQUEST['htaccess_process'])) {
                    $htaccess_options = ['auto' => tra('Automatic')];
                    if (function_exists('symlink')) {
                        $htaccess_options['symlink'] = tra('Make a symlink');
                    }
                    if (function_exists('copy')) {
                        $htaccess_options['copy'] = tra('Make a copy');
                    }
                    $htaccess_options[''] = tra('Do nothing');
                    $smarty->assign('htaccess_options', $htaccess_options);
                } else {
                    $htaccess_feedback = '';

                    if ($_REQUEST['htaccess_process'] === 'auto') {
                        if (function_exists('symlink') && symlink('_htaccess', '.htaccess')) {
                            $htaccess_feedback = tra('symlink created');
                        } else {
                            copy('_htaccess', '.htaccess');
                            $htaccess_feedback = tra('copy created');
                        }
                    } elseif ($_REQUEST['htaccess_process'] === 'symlink') {
                        @symlink('_htaccess', '.htaccess');
                        $htaccess_feedback = tra('symlink created');
                    } elseif ($_REQUEST['htaccess_process'] === 'copy') {
                        @copy('_htaccess', '.htaccess');
                        $htaccess_feedback = tra('copy created');
                    }
                    if (file_exists('.htaccess')) {
                        $smarty->assign('htaccess_feedback', $htaccess_feedback);
                    } else {
                        $smarty->assign('htaccess_error', 'y');
                    }
                }
            } else {
                // TODO: Perform up-to-date check as in the SEFURL admin panel
            }
        }
    }
}

if (! isset($install_type)) {
    if (isset($_POST['install_type'])) {
        $install_type = $_POST['install_type'];
    } else {
        $install_type = '';
    }
}

if ($install_step == '9') {
    if (! isset($_POST['nolockenter'])) {
        touch('db/' . $tikidomainslash . 'lock');
    }

    $userlib = TikiLib::lib('user');
    $cachelib = TikiLib::lib('cache');
    if (session_id()) {
        session_destroy();
    }
    include_once 'tiki-setup.php';
    TikiLib::lib('cache')->empty_cache();
    if ($install_type == 'scratch') {
        initialize_prefs(true);
        TikiLib::lib('unifiedsearch')->rebuild();
        $u = 'tiki-change_password.php?user=admin&oldpass=admin&newuser=y';
    } else {
        $u = '';
    }
    if (empty($_REQUEST['multi'])) {
        $userlib->user_logout($user, false, $u);    // logs out then redirects to home page or $u
    } else {
        $access->redirect('http://' . $_REQUEST['multi'] . $tikiroot . $u);     // send to the selected multitiki
    }
    exit;
}

$smarty->assign('metatag_robots', 'NOINDEX, NOFOLLOW');

$email_test_tw = 'mailtest@tiki.org';
$smarty->assign('email_test_tw', $email_test_tw);

//  Sytem requirements test.
if ($install_step == '2') {
    $smarty->assign('mail_test_performed', 'n');
    if (isset($_POST['perform_mail_test']) && $_POST['perform_mail_test'] == 'y') {
        $email_test_to = $email_test_tw;
        $email_test_headers = '';
        $email_test_ready = true;

        if (! empty($_POST['email_test_to'])) {
            $email_test_to = $_POST['email_test_to'];

            if (isset($_POST['email_test_cc']) && $_POST['email_test_cc'] == '1') {
                $email_test_headers .= "Cc: $email_test_tw\n";
            }

            // check email address format
            $validator = new Laminas\Validator\EmailAddress();
            if (! $validator->isValid($email_test_to)) {
                $smarty->assign('email_test_err', tra('Email address not valid, test mail not sent'));
                $email_test_ready = false;
            }
        } else {    // no email supplied, check copy checkbox
            if (! isset($_POST['email_test_cc']) || $_POST['email_test_cc'] != '1') {
                $smarty->assign('email_test_err', tra('Email address empty and "copy" checkbox not set, test mail not sent'));
                $email_test_ready = false;
            }
        }
        $smarty->assign('email_test_to', $email_test_to);

        if ($email_test_ready) {    // so send the mail
            $email_test_headers .= 'From: noreply@tiki.org' . "\n"; // needs a valid sender
            $email_test_headers .= 'Reply-to: ' . $email_test_to . "\n";
            $email_test_headers .= "Content-type: text/plain; charset=utf-8\n";
            $email_test_headers .= 'X-Mailer: Tiki/' . $TWV->version . ' - PHP/' . PHP_VERSION . "\n";
            $email_test_subject = tr('Test mail from Tiki installer %0', $TWV->version);
            $email_test_body = tra("Congratulations!\n\nYour server can send emails.\n\n");
            $email_test_body .= "\t" . tra('Tiki version:') . ' ' . $TWV->version . "\n";
            $email_test_body .= "\t" . tra('PHP version:') . ' ' . PHP_VERSION . "\n";
            $email_test_body .= "\t" . tra('Server:') . ' ' . (empty($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_ADDR'] : $_SERVER['SERVER_NAME']) . "\n";
            $email_test_body .= "\t" . tra('Sent:') . ' ' . date(DATE_RFC822) . "\n";

            $sentmail = mail($email_test_to, $email_test_subject, $email_test_body, $email_test_headers);
            if ($sentmail) {
                $mail_test = 'y';
            } else {
                $mail_test = 'n';
            }
            $smarty->assign('mail_test', $mail_test);
            $smarty->assign('mail_test_performed', 'y');
        }
    }

    // copy of most of $tikilib->return_bytes() not available at this stage
    $memory_limit = trim(ini_get('memory_limit'));
    $last = strtolower($memory_limit[strlen($memory_limit) - 1]);
    switch ($last) {
        // The 'G' modifier is available since PHP 5.1.0
        case 'g':
            $memory_limit *= 1024;
        // The 'm' modifier
        case 'm':
            $memory_limit *= 1024;
        // The 'k' modifier
        case 'k':
            $memory_limit *= 1024;
    }
    $smarty->assign('php_memory_limit', (int)$memory_limit);

    $phpPropertiesMissing = isMissingPHPRequirements($tikiVersionShort);
    $smarty->assign('php_properties_missing', $phpPropertiesMissing);

    if ((extension_loaded('gd') && function_exists('gd_info'))) {
        $gd_test = 'y';
        $gd_info = gd_info();
        $smarty->assign('gd_info', $gd_info['GD Version']);

        $im = @imagecreate(110, 20);
        if ($im) {
                $smarty->assign('sample_image', 'y');
                imagedestroy($im);
        } else {
                $smarty->assign('sample_image', 'n');
        }
    } else {
        $gd_test = 'n';
    }
    $smarty->assign('gd_test', $gd_test);
} elseif ($install_step == 6 && ! empty($_POST['validPatches'])) {
    foreach ($_POST['validPatches'] as $patch) {
        Patch::$list[$patch]->record();
    }
}

unset($TWV);

// write general settings
if (isset($_POST['general_settings']) && $_POST['general_settings'] == 'y') {
    $switch_ssl_mode = ( isset($_POST['feature_switch_ssl_mode']) && $_POST['feature_switch_ssl_mode'] == 'on' )
        ? 'y' : 'n';
    $show_stay_in_ssl_mode = ( isset($_POST['feature_show_stay_in_ssl_mode'])
        && $_POST['feature_show_stay_in_ssl_mode'] == 'on' ) ? 'y' : 'n';

    $installer->query(
        "DELETE FROM `tiki_preferences` WHERE `name` IN " .
        "('browsertitle', 'server_domain', 'sender_email', 'https_login', 'https_port', " .
        "'feature_switch_ssl_mode', 'feature_show_stay_in_ssl_mode', 'language'," .
        "'use_proxy', 'proxy_host', 'proxy_port', 'proxy_user', 'proxy_pass'," .
        "'error_reporting_level', 'error_reporting_adminonly', 'smarty_notice_reporting', 'log_tpl')"
    );

    $query = "INSERT INTO `tiki_preferences` (`name`, `value`) VALUES"
        . " ('browsertitle', ?),"
        . " ('server_domain', ?),"
        . " ('sender_email', ?),"
        . " ('https_login', ?),"
        . " ('https_port', ?),"
        . " ('error_reporting_level', ?),"
        . " ('use_proxy', '" . (isset($_POST['use_proxy'])
            && $_POST['use_proxy'] == 'on' ? 'y' : 'n') . "'),"
        . " ('proxy_host', '" . $_POST['proxy_host'] . "'),"
        . " ('proxy_port', '" . $_POST['proxy_port'] . "'),"
        . " ('proxy_user', '" . $_POST['proxy_user'] . "'),"
        . " ('proxy_pass', '" . $_POST['proxy_pass'] . "'),"
        . " ('error_reporting_adminonly', '" . (isset($_POST['error_reporting_adminonly'])
            && $_POST['error_reporting_adminonly'] == 'on' ? 'y' : 'n') . "'),"
        . " ('smarty_notice_reporting', '" . (isset($_POST['smarty_notice_reporting'])
            && $_POST['smarty_notice_reporting'] == 'on' ? 'y' : 'n') . "'),"
        . " ('log_tpl', '" . (isset($_POST['log_tpl']) && $_POST['log_tpl'] == 'on' ? 'y' : 'n') . "'),"
        . " ('feature_switch_ssl_mode', '$switch_ssl_mode'),"
        . " ('feature_show_stay_in_ssl_mode', '$show_stay_in_ssl_mode'),"
        . " ('language', ?)";


    $installer->query($query, [$_POST['browsertitle'], $_POST['server_domain'], $_POST['sender_email'], $_POST['https_login'],
        $_POST['https_port'], $_POST['error_reporting_level'], $language]);
    $installer->query("UPDATE `users_users` SET `email` = ? WHERE `users_users`.`userId`=1", [$_POST['admin_email']]);
    $logslib->add_log('install', 'updated preferences for browser title, sender email, https and SSL, '
        . 'error reporting, etc.');

    if (isset($_POST['admin_account']) && ! empty($_POST['admin_account'])) {
        fix_admin_account($_POST['admin_account']);
        $logslib->add_log('install', 'changed admin account user to ' . $_POST['admin_account']);
    }
    if (isset($_POST['fix_disable_accounts']) && $_POST['fix_disable_accounts'] == 'on') {
        $ret = fix_disable_accounts();
        $logslib->add_log('install', 'fixed disabled user accounts');
    }
}


$headerlib = TikiLib::lib('header');
$headerlib->add_js("var tiki_cookie_jar=new Array();");
$headerlib->add_cssfile('vendor_bundled/vendor/twbs/bootstrap/dist/css/bootstrap.css');
$headerlib->add_cssfile('vendor_bundled/vendor/bower-asset/fontawesome/css/all.css');
$headerlib->add_cssfile('themes/base_files/css/tiki_base.css');
$headerlib->add_jsfile('lib/tiki-js.js');
$headerlib->add_jsfile_dependency("vendor_bundled/vendor/components/jquery/jquery.min.js");
$headerlib->add_jsfile_dependency("vendor_bundled/vendor/components/jqueryui/jquery-ui.js");
$headerlib->add_jsfile('lib/jquery_tiki/tiki-jquery.js');
    $js = '
// JS Object to hold prefs for jq
var jqueryTiki = new Object();
jqueryTiki.ui = false;
jqueryTiki.ui_theme = "";
jqueryTiki.tooltips = false;
jqueryTiki.autocomplete = false;
jqueryTiki.reflection = false;
jqueryTiki.tablesorter = false;
jqueryTiki.colorbox = false;
jqueryTiki.cboxCurrent = "{current} / {total}";
jqueryTiki.carousel = false;

jqueryTiki.effect = "";
jqueryTiki.effect_direction = "";
jqueryTiki.effect_speed = 400;
jqueryTiki.effect_tabs = "";
jqueryTiki.effect_tabs_direction = "";
jqueryTiki.effect_tabs_speed = 400;
';
$headerlib->add_js($js, 100);

$iconset = TikiLib::lib('iconset')->getIconsetForTheme('default', '');

$smarty->assignByRef('headerlib', $headerlib);

$smarty->assign('install_step', $install_step);
$smarty->assign('install_type', $install_type);
$smarty->assignByRef('prefs', $prefs);
$smarty->assign('detected_https', isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == 'on');

$client_charset = '';

if (file_exists($local)) {
    include $local;
}

$smarty->assign('client_charset_in_file', $client_charset);

if (isset($_POST['convert_to_utf8'])) {
    convert_database_to_utf8($dbs_tiki);
}

$smarty->assign('double_encode_fix_attempted', 'n');
if (isset($_POST['fix_double_encoding']) && ! empty($_POST['previous_encoding'])) {
    fix_double_encoding($dbs_tiki, $_POST['previous_encoding']);
    $smarty->assign('double_encode_fix_attempted', 'y');
}

if ($install_step == '4') {
    // Show the innodb option in the (re)install section if InnoDB is present
    if (isset($installer) and $installer->hasInnoDB()) {
        $smarty->assign('hasInnoDB', true);
    } else {
        $smarty->assign('hasInnoDB', false);
    }

    $value = '';
    if (($db = TikiDB::get()) && ($result = $db->fetchAll('show variables like "character_set_database"'))) {
        $res = reset($result);
        $variable = array_shift($res);
        $value = array_shift($res);
    }
    $smarty->assign('database_charset', $value);
}

if (((isset($value) && $value == 'utf8mb4') || $install_step == '7') && $db = TikiDB::get()) {
    $result = $db->fetchAll(
        'SELECT TABLE_COLLATION FROM INFORMATION_SCHEMA.TABLES '
        . ' WHERE TABLE_SCHEMA = ? AND TABLE_COLLATION NOT LIKE "utf8mb4%" '
        . ' AND NOT (TABLE_NAME LIKE "index_%" OR TABLE_NAME LIKE "zzz_unused_%")', // Ignore tables that are not converted - but are generated
        $dbs_tiki
    );
    if (! empty($result)) {
        $smarty->assign('legacy_collation', $result[0]['TABLE_COLLATION']);
    }
}

if ($install_step == '6') {
    $smarty->assign('disableAccounts', list_disable_accounts());
}

$smarty->loadPlugin('smarty_modifier_ternary');

$mid_data = $smarty->fetch('tiki-install.tpl');
$smarty->assign('mid_data', $mid_data);

$smarty->assign('title', $title);
$smarty->assign('phpErrors', $phpErrors);
$smarty->display("tiki-install_screens.tpl");

/**
 * Check tiki php minimum requirements are missing
 *
 * @param int $tikiVersionShort
 *
 * @return array
 */
function isMissingPHPRequirements(int $tikiVersionShort): array
{
    $missing = [];

    $phpCompat = [
        // We use < to compare the PHP Compat
        // Next unsupported version => tiki supported versions
        '5.6' => [12],
        '7.0' => [15, 18],
        '7.1' => [18],
        '7.2' => [18, 19, 20],
        '7.3' => [19, 20, 21],
        '7.4' => [21],
    ];

    foreach ($phpCompat as $maxPHPVersion => $tikiVersions) {
        $phpVersion = preg_replace('/\.\d+$/', '', PHP_VERSION);
        if (
            version_compare($phpVersion, $maxPHPVersion, '<') &&
            ! in_array($tikiVersionShort, $tikiVersions)
        ) {
            $missing[] = tr('PHP version %0 not compatible with Tiki %1', PHP_VERSION, $tikiVersionShort);
            break;
        }
    }

    if (! function_exists('ini_set')) {
        $missing[] = tr('Function %s not found', 'init_set');
    }
    if (
        ! extension_loaded('pdo_mysql') &&
        ! extension_loaded('mysqli') &&
        ! extension_loaded('mysql')
    ) {
        $missing[] = 'Module pdo_mysql, mysqli or mysql not loaded';
    }

    if (strtolower(ini_get('default_charset')) !== 'utf-8') {
        $missing[] = tr('%0 is not %1', 'default_charset', 'UTF-8');
    }

    $modules = ['intl', 'mbstring', 'ctype', 'libxml', 'dom', 'curl', 'json', 'iconv'];

    foreach ($modules as $module) {
        if (! extension_loaded($module)) {
            $missing[] = tr('Module %s is not loaded', $module);
        }
    }

    $eval = eval('return 42;');
    if ($eval !== 42) {
        $missing[] = tr('Function %0 not found', 'eval');
    }

    return $missing;
}
