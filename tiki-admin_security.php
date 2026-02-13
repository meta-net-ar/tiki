<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

require_once('tiki-setup.php');
use Tiki\Sections;
$section = Sections::SECTION_ADMIN;
Sections::setCurrentSection($section);
$access->check_permission('tiki_p_admin');

// tikiwiki preferences check
// do we need to get the preferences or are they already loaded?
$tikisettings = [];
if ($prefs['feature_file_galleries'] == 'y' && ! empty($prefs['fgal_use_dir']) && ! str_starts_with($prefs['fgal_use_dir'], '/')) { // todo: check if absolute path is in tiki root
    $tikisettings['fgal_use_dir'] = [
        'risk' => tra('unsafe') ,
        'setting' => $prefs['fgal_use_dir'],
        'message' => tra('The Path to store files in the filegallery should be outside the tiki root directory')
    ];
}
if ($prefs['wikiplugin_snarf'] == 'y') {
    $tikisettings['wikiplugin_snarf'] = [
        'risk' => tra('unsafe') ,
        'setting' => tra('Enabled') ,
        'message' => tra('The "Snarf Wikiplugin" is activated. It can be used by wiki editors to include pages from the local network and via regex replacement create any HTML.')
    ];
}
if ($prefs['wikiplugin_regex'] == 'y') {
    $tikisettings['wikiplugin_regex'] = [
        'risk' => tra('unsafe') ,
        'setting' => tra('Enabled') ,
        'message' => tra('The "Regex Wikiplugin" is activated. It can be used by wiki editors to create any HTML via regex replacement.')
    ];
}
if ($prefs['wikiplugin_lsdir'] == 'y') {
    $tikisettings['wikiplugin_lsdir'] = [
        'risk' => tra('unsafe') ,
        'setting' => tra('Enabled') ,
        'message' => tra('The "Lsdir Wikiplugin" is activated. It can be used by wiki editors to view the contents of any directory.')
    ];
}
if ($prefs['wikiplugin_bloglist'] == 'y') {
    $tikisettings['wikiplugin_bloglist'] = [
        'risk' => tra('unsafe') ,
        'setting' => tra('Enabled') ,
        'message' => tra('The "Bloglist Wikiplugin" is activated. It can be used by wiki editors to disclose private blog posts.')
    ];
}
if ($prefs['wikiplugin_iframe'] == 'y') {
    $tikisettings['wikiplugin_iframe'] = [
        'risk' => tra('unsafe') ,
        'setting' => tra('Enabled') ,
        'message' => tra('The "iframe Wikiplugin" is activated. It can be used by wiki editors for cross site scripting attacks.')
    ];
}
if ($prefs['wikiplugin_js'] == 'y') {
    $tikisettings['wikiplugin_js'] = [
        'risk' => tra('unsafe') ,
        'setting' => tra('Enabled') ,
        'message' => tra('The "js Wikiplugin" is activated. It can be used by wiki editors to use JavaScript, which can be used to do all kind of nasty things like cross site scripting attacks, etc.')
    ];
}
if ($prefs['wikiplugin_jq'] == 'y') {
    $tikisettings['wikiplugin_jq'] = [
        'risk' => tra('unsafe') ,
        'setting' => tra('Enabled') ,
        'message' => tra('The "JQ Wikiplugin" is activated. It can be used by wiki editors to use JavaScript, which can be used to do all kind of nasty things like cross site scripting attacks, etc.')
    ];
}
if ($prefs['wikiplugin_redirect'] == 'y') {
    $tikisettings['wikiplugin_redirect'] = [
        'risk' => tra('unsafe') ,
        'setting' => tra('Enabled') ,
        'message' => tra('The "Redirect Wikiplugin" is activated. It can be used by wiki editors for cross site scripting attacks.')
    ];
}
if ($prefs['wikiplugin_module'] == 'y') {
    $tikisettings['wikiplugin_module'] = [
        'risk' => tra('unsafe') ,
        'setting' => tra('Enabled') ,
        'message' => tra('The "Module Wikiplugin" is activated. It can be used by wiki editors to add modules which permit to access information (see module list).')
    ];
}
if ($prefs['wikiplugin_userlist'] == 'y') {
    $tikisettings['wikiplugin_userlist'] = [
        'risk' => tra('unsafe') ,
        'setting' => tra('Enabled') ,
        'message' => tra('The "Userlist Wikiplugin" is activated. It can be used by wiki editors to display the list of users.')
    ];
}
if ($prefs['wikiplugin_usercount'] == 'y') {
    $tikisettings['wikiplugin_usercount'] = [
        'risk' => tra('unsafe') ,
        'setting' => tra('Enabled') ,
        'message' => tra('The "Usercount Wikiplugin" is activated. It can be used by wiki editors to display a count of the number of users.')
    ];
}
if ($prefs['wikiplugin_sql'] == 'y') {
    $tikisettings['wikiplugin_sql'] = [
        'risk' => tra('unsafe') ,
        'setting' => tra('Enabled') ,
        'message' => tra('The "SQL Wikiplugin" is activated. It can be used by wiki editors to execute SQL commands.')
    ];
}
if ($prefs['https_login'] != 'required') {
    $tikisettings['https_login'] = [
        'risk' => tra('risky') ,
        'setting' => ucfirst($prefs['https_login']),
        'message' => tra('To the extent secure logins are not required, data transmitted between the browser and server is not private.')
    ];
}
if ($prefs['scheduler_shell_command'] == 'y') {
    $tikisettings['scheduler_shell_command'] = [
        'risk' => tra('risky') ,
        'setting' => tra('Enabled'),
        'message' => tra('The "Scheduler shell command" is activated. It can be used by Tiki administrators to execute shell commands which can lead to security risks.')
    ];
}

$risky_message = tra('Enabling this preference is potentially dangerous! Only Tiki administrators should be allowed to enable and use this feature.');

if ($prefs['feature_blog_heading'] == 'y') {
    $tikisettings['feature_blog_heading'] = [
        'risk' => tra('risky') ,
        'setting' => tra('Enabled'),
        'message' => $risky_message
    ];
}
if ($prefs['feature_custom_html_head_content'] == 'y') {
    $tikisettings['feature_custom_html_head_content'] = [
        'risk' => tra('risky') ,
        'setting' => tra('Enabled'),
        'message' => $risky_message
    ];
}
if ($prefs['main_shadow_start'] == 'y') {
    $tikisettings['main_shadow_start'] = [
        'risk' => tra('risky') ,
        'setting' => tra('Enabled'),
        'message' => $risky_message
    ];
}
if ($prefs['header_shadow_start'] == 'y') {
    $tikisettings['header_shadow_start'] = [
        'risk' => tra('risky') ,
        'setting' => tra('Enabled'),
        'message' => $risky_message
    ];
}
if ($prefs['header_shadow_end'] == 'y') {
    $tikisettings['header_shadow_end'] = [
        'risk' => tra('risky') ,
        'setting' => tra('Enabled'),
        'message' => $risky_message
    ];
}
if ($prefs['center_shadow_start'] == 'y') {
    $tikisettings['center_shadow_start'] = [
        'risk' => tra('risky') ,
        'setting' => tra('Enabled'),
        'message' => $risky_message
    ];
}
if ($prefs['center_shadow_end'] == 'y') {
    $tikisettings['center_shadow_end'] = [
        'risk' => tra('risky') ,
        'setting' => tra('Enabled'),
        'message' => $risky_message
    ];
}
if ($prefs['footer_shadow_end'] == 'y') {
    $tikisettings['footer_shadow_end'] = [
        'risk' => tra('risky') ,
        'setting' => tra('Enabled'),
        'message' => $risky_message
    ];
}
if ($prefs['feature_endbody_code'] == 'y') {
    $tikisettings['feature_endbody_code'] = [
        'risk' => tra('risky') ,
        'setting' => tra('Enabled'),
        'message' => $risky_message
    ];
}
if ($prefs['smarty_enable_string_eval'] == 'y') {
    $tikisettings['smarty_enable_string_eval'] = [
        'risk' => tra('risky') ,
        'setting' => tra('Enabled'),
        'message' => $risky_message
    ];
}


function rebuild_security_database_logic()
{
    @ini_set('memory_limit', '512M');
    @set_time_limit(0);

    global $tikilib;

    require_once('lib/setup/twversion.class.php');
    $version = new TWVersion();
    $current_version = $version->version;

    $tikilib->query("TRUNCATE TABLE `tiki_secdb`");

    $query = "INSERT IGNORE INTO `tiki_secdb` (`filename`, `md5_value`, `tiki_version`, `severity`) VALUES (?, ?, ?, 0)";
    $allowed_extensions = ['php', 'js', 'tpl', 'css', 'sql'];

    $directory = new RecursiveDirectoryIterator('.', RecursiveDirectoryIterator::SKIP_DOTS | RecursiveDirectoryIterator::UNIX_PATHS);
    $iterator = new RecursiveIteratorIterator($directory, RecursiveIteratorIterator::LEAVES_ONLY);

    foreach ($iterator as $file) {
        if ($file->isDir()) {
            continue;
        }

        $ext = strtolower($file->getExtension());
        if (in_array($ext, $allowed_extensions)) {
            $pathname = './' . $file->getPathname();
            if (substr($pathname, 0, 4) === '././') {
                $pathname = substr($pathname, 2);
            }

            $md5val = md5_file($file->getPathname());
            $tikilib->query($query, [$pathname, $md5val, $current_version]);
        }
    }
}

// Check if any of the mail-in accounts uses "Allow anonymous access"
if ($prefs['feature_mailin'] == 'y') {
    $mailinlib = TikiLib::lib('mailin');
    $accs = $mailinlib->list_active_mailin_accounts(0, -1, 'account_desc', '');

    // Check anonymous access
    $errorCnt = 0;
    foreach ($accs['data'] as $acc) {
        if ($acc['anonymous'] === 'y') {
            $errorCnt++;
        }
    }
    if ($errorCnt > 0) {
        $tikisettings['feature_mailin-anonymous'] = [
            'risk' => tra('unsafe') ,
            'setting' => tra('Enabled') ,
            'message' => tra('One or more mail-in accounts have enabled "Allow anonymous access", which disables all permission checking for incoming email. Check tiki-admin_mailin.php')
        ];
    }

    // Check admin access
    $errorCnt = 0;
    foreach ($accs['data'] as $acc) {
        if ($acc['admin'] === 'y') {
            $errorCnt++;
        }
    }
    if ($errorCnt > 0) {
        $tikisettings['feature_mailin-admin'] = [
            'risk' => tra('unsafe') ,
            'setting' => tra('Enabled') ,
            'message' => tra('One or more mail-in accounts have enabled "Allow admin access", which allows for incoming email from admins. Admins have all rights, and web pages can easily be overwitten / tampered with. Check tiki-admin_mailin.php')
        ];
    }
}

//check to see if installer lock is being used
//check multitiki
if (is_file(TIKI_CONFIG_PATH . '/virtuals.inc')) {
    $virtuals = array_map('trim', file(TIKI_CONFIG_PATH . '/virtuals.inc'));
    foreach ($virtuals as $v) {
        if ($v) {
            if (is_file(TIKI_CONFIG_PATH . "/$v/local.php") && is_readable(TIKI_CONFIG_PATH . "/$v/local.php")) {
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
$multi = '';
if ($virtuals) {
    if (isset($_SERVER['TIKI_VIRTUAL']) && is_file(TIKI_CONFIG_PATH . '/' . $_SERVER['TIKI_VIRTUAL'] . '/local.php')) {
        $multi = $_SERVER['TIKI_VIRTUAL'];
    } elseif (isset($_SERVER['SERVER_NAME']) && is_file(TIKI_CONFIG_PATH . '/' . $_SERVER['SERVER_NAME'] . '/local.php')) {
        $multi = $_SERVER['SERVER_NAME'];
    } elseif (isset($_SERVER['HTTP_HOST']) && is_file(TIKI_CONFIG_PATH . '/' . $_SERVER['HTTP_HOST'] . '/local.php')) {
        $multi = $_SERVER['HTTP_HOST'];
    }
}
$tikidomain = $multi;
$tikidomainslash = (! empty($tikidomain) ? $tikidomain . '/' : '');
if (! file_exists(TIKI_CONFIG_PATH . '/' . $tikidomainslash . 'lock')) {
    $tikisettings['installer lock'] = [
        'risk' => tra('unsafe') ,
        'setting' => tra('Unlocked') ,
        'message' => tra('The installer is not locked. The installer could be accessed, putting the database at risk of being altered or destroyed.')
    ];
}

$fmap = [
    'good' => ['icon' => 'ok', 'class' => 'success'],
    'safe' => ['icon' => 'ok', 'class' => 'success'],
    'bad' => ['icon' => 'ban', 'class' => 'danger'],
    'unsafe' => ['icon' => 'ban', 'class' => 'danger'],
    'risky' => ['icon' => 'warning', 'class' => 'warning'],
    'ugly' => ['icon' => 'warning', 'class' => 'warning'],
    'info' => ['icon' => 'information', 'class' => 'info'],
    'unknown' => ['icon' => 'help', 'class' => 'muted'],
];
$smarty->assign('fmap', $fmap);


ksort($tikisettings);
$smarty->assign_by_ref('tikisettings', $tikisettings);
// array for severity in tiki_secdb table. This can go into a extra table if
// the array grows to much.
$secdb_severity = [
    //1000 Path disclosure
    1000 => tra('Path disclosure') ,
    1001 => tra('Path disclosure through error message') ,
    //2000 SQL injection
    2000 => tra('SQL injection') ,
    2001 => tra('SQL injection by authenticated user') ,
    2002 => tra('SQL injection by authenticated user with special privileges') ,
    2003 => tra('SQL injection without authentication') ,
    //3000 command injection
    3000 => tra('PHP command injection') ,
    3001 => tra('PHP command injection by authenticated user') ,
    3002 => tra('PHP command injection by authenticated user with special privileges') ,
    3003 => tra('PHP command injection without authentication') ,
    //4000 File upload
    4000 => tra('File upload')
];

function fast_check_dir($dir, &$result)
{
    @ini_set('memory_limit', '512M');
    @set_time_limit(0);

    global $tikilib;

    $query = "select count(*) from `tiki_secdb` where `filename`=?";
    $d = dir($dir);
    while (false !== ($e = $d->read())) {
        $entry = $dir . '/' . $e;
        if (is_dir($entry)) {
            if ($e != '..' && $e != '.' && $entry != './' . SMARTY_COMPILED_TEMPLATES_PATH && $entry != './' . TEMP_PATH) {
                fast_check_dir($entry, $result);
            }
        } elseif (preg_match('/\.(sql|css|tpl|js|php)$/', $e)) {
            if (! is_readable($entry)) {
                $result[$entry] = ['status' => 'error', 'message' => tra('File is not readable. Unable to check.')];
            } else {
                $count = $tikilib->getOne($query, [$entry]);
                if ($count == 0) {
                    $result[$entry] = ['status' => 'error', 'message' => tra('This is not a recognized Tiki file. It may have been added maliciously.')];
                }
            }
        }
    }
    $d->close();
}

function md5_check_dir($dir, &$result, $vcs_diff = [])
{
// save all suspicious files in $result
    global $tikilib, $tiki_versions;
    $c_tiki_versions = count($tiki_versions);
    $query = "select * from `tiki_secdb` where `filename`=?";
    $d = dir($dir);
    while (false !== ($e = $d->read())) {
        $entry = $dir . '/' . $e;
        if (is_dir($entry)) {
            if ($e != '..' && $e != '.' && $entry != './' . SMARTY_COMPILED_TEMPLATES_PATH && $entry != './' . TEMP_PATH) { // do not descend and no checking of templates_c since the file based md5 database would grow to big
                md5_check_dir($entry, $result, $vcs_diff);
            }
        } elseif (preg_match('/\.(sql|css|tpl|js|php)$/', $e)) {
            if (! is_readable($entry)) {
                $result[$entry] = ['status' => 'error', 'message' => tra('File is not readable. Unable to check.')];
            } else {
                $md5val = md5_file($entry);
                $dbresult = $tikilib->query($query, [$entry]);
                $is_tikifile = false;
                $is_tikiver = [];
                $valid_tikiver = [];

                while ($res = $dbresult->FetchRow()) {
                    $is_tikifile = true; // we know the filename ... probably modified
                    if ($res['md5_value'] == $md5val) {
                        $is_tikiver[] = $res['tiki_version']; // found
                    }
                    $k = array_search($res['tiki_version'], $tiki_versions);
                    if ($k > 0) {
                        //record the valid versions in this array
                        if ($res['md5_value'] == $md5val) {
                            $valid_tikiver[$k] = true;
                        } else {
                            $valid_tikiver[$k] = false;
                        }
                    }
                }

                if (! $is_tikifile) {
                    if ($vcs_diff && isset($vcs_diff[substr($entry, 2)]) && $vcs_diff[substr($entry, 2)] !== 'unversioned') {
                        $result[$entry] = ['status' => 'error', 'message' => tra('This Tiki file differs from the VCS repository version. Check if this file was uploaded and if it is dangerous.')];
                    } else {
                        $result[$entry] = ['status' => 'error', 'message' => tra('This is not a Tiki file. Check if this file was uploaded and if it is dangerous.')];
                    }
                } elseif (count($is_tikiver) == 0) {
                    $result[$entry] = ['status' => 'error', 'message' => tra('This is a modified File. Cannot check version. Check if it is dangerous.')];
                } else {
                    $most_recent = false;
                    for ($i = $c_tiki_versions; $i > 0; $i--) { // search $valid_tikiver top to down to find the most recent version
                        if (isset($valid_tikiver[$i])) {
                            if ($valid_tikiver[$i]) {
                                $most_recent = true; // in this case we have found the most recent version. good
                            }
                            break;
                        }
                    }

                    if (! $most_recent) {
                        $result[$entry] = ['status' => 'warning', 'message' => tra('This file is from another Tiki version: ') . implode(' ' . tra('or') . ' ', $is_tikiver)];
                    } else {
                        $result[$entry] = ['status' => 'ok', 'message' => tra('OK')];
                    }
                }
            }
        }
    }
    $d->close();
}

if (isset($_POST['check_files_fast'])) {
    $result = [];
    fast_check_dir(".", $result);
    $smarty->assign('filecheck', true);
    $smarty->assign_by_ref('tikifiles', $result);
}

if (isset($_POST['check_files_deep'])) {
    global $tiki_versions;
    require_once('lib/setup/twversion.class.php');
    $version = new TWVersion();
    $tiki_versions = $version->tikiVersions();
    $tiki_versions[] = $version->version;
    $result = [];

    if ($version->git == 'y' && is_readable(DEPRECATED_DEVTOOLS_PATH . '/gittools.php')) {   // git checkout
        require_once(DEPRECATED_DEVTOOLS_PATH . '/gittools.php');
        $git_diff = files_differ('./');
    } else {
        $git_diff = [];
    }

    $result = TikiLib::lib('tiki')->allocate_extra(
        'secdb_check',
        function () use (&$result, $git_diff) {
            md5_check_dir(".", $result, $git_diff);
            return $result;
        }
    );

    $smarty->assign('filecheck', true);
    $smarty->assign_by_ref('tikifiles', $result);
}

define('S_ISUID', '2048');
define('S_ISGID', '1024');
define('S_ISVTX', '512');
define('S_IRUSR', '256');
define('S_IWUSR', '128');
define('S_IXUSR', '64');
define('S_IRGRP', '32');
define('S_IWGRP', '16');
define('S_IXGRP', '8');
define('S_IROTH', '4');
define('S_IWOTH', '2');
define('S_IXOTH', '1');
// These constants were removed from PHP but are declared above, so available
// phpcs:disable PHPCompatibility.Constants.RemovedConstants.s_irgrpRemoved
// phpcs:disable PHPCompatibility.Constants.RemovedConstants.s_irothRemoved
// phpcs:disable PHPCompatibility.Constants.RemovedConstants.s_irusrRemoved
// phpcs:disable PHPCompatibility.Constants.RemovedConstants.s_iwgrpRemoved
// phpcs:disable PHPCompatibility.Constants.RemovedConstants.s_iwothRemoved
// phpcs:disable PHPCompatibility.Constants.RemovedConstants.s_iwusrRemoved
// phpcs:disable PHPCompatibility.Constants.RemovedConstants.s_ixgrpRemoved
// phpcs:disable PHPCompatibility.Constants.RemovedConstants.s_ixothRemoved
// phpcs:disable PHPCompatibility.Constants.RemovedConstants.s_ixusrRemoved

// Function to check Filesystem permissions
/**
 * @param $dir
 * @param $result
 */
function check_dir_perms($dir, &$result)
{
    @ini_set('memory_limit', '512M');
    @set_time_limit(0);

    $excluded_dirs = [
        './node_modules',
        './vendor',
        './temp',
        './templates_c',
        './img/wiki_up',
        './files',
    ];
    static $depth = 0;
    $depth++;
    $d = @dir($dir);

    if (! $d) {
        $depth--;
        return;
    }

    while (false !== ($e = $d->read())) {
        $entry = $dir . '/' . $e;
        if ($e != '..' && ($e != '.' || $depth == 1)) {
            $result[$entry]['w'] = @is_writable($entry);
            $result[$entry]['r'] = @is_readable($entry);
            $result[$entry]['t'] = @filetype($entry);
            $s = @stat($entry);

            if ($s) {
                if (function_exists('posix_getpwuid')) {
                    $t = @posix_getpwuid($s['uid']);
                    $result[$entry]['u'] = $t['name'] ?? $s['uid'];
                    $t = @posix_getgrgid($s['gid']);
                    $result[$entry]['g'] = $t['name'] ?? $s['gid'];
                } else {
                    $result[$entry]['u'] = $s['uid'];
                    $result[$entry]['g'] = $s['gid'];
                }
                $m = (int)$s['mode'];
                if ($m >= 32768) {
                    $m -= 32768;
                }
                if ($m >= 16384) {
                    $m -= 16384;
                }
                if ($m >= 8192) {
                    $m -= 8192;
                }
                if ($m >= 4096) {
                    $m -= 4096;
                }
                $result[$entry]['p'] = $m;
                $result[$entry]['suid'] = ($m >= S_ISUID && ($m -= S_ISUID) >= 0);
                $result[$entry]['sgid'] = ($m >= S_ISGID && ($m -= S_ISGID) >= 0);
                $result[$entry]['sticky'] = ($m >= S_ISVTX && ($m -= S_ISVTX) >= 0);
                $result[$entry]['ur'] = ($m >= S_IRUSR && ($m -= S_IRUSR) >= 0);
                $result[$entry]['uw'] = ($m >= S_IWUSR && ($m -= S_IWUSR) >= 0);
                $result[$entry]['ux'] = ($m >= S_IXUSR && ($m -= S_IXUSR) >= 0);
                $result[$entry]['gr'] = ($m >= S_IRGRP && ($m -= S_IRGRP) >= 0);
                $result[$entry]['gw'] = ($m >= S_IWGRP && ($m -= S_IWGRP) >= 0);
                $result[$entry]['gx'] = ($m >= S_IXGRP && ($m -= S_IXGRP) >= 0);
                $result[$entry]['or'] = ($m >= S_IROTH && ($m -= S_IROTH) >= 0);
                $result[$entry]['ow'] = ($m >= S_IWOTH && ($m -= S_IWOTH) >= 0);
                $result[$entry]['ox'] = ($m >= S_IXOTH && ($m -= S_IXOTH) >= 0);

                if ($result[$entry]['t'] == 'dir' && $e != '.' && ! in_array($entry, $excluded_dirs)) {
                    check_dir_perms($entry, $result);
                }
            }
        }
    }
    $d->close();
    $depth--;
}

if (isset($_REQUEST['check_file_permissions'])) {
    $fileperms = [];
    check_dir_perms('.', $fileperms);
    // walk throug array to find problematic entries
    $worldwritable = [];
    $suid = [];
    $executable = [];
    $strangeinode = [];
    $apachewritable = [];
    foreach ($fileperms as $fname => $fperms) {
        if ($fperms['suid']) {
            $suid[$fname] = & $fileperms[$fname];
        }
        if ($fperms['ow']) {
            $worldwritable[$fname] = & $fileperms[$fname];
        }
        if ($fperms['t'] != 'dir' && ($fperms['ux'] || $fperms['gx'] || $fperms['ox'])) {
            $executable[$fname] = & $fileperms[$fname];
        }
        if ($fperms['t'] != 'dir' && $fperms['t'] != 'file' && $fperms['t'] != 'link') {
            $strangeinode[$fname] = & $fileperms[$fname];
        }
        if ($fperms['w']) {
            $apachewritable[$fname] = & $fileperms[$fname];
        }
    }
    $smarty->assign_by_ref('worldwritable', $worldwritable);
    $smarty->assign_by_ref('suid', $suid);
    $smarty->assign_by_ref('executable', $executable);
    $smarty->assign_by_ref('strangeinode', $strangeinode);
    $smarty->assign_by_ref('apachewritable', $apachewritable);
    $smarty->assign('permcheck', true);
}

if (isset($_POST['rebuild_secdb_confirmation'])) {
    try {
        rebuild_security_database_logic();
        $access->redirect('tiki-admin_security.php?rebuild_status=success');
    } catch (Exception $e) {
        $smarty->assign('rebuild_error_message', tra('An error occurred during the database rebuild:') . ' ' . htmlspecialchars($e->getMessage()));
    }
}

if (isset($_GET['rebuild_status']) && $_GET['rebuild_status'] === 'success') {
    $smarty->assign('rebuild_success_message', tra('The security database has been successfully rebuilt.'));
}

$warn_htaccess_mismatch_enabled = TikiLib::lib('tiki')->get_preference('security_warn_htaccess_mismatch', 'y') === 'y';

$htaccessCheck = null;
if ($warn_htaccess_mismatch_enabled) {
    $checker = new \Tiki\Security\HtaccessChecker();
    $htaccessCheck = $checker->run(
        TIKI_PATH,
        TIKI_PATH . '/_htaccess',
        ['server_software' => $_SERVER['SERVER_SOFTWARE'] ?? '']
    );
}

$smarty->assign('htaccessCheckEnabled', $warn_htaccess_mismatch_enabled ? 'y' : 'n');
$smarty->assign('htaccessCheck', $htaccessCheck);
$smarty->assign('htaccessDocsUrl', 'https://doc.tiki.org/htaccess');

// disallow robots to index page:
$smarty->assign('metatag_robots', 'NOINDEX, NOFOLLOW');
$smarty->assign('mid', 'tiki-admin_security.tpl');
$smarty->display("tiki.tpl");
