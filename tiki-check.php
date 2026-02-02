<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/*
About the design:
tiki-check.php is designed to run in 2 modes
1) Regular mode. From inside Tiki, in Admin | General
2) Stand-alone mode. Used to check a server pre-Tiki installation, by copying (only) tiki-check.php onto the server and pointing your browser to it.
tiki-check.php should not crash but rather avoid running tests which lead to tiki-check crashes.

IMPORTANT:
1) Be careful, this file will copied to past branches as-is, so it needs to run on the oldest php version that supported tiki versions managed by this tool runs. As of 2023-05-18, it is Tiki 18, and thus PHP 7.2
*/

// Disable the following PHPCS checks. tiki-check.php is shared across tiki versions, so may refer to old software
// phpcs:disable PHPCompatibility.Extensions.RemovedExtensions
// phpcs:disable PHPCompatibility.FunctionUse.RemovedFunctions.mysql_queryDeprecatedRemoved
// phpcs:disable PHPCompatibility.FunctionUse.RemovedFunctions.mysql_fetch_arrayDeprecatedRemoved
// phpcs:disable PHPCompatibility.FunctionUse.RemovedFunctions.mysql_connectDeprecatedRemoved
// phpcs:disable PHPCompatibility.IniDirectives.RemovedIniDirectives.mbstring_func_overloadDeprecated

use Tiki\Lib\Alchemy\AlchemyLib;
use Tiki\Lib\Unoconv\UnoconvStrategy;
use Tiki\Lib\Unoconv\UnoserverStrategy;
use Tiki\Package\ComposerManager;
use Smarty\Smarty;

// Define fitness status constants early
define('FITNESS_STATUS_GOOD', 'good');
define('FITNESS_STATUS_BAD', 'bad');
define('FITNESS_STATUS_UNSURE', 'unsure');
define('FITNESS_STATUS_INFO', 'info');
define('FITNESS_STATUS_NA', 'N/A');
define('FITNESS_STATUS_SAFE', 'safe');
define('FITNESS_STATUS_UNSAFE', 'unsafe');
define('FITNESS_STATUS_UNKNOWN', 'unknown');
define('FITNESS_STATUS_RISKY', 'risky');

// TODO : Create sane 3rd mode for Monitoring Software like Nagios, Icinga, Shinken
// * needs authentication, if not standalone
$nagios = isset($_REQUEST['nagios']);
$locked = file_exists('tiki-check.php.lock');
$font = 'lib/captcha/DejaVuSansMono.ttf';

$inputConfiguration = array(
    array(
        'staticKeyFilters' => array(
            'dbhost' => 'text',
            'dbuser' => 'text',
            'dbpass' => 'text',
            'email_test_to' => 'email',
        ),
    ),
);

// reflector for SefURL check
if (isset($_REQUEST['tiki-check-ping'])) {
    die('pong:' . (int)$_REQUEST['tiki-check-ping']);
}

// AJAX dashboard refresh
if (isset($_REQUEST['ajax']) && $_REQUEST['ajax'] === 'dashboard') {
    // Set up standalone mode for AJAX
    $standalone = true;
    if (! function_exists('tra')) {
        function tra($string)
        {
            return $string;
        }
    }

    // Initialize variables for AJAX calculation
    $critical_count = 0;
    $warning_count = 0;
    $info_count = 0;
    $good_count = 0;
    $critical_issues = array();

    // Recalculate server properties
    $server_properties = array();
    $php_properties = array();
    $security = array();
    $mysql_properties = array();
    $tiki_security = array();

    // Check PHP properties (simplified version)
    $e = error_reporting();
    $d = ini_get('display_errors');
    $l = ini_get('log_errors');

    if ($l) {
        if (! $d) {
            $php_properties['Error logging'] = array(
                'fitness' => tra('info'),
                'fitness_status' => FITNESS_STATUS_INFO,
                'setting' => 'Enabled',
                'message' => tr('Errors will be logged, since %0 is enabled. Also, %1 is disabled.', 'log_errors', 'display_errors')
            );
        } else {
            $php_properties['Error logging'] = array(
                'fitness' => tra('info'),
                'fitness_status' => FITNESS_STATUS_INFO,
                'setting' => 'Enabled',
                'message' => tr('Errors will be logged, since %0 is enabled, but %1 is also enabled.', 'log_errors', 'display_errors')
            );
        }
    } else {
        $php_properties['Error logging'] = array(
            'fitness' => tra('info'),
            'fitness_status' => FITNESS_STATUS_INFO,
            'setting' => 'Full',
            'message' => tr('Errors will not be logged, since %0 is not enabled.', 'log_errors')
        );
    }

    // Check critical security functions
    $dangerous_functions = array('exec', 'passthru', 'shell_exec', 'system', 'proc_open', 'popen', 'curl_exec', 'curl_multi_exec', 'parse_ini_file');
    foreach ($dangerous_functions as $func) {
        if (function_exists($func)) {
            $security[$func] = array(
                'fitness' => tra('bad'),
                'fitness_status' => FITNESS_STATUS_BAD,
                'setting' => 'Enabled',
                'message' => tra('This function is enabled and could be a security risk.')
            );
        } else {
            $security[$func] = array(
                'fitness' => tra('good'),
                'fitness_status' => FITNESS_STATUS_GOOD,
                'setting' => 'Disabled',
                'message' => tra('This function is disabled, which is good for security.')
            );
        }
    }

    // Check allow_url_fopen
    if (ini_get('allow_url_fopen')) {
        $security['allow_url_fopen'] = array(
            'fitness' => tra('bad'),
            'fitness_status' => FITNESS_STATUS_BAD,
            'setting' => 'Enabled',
            'message' => tr('%0 is enabled, which could be a security risk.', 'allow_url_fopen')
        );
    } else {
        $security['allow_url_fopen'] = array(
            'fitness' => tra('good'),
            'fitness_status' => FITNESS_STATUS_GOOD,
            'setting' => 'Disabled',
            'message' => tr('%0 is disabled, which is good for security.', 'allow_url_fopen')
        );
    }

    // Check PHP extensions
    $required_extensions = array('tidy', 'gd', 'mbstring', 'curl', 'xml', 'json');
    foreach ($required_extensions as $ext) {
        if (extension_loaded($ext)) {
            $php_properties[$ext] = array(
                'fitness' => tra('good'),
                'fitness_status' => FITNESS_STATUS_GOOD,
                'setting' => 'Loaded',
                'message' => tr('The %0 extension is loaded.', $ext)
            );
        } else {
            $php_properties[$ext] = array(
                'fitness' => tra('bad'),
                'fitness_status' => FITNESS_STATUS_BAD,
                'setting' => 'Not loaded',
                'message' => tr('The %0 extension is not loaded.', $ext)
            );
        }
    }

    // Check database connection if available
    if (file_exists('./db/local.php')) {
        require_once './db/local.php';
        if (isset($host_tiki) && isset($user_tiki) && isset($pass_tiki) && isset($dbs_tiki)) {
            $connection = mysqli_connect($host_tiki, $user_tiki, $pass_tiki, $dbs_tiki);
            if ($connection) {
                $mysql_properties['Database Connection'] = array(
                    'fitness' => tra('good'),
                    'fitness_status' => FITNESS_STATUS_GOOD,
                    'setting' => 'Connected',
                    'message' => tra('Database connection successful.')
                );
                mysqli_close($connection);
            } else {
                $mysql_properties['Database Connection'] = array(
                    'fitness' => tra('bad'),
                    'fitness_status' => FITNESS_STATUS_BAD,
                    'setting' => 'Failed',
                    'message' => tra('Database connection failed.')
                );
            }
        }
    }

    // Run enhanced security checks for AJAX
    $db_permissions = check_database_config_permissions();
    $phpmyadmin_check = check_phpmyadmin_installations();
    $adminer_check = check_adminer_installations();
    $backup_config_check = check_backup_configuration_files();
    $directory_listing_check = check_directory_listing_vulnerabilities();
    $ssl_check = check_ssl_configuration();

    $tiki_security['Database Configuration Permissions'] = $db_permissions;
    $tiki_security['phpMyAdmin Security'] = $phpmyadmin_check;
    $tiki_security['Adminer Security'] = $adminer_check;
    $tiki_security['Backup Configuration Files'] = $backup_config_check;
    $tiki_security['Directory Listing Security'] = $directory_listing_check;
    $tiki_security['SSL/TLS Configuration'] = $ssl_check;

    // Count issues from different sections
    $all_properties = array_merge(
        $server_properties,
        $mysql_properties,
        $php_properties,
        $security,
        $tiki_security,
        isset($apache_properties) && is_array($apache_properties) ? $apache_properties : array(),
        isset($iis_properties) && is_array($iis_properties) ? $iis_properties : array()
    );

    foreach ($all_properties as $key => $item) {
        if (! isset($item['fitness_status'])) { // Some items so not have fitness_status
            continue;
        }
        switch ($item['fitness_status']) {
            case FITNESS_STATUS_BAD:
            case FITNESS_STATUS_UNSAFE:
            case FITNESS_STATUS_RISKY:
                $critical_count++;

                // Determine the correct section based on which array the item came from
                $section = 'Server_Properties'; // default
                if (isset($server_properties[$key])) {
                    $section = 'Server_Properties';
                } elseif (isset($mysql_properties[$key])) {
                    $section = 'MySQL_or_MariaDB_Database_Properties';
                } elseif (isset($php_properties[$key])) {
                    $section = 'PHP_scripting_language_properties';
                } elseif (isset($security[$key])) {
                    $section = 'Tiki_Security';
                } elseif (isset($tiki_security[$key])) {
                    $section = 'Tiki_Security';
                } elseif (isset($apache_properties) && isset($apache_properties[$key])) {
                    $section = 'Apache_properties';
                } elseif (isset($iis_properties) && isset($iis_properties[$key])) {
                    $section = 'IIS_properties';
                }

                $critical_issues[] = array(
                    'title' => $key,
                    'message' => $item['message'],
                    'section' => $section
                );
                break;
            case FITNESS_STATUS_UNSURE:
                $warning_count++;
                break;
            case FITNESS_STATUS_INFO:
                $info_count++;
                break;
            case FITNESS_STATUS_GOOD:
            case FITNESS_STATUS_SAFE:
                $good_count++;
                break;
        }
    }

    // Calculate health score (0-100)
    $total_checks = $critical_count + $warning_count + $info_count + $good_count;
    $health_score = $total_checks > 0 ? round((($good_count + $info_count * 0.5) / $total_checks) * 100) : 100;

    // Calculate percentages for progress bar
    $critical_percentage = $total_checks > 0 ? round(($critical_count / $total_checks) * 100) : 0;
    $warning_percentage = $total_checks > 0 ? round(($warning_count / $total_checks) * 100) : 0;
    $health_percentage = $total_checks > 0 ? round((($good_count + $info_count) / $total_checks) * 100) : 100;

    // Critical issues are now handled by the template

    // Use Smarty template for dashboard
    $smarty = new Smarty();
    $smarty->setTemplateDir('./templates/');
    $smarty->setCompileDir('./temp/');
    $smarty->setCacheDir('./temp/');

    // Add translation function for template
    if (! function_exists('tr')) {
        function tr($string)
        {
            return $string;
        }
    }

    // Assign variables to template
    $smarty->assign('critical_count', $critical_count);
    $smarty->assign('warning_count', $warning_count);
    $smarty->assign('info_count', $info_count);
    $smarty->assign('good_count', $good_count);
    $smarty->assign('health_percentage', $health_percentage);
    $smarty->assign('warning_percentage', $warning_percentage);
    $smarty->assign('critical_percentage', $critical_percentage);
    $smarty->assign('health_score', $health_score);
    $smarty->assign('critical_issues', $critical_issues);
    $smarty->assign('source_breakdown', array(
        'critical' => array('main' => 0, 'packages' => 0, 'ocr' => 0),
        'warning' => array('main' => 0, 'packages' => 0, 'ocr' => 0),
        'info' => array('main' => 0, 'packages' => 0, 'ocr' => 0),
        'good' => array('main' => 0, 'packages' => 0, 'ocr' => 0)
    ));

    // Render dashboard using template
    $dashboard_html = $smarty->fetch('tiki-check-dashboard.tpl');
    die($dashboard_html);
}

function checkOPcacheCompatibility()
{
    return ! ((version_compare(PHP_VERSION, '7.1.0', '>=') && version_compare(PHP_VERSION, '7.2.0', '<')) //7.1.x
        || (version_compare(PHP_VERSION, '7.2.0', '>=') && version_compare(PHP_VERSION, '7.2.19', '<')) // >= 7.2.0 < 7.2.19
        || (version_compare(PHP_VERSION, '7.3.0', '>=') && version_compare(PHP_VERSION, '7.3.6', '<'))); // >= 7.3.0 < 7.3.6
}

function getTikiRequirements()
{
    return array(
        array(
            'name'    => 'Tiki 29.x',
            'version' => 29,
            'php'     => array(
                'min' => '8.1.0', // For the latest version, this should match TIKI_MIN_PHP_VERSION, but cannot use it since tiki-check is expected to run standalone
                'max' => '8.4.99', // For the latest version, this should match TIKI_MAX_SUPPORTED_PHP_VERSION, but cannot use it since tiki-check is expected to run standalone
            ),
            'mariadb' => array(
                'min' => '10.5',
                'max' => null
            ),
            'mysql'   => array(
                'min' => '8.0',
                'max' => null
            ),
        ),
        array(
            'name'    => 'Tiki 28.x',
            'version' => 28,
            'php'     => array(
                'min' => '8.1.0',
                'max' => '8.4.99',
            ),
            'mariadb' => array(
                'min' => '10.5',
                'max' => null
            ),
            'mysql'   => array(
                'min' => '8.0',
                'max' => null
            ),
        ),
        array(
            'name'    => 'Tiki 27.x',
            'version' => 27,
            'php'     => array(
                'min' => '8.1.0',
                'max' => '8.4.99',
            ),
            'mariadb' => array(
                'min' => '10.5',
                'max' => null
            ),
            'mysql'   => array(
                'min' => '8.0',
                'max' => null
            ),
        ),
        array(
            'name'    => 'Tiki 26.x',
            'version' => 26,
            'php'     => array(
                'min' => '8.1',
                'max' => '8.2.99',
            ),
            'mariadb' => array(
                'min' => '5.5',
                'max' => null
            ),
            'mysql'   => array(
                'min' => '5.7',
                'max' => null
            ),
        ),
        array(
            'name'    => 'Tiki 25.x',
            'version' => 25,
            'php'     => array(
                'min' => '7.4',
                'max' => '7.4.99',
            ),
            'mariadb' => array(
                'min' => '5.5',
                'max' => null
            ),
            'mysql'   => array(
                'min' => '5.7',
                'max' => null
            ),
        ),
        array(
            'name'    => 'Tiki 24.x',
            'version' => 24,
            'php'     => array(
                'min' => '7.4',
                'max' => '7.4.99',
            ),
            'mariadb' => array(
                'min' => '5.5',
                'max' => null
            ),
            'mysql'   => array(
                'min' => '5.7',
                'max' => null
            ),
        ),
        array(
            'name'    => 'Tiki 23.x',
            'version' => 23,
            'php'     => array(
                'min' => '7.4',
                'max' => '7.4.99',
            ),
            'mariadb' => array(
                'min' => '5.5',
                'max' => null
            ),
            'mysql'   => array(
                'min' => '5.7',
                'max' => null
            ),
        ),
        array(
            'name'    => 'Tiki 22.x',
            'version' => 22,
            'php'     => array(
                'min' => '7.4',
                'max' => '7.4.99',
            ),
            'mariadb' => array(
                'min' => '5.5',
                'max' => null
            ),
            'mysql'   => array(
                'min' => '5.7',
                'max' => null
            ),
        ),
        array(
            'name'    => 'Tiki 21.x LTS',
            'version' => 21,
            'php'     => array(
                'min' => '7.2',
                'max' => '7.3.99',
            ),
            'mariadb' => array(
                'min' => '5.5',
                'max' => null
            ),
            'mysql'   => array(
                'min' => '5.7',
                'max' => null
            ),
        ),
        array(
            'name'    => 'Tiki 20.x',
            'version' => 20,
            'php'     => array(
                'min' => '7.1',
                'max' => '7.2.99',
            ),
            'mariadb' => array(
                'min' => '5.5',
                'max' => '10.4.99',
            ),
            'mysql'   => array(
                'min' => '5.5.3',
                'max' => '5.7.99',
            ),
        ),
        array(
            'name'    => 'Tiki 19.x',
            'version' => 19,
            'php'     => array(
                'min' => '7.1',
                'max' => '7.2.99',
            ),
            'mariadb' => array(
                'min' => '5.5',
                'max' => '10.4.99',
            ),
            'mysql'   => array(
                'min' => '5.5.3',
                'max' => '5.7.99',
            ),
        ),
        array(
            'name'    => 'Tiki 18.x',
            'version' => 18,
            'php'     => array(
                'min' => '5.6',
                'max' => '7.2.99',
            ),
            'mariadb' => array(
                'min' => '5.1',
                'max' => '10.4.99',
            ),
            'mysql'   => array(
                'min' => '5.0',
                'max' => '5.7.99',
            ),
        )
    );
}

function checkServerRequirements($phpVersion, $dbEngine, $dbVersion)
{
    $dbEnginesLabels = array(
        'mysql'   => 'MySQL',
        'mariadb' => 'MariaDB',
    );

    $tikiRequirements = getTikiRequirements();

    $phpValid = false;
    $dbValid = false;

    foreach ($tikiRequirements as $requirement) {
        if (version_compare($phpVersion, $requirement['php']['min'], '<')) {
            continue;
        }
        if (
            isset($requirement['php']['max'])
            && version_compare($phpVersion, $requirement['php']['max'], '>')
        ) {
            continue;
        } else {
        }
        $phpValid = true;
        break;
    }

    $tiki_server_req['PHP'] = array(
        'value'   => PHP_VERSION,
        'fitness' => tra('good'),
        'fitness_status' => FITNESS_STATUS_GOOD,
        'message' => tra('PHP version is supported by one of Tiki versions'),
    );

    if (! $phpValid) {
        $tiki_server_req['PHP']['fitness'] = tra('bad');
        $tiki_server_req['PHP']['fitness_status'] = FITNESS_STATUS_BAD;
        $tiki_server_req['PHP']['message'] = tra('PHP version is not supported by Tiki');
    }

    if ($dbEngine && $dbVersion) {
        foreach ($tikiRequirements as $tikiVersion) {
            if (version_compare($dbVersion, $tikiVersion[$dbEngine]['min'], '<')) {
                continue;
            }
            if (
                isset($tikiVersion[$dbEngine]['max'])
                && $tikiVersion[$dbEngine]['max'] !== $tikiVersion[$dbEngine]['min']
                && version_compare($dbVersion, $tikiVersion[$dbEngine]['max'], '>')
            ) {
                continue;
            }
            $dbValid = true;
            break;
        }

        $tiki_server_req['Database'] = array(
            'value'   => $dbEnginesLabels[$dbEngine] . ' ' . $dbVersion,
            'fitness' => tra('good'),
            'fitness_status' => FITNESS_STATUS_GOOD,
            'message' => tra('Database version is supported by one of Tiki Versions.'),
        );

        if (! $dbValid) {
            $tiki_server_req['Database']['fitness'] = tra('bad');
            $tiki_server_req['Database']['fitness_status'] = FITNESS_STATUS_BAD;
            $tiki_server_req['Database']['message'] = tra('Database version is not supported by Tiki.');
        }
    } else {
        $tiki_server_req['Database'] = array(
            'value'   => 'N/A',
            'fitness' => tra('unsure'),
            'fitness_status' => FITNESS_STATUS_UNSURE,
            'message' => tra('Unable to determine database compatibility'),
        );
    }

    return $tiki_server_req;
}

/**
 * @param string $dbEngine
 * @param string $dbVersion
 *
 * @return array
 */
function getCompatibleVersions($dbEngine = '', $dbVersion = '')
{
    $tikiRequirements = getTikiRequirements();
    $compatibleVersions = array();

    $dbVersion = $dbVersion ?: 0;
    foreach ($tikiRequirements as $requirement) {
        if (version_compare(PHP_VERSION, $requirement['php']['min'], '<')) {
            continue;
        }

        if (
            isset($requirement['php']['max'])
            && $requirement['php']['max'] !== $requirement['php']['min']
            && version_compare(PHP_VERSION, $requirement['php']['max'], '>')
        ) {
            continue;
        }

        if ($dbEngine === 'mysql' || $dbEngine === 'mariadb') {
            if (version_compare($dbVersion, $requirement[$dbEngine]['min'], '<')) {
                continue;
            }
            if (
                isset($requirement[$dbEngine]['max'])
                && version_compare($dbVersion, $requirement[$dbEngine]['max'], '>')
            ) {
                continue;
            }
        }

        $requirement['fitness'] = tra('unsure');
        $requirement['fitness_status'] = FITNESS_STATUS_UNSURE;
        $requirement['message'] = tra('Unable to check database requirements');

        if ($dbEngine && $dbVersion) {
            $requirement['fitness'] = tra('info');
            $requirement['fitness_status'] = FITNESS_STATUS_INFO;
            $requirement['message'] = tra('Supported version');

            if (count($compatibleVersions) == 0) {
                $requirement['fitness'] = tra('good');
                $requirement['fitness_status'] = FITNESS_STATUS_GOOD;
                $requirement['message'] = tra('Recommended version');
            }
        }

        $compatibleVersions[] = $requirement;
    }
    return $compatibleVersions;
}

if (file_exists('./db/local.php') && file_exists('./templates/tiki-check.tpl')) {
    $standalone = false;
    require_once('tiki-setup.php');
    // TODO : Proper authentication
    $access->check_permission('tiki_p_admin');

    // This page is an admin tool usually used in the early stages of setting up Tiki, before layout considerations.
    // Restricting the width is contrary to its purpose.
    $prefs['feature_fixed_width'] = 'n';

    // Add CSS for enhanced status indicators (only if file exists)
    if (file_exists('themes/base_files/feature_css/tiki-check.css')) {
        $headerlib->add_cssfile('themes/base_files/feature_css/tiki-check.css');
    }
} else {
    $standalone = true;
    $render = "";

    /**
     * @param $string
     * @return mixed
     */
    function tra($string)
    {
        return $string;
    }

    function tr($string)
    {
        return tra($string);
    }

    /**
      * @param $var
      * @param $style
      */
    function renderTable($var, $style = "")
    {
        global $render;
        $morestyle = "";
        if ($style == "wrap") {
            $morestyle = "overflow-wrap: anywhere;";
        }
        if (is_array($var)) {
            $render .= '<table class="table table-bordered" style="' . $morestyle . '">';
            $render .= "<thead><tr></tr></thead>";
            $render .= "<tbody>";
            foreach ($var as $key => $value) {
                $render .= "<tr>";
                $render .= '<th><span class="visible-on-mobile">Property:&nbsp;</span>';
                $render .= $key;
                $render .= "</th>";
                $iNbCol = 0;
                foreach ($value as $key2 => $value2) {
                    // Skip displaying `fitness_level` but use it in logic
                    if ($key2 === 'fitness_status') {
                        continue;
                    }
                    $render .= '<td data-th="' . $key2 . ':&nbsp;" style="';
                    if ($iNbCol != count(array_keys($value)) - 1) {
                        $render .= 'text-align: center;white-space:nowrap;';
                    }
                    $render .= '"><span class="';
                    if (! empty($value['fitness_status'])) {
                        switch ($value['fitness_status']) {
                            case FITNESS_STATUS_GOOD:
                            case FITNESS_STATUS_SAFE:
                            case FITNESS_STATUS_UNSURE:
                            case FITNESS_STATUS_BAD:
                            case FITNESS_STATUS_RISKY:
                            case FITNESS_STATUS_INFO:
                                $render .= "button {$value['fitness_status']}";
                                break;
                        }
                    }
                    $render .= '">' . $value2 . '</span></td>';
                    $iNbCol++;
                }
                $render .= '</tr>';
            }
            $render .= '</tbody></table>';
        } else {
            $render .= 'Nothing to display.';
        }
    }

    /**
     * @param $var
     */
    function renderAvailableTikiTable($var)
    {
        global $render;

        $formatValue = function ($value, $property) {
            return $value[$property]['min'] .
                ((! empty($value[$property]['max']) && $value[$property]['max'] != $value[$property]['min'])
                    ? ' - ' . $value[$property]['max']
                    : (empty($value[$property]['max']) ? '+' : ''));
        };
        if (is_array($var) && ! empty($var)) {
            $render .= '<table class="table table-bordered"><thead>';
            $render .= '<tr><th>Version</th><th>PHP</th><th>MySQL</th><th>MariaDB</th>';
            $render .= '<th>Fitness</th><th>Explanation</th></tr>';
            foreach ($var as $value) {
                $phpReq = $formatValue($value, 'php');
                $mysqlReq = $formatValue($value, 'mysql');
                $mariadbReq = $formatValue($value, 'mariadb');
                $render .= '<th> ' . $value['name'] . ' </th>';
                $render .= '<td> ' . $phpReq . ' </td>';
                $render .= '<td> ' . $mysqlReq . ' </td>';
                $render .= '<td> ' . $mariadbReq . ' </td>';
                $render .= '<td><span class="button ' . $value['fitness_status'] . '">' . $value['fitness'] . '</span> </td>';
                $render .= '<td> ' . $value['message'] . ' </td></tr>';
            }
            $render .= '</tbody></table>';
        } else {
            $render .= 'Nothing to display.';
        }

        $render .= '<p>For more details, check the <a href="https://doc.tiki.org/Requirements" target="_blank">Tiki Requirements</a> documentation.</p>';
    }
}

// Get PHP properties and check them
$php_properties = array();

if (! file_exists('./db/local.php')) {
    $errorMessage = tra('Mysql benchmark was skipped because no DB configuration was found.');

    if ($standalone) {
        $errorMessage = tra('Mysql benchmark was skipped because it was running as standalone and no DB configuration was found.');
    }

    $render .= '<div class="alert alert-danger"><div class="rboxcontent" style="display: inline">' . $errorMessage . '</div></div>';
}

// Check error reporting level
$e = error_reporting();
$d = ini_get('display_errors');
$l = ini_get('log_errors');
if ($l) {
    if (! $d) {
        $php_properties['Error logging'] = array(
        'fitness' => tra('info'),
        'fitness_status' => FITNESS_STATUS_INFO,
        'setting' => 'Enabled',
        'message' => tr('Errors will be logged, since %0 is enabled. Also, %1 is disabled. This is good practice for a production site, to log the errors instead of displaying them. %2 More info about %0 %3', 'log_errors', 'display_errors', '<a href="#php_conf_info">', '</a>')
        );
    } else {
        $php_properties['Error logging'] = array(
        'fitness' => tra('info'),
        'fitness_status' => FITNESS_STATUS_INFO,
        'setting' => 'Enabled',
        'message' => tr('Errors will be logged, since %0 is enabled, but %1 is also enabled. Good practice, especially for a production site, is to log all errors instead of displaying them. %2 How to change this value %3', 'log_errors', 'display_errors', '<a href="#php_conf_info">', '</a>')
        );
    }
} else {
    $php_properties['Error logging'] = array(
    'fitness' => tra('info'),
    'fitness_status' => FITNESS_STATUS_INFO,
    'setting' => 'Full',
    'message' => tr('Errors will not be logged, since %0 is not enabled. Good practice, especially for a production site, is to log all errors. %1 How to change this value %2', 'log_errors', '<a href="#php_conf_info">', '</a>')
    );
}
if ($e == 0) {
    if ($d != 1) {
        $php_properties['Error reporting'] = array(
            'fitness' => tra('info'),
            'fitness_status' => FITNESS_STATUS_INFO,
            'setting' => 'Disabled',
            'message' => tr('Errors will not be reported, because %0 and %1 are both turned off. This may be appropriate for a production site but, if any problems occur, enable these in php.ini to get more information. %2 How to change this value %3', 'error_reporting', 'display_errors', '<a href="#php_conf_info">', '</a>')
        );
    } else {
        $php_properties['Error reporting'] = array(
            'fitness' => tra('info'),
            'fitness_status' => FITNESS_STATUS_INFO,
            'setting' => 'Disabled',
            'message' => tr('No errors will be reported, although %0 is On, because the %1 level is set to 0. This may be appropriate for a production site but, in if any problems occur, raise the value in php.ini to get more information. %2 How to change this value %3', 'display_errors', 'error_reporting', '<a href="#php_conf_info">', '</a>')
        );
    }
} elseif ($e > 0 && $e < 32767) {
    if ($d != 1) {
        $php_properties['Error reporting'] = array(
            'fitness' => tra('info'),
            'fitness_status' => FITNESS_STATUS_INFO,
            'setting' => 'Disabled',
            'message' => tr('No errors will be reported, because %0 is turned off. This may be appropriate for a production site but, in any problems occur, enable it in php.ini to get more information. The %1 level is reasonable at %2. %3 How to change this value %4', 'display_errors', 'error_reporting', $e, '<a href="#php_conf_info">', '</a>')
        );
    } else {
        $php_properties['Error reporting'] = array(
            'fitness' => tra('info'),
            'fitness_status' => FITNESS_STATUS_INFO,
            'setting' => 'Partly',
            'message' => tr('Not all errors will be reported as the %0 level is at %1. This is not necessarily a bad thing (and it may be appropriate for a production site) as critical errors will be reported, but sometimes it may be useful to get more information. Check the %0 level in php.ini if any problems are occurring. %2 How to change this value %3', 'error_reporting', $e, '<a href="#php_conf_info">', '</a>')
        );
    }
} else {
    if ($d != 1) {
        $php_properties['Error reporting'] = array(
            'fitness' => tra('info'),
            'fitness_status' => FITNESS_STATUS_INFO,
            'setting' => 'Disabled',
            'message' => tr('No errors will be reported although the %0 level is all the way up at %1, because %2 is off. This may be appropriate for a production site but, in case of problems, enable it in php.ini to get more information. %3 How to change this value %4', 'error_reporting', $e, 'display_errors', '<a href="#php_conf_info">', '</a>')
        );
    } else {
        $php_properties['Error reporting'] = array(
            'fitness' => tra('info'),
            'fitness_status' => FITNESS_STATUS_INFO,
            'setting' => 'Full',
            'message' => tr('All errors will be reported as the %0 level is all the way up at %1 and %2 is on. This is good because, in case of problems, the error reports usually contain useful information. %3 How to change this value %4', 'error_reporting', $e, 'display_errors', '<a href="#php_conf_info">', '</a>')
        );
    }
}

// Now we can raise our error_reporting to make sure we get all errors
// This is especially important as we can't use proper exception handling with PDO as we need to be PHP 4 compatible
error_reporting(-1);

// Check if ini_set works
if (function_exists('ini_set')) {
    $php_properties['ini_set'] = array(
        'fitness' => tra('good'),
        'fitness_status' => FITNESS_STATUS_GOOD,
        'setting' => 'Enabled',
        'message' => tr('%0 is used in some places to accommodate special needs of some Tiki features. %1 More info about %0 %2', 'ini_set', '<a href="#php_conf_info">', '</a>')
    );
    // As ini_set is available, use it for PDO error reporting
    ini_set('display_errors', '1');
} else {
    $php_properties['ini_set'] = array(
        'fitness' => tra('unsure'),
        'fitness_status' => FITNESS_STATUS_UNSURE,
        'setting' => 'Disabled',
        'message' => tr('%0 is used in some places to accommodate special needs of some Tiki features. Check disable_functions in your php.ini. %1 How to change this value %2', 'ini_set', '<a href="#php_conf_info">', '</a>')
    );
}

// First things first
// If we don't have a DB-connection, some tests don't run
$s = extension_loaded('pdo_mysql');
if ($s) {
    $php_properties['DB Driver'] = array(
        'fitness' => tra('good'),
        'fitness_status' => FITNESS_STATUS_GOOD,
        'setting' => 'PDO',
        'message' => tra('The PDO extension is the suggested database driver/abstraction layer.')
    );
} elseif ($s = extension_loaded('mysqli')) {
    $php_properties['DB Driver'] = array(
        'fitness' => tra('unsure'),
        'fitness_status' => FITNESS_STATUS_UNSURE,
        'setting' => 'MySQLi',
        'message' => tra('The recommended PDO database driver/abstraction layer cannot be found. The MySQLi driver is available.')
    );
} elseif (extension_loaded('mysql')) {
    $php_properties['DB Driver'] = array(
        'fitness' => tra('unsure'),
        'fitness_status' => FITNESS_STATUS_UNSURE,
        'setting' => 'MySQL',
        'message' => tra('The recommended PDO database driver/abstraction layer cannot be found. The MySQL driver is available.')
    );
} else {
    $php_properties['DB Driver'] = array(
        'fitness' => tra('bad'),
        'fitness_status' => FITNESS_STATUS_BAD,
        'setting' => 'Not available',
        'message' => tra('None of the supported database drivers (PDO/mysqli/mysql) is loaded. This prevents Tiki from functioning.')
    );
}

// Now connect to the DB and make all our connectivity methods work the same
$connection = false;
if ($standalone && ! $locked) {
    if (empty($_POST['dbhost']) && ! ($php_properties['DB Driver']['setting'] == 'Not available')) {
            $render .= <<<DBC
<h2>Database credentials</h2>
Couldn't connect to database, please provide valid credentials.
<form method="post" action="{$_SERVER['SCRIPT_NAME']}">
    <div class="tiki-form-group mt-3">
        <label for="dbhost">Database host</label>
        <input class="form-control" type="text" id="dbhost" name="dbhost" value="localhost" />
    </div>
    <div class="tiki-form-group">
        <label for="dbuser">Database username</label>
        <input class="form-control" type="text" id="dbuser" name="dbuser" />
    </div>
    <div class="tiki-form-group">
        <label for="dbpass">Database password</label>
        <input class="form-control" type="password" id="dbpass" name="dbpass" />
    </div>
    <div class="tiki-form-group">
        <input type="submit" class="btn btn-primary btn-sm" value=" Connect " />
    </div>
</form>
DBC;
    } else {
        try {
            switch ($php_properties['DB Driver']['setting']) {
                case 'PDO':
                    // We don't do exception handling here to be PHP 4 compatible
                    $connection = new PDO('mysql:host=' . $_POST['dbhost'], $_POST['dbuser'], $_POST['dbpass']);
                    /**
                      * @param $query
                       * @param $connection
                       * @return mixed
                      */
                    function query($query, $connection)
                    {
                        $result = $connection->query($query);
                        $return = $result->fetchAll();
                        return($return);
                    }
                    break;
                case 'MySQLi':
                    $error = false;
                    $connection = new mysqli($_POST['dbhost'], $_POST['dbuser'], $_POST['dbpass']);
                    $error = mysqli_connect_error();
                    if (! empty($error)) {
                        $connection = false;
                        $render .= 'Couldn\'t connect to database: ' . htmlspecialchars($error);
                    }
                    /**
                     * @param $query
                     * @param $connection
                     * @return array
                     */
                    function query($query, $connection)
                    {
                        $result = $connection->query($query);
                        $return = array();
                        while ($row = $result->fetch_assoc()) {
                            $return[] = $row;
                        }
                        return($return);
                    }
                    break;
                default:
                    throw new Exception('Unsupported database driver.');
            }
        } catch (Exception $e) {
            $render .= 'Cannot connect to MySQL. Error: ' . htmlspecialchars($e->getMessage());
        }
    }
} else {
    /**
      * @param $query
      * @return array
      */
    function query($query)
    {
        global $tikilib;
        $result = $tikilib->query($query);
        $return = array();
        while ($row = $result->fetchRow()) {
            $return[] = $row;
        }
        return($return);
    }
}

// Basic Server environment
$server_information['Operating System'] = array(
    'value' => PHP_OS,
);

if (PHP_OS == 'Linux' && function_exists('exec')) {
    exec('lsb_release -d', $output, $retval);
    if ($retval == 0) {
        $server_information['Release'] = array(
            'value' => str_replace('Description:', '', $output[0])
        );
        # Check for FreeType fails without a font, i.e. standalone mode
        # Using a URL as font source doesn't work on all PHP installs
        # So let's try to gracefully fall back to some locally installed font at least on Linux
        if (! file_exists($font)) {
            $font = exec('find /usr/share/fonts/ -type f -name "*.ttf" | head -n 1', $output);
        }
    } else {
        $server_information['Release'] = array(
            'value' => tra('N/A')
        );
    }
}

$server_information['Web Server'] = array(
    'value' => $_SERVER['SERVER_SOFTWARE']
);

$server_information['Server Signature']['value'] = ! empty($_SERVER['SERVER_SIGNATURE']) ? $_SERVER['SERVER_SIGNATURE'] : 'off';

// Free disk space
if (function_exists('disk_free_space')) {
    $bytes = @disk_free_space('.');    // this can fail on 32 bit systems with lots of disc space so suppress the possible warning
    $si_prefix = array( 'B', 'KB', 'MB', 'GB', 'TB', 'EB', 'ZB', 'YB' );
    $base = 1024;
    $class = min((int) log($bytes, $base), count($si_prefix) - 1);
    $free_space = sprintf('%1.2f', $bytes / pow($base, $class)) . ' ' . $si_prefix[$class];
    if ($bytes === false) {
        $server_properties['Disk Space'] = array(
            'fitness' => tra('unsure'),
            'fitness_status' => FITNESS_STATUS_UNSURE,
            'setting' => 'Unable to detect',
            'message' => tra('Cannot determine the size of this disk drive.')
        );
    } elseif ($bytes < 200 * 1024 * 1024) {
        $server_properties['Disk Space'] = array(
            'fitness' => tra('bad'),
            'fitness_status' => FITNESS_STATUS_BAD,
            'setting' => $free_space,
            'message' => tr('Less than %0 of free disk space is available. Tiki will not fit in this amount of disk space.', '200MB')
        );
    } elseif ($bytes < 250 * 1024 * 1024) {
        $server_properties['Disk Space'] = array(
            'fitness' => tra('unsure'),
            'fitness_status' => FITNESS_STATUS_UNSURE,
            'setting' => $free_space,
            'message' => tr('Less than %0 of free disk space is available. This would be quite tight for a Tiki installation. Tiki needs disk space for compiled templates and uploaded files. When the disk space is filled, users, including administrators, will not be able to log in to Tiki. This test cannot reliably check for quotas, so be warned that if this server makes use of them, there might be less disk space available than reported.', '250MB')
        );
    } else {
        $server_properties['Disk Space'] = array(
            'fitness' => tra('good'),
            'fitness_status' => FITNESS_STATUS_GOOD,
            'setting' => $free_space,
            'message' => tr('More than %0 of free disk space is available. Tiki will run smoothly, but there may be issues when the site grows (because of file uploads, for example). When the disk space is filled, users, including administrators, will not be able to log in to Tiki. This test cannot reliably check for quotas, so be warned that if this server makes use of them, there might be less disk space available than reported.', '251MB')
        );
    }
} else {
        $server_properties['Disk Space'] = array(
            'fitness' => tra('N/A'),
            'fitness_status' => FITNESS_STATUS_NA,
            'setting' => 'N/A',
            'message' => tr('The PHP function %0 is not available on your server, so the amount of available disk space can\'t be checked for.', 'disk_free_space')
        );
}

if (! $standalone) {
    $tikiWikiVersion = new TWVersion();
    $tikiBaseVersion = $tikiWikiVersion->getBaseVersion();
}

/**
 * @param string $tikiBaseVersion
 * @param string $min The first minimum value in bounds, for example 15.0 if support 15.x or newer
 * @param string $max The first value out of bounds, for example 16.0 if only support up to 15.x
 *
 * @return bool
 */
function isVersionInRange($version, $min, $max)
{
    return version_compare($version, $min, '>=')
        && version_compare($version, $max, '<');
}
$tikiRequirements = getTikiRequirements();

$minCompatibleTikiVersion = null;
$maxCompatibleTikiVersion = null;
foreach ($tikiRequirements as $requirement) {
    if (isVersionInRange(PHP_VERSION, $requirement['php']['min'], $requirement['php']['max'])) {
        //Remember the list is sorted from most recent to oldest
        $minCompatibleTikiVersion = $requirement['version'] ?: $minCompatibleTikiVersion;
        $maxCompatibleTikiVersion = $maxCompatibleTikiVersion ?: $requirement['version'];
    }
}
$php_properties['PHP version'] = array(
    'fitness' => ($minCompatibleTikiVersion && $maxCompatibleTikiVersion) ? tra('good') : tra('bad'),
    'setting' => PHP_VERSION,
    'fitness_status' => ($minCompatibleTikiVersion && $maxCompatibleTikiVersion) ? FITNESS_STATUS_GOOD : FITNESS_STATUS_BAD,
    'message' => tr("Tiki %0.x to Tiki %1.x will work fine on this version of PHP. Please see http://doc.tiki.org/Requirements for details.", $minCompatibleTikiVersion, $maxCompatibleTikiVersion)
);

// Check PHP command line version
if (function_exists('exec')) {
    $cliSearchList = array('php', 'php56', 'php5.6', 'php5.6-cli');
    $isUnix = ! str_starts_with(strtoupper(PHP_OS), 'WIN');
    if ($isUnix) {
        // add virtualmin per-domain php configurations
        array_unshift($cliSearchList, __DIR__ . '/bin/php');
        array_unshift($cliSearchList, __DIR__ . '/../bin/php');
    }
    $cliCommand = '';
    $cliVersion = '';
    foreach ($cliSearchList as $command) {
        if ($isUnix) {
            $output = exec('command -v ' . escapeshellarg($command) . ' 2>/dev/null');
        } else {
            $output = exec('where ' . escapeshellarg($command . '.exe'));
        }
        if (! $output) {
            continue;
        }

        $cliCommand = trim($output);
        exec(escapeshellcmd(trim($cliCommand)) . ' --version', $output);
        foreach ($output as $line) {
            $parts = explode(' ', $line);
            if ($parts[0] === 'PHP') {
                $cliVersion = $parts[1];
                break;
            }
        }
        break;
    }
    if ($cliCommand) {
        if (PHP_VERSION == $cliVersion) {
            $php_properties['PHP CLI version'] = array(
                'fitness' => tra('good'),
                'fitness_status' => FITNESS_STATUS_GOOD,
                'setting' => $cliVersion,
                'message' => tr('The version of the command line executable of PHP (%0) is the same version as the web server version.', $cliCommand),
            );
        } else {
            $php_properties['PHP CLI version'] = array(
                'fitness' => tra('unsure'),
                'fitness_status' => FITNESS_STATUS_UNSURE,
                'setting' => $cliVersion,
                'message' => tr('The version of the command line executable of PHP (%0) is not the same as the web server version.', $cliCommand),
            );
        }
    } else {
        $php_properties['PHP CLI version'] = array(
            'fitness' => tra('unsure'),
            'fitness_status' => FITNESS_STATUS_UNSURE,
            'setting' => '',
            'message' => tra('Unable to determine the command line executable for PHP.'),
        );
    }
}

// PHP Server API (SAPI)
if (str_starts_with(PHP_SAPI, 'cgi')) {
    $php_properties['PHP Server API'] = array(
        'fitness' => tra('info'),
        'fitness_status' => FITNESS_STATUS_INFO,
        'setting' => PHP_SAPI,
        'message' => tra('PHP is being run as CGI. Feel free to use a threaded Apache MPM to increase performance.')
    );

    $php_sapi_info = array(
        'message' => tra('Looks like you are running PHP as FPM/CGI/FastCGI, you may be able to override some of your PHP configurations by add them to .user.ini files, see:'),
        'link' => 'http://php.net/manual/en/configuration.file.per-user.php'
    );
} elseif (str_starts_with(PHP_SAPI, 'fpm')) {
    $php_properties['PHP Server API'] = array(
        'fitness' => tra('info'),
        'fitness_status' => FITNESS_STATUS_INFO,
        'setting' => PHP_SAPI,
        'message' => tra('PHP is being run using FPM (Fastcgi Process Manager). Feel free to use a threaded Apache MPM to increase performance.')
    );

    $php_sapi_info = array(
        'message' => tra('Looks like you are running PHP as FPM/CGI/FastCGI, you may be able to override some of your PHP configurations by add them to .user.ini files, see:'),
        'link' => 'http://php.net/manual/en/configuration.file.per-user.php'
    );
} else {
    if (str_starts_with(PHP_SAPI, 'apache')) {
        $php_sapi_info = array(
            'message' => tra('Looks like you are running PHP as a module in Apache, you may be able to override some of your PHP configurations by add them to .htaccess files, see:'),
            'link' => 'http://php.net/manual/en/configuration.changes.php#configuration.changes.apache'
        );
    }

    $php_properties['PHP Server API'] = array(
        'fitness' => tra('info'),
        'fitness_status' => FITNESS_STATUS_INFO,
        'setting' => PHP_SAPI,
        'message' => tra('PHP is not being run as CGI. Be aware that PHP is not thread-safe and you should not use a threaded Apache MPM (like worker).')
    );
}

// ByteCode Cache
if (function_exists('opcache_get_configuration') && (ini_get('opcache.enable') == 1 || ini_get('opcache.enable') == '1')) {
    $message = tra('OPcache is being used as the ByteCode Cache, which increases performance if correctly configured. See Admin->Performance in the Tiki for more details.');
    $fitness = tra('good');
    $fitness_status = FITNESS_STATUS_GOOD;
    if (! checkOPcacheCompatibility()) {
        $message = tra('Some PHP versions may exhibit randomly issues with the OPcache leading to the server starting to fail to serve all PHP requests, your PHP version seems to
         be affected, despite the performance penalty, we would recommend disabling the OPcache if you experience random crashes.');
        $fitness = tra('unsure');
        $fitness_status = FITNESS_STATUS_UNSURE;
    }
    $php_properties['ByteCode Cache'] = array(
        'fitness' => $fitness,
        'fitness_status' => $fitness_status,
        'setting' => 'OPcache',
        'message' => $message
    );
} elseif (function_exists('wincache_fcache_fileinfo')) {
    // Determine if version 1 or 2 is used. Version 2 does not support ocache

    if (function_exists('wincache_ocache_fileinfo')) {
        // Wincache version 1
        if (ini_get('wincache.ocenabled') == '1') {
            if (PHP_SAPI == 'cgi-fcgi') {
                $php_properties['ByteCode Cache'] = array(
                    'fitness' => tra('good'),
                    'fitness_status' => FITNESS_STATUS_GOOD,
                    'setting' => 'WinCache',
                    'message' => tra('WinCache is being used as the ByteCode Cache, which increases performance if correctly configured. See Admin->Performance in the Tiki for more details.')
                );
            } else {
                $php_properties['ByteCode Cache'] = array(
                    'fitness' => tra('unsure'),
                    'fitness_status' => FITNESS_STATUS_UNSURE,
                    'setting' => 'WinCache',
                    'message' => tra('WinCache is being used as the ByteCode Cache, but the required CGI/FastCGI server API is apparently not being used.')
                );
            }
        } else {
            no_cache_found();
        }
    } else {
        // Wincache version 2 or higher
        if (ini_get('wincache.fcenabled') == '1') {
            if (PHP_SAPI == 'cgi-fcgi') {
                $php_properties['ByteCode Cache'] = array(
                    'fitness' => tra('info'),
                    'fitness_status' => FITNESS_STATUS_INFO,
                    'setting' => 'WinCache',
                    'message' => tr('WinCache version 2 or higher is being used as the FileCache. It does not support a ByteCode Cache. It is recommended to use Zend opcode cache as the ByteCode Cache.')
                );
            } else {
                $php_properties['ByteCode Cache'] = array(
                    'fitness' => tra('unsure'),
                    'fitness_status' => FITNESS_STATUS_UNSURE,
                    'setting' => 'WinCache',
                    'message' => tr('WinCache version 2 or higher is being used as the FileCache, but the required CGI/FastCGI server API is apparently not being used. It is recommended to use Zend opcode cache as the ByteCode Cache.')
                );
            }
        } else {
            no_cache_found();
        }
    }
} else {
    no_cache_found();
}


// memory_limit
$memory_limit = ini_get('memory_limit');
$s = trim($memory_limit);
$last = strtolower(substr($s, -1));
if (! is_numeric($last)) {
    $s = substr($s, 0, -1);
}
switch ($last) {
    case 'g':
        $s *= 1024;
        // no break
    case 'm':
        $s *= 1024;
        // no break
    case 'k':
        $s *= 1024;
}
if ($s >= 160 * 1024 * 1024) {
    $php_properties['memory_limit'] = array(
        'fitness' => tra('good'),
        'fitness_status' => FITNESS_STATUS_GOOD,
        'setting' => $memory_limit,
        'message' => tr('The %0 is at %1. This is known to support smooth functioning even for bigger sites. %2 More info about %0 %3', 'memory_limit', $memory_limit, '<a href="#php_conf_info">', '</a>')
    );
} elseif ($s < 160 * 1024 * 1024 && $s > 127 * 1024 * 1024) {
    $php_properties['memory_limit'] = array(
        'fitness' => tra('unsure') ,
        'fitness_status' => FITNESS_STATUS_UNSURE,
        'setting' => $memory_limit,
        'message' => tr('The %0 is at %1. This will normally work, but the site might run into problems when it grows. %2 How to change this value %3', 'memory_limit', $memory_limit, '<a href="#php_conf_info">', '</a>')
    );
} elseif ($s == -1) {
    $php_properties['memory_limit'] = array(
        'fitness' => tra('unsure') ,
        'fitness_status' => FITNESS_STATUS_UNSURE,
        'setting' => $memory_limit,
        'message' => tr("The %0 is unlimited. This is not necessarily bad, but it's a good idea to limit this on productions servers in order to eliminate unexpectedly greedy scripts. %1 How to change this value %2", 'memory_limit', '<a href="#php_conf_info">', '</a>')
    );
} else {
    $php_properties['memory_limit'] = array(
        'fitness' => tra('bad'),
        'fitness_status' => FITNESS_STATUS_BAD,
        'setting' => $memory_limit,
        'message' => tr('Your %0 is at %1. This is known to cause issues! The %0 should be increased to at least %2, which is the PHP default. %3 How to change this value %4', 'memory_limit', $memory_limit, '128M', '<a href="#php_conf_info">', '</a>')
    );
}

// session.save_handler
$s = ini_get('session.save_handler');
if ($s != 'files') {
    $php_properties['session.save_handler'] = array(
        'fitness' => tra('unsure'),
        'fitness_status' => FITNESS_STATUS_UNSURE,
        'setting' => $s,
        'message' => tr('The %0 should be set to \'files\'. %1 How to change this value %2', 'session.save_handler', '<a href="#php_conf_info">', '</a>')
    );
} else {
    $php_properties['session.save_handler'] = array(
        'fitness' => tra('good'),
        'fitness_status' => FITNESS_STATUS_GOOD,
        'setting' => $s,
        'message' => tr('Well set! The default setting of \'files\' is recommended for Tiki. %0 More info about this value %1', '<a href="#php_conf_info">', '</a>')
    );
}

// session.save_path
$s = ini_get('session.save_path');
if ($php_properties['session.save_handler']['setting'] == 'files') {
    $writableSessionPath = false;
    $currentSession = session_id();
    session_write_close();

    $newSession = session_create_id('tikicheck');
    session_id($newSession);
    session_start();
    $_SESSION['tikisession'] = 'tikisession';
    session_write_close();

    session_id($newSession);
    session_start();
    $writableSessionPath = isset($_SESSION['tikisession']) && count($_SESSION) === 1 && $_SESSION['tikisession'] === 'tikisession';
    session_write_close();

    session_id($currentSession);
    session_start();

    if (! $writableSessionPath) {
        $php_properties['session.save_path'] = array(
            'fitness' => tra('bad'),
            'fitness_status' => FITNESS_STATUS_BAD,
            'setting' => $s,
            'message' => tr("The %0 must be writable. %1 How to change this value %2", 'session.save_path', '<a href="#php_conf_info">', '</a>') .
                        '<br><strong>' . tra('Alternative solution:') . '</strong> ' .
                        tr("Consider using database session storage instead. Navigate to Admin → General Settings, switch to 'Advanced' mode, look for the 'Sessions' group, and set 'Session storage location' to 'Database'. This will store sessions in the database instead of the filesystem. %0 Go to General Settings %1", '<a href="tiki-admin.php?page=general#contentadmin_general-2">', '</a>')
        );
    } else {
        $php_properties['session.save_path'] = array(
            'fitness' => tra('good'),
            'fitness_status' => FITNESS_STATUS_GOOD,
            'setting' => $s,
            'message' => tr("The %0 is writable. %1 More info about %0 %2", 'session.save_path', '<a href="#php_conf_info">', '</a>')
        );
    }
} else {
    $openDir = ini_get('open_basedir');
    if (! str_contains($openDir, $s) && ! empty($openDir)) {
        $php_properties['session.save_path'] = array(
            'fitness' => tra('unknown'),
            'fitness_status' => FITNESS_STATUS_UNKNOWN,
            'setting' => $s,
            'message' => tr("The %0 can't be checked because %1 is defined. %2 More info about %0 %3", "session.save_path", "open_basedir", '<a href="#php_conf_info">', '</a>')
        );
    } else {
        $php_properties['session.save_path'] = array(
            'fitness' => tra('info'),
            'fitness_status' => FITNESS_STATUS_INFO,
            'setting' => $s,
            'message' => tr("The %0 is writable. It doesn't matter though, since your %1 is not set to 'files'. %2 More info about %0 %3", "session.save_path", "session.save_handler", '<a href="#php_conf_info">', '</a>')
        );
    }
}

$s = ini_get('session.gc_probability');
$php_properties['session.gc_probability'] = array(
    'fitness' => tra('info'),
    'fitness_status' => FITNESS_STATUS_INFO,
    'setting' => $s,
    'message' => tr('In conjunction with %0 is used to manage probability that the gc (garbage collection) routine is started.', 'gc_divisor')
);

$s = ini_get('session.gc_divisor');
$php_properties['session.gc_divisor'] = array(
    'fitness' => tra('info'),
    'fitness_status' => FITNESS_STATUS_INFO,
    'setting' => $s,
    'message' => tr('Coupled with %0 defines the probability that the gc (garbage collection) process is started on every session initialization. The probability is calculated by using %1/%2, e.g. 1/100 means there is a 1% chance that the GC process starts on each request.', 'session.gc_probability', 'gc_probability', 'gc_divisor')
);

$s = ini_get('session.gc_maxlifetime');
$php_properties['session.gc_maxlifetime'] = array(
    'fitness' => tra('info'),
    'fitness_status' => FITNESS_STATUS_INFO,
    'setting' => $s . 's',
    'message' => tr('Specifies the number of seconds after which data will be seen as \'garbage\' and potentially cleaned up. Garbage collection may occur during session start.')
);

// test session work
@session_start();

if (empty($_SESSION['tiki-check'])) {
    $php_properties['session'] = array(
        'fitness' => tra('unsure'),
        'fitness_status' => FITNESS_STATUS_UNSURE,
        'setting' => tra('empty'),
        'message' => tra('The session is empty. Try reloading the page and, if this message is displayed again, there may be a problem with the server setup.')
    );
    $_SESSION['tiki-check'] = 1;
} else {
    $php_properties['session'] = array(
        'fitness' => tra('good'),
        'fitness_status' => FITNESS_STATUS_GOOD,
        'setting' => 'ok',
        'message' => tra('This appears to work.')
    );
}

// zlib.output_compression
$s = ini_get('zlib.output_compression');
if ($s) {
    $php_properties['zlib.output_compression'] = array(
        'fitness' => tra('info'),
        'fitness_status' => FITNESS_STATUS_INFO,
        'setting' => 'On',
        'message' => tr('zlib output compression is turned on. This saves bandwidth. On the other hand, turning it off would reduce CPU usage. The appropriate choice can be made for this Tiki. %0 More info about this value %1', '<a href="#php_conf_info">', '</a>')
    );
} else {
    $php_properties['zlib.output_compression'] = array(
        'fitness' => tra('info'),
        'fitness_status' => FITNESS_STATUS_INFO,
        'setting' => 'Off',
        'message' => tr('zlib output compression is turned off. This reduces CPU usage. On the other hand, turning it on would save bandwidth. The appropriate choice can be made for this Tiki. %0 More info about this value %1', '<a href="#php_conf_info">', '</a>')
    );
}

// default_charset
$s = ini_get('default_charset');
if (strtolower($s) == 'utf-8') {
    $php_properties['default_charset'] = array(
        'fitness' => tra('good'),
        'fitness_status' => FITNESS_STATUS_GOOD,
        'setting' => $s,
        'message' => tr('Correctly set! Tiki is fully UTF-8 and so should be this installation. %0 More info about this value %1', '<a href="#php_conf_info">', '</a>')
    );
} else {
    $php_properties['default_charset'] = array(
        'fitness' => tra('unsure'),
        'fitness_status' => FITNESS_STATUS_UNSURE,
        'setting' => $s,
        'message' => tr('default_charset should be UTF-8 as Tiki is fully UTF-8. Please check the php.ini file. %0 How to change this value %1', '<a href="#php_conf_info">', '</a>')
    );
}

// date.timezone
$s = ini_get('date.timezone');
if (empty($s)) {
    $php_properties['date.timezone'] = array(
        'fitness' => tra('unsure'),
        'fitness_status' => FITNESS_STATUS_UNSURE,
        'setting' => $s,
        'message' => tr('No time zone is set! While there are a number of fallbacks in PHP to determine the time zone, the only reliable solution is to set it explicitly in php.ini! Please check the value of %0 in php.ini. %1 How to change this value %2', 'date.timezone', '<a href="#php_conf_info">', '</a>')
    );
} else {
    $php_properties['date.timezone'] = array(
        'fitness' => tra('good'),
        'fitness_status' => FITNESS_STATUS_GOOD,
        'setting' => $s,
        'message' => tr('Well done! Having a time zone set protects the site from related errors. %0 More info about this value %1', '<a href="#php_conf_info">', '</a>')
    );
}

$tempDir = sys_get_temp_dir();
$tmpfile = tempnam($tempDir, 'symfony');

if (! is_writable($tmpfile) || empty($tmpfile)) {
    $php_properties['sys_get_temp_dir'] = array(
        'fitness' => tra('bad'),
        'fitness_status' => FITNESS_STATUS_BAD,
        'setting' => '',
        'message' => tra("Temporary folder is set to $tempDir, but it is not accessible by Tiki.")
    );
} else {
    $php_properties['sys_get_temp_dir'] = array(
        'fitness' => tra('good'),
        'fitness_status' => FITNESS_STATUS_GOOD,
        'setting' => 'Ok',
        'message' => tra('The Temporary is accessible and writable by Tiki.')
    );
}

// file_uploads
$s = ini_get('file_uploads');
if ($s) {
    $php_properties['file_uploads'] = array(
        'fitness' => tra('good'),
        'fitness_status' => FITNESS_STATUS_GOOD,
        'setting' => 'On',
        'message' => tra('Files can be uploaded to Tiki.')
    );
} else {
    $php_properties['file_uploads'] = array(
        'fitness' => tra('bad'),
        'fitness_status' => FITNESS_STATUS_BAD,
        'setting' => 'Off',
        'message' => tra('Files cannot be uploaded to Tiki.')
    );
}

// max_execution_time
$s = ini_get('max_execution_time');
if ($s >= 30 && $s <= 90) {
    $php_properties['max_execution_time'] = array(
        'fitness' => tra('good'),
        'fitness_status' => FITNESS_STATUS_GOOD,
        'setting' => $s . 's',
        'message' => tr('The %0 is at %1. This is a good value for production sites. If timeouts are experienced (such as when performing admin functions) this may need to be increased nevertheless. %2 How to change this value %3', 'max_execution_time', $s, '<a href="#php_conf_info">', '</a>')
    );
} elseif ($s == -1 || $s == 0) {
    $php_properties['max_execution_time'] = array(
        'fitness' => tra('unsure'),
        'fitness_status' => FITNESS_STATUS_UNSURE,
        'setting' => $s . 's',
        'message' => tr('The %0 is unlimited. This is not necessarily bad, but it\'s a good idea to limit this time on productions servers in order to eliminate unexpectedly long running scripts. %1 How to change this value %2', 'max_execution_time', '<a href="#php_conf_info">', '</a>')
    );
} elseif ($s > 90) {
    $php_properties['max_execution_time'] = array(
        'fitness' => tra('unsure'),
        'fitness_status' => FITNESS_STATUS_UNSURE,
        'setting' => $s . 's',
        'message' => tr('The %0 is at %1. This is not necessarily bad, but it\'s a good idea to limit this time on productions servers in order to eliminate unexpectedly long running scripts. %2 How to change this value %3', 'max_execution_time', $s, '<a href="#php_conf_info">', '</a>')
    );
} else {
    $php_properties['max_execution_time'] = array(
        'fitness' => tra('bad'),
        'fitness_status' => FITNESS_STATUS_BAD,
        'setting' => $s . 's',
        'message' => tr('The %0 is at %1. It is likely that some scripts, such as admin functions, will not finish in this time! The %0 should be incresed to at least %2. %3 How to change this value %4', 'max_execution_time', $s, '30s', '<a href="#php_conf_info">', '</a>')
    );
}

// max_input_time
$s = ini_get('max_input_time');
if ($s >= 30 && $s <= 90) {
    $php_properties['max_input_time'] = array(
        'fitness' => tra('good'),
        'fitness_status' => FITNESS_STATUS_GOOD,
        'setting' => $s . 's',
        'message' => tr('The %0 is at %1. This is a good value for production sites. If timeouts are experienced (such as when performing admin functions) this may need to be increased nevertheless. %2 How to change this value %3', 'max_input_time', $s, '<a href="#php_conf_info">', '</a>')
    );
} elseif ($s == -1 || $s == 0) {
    $php_properties['max_input_time'] = array(
        'fitness' => tra('unsure'),
        'fitness_status' => FITNESS_STATUS_UNSURE,
        'setting' => $s . 's',
        'message' => tr('The %0 is unlimited. This is not necessarily bad, but it\'s a good idea to limit this time on productions servers in order to eliminate unexpectedly long running scripts. %1 How to change this value %2', 'max_input_time', '<a href="#php_conf_info">', '</a>')
    );
} elseif ($s > 90) {
    $php_properties['max_input_time'] = array(
        'fitness' => tra('unsure'),
        'fitness_status' => FITNESS_STATUS_UNSURE,
        'setting' => $s . 's',
        'message' => tr('The %0 is at %1. This is not necessarily bad, but it\'s a good idea to limit this time on productions servers in order to eliminate unexpectedly long running scripts. %2 How to change this value %3', 'max_input_time', $s, '<a href="#php_conf_info">', '</a>')
    );
} else {
    $php_properties['max_input_time'] = array(
        'fitness' => tra('bad'),
        'fitness_status' => FITNESS_STATUS_BAD,
        'setting' => $s . 's',
        'message' => tr('The %0 is at %1. It is likely that some scripts, such as admin functions, will not finish in this time! The %0 should be increased to at least %2. %3 How to change this value %4', 'max_input_time', $s, '30 seconds', '<a href="#php_conf_info">', '</a>')
    );
}
// max_file_uploads
$max_file_uploads = ini_get('max_file_uploads');
if ($max_file_uploads) {
    $php_properties['max_file_uploads'] = array(
        'fitness' => tra('info'),
        'fitness_status' => FITNESS_STATUS_INFO,
        'setting' => $max_file_uploads,
        'message' => tr('The %0 is at %1. This is the maximum number of files allowed to be uploaded simultaneously. %2 More info about %0 %3', 'max_file_uploads', $max_file_uploads, '<a href="#php_conf_info">', '</a>')
    );
} else {
    $php_properties['max_file_uploads'] = array(
        'fitness' => tra('info'),
        'fitness_status' => FITNESS_STATUS_INFO,
        'setting' => 'Not Available',
        'message' => tra('The maximum number of files allowed to be uploaded is not available')
    );
}
// upload_max_filesize
$upload_max_filesize = ini_get('upload_max_filesize');
$s = trim($upload_max_filesize);
$last = strtolower(substr($s, -1));
$s = substr($s, 0, -1);
switch ($last) {
    case 'g':
        $s *= 1024;
        // no break
    case 'm':
        $s *= 1024;
        // no break
    case 'k':
        $s *= 1024;
}
if ($s >= 8 * 1024 * 1024) {
    $php_properties['upload_max_filesize'] = array(
        'fitness' => tra('good'),
        'fitness_status' => FITNESS_STATUS_GOOD,
        'setting' => $upload_max_filesize,
        'message' => tr('The %0 is at %1. Quite large files can be uploaded, but keep in mind to set the script timeouts accordingly. %2 More info about %0 %3', 'upload_max_filesize', $upload_max_filesize, '<a href="#php_conf_info">', '</a>')
    );
} elseif ($s == 0) {
    $php_properties['upload_max_filesize'] = array(
        'fitness' => tra('unsure'),
        'fitness_status' => FITNESS_STATUS_UNSURE,
        'setting' => $upload_max_filesize,
        'message' => tr('The %0 is at %1. Upload size is unlimited and this not advised. A user could mistakenly upload a very large file which could fill up the disk. This value should be set to accommodate the realistic needs of the site. %2 How to change this value %3', 'upload_max_filesize', $upload_max_filesize, '<a href="#php_conf_info">', '</a>')
    );
} else {
    $php_properties['upload_max_filesize'] = array(
        'fitness' => tra('unsure'),
        'fitness_status' => FITNESS_STATUS_UNSURE,
        'setting' => $upload_max_filesize,
        'message' => tr('The %0 is at %1. This is not a bad amount, but be sure the level is high enough to accommodate the needs of the site. %2 How to change this value %3', 'upload_max_filesize', $upload_max_filesize, '<a href="#php_conf_info">', '</a>')
    );
}

// post_max_size
$post_max_size = ini_get('post_max_size');
$s = trim($post_max_size);
$last = strtolower(substr($s, -1));
$s = substr($s, 0, -1);
switch ($last) {
    case 'g':
        $s *= 1024;
        // no break
    case 'm':
        $s *= 1024;
        // no break
    case 'k':
        $s *= 1024;
}
if ($s >= 8 * 1024 * 1024) {
    $php_properties['post_max_size'] = array(
        'fitness' => tra('good'),
        'fitness_status' => FITNESS_STATUS_GOOD,
        'setting' => $post_max_size,
        'message' => tr('The %0 is at %1. Quite large files can be uploaded, but keep in mind to set the script timeouts accordingly. %2 More info about %0 %3', 'post_max_size', $post_max_size, '<a href="#php_conf_info">', '</a>')
    );
} else {
    $php_properties['post_max_size'] = array(
        'fitness' => tra('unsure'),
        'fitness_status' => FITNESS_STATUS_UNSURE,
        'setting' => $post_max_size,
        'message' => tr('The %0 is at %1. This is not a bad amount, but be sure the level is high enough to accommodate the needs of the site. %2 How to change this value %3', 'post_max_size', $post_max_size, '<a href="#php_conf_info">', '</a>')
    );
}

// PHP Extensions
// fileinfo
$s = extension_loaded('fileinfo');
if ($s) {
    $php_properties['fileinfo'] = array(
        'fitness' => tra('good'),
        'fitness_status' => FITNESS_STATUS_GOOD,
        'setting' => 'Loaded',
        'message' => tra("The fileinfo extension is needed for the 'Validate uploaded file content' preference.")
    );
} else {
    $php_properties['fileinfo'] = array(
        'fitness' => tra('unsure'),
        'fitness_status' => FITNESS_STATUS_UNSURE,
        'setting' => 'Not available',
        'message' => tra("The fileinfo extension is needed for the 'Validate uploaded file content' preference.")
    );
}

// intl
$s = extension_loaded('intl');
if ($s) {
    $php_properties['intl'] = array(
        'fitness' => tra('good'),
        'fitness_status' => FITNESS_STATUS_GOOD,
        'setting' => 'Loaded',
        'message' => tra("The intl extension is required for Tiki 15 and newer.")
    );
} else {
    $php_properties['intl'] = array(
        'fitness' => tra('unsure'),
        'fitness_status' => FITNESS_STATUS_UNSURE,
        'setting' => 'Not available',
        'message' => tra("The intl extension is preferred for Tiki 15 and newer. While a polyfill is used to emulate some of the features, for better performance and broader language support. It’s recommended that you install the intl extension for PHP, more information on https://www.php.net/intl.")
    );
}

// GD
$s = extension_loaded('gd');
if ($s && function_exists('gd_info')) {
    $gd_info = gd_info();
    $im = $ft = null;
    if (function_exists('imagecreate')) {
        $im = @imagecreate(110, 20);
    }
    if (function_exists('imageftbbox')) {
        $ft = @imageftbbox(12, 0, $font, 'test');
    }
    if ($im && $ft) {
        $php_properties['gd'] = array(
            'fitness' => tra('good'),
            'fitness_status' => FITNESS_STATUS_GOOD,
            'setting' => $gd_info['GD Version'],
            'message' => tra('The GD extension is needed for manipulation of images and for CAPTCHA images.')
        );
    } elseif ($im) {
        $php_properties['gd'] = array(
                'fitness' => tra('unsure'),
                'fitness_status' => FITNESS_STATUS_UNSURE,
                'setting' => $gd_info['GD Version'],
                'message' => tra('The GD extension is loaded, and Tiki can create images, but the FreeType extension is needed for CAPTCHA text generation.')
            );
    } else {
        $php_properties['gd'] = array(
            'fitness' => tra('unsure'),
            'fitness_status' => FITNESS_STATUS_UNSURE,
            'setting' => 'Dysfunctional',
            'message' => tra('The GD extension is loaded, but Tiki is unable to create images. Please check your GD library configuration.')
        );
    }
} else {
    $php_properties['gd'] = array(
        'fitness' => tra('bad'),
        'fitness_status' => FITNESS_STATUS_BAD,
        'setting' => 'Not available',
        'message' => tra('The GD extension is needed for manipulation of images and for CAPTCHA images.')
    );
}

// Image Magick
$s = class_exists('Imagick');
if ($s) {
    $image = new Imagick();
    $image->newImage(100, 100, new ImagickPixel('red'));
    if ($image) {
        $php_properties['Image Magick'] = array(
            'fitness' => tra('good'),
            'fitness_status' => FITNESS_STATUS_GOOD,
            'setting' => 'Available',
            'message' => tra('ImageMagick is used as a fallback in case GD is not available.')
        );
        $image->destroy();
    } else {
        $php_properties['Image Magick'] = array(
            'fitness' => tra('unsure'),
            'fitness_status' => FITNESS_STATUS_UNSURE,
            'setting' => 'Dysfunctional',
            'message' => tr('%0 is used as a fallback in case GD is not available. %0 is available, but unable to create images. Please check your %0 configuration.', 'ImageMagick')
            );
    }
} else {
    $php_properties['Image Magick'] = array(
        'fitness' => tra('info'),
        'fitness_status' => FITNESS_STATUS_INFO,
        'setting' => 'Not Available',
        'message' => tra('ImageMagick is used as a fallback in case GD is not available.')
        );
}

// mbstring
$s = extension_loaded('mbstring');
if ($s) {
    // phpcs:ignore PHPCompatibility.IniDirectives.RemovedIniDirectives.mbstring_func_overloadDeprecatedRemoved -- tiki-check supports also older versions of PHP
    $func_overload = ini_get('mbstring.func_overload');
    if (! function_exists('mb_split')) {
        $php_properties['mbstring'] = array(
            'fitness' => tra('bad'),
            'fitness_status' => FITNESS_STATUS_BAD,
            'setting' => 'Badly installed',
            'message' => tra('mbstring extension is loaded, but missing important functions such as mb_split(). Reinstall it with --enable-mbregex or ask your a server administrator to do it.')
        );
    } elseif ($func_overload !== false || $func_overload > 0) {//Yes, this reads weird.  But in php 8 func_overload no longer exists.  See https://www.php.net/manual/en/mbstring.overload.php
        $php_properties['mbstring'] = array(
            'fitness' => tra('unsure'),
            'fitness_status' => FITNESS_STATUS_UNSURE,
            'setting' => 'Badly configured',
            'message' => tr('mbstring extension is loaded, but %0 = %1. Tiki only works with %0 = 0. Please check the php.ini file.', 'mbstring.func_overload', $func_overload)
            );
    } else {
        $php_properties['mbstring'] = array(
            'fitness' => tra('good'),
            'fitness_status' => FITNESS_STATUS_GOOD,
            'setting' => 'Loaded',
            'message' => tra('mbstring extension is needed for an UTF-8 compatible lower case filter, in the admin search for example.')
        );
    }
} else {
    $php_properties['mbstring'] = array(
        'fitness' => tra('bad'),
        'fitness_status' => FITNESS_STATUS_BAD,
        'setting' => 'Not available',
        'message' => tra('mbstring extension is needed for an UTF-8 compatible lower case filter.')
    );
}

// calendar
$s = extension_loaded('calendar');
if ($s) {
    $php_properties['calendar'] = array(
        'fitness' => tra('good'),
        'fitness_status' => FITNESS_STATUS_GOOD,
        'setting' => 'Loaded',
        'message' => tra('calendar extension is needed by Tiki.')
    );
} else {
    $php_properties['calendar'] = array(
        'fitness' => tra('bad'),
        'fitness_status' => FITNESS_STATUS_BAD,
        'setting' => 'Not available',
        'message' => tr('calendar extension is needed by Tiki. The calendar feature of Tiki will not function without this.')
    );
}

// ctype
$s = extension_loaded('ctype');
if ($s) {
    $php_properties['ctype'] = array(
        'fitness' => tra('good'),
        'fitness_status' => FITNESS_STATUS_GOOD,
        'setting' => 'Loaded',
        'message' => tra('ctype extension is needed by Tiki.')
    );
} else {
    $php_properties['ctype'] = array(
        'fitness' => tra('bad'),
        'fitness_status' => FITNESS_STATUS_BAD,
        'setting' => 'Not available',
        'message' => tra('ctype extension is needed by Tiki.')
    );
}

// libxml
$s = extension_loaded('libxml');
if ($s) {
    $php_properties['libxml'] = array(
        'fitness' => tra('good'),
        'fitness_status' => FITNESS_STATUS_GOOD,
        'setting' => 'Loaded',
        'message' => tra('This extension is needed for the dom extension (see below).')
    );
} else {
    $php_properties['libxml'] = array(
        'fitness' => tra('bad'),
        'fitness_status' => FITNESS_STATUS_BAD,
        'setting' => 'Not available',
        'message' => tra('This extension is needed for the dom extension (see below).')
    );
}

// dom (depends on libxml)
$s = extension_loaded('dom');
if ($s) {
    $php_properties['dom'] = array(
        'fitness' => tra('good'),
        'fitness_status' => FITNESS_STATUS_GOOD,
        'setting' => 'Loaded',
        'message' => tra('This extension is needed by Tiki')
    );
} else {
    $php_properties['dom'] = array(
        'fitness' => tra('bad'),
        'fitness_status' => FITNESS_STATUS_BAD,
        'setting' => 'Not available',
        'message' => tra('This extension is needed by Tiki')
    );
}

$s = extension_loaded('ldap');
if ($s) {
    $php_properties['LDAP'] = array(
        'fitness' => tra('good'),
        'fitness_status' => FITNESS_STATUS_GOOD,
        'setting' => 'Loaded',
        'message' => tra('This extension is needed to connect Tiki to an LDAP server. More info at: http://doc.tiki.org/LDAP ')
    );
} else {
    $php_properties['LDAP'] = array(
        'fitness' => tra('info'),
        'fitness_status' => FITNESS_STATUS_INFO,
        'setting' => 'Not available',
        'message' => tra('Tiki will not be able to connect to an LDAP server as the needed PHP extension is missing. More info at: http://doc.tiki.org/LDAP')
    );
}

$s = extension_loaded('memcached');
if ($s) {
    $php_properties['memcached'] = array(
        'fitness' => tra('good'),
        'fitness_status' => FITNESS_STATUS_GOOD,
        'setting' => 'Loaded',
        'message' => tra('This extension can be used to speed up Tiki by saving sessions as well as wiki and forum data on a memcached server.')
    );
} else {
    $php_properties['memcached'] = array(
        'fitness' => tra('info'),
        'fitness_status' => FITNESS_STATUS_INFO,
        'setting' => 'Not available',
        'message' => tra('This extension can be used to speed up Tiki by saving sessions as well as wiki and forum data on a memcached server.')
    );
}

$s = extension_loaded('redis');
if ($s) {
    $php_properties['redis'] = array(
        'fitness' => tra('good'),
        'fitness_status' => FITNESS_STATUS_GOOD,
        'setting' => 'Loaded',
        'message' => tra('This extension can be used to speed up Tiki by saving wiki and forum data on a redis server.')
    );
} else {
    $php_properties['redis'] = array(
        'fitness' => tra('info'),
        'fitness_status' => FITNESS_STATUS_INFO,
        'setting' => 'Not available',
        'message' => tra('This extension can be used to speed up Tiki by saving wiki and forum data on a redis server.')
    );
}

$s = extension_loaded('soap');
if ($s) {
    $php_properties['soap'] = array(
        'fitness' => tra('good'),
        'fitness_status' => FITNESS_STATUS_GOOD,
        'setting' => 'Loaded',
        'message' => tra('This extension is used by Tiki for some types of web services.')
    );
} else {
    $php_properties['soap'] = array(
        'fitness' => tra('info'),
        'fitness_status' => FITNESS_STATUS_INFO,
        'setting' => 'Not available',
        'message' => tra('This extension is used by Tiki for some types of web services.')
    );
}

$s = extension_loaded('curl');
if ($s) {
    $php_properties['curl'] = array(
        'fitness' => tra('good'),
        'fitness_status' => FITNESS_STATUS_GOOD,
        'setting' => 'Loaded',
        'message' => tra('This extension is required for H5P.')
    );
} else {
    $php_properties['curl'] = array(
        'fitness' => tra('bad'),
        'fitness_status' => FITNESS_STATUS_BAD,
        'setting' => 'Not available',
        'message' => tra('This extension is required for H5P.')
    );
}

$s = extension_loaded('json');
if ($s) {
    $php_properties['json'] = array(
        'fitness' => tra('good'),
        'fitness_status' => FITNESS_STATUS_GOOD,
        'setting' => 'Loaded',
        'message' => tra('This extension is required for many features in Tiki.')
    );
} else {
    $php_properties['json'] = array(
        'fitness' => tra('bad'),
        'fitness_status' => FITNESS_STATUS_BAD,
        'setting' => 'Not available',
        'message' => tra('This extension is required for many features in Tiki.')
    );
}

$s = extension_loaded('tidy');
if ($s) {
    $php_properties['tidy'] = array(
        'fitness' => tra('good'),
        'fitness_status' => FITNESS_STATUS_GOOD,
        'setting' => 'Loaded',
        'message' => tra('This extension is required by Tiki PdfGenerator for parsing an html document stored in a string.')
    );
} else {
    $php_properties['tidy'] = array(
        'fitness' => tra('bad'),
        'fitness_status' => FITNESS_STATUS_BAD,
        'setting' => 'Not available',
        'message' => tra('This extension is required by Tiki PdfGenerator for parsing an html document stored in a string.')
    );
}

$s = extension_loaded('sodium');
$msg = tr('This extension is required to encrypt data such as CSRF ticket cookie and user data. %0 Enable safe, encrypted storage of data such as passwords. Since Tiki 22, Sodium lib (included in PHP 7.2 core) is used for the User Encryption feature and improves encryption in other features, when available', PHP_EOL);
if ($s) {
    $php_properties['sodium'] = array(
        'fitness' => tra('good'),
        'fitness_status' => FITNESS_STATUS_GOOD,
        'setting' => 'Loaded',
        'message' => $msg
    );
} else {
    $php_properties['sodium'] = array(
        'fitness' => tra('unsure'),
        'fitness_status' => FITNESS_STATUS_UNSURE,
        'setting' => 'Not available',
        'message' => $msg
    );
}

$s = extension_loaded('openssl');
if (! $standalone) {
    $msg = tr('Enable safe, encrypted storage of data such as passwords. Tiki 21 and earlier versions, require OpenSSL for the User Encryption feature and improves encryption in other features, when available. Tiki still uses OpenSSL to decrypt user data encrypted with OpenSSL, when converting that data to Sodium (PHP 7.2+). Please check the \'User Data Encryption\' section to see if there is user data encrypted with OpenSSL.');
} else {
    $msg = tr('Enable safe, encrypted storage of data such as passwords. Tiki 21 and earlier versions, require OpenSSL for the User Encryption feature and improves encryption in other features, when available.');
}
if ($s) {
    $php_properties['openssl'] = array(
        'fitness' => tra('good'),
        'fitness_status' => FITNESS_STATUS_GOOD,
        'setting' => 'Loaded',
        'message' => $msg
    );
} else {
    $php_properties['openssl'] = array(
        'fitness' => tra('unsure'),
        'fitness_status' => FITNESS_STATUS_UNSURE,
        'setting' => 'Not available',
        'message' => $msg
    );
}


$s = extension_loaded('mcrypt');
if (! $standalone) {
    $msg = tr('MCrypt is abandonware and is being phased out. Starting in version 18 up to 21, Tiki uses OpenSSL where it previously used MCrypt, except perhaps via third-party libraries. Tiki still uses MCrypt to decrypt user data encrypted with MCrypt, when converting that data to OpenSSL. Please check the \'User Data Encryption\' section to see if there is user data encrypted with MCrypt.');
} else {
    $msg = tr('MCrypt is abandonware and is being phased out. Starting in version 18 up to 21, Tiki uses OpenSSL where it previously used MCrypt, except perhaps via third-party libraries.');
}
if ($s) {
    $php_properties['mcrypt'] = array(
        'fitness' => tra('info'),
        'fitness_status' => FITNESS_STATUS_INFO,
        'setting' => 'Loaded',
        'message' => $msg
    );
} else {
    $php_properties['mcrypt'] = array(
        'fitness' => tra('info'),
        'fitness_status' => FITNESS_STATUS_INFO,
        'setting' => 'Not available',
        'message' => $msg
    );
}


if (! $standalone) {
    // check Zend captcha will work which depends on \Laminas\Math\Rand
    $captcha = new Laminas\Captcha\Dumb();
    $math_random = array(
        'fitness' => tra('good'),
        'fitness_status' => FITNESS_STATUS_GOOD,
        'setting' => 'Available',
        'message' => tra('Ability to generate random numbers, useful for example for CAPTCHA and other security features.'),
    );
    try {
        $captchaId = $captcha->getId();    // simple test for missing random generator
    } catch (Throwable $e) {
        $math_random['fitness'] = tra('unsure');
        $math_random['fitness_status'] = FITNESS_STATUS_UNSURE;
        $math_random['setting'] = 'Not available';
    }
    $php_properties['Random Bytes'] = $math_random;
}


$s = extension_loaded('iconv');
$msg = tra('This extension is required and used frequently in validation functions invoked within Zend Framework.');
if ($s) {
    $php_properties['iconv'] = array(
        'fitness' => tra('good'),
        'fitness_status' => FITNESS_STATUS_GOOD,
        'setting' => 'Loaded',
        'message' => $msg
    );
} else {
    $php_properties['iconv'] = array(
        'fitness' => tra('bad'),
        'fitness_status' => FITNESS_STATUS_BAD,
        'setting' => 'Not available',
        'message' => $msg
    );
}

// Check for existence of eval()
// eval() is a language construct and not a function
// so function_exists() doesn't work
$s = eval('return 42;');
if ($s == 42) {
    $php_properties['eval()'] = array(
        'fitness' => tra('good'),
        'fitness_status' => FITNESS_STATUS_GOOD,
        'setting' => 'Available',
        'message' => tr('The eval() function is required by the Smarty templating engine.')
    );
} else {
    $php_properties['eval()'] = array(
        'fitness' => tra('bad'),
        'fitness_status' => FITNESS_STATUS_BAD,
        'setting' => 'Not available',
        'message' => tr('The eval() function is required by the Smarty templating engine. You will get \"Please contact support about\" messages instead of modules. eval() is most probably disabled via Suhosin.')
    );
}

// Zip Archive class
$s = class_exists('ZipArchive');
if ($s) {
    $php_properties['ZipArchive class'] = array(
        'fitness' => tra('good'),
        'fitness_status' => FITNESS_STATUS_GOOD,
        'setting' => 'Available',
        'message' => tra('The ZipArchive class is needed for features such as XML Wiki Import/Export and PluginArchiveBuilder.')
        );
} else {
    $php_properties['ZipArchive class'] = array(
        'fitness' => tra('unsure'),
        'fitness_status' => FITNESS_STATUS_UNSURE,
        'setting' => 'Not Available',
        'message' => tra('The ZipArchive class is needed for features such as XML Wiki Import/Export and PluginArchiveBuilder.')
        );
}

// DateTime class
$s = class_exists('DateTime');
if ($s) {
    $php_properties['DateTime class'] = array(
        'fitness' => tra('good'),
        'fitness_status' => FITNESS_STATUS_GOOD,
        'setting' => 'Available',
        'message' => tra('The DateTime class is needed for the WebDAV feature.')
        );
} else {
    $php_properties['DateTime class'] = array(
        'fitness' => tra('unsure'),
        'fitness_status' => FITNESS_STATUS_UNSURE,
        'setting' => 'Not Available',
        'message' => tra('The DateTime class is needed for the WebDAV feature.')
        );
}

// Xdebug
$has_xdebug = function_exists('xdebug_get_code_coverage') && is_array(xdebug_get_code_coverage());
if ($has_xdebug) {
    $php_properties['Xdebug'] = array(
        'fitness' => tra('info'),
        'fitness_status' => FITNESS_STATUS_INFO,
        'setting' => 'Loaded',
        'message' => tra('Xdebug can be very handy for a development server, but it might be better to disable it when on a production server.')
    );
} else {
    $php_properties['Xdebug'] = array(
        'fitness' => tra('info'),
        'fitness_status' => FITNESS_STATUS_INFO,
        'setting' => 'Not Available',
        'message' => tra('Xdebug can be very handy for a development server, but it might be better to disable it when on a production server.')
    );
}

// Get MySQL properties and check them
$mysql_properties = array();
$mysql_variables = array();
if ($connection || ! $standalone) {
    // MySQL version
    $query = 'SELECT VERSION();';
    $result = query($query, $connection) ?? array();
    $mysql_version = $result[0]['VERSION()'];
    $isMariaDB = preg_match('/mariadb/i', $mysql_version);
    $minVersion = $isMariaDB ? '5.5' : '5.7';
    $s = version_compare($mysql_version, $minVersion, '>=');
    $mysql_properties['Version'] = array(
        'fitness' => $s ? tra('good') : tra('bad'),
        'fitness_status' => $s ? FITNESS_STATUS_GOOD : FITNESS_STATUS_BAD,
        'setting' => $mysql_version,
        'message' => tr('Tiki requires MariaDB >= %0 or MySQL >= %1', '5.5', '5.7')
    );

    // max_allowed_packet
    $query = "SHOW VARIABLES LIKE 'max_allowed_packet'";
    $result = query($query, $connection);
    $s = $result[0]['Value'];
    $max_allowed_packet = $s / 1024 / 1024;
    if ($s >= 8 * 1024 * 1024) {
        $mysql_properties['max_allowed_packet'] = array(
            'fitness' => tra('good'),
            'fitness_status' => FITNESS_STATUS_GOOD,
            'setting' => $max_allowed_packet . 'M',
            'message' => tr('The %0 setting is at %1. Quite large files can be uploaded, but keep in mind to set the script timeouts accordingly. This limits the size of binary files that can be uploaded to Tiki, when storing files in the database. Please see: %2', 'max_allowed_packet', $max_allowed_packet . 'M', '<a href="http://doc.tiki.org/File-Storage">file storage</a>')
        );
    } else {
        $mysql_properties['max_allowed_packet'] = array(
            'fitness' => tra('unsure'),
            'fitness_status' => FITNESS_STATUS_UNSURE,
            'setting' => $max_allowed_packet . 'M',
            'message' => tr('The %0 setting is at %1. This is not a bad amount, but be sure the level is high enough to accommodate the needs of the site. This limits the size of binary files that can be uploaded to Tiki, when storing files in the database. Please see: %2', 'max_allowed_packet', $max_allowed_packet . 'M', '<a href="http://doc.tiki.org/File-Storage">file storage</a>')
        );
    }

    // UTF-8 MB4 test (required for Tiki19+)
    $query = "SELECT COUNT(*) FROM `information_schema`.`character_sets` WHERE `character_set_name` = 'utf8mb4';";
    $result = query($query, $connection);
    if (! empty($result[0]['COUNT(*)'])) {
        $mysql_properties['utf8mb4'] = array(
            'fitness' => tra('good'),
            'fitness_status' => FITNESS_STATUS_GOOD,
            'setting' => 'available',
            'message' => tr('Your database supports the %0 character set required in %1 and above.', 'utf8mb4', 'Tiki19')
        );
    } else {
        $mysql_properties['utf8mb4'] = array(
            'fitness' => tra('bad'),
            'fitness_status' => FITNESS_STATUS_BAD,
            'setting' => 'not available',
            'message' => tr('Your database does not support the %0 character set required in %1 and above. You need to upgrade your mysql or mariadb installation.', 'utf8mb4', 'Tiki19')
        );
    }

    // UTF-8 Charset
    // Tiki communication is done using UTF-8 MB4 (required for Tiki19+)
    $charset_types = "client connection database results server system";
    foreach (explode(' ', $charset_types) as $type) {
        $query = "SHOW VARIABLES LIKE 'character_set_" . $type . "';";
        $result = query($query, $connection);
        foreach ($result as $value) {
            if ($value['Value'] == 'utf8mb4') {
                $mysql_properties[$value['Variable_name']] = array(
                    'fitness' => tra('good'),
                    'fitness_status' => FITNESS_STATUS_GOOD,
                    'setting' => $value['Value'],
                    'message' => tr('Tiki is fully %0 and so should be every part of the stack.', 'utf8mb4')
                );
            } else {
                $mysql_properties[$value['Variable_name']] = array(
                    'fitness' => tra('unsure'),
                    'fitness_status' => FITNESS_STATUS_UNSURE,
                    'setting' => $value['Value'],
                    'message' => tr('On a fresh install everything should be set to %0 to avoid unexpected results. For further information please see %1 Understanding Encoding %2.', 'utf8mb4', '<a href="http://doc.tiki.org/Understanding-Encoding">', '</a>')
                );
            }
        }
    }
    // UTF-8 is correct for character_set_system
    // Because mysql does not allow any config to change this value, and character_set_system is overwritten by the other character_set_* variables anyway. They may change this default in later versions.
    $query = "SHOW VARIABLES LIKE 'character_set_system';";
    $result = query($query, $connection);
    foreach ($result as $value) {
        if (str_starts_with($value['Value'], 'utf8')) {
            $mysql_properties[$value['Variable_name']] = array(
                'fitness' => tra('good'),
                'fitness_status' => FITNESS_STATUS_GOOD,
                'setting' => $value['Value'],
                'message' => tr('Tiki is fully %0 but some database underlying variables are set to %1 by the database engine and cannot be modified.', 'utf8mb4', 'utf8')
            );
        } else {
            $mysql_properties[$value['Variable_name']] = array(
                'fitness' => tra('unsure'),
                'fitness_status' => FITNESS_STATUS_UNSURE,
                'setting' => $value['Value'],
                'message' => tr('On a fresh install everything should be set to %0 or %1 to avoid unexpected results. For further information please see %2 Understanding Encoding %3.', 'utf8mb4', 'utf8', '<a href="http://doc.tiki.org/Understanding-Encoding">', '</a>')
            );
        }
    }
    // UTF-8 Collation
    $collation_types = "connection database server";
    foreach (explode(' ', $collation_types) as $type) {
        $query = "SHOW VARIABLES LIKE 'collation_" . $type . "';";
        $result = query($query, $connection);
        foreach ($result as $value) {
            if (str_starts_with($value['Value'], 'utf8mb4')) {
                $mysql_properties[$value['Variable_name']] = array(
                    'fitness' => tra('good'),
                    'fitness_status' => FITNESS_STATUS_GOOD,
                    'setting' => $value['Value'],
                    'message' => tr('Tiki is fully %0 and so should be every part of the stack. %1 is the default collation for Tiki.', 'utf8mb4', 'utf8mb4_unicode_ci')
                );
            } else {
                $mysql_properties[$value['Variable_name']] = array(
                    'fitness' => tra('unsure'),
                    'fitness_status' => FITNESS_STATUS_UNSURE,
                    'setting' => $value['Value'],
                    'message' => tr('On a fresh install everything should be set to %0 to avoid unexpected results. %1 is the default collation for Tiki. For further information please see %2 Understanding Encoding %3.', 'utf8mb4', 'utf8mb4_unicode_ci', '<a href="http://doc.tiki.org/Understanding-Encoding">', '</a>')
                );
            }
        }
    }

    // slow_query_log
    $query = "SHOW VARIABLES LIKE 'slow_query_log'";
    $result = query($query, $connection);
    $s = $result[0]['Value'];
    if ($s == 'OFF') {
        $mysql_properties['slow_query_log'] = array(
            'fitness' => tra('info'),
            'fitness_status' => FITNESS_STATUS_INFO,
            'setting' => $s,
            'message' => tra('MySQL doesn\'t log slow queries. If performance issues are noticed, this could be enabled, but keep in mind that the logging itself slows MySQL down.')
        );
    } else {
        $mysql_properties['slow_query_log'] = array(
            'fitness' => tra('info'),
            'fitness_status' => FITNESS_STATUS_INFO,
            'setting' => $s,
            'message' => tra('MySQL logs slow queries. If no performance issues are noticed, this should be disabled on a production site as it slows MySQL down.')
        );
    }

    // MySQL SSL
    $query = 'show variables like "have_ssl";';
    $result = query($query, $connection);
    if (empty($result)) {
        $query = 'show variables like "have_openssl";';
        $result = query($query, $connection);
    }
    $haveMySQLSSL = false;
    if (! empty($result)) {
        $ssl = $result[0]['Value'];
        $haveMySQLSSL = $ssl == 'YES';
    }
    $s = '';
    if ($haveMySQLSSL) {
        $query = 'show status like "Ssl_cipher";';
        $result = query($query, $connection);
        $isSSL = ! empty($result[0]['Value']);
    } else {
        $isSSL = false;
    }
    if ($isSSL) {
        $msg = tra('MySQL SSL connection is active');
        $s = 'ON';
    } elseif ($haveMySQLSSL) {
        $msg = tra('MySQL connection is not encrypted');
        $s = 'OFF';
    } else {
        $msg = tra('MySQL Server does not have SSL activated.');
        $s = 'OFF';
    }
    $fitness = tra('info');
    $fitness_status = FITNESS_STATUS_INFO;
    if ($s === 'ON') {
        $fitness = tra('good');
        $fitness_status = FITNESS_STATUS_GOOD;
    }
    $mysql_properties['SSL connection'] = array(
        'fitness' => $fitness,
        'fitness_status' => $fitness_status,
        'setting' => $s,
        'message' => $msg
    );

    // Strict mode
    $query = 'SELECT @@sql_mode as Value;';
    $result = query($query, $connection);
    $s = '';
    $msg = 'Unable to query strict mode';
    if (! empty($result)) {
        $sql_mode = $result[0]['Value'];
        $modes = explode(',', $sql_mode);

        if (in_array('STRICT_ALL_TABLES', $modes)) {
            $s = 'STRICT_ALL_TABLES';
        }
        if (in_array('STRICT_TRANS_TABLES', $modes)) {
            if (! empty($s)) {
                $s .= ',';
            }
            $s .= 'STRICT_TRANS_TABLES';
        }

        if (! empty($s)) {
            $msg = tra('MySQL is using strict mode');
        } else {
            $msg = tra('MySQL is not using strict mode.');
        }
    }
    $mysql_properties['Strict Mode'] = array(
        'fitness' => tra('info'),
        'fitness_status' => FITNESS_STATUS_INFO,
        'setting' => $s,
        'message' => $msg
    );

    // MySQL Variables
    $query = "SHOW VARIABLES;";
    $result = query($query, $connection) ?? array();
    foreach ($result as $value) {
        $mysql_variables[$value['Variable_name']] = array('value' => $value['Value']);
    }

    if (! $standalone) {
        $mysql_crashed_tables = array();
        // This should give all crashed tables (MyISAM at least) - does need testing though !!
        $query = 'SHOW TABLE STATUS WHERE engine IS NULL AND comment <> "VIEW";';
        $result = query($query, $connection);
        foreach ($result as $value) {
            $mysql_crashed_tables[$value['Name']] = array('Comment' => $value['Comment']);
        }
    }
}

// Apache properties

$apache_properties = false;
if (function_exists('apache_get_version')) {
    // Apache Modules
    $apache_modules = apache_get_modules();

    // mod_rewrite
    $s = false;
    $s = array_search('mod_rewrite', $apache_modules);
    $apache_properties = array();
    if ($s) {
        $apache_properties['mod_rewrite'] = array(
            'setting' => 'Loaded',
            'fitness' => tra('good') ,
            'fitness_status' => FITNESS_STATUS_GOOD,
            'message' => tra('Tiki needs this module for Search Engine Friendly URLs via .htaccess. However, it can\'t be checked if this web server respects configurations made in .htaccess. For further information go to Admin->SefURL in your Tiki.')
        );
    } else {
        $apache_properties['mod_rewrite'] = array(
            'setting' => 'Not available',
            'fitness' => tra('unsure') ,
            'fitness_status' => FITNESS_STATUS_UNSURE,
            'message' => tra('Tiki needs this module for Search Engine Friendly URLs. For further information go to Admin->SefURL in the Tiki.')
        );
    }

    if (! $standalone) {
        // work out if RewriteBase is set up properly
        global $url_path;
        $enabledFileName = '.htaccess';
        if (file_exists($enabledFileName)) {
            $enabledFile = fopen($enabledFileName, "r");
            $rewritebase = '/';
            while ($nextLine = fgets($enabledFile)) {
                if (preg_match('/^RewriteBase\s*(.*)$/', $nextLine, $m)) {
                    $rewritebase = ! str_ends_with($m[1], '/') ? $m[1] . '/' : $m[1];
                    break;
                }
            }
            if ($url_path == $rewritebase) {
                $smarty->assign('rewritebaseSetting', $rewritebase);
                $apache_properties['RewriteBase'] = array(
                    'setting' => $rewritebase,
                    'fitness' => tra('good') ,
                    'fitness_status' => FITNESS_STATUS_GOOD,
                    'message' => tra('RewriteBase is set correctly in .htaccess. Search Engine Friendly URLs should work. Be aware, though, that this test can\'t checked if Apache really loads .htaccess.')
                );
            } else {
                $apache_properties['RewriteBase'] = array(
                    'setting' => $rewritebase,
                    'fitness' => tra('bad') ,
                    'fitness_status' => FITNESS_STATUS_BAD,
                    'message' => tr('RewriteBase is not set correctly in .htaccess. Search Engine Friendly URLs are not going to work with this configuration. It should be set to \"%0\".', substr($url_path, 0, -1))
                );
            }
        } else {
            $apache_properties['RewriteBase'] = array(
                'setting' => 'Not found',
                'fitness' => tra('info'),
                'fitness_status' => FITNESS_STATUS_INFO,
                'message' => tra('The .htaccess file has not been activated, so this check cannot be  performed. To use Search Engine Friendly URLs, activate .htaccess by copying _htaccess into its place (or a symlink if supported by your Operating System). Then do this check again.')
            );
        }
    }

    if ($pos = strpos($_SERVER['REQUEST_URI'], 'tiki-check.php')) {
        $sef_test_protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']) ? 'https://' : 'http://';
        $sef_test_base_url = $sef_test_protocol . $_SERVER['HTTP_HOST'] . substr($_SERVER['REQUEST_URI'], 0, $pos);
        $sef_test_ping_value = mt_rand();
        $sef_test_url = $sef_test_base_url . 'tiki-check?tiki-check-ping=' . $sef_test_ping_value;
        $sef_test_folder_created = false;
        $sef_test_folder_writable = true;
        if ($standalone) {
            $sef_test_path_current = __DIR__;
            $sef_test_dir_name = 'tiki-check-' . $sef_test_ping_value;
            $sef_test_folder = $sef_test_path_current . DIRECTORY_SEPARATOR . $sef_test_dir_name;
            if (is_writable($sef_test_path_current) && ! file_exists($sef_test_folder)) {
                if (mkdir($sef_test_folder)) {
                    $sef_test_folder_created = true;
                    copy(__FILE__, $sef_test_folder . DIRECTORY_SEPARATOR . 'tiki-check.php');
                    file_put_contents($sef_test_folder . DIRECTORY_SEPARATOR . '.htaccess', "<IfModule mod_rewrite.c>\nRewriteEngine On\nRewriteRule tiki-check$ tiki-check.php [L]\n</IfModule>\n");
                    $sef_test_url = $sef_test_base_url . $sef_test_dir_name . '/tiki-check?tiki-check-ping=' . $sef_test_ping_value;
                }
            } else {
                $sef_test_folder_writable = false;
            }
        }

        if (! $sef_test_folder_writable) {
            $apache_properties['SefURL Test'] = array(
            'setting' => 'Not Working',
            'fitness' => tra('info') ,
            'fitness_status' => FITNESS_STATUS_INFO,
            'message' => tra('The automated test could not run. The required files could not be created  on the server to run the test. That may only mean that there were no permissions, but the Apache configuration should be checked. For further information go to Admin->SefURL in the Tiki.')
            );
        } else {
            $pong_value = get_content_from_url($sef_test_url);
            if ($pong_value != 'fail-no-request-done') {
                if ('pong:' . $sef_test_ping_value == $pong_value) {
                    $apache_properties['SefURL Test'] = array(
                        'setting' => 'Working',
                        'fitness' => tra('good') ,
                        'fitness_status' => FITNESS_STATUS_GOOD,
                        'message' => tra('An automated test was done, and the server appears to be configured correctly to handle Search Engine Friendly URLs.')
                    );
                } else {
                    if (strncmp('fail-http-', $pong_value, 10) == 0) {
                        $apache_return_code = substr($pong_value, 10);
                        $apache_properties['SefURL Test'] = array(
                            'setting' => 'Not Working',
                            'fitness' => tra('info') ,
                            'fitness_status' => FITNESS_STATUS_INFO,
                            'message' => sprintf(tr('An automated test was done and, based on the results, the server does not appear to be configured correctly to handle Search Engine Friendly URLs. The server returned an unexpected HTTP code: "%0". This automated test may fail due to the infrastructure setup, but the Apache configuration should be checked. For further information go to Admin->SefURL in your Tiki.', $apache_return_code))
                        );
                    } else {
                        $apache_properties['SefURL Test'] = array(
                            'setting' => 'Not Working',
                            'fitness' => tra('info') ,
                            'fitness_status' => FITNESS_STATUS_INFO,
                            'message' => tra('An automated test was done and, based on the results, the server does not appear to be configured correctly to handle Search Engine Friendly URLs. This automated test may fail due to the infrastructure setup, but the Apache configuration should be checked. For further information go to Admin->SefURL in your Tiki.')
                        );
                    }
                }
            }
        }
        if ($sef_test_folder_created) {
            unlink($sef_test_folder . DIRECTORY_SEPARATOR . 'tiki-check.php');
            unlink($sef_test_folder . DIRECTORY_SEPARATOR . '.htaccess');
            rmdir($sef_test_folder);
        }
    }

    // mod_expires
    $s = false;
    $s = array_search('mod_expires', $apache_modules);
    if ($s) {
        $apache_properties['mod_expires'] = array(
            'setting' => 'Loaded',
            'fitness' => tra('good') ,
            'fitness_status' => FITNESS_STATUS_GOOD,
            'message' => tra('With this module, the HTTP Expires header can be set, which increases performance. It can\'t be checked, though, if mod_expires is configured correctly.')
        );
    } else {
        $apache_properties['mod_expires'] = array(
            'setting' => 'Not available',
            'fitness' => tra('unsure') ,
            'fitness_status' => FITNESS_STATUS_UNSURE,
            'message' => tra('With this module, the HTTP Expires header can be set, which increases performance. Once it is installed, it still needs to be configured correctly.')
        );
    }

    // mod_deflate
    $s = false;
    $s = array_search('mod_deflate', $apache_modules);
    if ($s) {
        $apache_properties['mod_deflate'] = array(
            'setting' => 'Loaded',
            'fitness' => tra('good') ,
            'fitness_status' => FITNESS_STATUS_GOOD,
            'message' => tra('With this module, the data the webserver sends out can be compressed, which reduced data transfer amounts and increases performance. This test can\'t check, though, if mod_deflate is configured correctly.')
        );
    } else {
        $apache_properties['mod_deflate'] = array(
            'setting' => 'Not available',
            'fitness' => tra('unsure') ,
            'fitness_status' => FITNESS_STATUS_UNSURE,
            'message' => tra('With this module, the data the webserver sends out can be compressed, which reduces data transfer amounts and increases performance. Once it is installed, it still needs to be configured correctly.')
        );
    }

    // mod_security
    $s = false;
    $s = array_search('mod_security', $apache_modules);
    if ($s) {
        $apache_properties['mod_security'] = array(
            'setting' => 'Loaded',
            'fitness' => tra('info') ,
            'fitness_status' => FITNESS_STATUS_INFO,
            'message' => tra('This module can increase security of Tiki and therefore the server, but be aware that it is very tricky to configure correctly. A misconfiguration can lead to failed page saves or other hard to trace bugs.')
        );
    } else {
        $apache_properties['mod_security'] = array(
            'setting' => 'Not available',
            'fitness' => tra('info') ,
            'fitness_status' => FITNESS_STATUS_INFO,
            'message' => tra('This module can increase security of Tiki and therefore the server, but be aware that it is very tricky to configure correctly. A misconfiguration can lead to failed page saves or other hard to trace bugs.')
        );
    }

    // Get /server-info, if available
    if (function_exists('curl_init') && function_exists('curl_exec')) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'http://localhost/server-info');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
        $apache_server_info = curl_exec($curl);
        if (curl_getinfo($curl, CURLINFO_HTTP_CODE) == 200) {
            $apache_server_info = preg_replace('%^.*<body>(.*)</body>.*$%ms', '$1', $apache_server_info);
        } else {
            $apache_server_info = false;
        }
    } else {
        $apache_server_info = 'nocurl';
    }
}


// IIS Properties
$iis_properties = false;

if (check_isIIS()) {
    // IIS Rewrite module
    if (check_hasIIS_UrlRewriteModule()) {
        $iis_properties['IIS Url Rewrite Module'] = array(
            'fitness' => tra('good'),
            'fitness_status' => FITNESS_STATUS_GOOD,
            'setting' => 'Available',
            'message' => tra('The URL Rewrite Module is required to use SEFURL on IIS.')
            );
    } else {
        $iis_properties['IIS Url Rewrite Module'] = array(
            'fitness' => tra('bad'),
            'fitness_status' => FITNESS_STATUS_BAD,
            'setting' => 'Not Available',
            'message' => tra('The URL Rewrite Module is required to use SEFURL on IIS.')
            );
    }
}

// Check Tiki Packages
if (! $standalone) {
    global $tikipath, $base_host;

    $composerManager = new ComposerManager($tikipath);
    $installedLibs = $composerManager->getInstalled() ?: array();

    $packagesToCheck = array(
        array(
            'name' => 'jerome-breton/casperjs-installer',
            'commands' => array(
                'python'
            ),
            'preferences' => array(
                'casperjs_path' => array(
                    'name' => tra('casperjs path'),
                    'type' => 'path'
                )
            ),
        ),
        array(
            'name' => 'media-alchemyst/media-alchemyst',
            'preferences' => array(
                'alchemy_ffmpeg_path' => array(
                    'name' => tra('ffmpeg path'),
                    'type' => 'path'
                ),
                'alchemy_ffprobe_path' => array(
                    'name' => tra('ffprobe path'),
                    'type' => 'path'
                ),
                'alchemy_unoconv_path' => array(
                    'name' => tra('unoconv path'),
                    'type' => 'path'
                ),
                'alchemy_gs_path' => array(
                    'name' => tra('ghostscript path'),
                    'type' => 'path'
                ),
                'alchemy_imagine_driver' => array(
                    'name' => tra('Alchemy Image library'),
                    'type' => 'classOptions',
                    'options' => array(
                        'imagick' => array(
                            'name' => tra('Imagemagick'),
                            'classLib' => 'Imagine\Imagick\Imagine',
                            'className' => 'Imagick',
                            'extension' => false
                        ),
                        'gd' => array(
                            'name' => tra('GD'),
                            'classLib' => 'Imagine\Gd\Imagine',
                            'className' => false,
                            'extension' => 'gd'
                        )
                    ),
                ),
            )
        ),
        array(
            'name' => 'tikiwiki/media-alchemyst',
            'preferences' => array(
                'alchemy_ffmpeg_path' => array(
                    'name' => tra('ffmpeg path'),
                    'type' => 'path'
                ),
                'alchemy_ffprobe_path' => array(
                    'name' => tra('ffprobe path'),
                    'type' => 'path'
                ),
                'alchemy_unoconv_path' => array(
                    'name' => tra('unoconv path'),
                    'type' => 'path'
                ),
                'alchemy_gs_path' => array(
                    'name' => tra('ghostscript path'),
                    'type' => 'path'
                ),
                'alchemy_imagine_driver' => array(
                    'name' => tra('Alchemy Image library'),
                    'type' => 'classOptions',
                    'options' => array(
                        'imagick' => array(
                            'name' => tra('Imagemagick'),
                            'classLib' => 'Imagine\Imagick\Imagine',
                            'className' => 'Imagick',
                            'extension' => false
                        ),
                        'gd' => array(
                            'name' => tra('GD'),
                            'classLib' => 'Imagine\Gd\Imagine',
                            'className' => false,
                            'extension' => 'gd'
                        )
                    ),
                ),
            )
        ),
        array(
            'name' => 'php-unoconv/php-unoconv',
            'preferences' => array(
                'alchemy_unoconv_path' => array(
                    'name' => tra('unoconv path'),
                    'type' => 'path'
                )
            )
        ),
        array(
            'name' => 'mpdf/mpdf',
            'urls' => array(
                $base_host . '/tiki-print.php'
            )
        ),
        array(
            'name' => 'tikiwiki/diagram',
            'urls' => array(
                $prefs['fgal_drawio_service_endpoint']
            )
        )
    );

    $packagesToDisplay = array();
    foreach ($installedLibs as $installedPackage) {
        $key = array_search($installedPackage['name'], array_column($packagesToCheck, 'name'));
        if ($key !== false) {
            $messages = array(
                'successes' => array(),
                'warnings' => array()
            );
            if (isset($packagesToCheck[$key]['preferences'])) {
                $preferenceMessages = checkPreferences($packagesToCheck[$key]['preferences']);
                $messages['successes'] = array_merge($messages['successes'], $preferenceMessages['successes']);
                $messages['warnings'] = array_merge($messages['warnings'], $preferenceMessages['warnings']);
            }
            if (isset($packagesToCheck[$key]['commands'])) {
                foreach ($packagesToCheck[$key]['commands'] as $command) {
                    if (! commandIsAvailable($command)) {
                        $messages['warnings'][] = tr("Command '%0' not found, check if it is installed and available.", $command);
                    } else {
                        $messages['successes'][] = tr("Command '%0' found, it is installed and available.", $command);
                    }
                }
            }
            if (isset($packagesToCheck[$key]['urls'])) {
                foreach ($packagesToCheck[$key]['urls'] as $url) {
                    if (! urlIsAvailable($url)) {
                        $messages['warnings'][] = tr("URL '%0' is not reachable, check your firewall or proxy configurations.", $url);
                    } else {
                        $messages['successes'][] = tr("Command '%0' found, it is installed and available.", $command);
                    }
                }
            }

            $messages = checkPackageMessages($messages, $installedPackage);

            $packageInfo = array(
                'name' => $installedPackage['name'],
                'version' => $installedPackage['installed'],
                'status' => count($messages['warnings']) > 0 ? tra('unsure') : tra('good'),
                'fitness_status' => count($messages['warnings']) > 0 ? FITNESS_STATUS_UNSURE : FITNESS_STATUS_GOOD,
                'message' => array_merge($messages['warnings'], $messages['successes'])
            );
        } else {
            $packageInfo = array(
                'name' => $installedPackage['name'],
                'version' => $installedPackage['installed'],
                'status' => tra('good'),
                'fitness_status' => FITNESS_STATUS_GOOD,
                'message' => array()
            );
        }
        $packagesToDisplay[] = $packageInfo;
    }

    /**
     * Tesseract PHP Package Check
     */

    /** @var string The version of Tesseract required */
    $tesseractPkgMinVersion = '2.7.0';
    /** @var string Current Tesseract installed version */
    $ocrVersion = false;
    foreach ($packagesToDisplay as $arrayValue) {
        if ($arrayValue['name'] === 'thiagoalessio/tesseract_ocr') {
            $ocrVersion = $arrayValue['version'];
            break;
        }
    }

    if (! $ocrVersion) {
        $ocrVersion = tra('Not Installed');
        $ocrMessage = tra(
            'Tesseract PHP package could not be found. Try installing through Packages.'
        );
        $ocrStatus = 'bad';
        $fitness_status = FITNESS_STATUS_BAD;
    } elseif (version_compare($ocrVersion, $tesseractPkgMinVersion, '>=')) {
        $ocrMessage = tra('Tesseract PHP dependency installed.');
        $ocrStatus = 'good';
        $fitness_status = FITNESS_STATUS_GOOD;
    } else {
        $ocrMessage = tra(
            'The installed Tesseract version is lower than the required version.'
        );
        $ocrStatus = 'bad';
        $fitness_status = FITNESS_STATUS_BAD;
    }

    $ocrToDisplay = array(array(
                         'name'    => tra('Tesseract package'),
                         'version' => $ocrVersion,
                         'status'  => tra($ocrStatus),
                         'fitness_status' => $fitness_status,
                         'message' => $ocrMessage,
                     ));

    /**
     * Tesseract Binary dependency Check
     */

    $ocr = TikiLib::lib('ocr');
    $langCount = count($ocr->getTesseractLangs());

    if ($langCount >= 5) {
        $ocrMessage = $langCount . ' ' . tra('languages installed.');
        $ocrStatus = 'good';
        $fitness_status = FITNESS_STATUS_GOOD;
    } else {
        $ocrMessage = tra(
            'Not all languages installed. You may need to install additional languages for multilingual support.'
        );
        $ocrStatus = 'unsure';
        $fitness_status = FITNESS_STATUS_UNSURE;
    }

    $ocrToDisplay[] = array(
        'name'    => tra('Tesseract languages'),
        'status'  => tra($ocrStatus),
        'fitness_status' => $fitness_status,
        'message' => $ocrMessage,
    );

    $ocrVersion = $ocr->getTesseractVersion();

    if (! $ocrVersion) {
        $ocrVersion = tra('Not Found');
        $ocrMessage = tra(
            'Tesseract could not be found.'
        );
        $ocrStatus = 'bad';
        $fitness_status = FITNESS_STATUS_BAD;
    } elseif ($ocr->checkTesseractVersion()) {
        $ocrMessage = tra(
            'Tesseract meets or exceeds the version requirements.'
        );
        $ocrStatus = 'good';
        $fitness_status = FITNESS_STATUS_GOOD;
    } else {
        $ocrMessage = tra(
            'The installed Tesseract version is lower than the required version.'
        );
        $ocrStatus = 'bad';
        $fitness_status = FITNESS_STATUS_BAD;
    }

    $ocrToDisplay[] = array(
        'name'    => tra('Tesseract binary'),
        'version' => $ocrVersion,
        'status'  => tra($ocrStatus),
        'fitness_status' => $fitness_status,
        'message' => $ocrMessage,
    );
    try {
        if (empty($prefs['ocr_tesseract_path'])    || $prefs['ocr_tesseract_path'] === 'tesseract') {
            $ocrStatus = 'bad';
            $fitness_status = FITNESS_STATUS_BAD;
            $ocrMessage = tra(
                'Your path preference is not configured. It may work now but will likely fail with cron. Specify an absolute path.'
            );
        } elseif ($prefs['ocr_tesseract_path'] === $ocr->whereIsExecutable('tesseract')) {
            $ocrStatus = 'good';
            $fitness_status = FITNESS_STATUS_GOOD;
            $ocrMessage = tra('Path setup correctly.');
        } else {
            $ocrStatus = 'unsure';
            $fitness_status = FITNESS_STATUS_UNSURE;
            $ocrMessage = tra(
                'Your path may not be configured correctly. It appears to be located at '
            ) . $ocr->whereIsExecutable(
                'tesseract' . '.'
            );
        }
    } catch (Exception $e) {
        if (
            empty($prefs['ocr_tesseract_path'])
            || $prefs['ocr_tesseract_path'] === 'tesseract'
        ) {
            $ocrStatus = 'bad';
            $fitness_status = FITNESS_STATUS_BAD;
            $ocrMessage = tra(
                'Your path preference is not configured. It may work now but will likely fail with cron. Specify an absolute path.'
            );
        } else {
            $ocrStatus = 'unsure';
            $fitness_status = FITNESS_STATUS_UNSURE;
            $ocrMessage = tra(
                'Your path is configured, but we were unable to tell if it was configured properly or not.'
            );
        }
    }

    $ocrToDisplay[] = array(
        'name'    => tra('Tesseract path'),
        'status'  => tra($ocrStatus),
        'fitness_status' => $fitness_status,
        'message' => $ocrMessage,
    );


    $pdfimages = TikiLib::lib('pdfimages');
    $pdfimages->setVersion();

    //lets fall back to configured options for a binary path if its not found with default options.
    if (! $pdfimages->version) {
        $pdfimages->setBinaryPath();
        $pdfimages->setVersion();
    }

    if ($pdfimages->version) {
        $ocrStatus = 'good';
        $fitness_status = FITNESS_STATUS_GOOD;
        $ocrMessage = tra('It appears that pdfimages is installed on your system.');
    } else {
        $ocrStatus = 'bad';
        $fitness_status = FITNESS_STATUS_BAD;
        $ocrMessage = tra('Could not find pdfimages. PDF files will not be processed.');
    }

    $ocrToDisplay[] = array(
        'name'    => tra('Pdfimages binary'),
        'version' => $pdfimages->version,
        'status'  => tra($ocrStatus),
        'fitness_status' => $fitness_status,
        'message' => $ocrMessage,
    );

    try {
        if (empty($prefs['ocr_pdfimages_path']) || $prefs['ocr_pdfimages_path'] === 'pdfimages') {
            $ocrStatus = 'bad';
            $fitness_status = FITNESS_STATUS_BAD;
            $ocrMessage = tra('Your path preference is not configured. It may work now but will likely fail with cron. Specify an absolute path.');
        } elseif ($prefs['ocr_pdfimages_path'] === $ocr->whereIsExecutable('pdfimages')) {
            $ocrStatus = 'good';
            $fitness_status = FITNESS_STATUS_GOOD;
            $ocrMessage = tra('Path setup correctly');
        } else {
            $ocrStatus = 'unsure';
            $fitness_status = FITNESS_STATUS_UNSURE;
            $ocrMessage = tra('Your path may not be configured correctly. It appears to be located at ') .
                $ocr->whereIsExecutable('pdfimages' . ' ');
        }
    } catch (Exception $e) {
        if (empty($prefs['ocr_pdfimages_path']) || $prefs['ocr_pdfimages_path'] === 'pdfimages') {
            $ocrStatus = 'bad';
            $fitness_status = FITNESS_STATUS_BAD;
            $ocrMessage = tra('Your path preference is not configured. It may work now but will likely fail with cron. Specify an absolute path.');
        } else {
            $ocrStatus = 'unsure';
            $fitness_status = FITNESS_STATUS_UNSURE;
            $ocrMessage = tra(
                'Your path is configured, but we were unable to tell if it was configured properly or not.'
            );
        }
    }

    $ocrToDisplay[] = array(
        'name'    => tra('Pdfimages path'),
        'status'  => tra($ocrStatus),
        'fitness_status' => $fitness_status,
        'message' => $ocrMessage,
    );

    // check if scheduler is set up properly.
    $scheduleDb = $ocr->table('tiki_scheduler');
    $conditions['status'] = 'active';
    $conditions['params'] = $scheduleDb->contains('ocr:all');
    if ($scheduleDb->fetchBool($conditions)) {
        $ocrToDisplay[] = array(
            'name'    => tra('Scheduler'),
            'status'  => tra('good'),
            'fitness_status' => FITNESS_STATUS_GOOD,
            'message' => tra('Scheduler has been successfully setup.'),
        );
    } else {
        $ocrToDisplay[] = array(
            'name'    => tra('Scheduler'),
            'status'  => tra('bad'),
            'fitness_status' => FITNESS_STATUS_BAD,
            'message' => tra('Scheduler needs to have a console command of "ocr:all" set.'),
        );
    }

    // Check if PCRE (Perl Compatible Regular Expressions) backtrack_limit
    // is enough higher to allow sufficient attempts
    // when trying to understand a complicated pattern written in a regular expression
    $backtrack_limit = ini_get('pcre.backtrack_limit');
    $url = '<a href="https://doc.tiki.org/Server-Check#OCR_Status_section">doc.tiki.org/Server-Check#OCR_Status_section</a>';
    if ($backtrack_limit !== false) {
        $moreInformation = tr('For more detailed information please check %0', $url);
        if ($backtrack_limit < 1000000) {
            $status = tr('unsure');
            $fitness_status = FITNESS_STATUS_UNSURE;
            $message = tr('pcre.backtrack_limit is lower that the PHP default of 1000000 in php.ini');
        } else {
            $status = tr('good');
            $fitness_status = FITNESS_STATUS_GOOD;
            $message = tr('pcre.backtrack_limit is good.');
        }
        $message .= ' ' . $moreInformation;
        $ocrToDisplay[] = array(
            'name' => tr('PCRE backtrack_limit'),
            'status' => $status,
            'fitness_status' => $fitness_status,
            'message' => $message
        );
    }

    $smarty->assign('ocr', $ocrToDisplay);
}
// Security Checks
// get all dangerous php settings and check them
$security = false;

// check file upload dir and compare it to tiki root dir
$s = ini_get('upload_tmp_dir');
$sn = substr($_SERVER['SCRIPT_NAME'], 0, -14);
$isInsideTiki = $s !== "" && str_contains($sn, $s);
$security = array(
    'upload_tmp_dir' => array(
        'fitness' => $isInsideTiki ? tra('unsafe') : tra('unknown'),
        'fitness_status' => $isInsideTiki ? FITNESS_STATUS_UNSAFE : FITNESS_STATUS_UNKNOWN,
        'setting' => $s,
        'message' => $isInsideTiki
            ? tra(
                'upload_tmp_dir is probably inside the Tiki directory. There is a risk that someone can upload any file to this directory and access it via web browser.'
            )
            : tra(
                'It can\'t be reliably determined if the upload_tmp_dir is accessible via a web browser. To be sure, check the webserver configuration.'
            ),
    ),
);

// Determine system state
$pdf_webkit = '';
if (isset($prefs) && $prefs['print_pdf_from_url'] == 'webkit') {
    $pdf_webkit = '<b>' . tra('WebKit is enabled') . '.</b> ';
}
$feature_blogs = '';
if (isset($prefs) && $prefs['feature_blogs'] == 'y') {
    $feature_blogs = '<b>' . tra('The Blogs feature is enabled') . '.</b> ';
}

$fcts = array(
         array(
            'function' => 'exec',
            'risky' => tr('Exec can potentially be used to execute arbitrary code on the server. Tiki does not need it; perhaps it should be disabled. However, the Plugins R/RR need it. If you use the Plugins R/RR and the other PHP software on the server can be trusted, this should be enabled.'),
            'safe' => tr('Exec can be potentially be used to execute arbitrary code on the server. Tiki needs it to run the Plugins R/RR. If this is needed and the other PHP software on the server can be trusted, this should be enabled.')
         ),
         array(
            'function' => 'passthru',
            'risky' => tr('Passthru is similar to exec. Tiki does not need it; perhaps it should be disabled. However, the Composer package manager used for installations in git checkouts may need it.'),
            'safe' => tr('Passthru is similar to exec. Tiki does not need it; it is good that it is disabled. However, the Composer package manager used for installations in git checkouts may need it.')
         ),
         array(
            'function' => 'shell_exec',
            'risky' => tr('Shell_exec is similar to exec. Tiki needs it to run PDF from URL: WebKit (wkhtmltopdf). %0 If this is needed and the other PHP software on the server can be trusted, this should be enabled.', $pdf_webkit),
            'safe' => tr('Shell_exec is similar to exec. Tiki needs it to run PDF from URL: WebKit (wkhtmltopdf). %0 If this is needed and the other PHP software on the server can be trusted, this should be enabled.', $pdf_webkit)
         ),
         array(
            'function' => 'system',
            'risky' => tr('System is similar to exec. Tiki does not need it; perhaps it should be disabled.'),
            'safe' => tr('System is similar to exec. Tiki does not need it; it is good that it is disabled.')
         ),
        array(
            'function' => 'proc_open',
            'risky' => tr('Proc_open is similar to exec. Tiki does not need it; perhaps it should be disabled. However, the Composer package manager used for installations in git checkouts or when using the package manager from the %0admin interface%1 may need it. The Sendmail mailer option also requires it.', '<a href="https://doc.tiki.org/Packages" target="_blank">', '</a>'),
            'safe' => tr('Proc_open is similar to exec. Tiki does not need it; it is good that it is disabled. However, the Composer package manager used for installations in git checkouts or when using the package manager from the %0admin interface%1 may need it. The Sendmail mailer option also requires it.', '<a href="https://doc.tiki.org/Packages" target="_blank">', '</a>')
        ),
         array(
            'function' => 'popen',
            'risky' => tr('popen is similar to exec. Tiki needs it for file search indexing in file galleries. If this is needed and other PHP software on the server can be trusted, this should be enabled.'),
            'safe' => tr('popen is similar to exec. Tiki needs it for file search indexing in file galleries. If this is needed and other PHP software on the server can be trusted, this should be enabled.')
         ),
         array(
            'function' => 'curl_exec',
            'risky' => tr('Curl_exec can potentially be abused to write malicious code. Tiki needs it to run features like Kaltura, CAS login and CClite. If these are needed and other PHP software on the server can be trusted, this should be enabled.'),
            'safe' => tr('Curl_exec can potentially be abused to write malicious code. Tiki needs it to run features like Kaltura, CAS login and CClite. If these are needed and other PHP software on the server can be trusted, this should be enabled.')
         ),
         array(
            'function' => 'curl_multi_exec',
            'risky' => tr('Curl_multi_exec can potentially be abused to write malicious code. Tiki needs it to run features like Kaltura, CAS login and CClite. If these are needed and other PHP software on the server can be trusted, this should be enabled.'),
            'safe' => tr('Curl_multi_exec can potentially be abused to write malicious code. Tiki needs it to run features like Kaltura, CAS login and CClite. If these are needed and other PHP software on the server can be trusted, this should be enabled.')
         ),
         array(
            'function' => 'parse_ini_file',
            'risky' => tr('It is probably an urban myth that this is dangerous. Tiki team will reconsider this check, but be warned. It is required for the %0System Configuration%1 feature.', '<a href="http://doc.tiki.org/System-Configuration" target="_blank">', '</a>'),
            'safe' => tr('It is probably an urban myth that this is dangerous. Tiki team will reconsider this check, but be warned. It is required for the %0System Configuration%1 feature.', '<a href="http://doc.tiki.org/System-Configuration" target="_blank">', '</a>'),
         )
    );

foreach ($fcts as $fct) {
    if (function_exists($fct['function'])) {
        $security[$fct['function']] = array(
            'setting' => 'Enabled',
            'fitness' => tra('risky'),
            'fitness_status' => FITNESS_STATUS_RISKY,
            'message' => $fct['risky']
        );
    } else {
        $security[$fct['function']] = array(
            'setting' => 'Disabled',
            'fitness' => tra('safe'),
            'fitness_status' => FITNESS_STATUS_SAFE,
            'message' => $fct['safe']
        );
    }
}

// trans_sid
$s = ini_get('session.use_trans_sid');
if ($s) {
    $security['session.use_trans_sid'] = array(
        'setting' => 'Enabled',
        'fitness' => tra('unsafe'),
        'fitness_status' => FITNESS_STATUS_UNSAFE,
        'message' => tr('session.use_trans_sid should be off by default. See the PHP manual for details. %0 How to change this value %1', '<a href="#php_conf_info">', '</a>')
    );
} else {
    $security['session.use_trans_sid'] = array(
        'setting' => 'Disabled',
        'fitness' => tra('safe'),
        'fitness_status' => FITNESS_STATUS_SAFE,
        'message' => tr('session.use_trans_sid should be off by default. See the PHP manual for details. %0 How to change this value %1', '<a href="#php_conf_info">', '</a>')
    );
}

$s = ini_get('xbithack');
if ($s == 1) {
    $security['xbithack'] = array(
        'setting' => 'Enabled',
        'fitness' => tra('unsafe'),
        'fitness_status' => FITNESS_STATUS_UNSAFE,
        'message' => tr('Setting the xbithack option is unsafe. Depending on the file handling of the webserver and the Tiki settings, an attacker may be able to upload scripts to file gallery and execute them. %0 How to change this value %1', '<a href="#php_conf_info">', '</a>')
    );
} else {
    $security['xbithack'] = array(
        'setting' => 'Disabled',
        'fitness' => tra('safe'),
        'fitness_status' => FITNESS_STATUS_SAFE,
        'message' => tr('setting the xbithack option is unsafe. Depending on the file handling of the webserver and the Tiki settings,  an attacker may be able to upload scripts to file gallery and execute them. %0 How to change this value %1', '<a href="#php_conf_info">', '</a>')
    );
}

$s = ini_get('allow_url_fopen');
if ($s == 1) {
    $security['allow_url_fopen'] = array(
        'setting' => 'Enabled',
        'fitness' => tra('risky'),
        'fitness_status' => FITNESS_STATUS_RISKY,
        'message' => tr('allow_url_fopen may potentially be used to upload remote data or scripts. Also used by Composer to fetch dependencies. %0 If this Tiki does not use the Blogs feature, this can be switched off.', $feature_blogs)
    );
} else {
    $security['allow_url_fopen'] = array(
        'setting' => 'Disabled',
        'fitness' => tra('safe'),
        'fitness_status' => FITNESS_STATUS_SAFE,
        'message' => tr('allow_url_fopen may potentially be used to upload remote data or scripts. Also used by Composer to fetch dependencies. %0 If this Tiki does not use the Blogs feature, this can be switched off.', $feature_blogs)
    );
}

if ($standalone || (! empty($prefs) && $prefs['fgal_enable_auto_indexing'] === 'y')) {
    // adapted from \Tiki\Lib\Filegals\FileGalLib::get_file_handlers
    $fh_possibilities = array(
        'application/ms-excel' => array('xls2csv %1'),
        'application/msexcel' => array('xls2csv %1'),
        // vnd.openxmlformats are handled natively in Zend
        //'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => array('xlsx2csv.py %1'),
        'application/ms-powerpoint' => array('catppt %1'),
        'application/mspowerpoint' => array('catppt %1'),
        //'application/vnd.openxmlformats-officedocument.presentationml.presentation' => array('pptx2txt.pl %1 -'),
        'application/msword' => array('catdoc %1', 'strings %1'),
        //'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => array('docx2txt.pl %1 -'),
        'application/pdf' => array('pstotext %1', 'pdftotext %1 -'),
        'application/postscript' => array('pstotext %1'),
        'application/ps' => array('pstotext %1'),
        'application/rtf' => array('catdoc %1'),
        'application/sgml' => array('col -b %1', 'strings %1'),
        'application/vnd.ms-excel' => array('xls2csv %1'),
        'application/vnd.ms-powerpoint' => array('catppt %1'),
        'application/x-msexcel' => array('xls2csv %1'),
        'application/x-pdf' => array('pstotext %1', 'pdftotext %1 -'),
        'application/x-troff-man' => array('man -l %1'),
        'application/zip' => array('unzip -l %1'),
        'text/enriched' => array('col -b %1', 'strings %1'),
        'text/html' => array('elinks -dump -no-home %1'),
        'text/richtext' => array('col -b %1', 'strings %1'),
        'text/sgml' => array('col -b %1', 'strings %1'),
        'text/tab-separated-values' => array('col -b %1', 'strings %1'),
    );

    $fh_native = array(
        'application/pdf' => 18.0,
        'application/x-pdf' => 18.0,
    );

    $file_handlers = array();

    foreach ($fh_possibilities as $type => $options) {
        $file_handler = array(
            'fitness' => '',
            'fitness_status' => '',
            'message' => '',
        );

        if (! $standalone && array_key_exists($type, $fh_native)) {
            if ($tikiWikiVersion->getBaseVersion() >= $fh_native["$type"]) {
                $file_handler['fitness'] = tra('good');
                $file_handler['fitness_status'] = FITNESS_STATUS_GOOD;
                $file_handler['message'] = tra("will be handled natively");
            }
        }
        if ($standalone && array_key_exists($type, $fh_native)) {
            $file_handler['fitness'] = tra('info');
            $file_handler['fitness_status'] = FITNESS_STATUS_INFO;
            $file_handler['message'] = tr("will be handled natively by Tiki &gt;= %0", $fh_native["$type"]);
        }
        if ($file_handler['fitness_status'] == '' || $file_handler['fitness_status'] == FITNESS_STATUS_INFO) {
            foreach ($options as $opt) {
                $optArray = explode(' ', $opt, 2);
                $exec = reset($optArray);
                $which_exec = shell_exec("which $exec");
                if ($which_exec) {
                    if ($file_handler['fitness_status'] == FITNESS_STATUS_INFO) {
                        $file_handler['message'] .= tr(", otherwise handled by %0", $which_exec);
                    } else {
                        $file_handler['message'] = tr("will be handled by %0", $which_exec);
                    }
                    $file_handler['fitness'] = tra('good');
                    $file_handler['fitness_status'] = FITNESS_STATUS_GOOD;
                    break;
                }
            }
            if ($file_handler['fitness_status'] == FITNESS_STATUS_INFO) {
                $fh_commands = '';
                foreach ($options as $opt) {
                    $fh_commands .= $fh_commands ? ' or ' : '';
                    $fh_commands .= '"' . substr($opt, 0, strpos($opt, ' ')) . '"';
                }
                $file_handler['message'] .= tr(', otherwise you need to install %0 to index this type of file', $fh_commands);
            }
        }
        if (! $file_handler['fitness']) {
            $file_handler['fitness'] = tra('unsure');
            $file_handler['fitness_status'] = FITNESS_STATUS_UNSURE;
            $fh_commands = '';
            foreach ($options as $opt) {
                $fh_commands .= $fh_commands ? ' or ' : '';
                $fh_commands .= '"' . substr($opt, 0, strpos($opt, ' ')) . '"';
            }
            $file_handler['message'] = tr('You need to install %0 to index this type of file', $fh_commands);
        }
        $file_handlers[$type] = $file_handler;
    }
}


if (! $standalone) {
    // The following is borrowed from tiki-admin_system.php
    $useDatabase = array();
    if ($prefs['feature_forums'] == 'y') {
        $dirs = TikiLib::lib('comments')->list_directories_to_save();
    } else {
        $dirs = array();
    }
    if ($prefs['feature_file_galleries'] == 'y' && ! empty($prefs['fgal_use_dir'])) {
        $dirs[] = $prefs['fgal_use_dir'];
        $useDatabase[] = $prefs['fgal_use_db'];
    }
    if ($prefs['feature_trackers'] == 'y') {
        if (! empty($prefs['t_use_dir'])) {
            $dirs[] = $prefs['t_use_dir'];
            $useDatabase[] = $prefs['t_use_db'];
        }
        $dirs[] = TRACKER_FIELD_IMAGE_STORAGE_PATH;
        $useDatabase[] = ''; //add this to make the array dirs and useDatabase to have the same lenght
    }
    if ($prefs['feature_wiki'] == 'y') {
        if (! empty($prefs['w_use_dir'])) {
            $dirs[] = $prefs['w_use_dir'];
            $useDatabase[] = $prefs['w_use_db'];
        }
        if ($prefs['feature_create_webhelp'] == 'y') {
            $dirs[] = WHELP_PATH;
            $useDatabase[] = '';
        }
        $dirs[] = DEPRECATED_IMG_WIKI_PATH;
        $dirs[] = DEPRECATED_IMG_WIKI_UP_PATH;
        $useDatabase[] = ''; //add this to make the array dirs and useDatabase to have the same lenght
        $useDatabase[] = ''; //add this to make the array dirs and useDatabase to have the same lenght
    }
    $dirs = array_unique($dirs);
    $dirsExist = array();
    foreach ($dirs as $i => $d) {
        $dirsWritable[$i] = is_writable($d);
    }
    $smarty->assign_by_ref('dirs', $dirs);
    $smarty->assign_by_ref('dirsWritable', $dirsWritable);
    $smarty->assign_by_ref('useDatabase', $useDatabase);
    // Prepare Monitoring acks
    $query = "SELECT `value` FROM tiki_preferences WHERE `name`='tiki_check_status'";
    $result = $tikilib->getOne($query);
    $last_state = json_decode($result, true);
    $smarty->assign_by_ref('last_state', $last_state);

    function deack_on_state_change(&$check_group, $check_group_name)
    {
        global $last_state;
        foreach ($check_group as $key => $value) {
            if (! empty($last_state["$check_group_name"]["$key"])) {
                $check_group["$key"]['ack'] = $last_state["$check_group_name"]["$key"]['ack'];
                if (
                    isset($check_group["$key"]['setting']) && isset($last_state["$check_group_name"]["$key"]['setting']) &&
                            $check_group["$key"]['setting'] != $last_state["$check_group_name"]["$key"]['setting']
                ) {
                    $check_group["$key"]['ack'] = false;
                }
            }
        }
    }
    deack_on_state_change($mysql_properties, 'MySQL');
    deack_on_state_change($server_properties, 'Server');
    if ($apache_properties) {
        deack_on_state_change($apache_properties, 'Apache');
    }
    if ($iis_properties) {
        deack_on_state_change($iis_properties, 'IIS');
    }
    deack_on_state_change($php_properties, 'PHP');
    deack_on_state_change($security, 'PHP Security');

    $tikiWikiVersion = new TWVersion();
    if (
        version_compare($tikiWikiVersion->getBaseVersion(), '18.0', '<') && ! class_exists('mPDF')
        || version_compare($tikiWikiVersion->getBaseVersion(), '18.0', '>=') && ! class_exists('\\Mpdf\\Mpdf')
    ) {
        $smarty->assign('mPDFClassMissing', true);
    }

    // Engine tables type
    $db = TikiDb::get();
    if ($db) {
        $engineType = '';
        $query = 'SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_NAME = "tiki_schema" AND TABLE_SCHEMA = DATABASE();';
        $result = query($query, $connection);
        if (! empty($result[0]['ENGINE'])) {
            $engineType = $result[0]['ENGINE'];
        }
    }
    if (version_compare($tikiWikiVersion->getBaseVersion(), '18.0', '>=') && $db && $engineType != 'InnoDB') {
        $smarty->assign('engineTypeNote', true);
    } else {
        $smarty->assign('engineTypeNote', false);
    }

    //Verify composer and composer install requirements: bzip and unzip bin
    if ($composerAvailable = $composerManager->composerIsAvailable()) {
        $composerChecks['composer'] = array(
            'fitness' => tra('good'),
            'fitness_status' => FITNESS_STATUS_GOOD,
            'message' => tra('Composer found')
        );
    } else {
        $composerChecks['composer'] = array(
            'fitness' => tra('bad'),
            'fitness_status' => FITNESS_STATUS_BAD,
            'message' => tra('Composer not found')
        );
    }

    if (extension_loaded('bz2')) {
        $composerChecks['php-bz2'] = array(
            'fitness' => tra('good'),
            'fitness_status' => FITNESS_STATUS_GOOD,
            'message' => tra('Extension loaded in PHP')
        );
    } else {
        $composerChecks['php-bz2'] = array(
            'fitness' => tra('bad'),
            'fitness_status' => FITNESS_STATUS_BAD,
            'message' => tra('Bz2 extension not loaded in PHP. It may be needed to install composer packages.')
        );
    }

    if (commandIsAvailable('unzip')) {
        $composerChecks['unzip'] = array(
            'fitness' => tra('good'),
            'fitness_status' => FITNESS_STATUS_GOOD,
            'message' => tra('Command found')
        );
    } else {
        $composerChecks['unzip'] = array(
            'fitness' => tra('unsure'),
            'fitness_status' => FITNESS_STATUS_UNSURE,
            'message' => tra('Command not found. As there is no \'unzip\' command installed zip files are being unpacked using the PHP zip extension.
            This may cause invalid reports of corrupted archives. Besides, any UNIX permissions (e.g. executable) defined in the archives will be lost.')
        );
    }

    $packageRepos = array(
        'composer.tiki.org' => 'https://composer.tiki.org',
        'packagist.org' => 'https://packagist.org'
    );

    foreach ($packageRepos as $key => $url) {
        $isAvailable = urlIsAvailable($url);
        $composerChecks[$key] = array(
            'fitness' => $isAvailable ? tra('good') : tra('unsure'),
            'fitness_status' => $isAvailable ? FITNESS_STATUS_GOOD : FITNESS_STATUS_UNSURE,
            'message' => $isAvailable ? tr("URL '%0' is reachable.", $url) : tr("URL '%0' is not reachable, check your firewall or proxy configurations.", $url)
        );
    }

    $smarty->assign('composer_available', $composerAvailable);
    $smarty->assign('composer_checks', $composerChecks);
    $smarty->assign('packages', $packagesToDisplay);
}

// Enhanced Security Checks
$sensitiveDataDetectedFiles = array();
check_for_remote_readable_files($sensitiveDataDetectedFiles);

// Check database configuration file permissions
$db_config_issues = check_database_config_permissions();
if (! empty($db_config_issues)) {
    $tiki_security['Database Configuration Permissions'] = array(
        'fitness' => tra('bad'),
        'fitness_status' => FITNESS_STATUS_BAD,
        'message' => tr('Database configuration file has insecure permissions: %0', implode(', ', $db_config_issues))
    );
} else {
    $tiki_security['Database Configuration Permissions'] = array(
        'fitness' => tra('safe'),
        'fitness_status' => FITNESS_STATUS_SAFE,
        'message' => tra('Database configuration file permissions are secure')
    );
}

// Check for phpMyAdmin installations
$phpmyadmin_issues = check_phpmyadmin_installations();
if (! empty($phpmyadmin_issues)) {
    $tiki_security['phpMyAdmin Security'] = array(
        'fitness' => tra('bad'),
        'fitness_status' => FITNESS_STATUS_BAD,
        'message' => tr('phpMyAdmin installation detected that may expose database management: %0', implode(', ', $phpmyadmin_issues))
    );
} else {
    $tiki_security['phpMyAdmin Security'] = array(
        'fitness' => tra('safe'),
        'fitness_status' => FITNESS_STATUS_SAFE,
        'message' => tra('No insecure phpMyAdmin installations detected')
    );
}

// Check for Adminer installations
$adminer_issues = check_adminer_installations();
if (! empty($adminer_issues)) {
    $tiki_security['Adminer Security'] = array(
        'fitness' => tra('bad'),
        'fitness_status' => FITNESS_STATUS_BAD,
        'message' => tr('Adminer installation detected that may expose database management: %0', implode(', ', $adminer_issues))
    );
} else {
    $tiki_security['Adminer Security'] = array(
        'fitness' => tra('safe'),
        'fitness_status' => FITNESS_STATUS_SAFE,
        'message' => tra('No insecure Adminer installations detected')
    );
}

// Check for additional backup and configuration files
$backup_file_issues = check_backup_configuration_files();
if (! empty($backup_file_issues)) {
    $tiki_security['Backup Configuration Files'] = array(
        'fitness' => tra('risky'),
        'fitness_status' => FITNESS_STATUS_RISKY,
        'message' => tr('Backup configuration files detected that may expose sensitive information: %0', implode(', ', $backup_file_issues))
    );
} else {
    $tiki_security['Backup Configuration Files'] = array(
        'fitness' => tra('safe'),
        'fitness_status' => FITNESS_STATUS_SAFE,
        'message' => tra('No insecure backup configuration files detected')
    );
}

// Check for directory listing vulnerabilities
$directory_listing_issues = check_directory_listing_vulnerabilities();
if (! empty($directory_listing_issues)) {
    $tiki_security['Directory Listing Security'] = array(
        'fitness' => tra('risky'),
        'fitness_status' => FITNESS_STATUS_RISKY,
        'message' => tr('Directory listing may be enabled in sensitive directories: %0', implode(', ', $directory_listing_issues))
    );
} else {
    $tiki_security['Directory Listing Security'] = array(
        'fitness' => tra('safe'),
        'fitness_status' => FITNESS_STATUS_SAFE,
        'message' => tra('Directory listing security is properly configured')
    );
}

// Check SSL/TLS configuration
$ssl_issues = check_ssl_configuration();
if (! empty($ssl_issues)) {
    $tiki_security['SSL/TLS Configuration'] = array(
        'fitness' => tra('risky'),
        'fitness_status' => FITNESS_STATUS_RISKY,
        'message' => tr('SSL/TLS configuration issues detected: %0', implode(', ', $ssl_issues))
    );
} else {
    $tiki_security['SSL/TLS Configuration'] = array(
        'fitness' => tra('safe'),
        'fitness_status' => FITNESS_STATUS_SAFE,
        'message' => tra('SSL/TLS configuration appears secure')
    );
}

if (! empty($sensitiveDataDetectedFiles)) {
    $files = ' (Files: ' . trim(implode(', ', $sensitiveDataDetectedFiles)) . ')';
    $tiki_security['Sensitive Data Exposure'] = array(
        'fitness' => tra('risky'),
        'fitness_status' => FITNESS_STATUS_RISKY,
        'message' => tr('Tiki detected that there are temporary files in the db folder that may expose credentials or other sensitive information. %0', $files)
    );
} else {
    $tiki_security['Sensitive Data Exposure'] = array(
        'fitness' => tra('safe'),
        'fitness_status' => FITNESS_STATUS_SAFE,
        'message' => tra('Tiki did not detect temporary files in the db folder that may expose credentials or other sensitive information.')
    );
}

if (isset($_REQUEST['benchmark'])) {
    $benchmark = BenchmarkPhp::run();
} else {
    $benchmark = '';
}

if (
    isset($_REQUEST["removeTable"]) && $access->checkCsrf(true)
) {
    $checkResult = check_db_mismatches();
    $whiteList = $checkResult['queriedTables'];
    $tableName = $_REQUEST['removeTable'];
    if (in_array($tableName, $whiteList)) {
        $escapedTableName = "`" . $tableName . "`";
         // Drop the table
        $query = "DROP TABLE IF EXISTS $escapedTableName";
        $result = $tikilib->query($query);
        if ($result) {
            echo '<div class="alert alert-info">Table ' . htmlspecialchars($tableName) . ' dropped successfully</div>';
        } else {
            echo '<div class="alert alert-danger">Failed to drop table ' . htmlspecialchars($tableName) . '</div>';
        }
    } else {
        echo '<div class="alert alert-danger">Invalid table\'s name </div>';
    }
}

$diffDatabase = false;
$diffDbTables = array();
$diffDbColumns = array();
$diffFileTables = array();
$diffFileColumns = array();
$dynamicTables = array();
$sqlFileTables = array();
$diffColDefs = array();
$diffIndexDefis = array();
$alterTableFile = array();

// Get Security token, neccessary for mismatch tables deletion
if (! $standalone) {
    $ticket = smarty_function_ticket(array('mode' => 'get'), $smarty->getEmptyInternalTemplate());
    $smarty->assign('ticket', $ticket);
}

function processKeyFields(&$alterTableFile, $tableName, $keyFields, $isUnique = false)
{
    $keyLen = isset($alterTableFile[$tableName]) ? count($alterTableFile[$tableName]) : 0;
    $indexNameField = $isUnique ? 2 : 1;
    $indexColumnsField = $isUnique ? 3 : 3;
    $indexType = $isUnique ? 'UNIQUE' : $keyFields[2];
    $keyFields[$indexNameField] = strtolower(trim(preg_replace('/\s+/', '', $keyFields[$indexNameField])));
    $keyFields[$indexColumnsField] = strtolower(trim(preg_replace(array('/(\s+)|(\([^)]*\))/'), array('', ''), $keyFields[$indexColumnsField])));
    $alterTableFile[$tableName][$keyLen] = array(
        'INDEX_NAME' => $keyFields[$indexNameField],
        'INDEX_COLUMNS' => $keyFields[$indexColumnsField],
        'INDEX_TYPE' => $indexType
    );
}

// Function used to check db mismatches
function check_db_mismatches()
{
    $diffDbColumns = array();
    $tikiSql = file_get_contents('db/tiki.sql');
    preg_match_all('/CREATE TABLE (?:.(?!;[^\S]))+./s', $tikiSql, $tables);
    preg_match_all("/ALTER TABLE (?:.(?!;[^\S]))+./s", $tikiSql, $alterTables);

    if (is_array($alterTables) && count($alterTables)) {
        foreach ($alterTables[0] as $alterTable) {
            preg_match('/ALTER TABLE[\s\t]*`?(\w+)`?/', $alterTable, $matches);
            $tableName = strtolower(trim($matches[1]));
            preg_match(
                "/(ADD (INDEX|KEY) [^ ]+) \((.+)\)/",
                $alterTable,
                $indKeyfields
            );
            preg_match(
                "/(ADD UNIQUE INDEX ([^\s]+) \(([^)]+)\))/",
                $alterTable,
                $uniqKeyfields
            );

            if (is_array($uniqKeyfields) && count($uniqKeyfields)) {
                processKeyFields($alterTableFile, $tableName, $uniqKeyfields, true);
            }

            if (is_array($indKeyfields) && count($indKeyfields)) {
                $indKeyfields[1] = str_replace('ADD INDEX', '', $indKeyfields[1]);
                processKeyFields($alterTableFile, $tableName, $indKeyfields);
            }
        }
    }

    foreach ($tables[0] as $table) {
        preg_match('/CREATE TABLE[\s\t]*`?(\w+)`?/', $table, $matches);
        $tableName = strtolower(trim($matches[1]));
        $sqlFileTables[$tableName] = array();

        //preg_match_all('/^[\s\t]*`?(?!CREATE|KEY|PRIMARY|UNIQUE|INDEX)(\w+)`?/m', $table, $fields);
        preg_match_all('/^[\s\t]*`?(?!CREATE|KEY|PRIMARY|UNIQUE|INDEX)(\w+)`?\h*(\w+\s*\([^)]*\)|\w+)/m', $table, $fields);

        $cols = $fields[1];
        $types = $fields[2];

        foreach ($types as $k => $type) {
            $types[$k] = preg_replace_callback('/(\w+)\s*\((.*?)\)/', function ($matches) {
                $inner = trim(preg_replace('/\s*,\s*/', ',', $matches[2]));
                return $matches[1] . '(' . $inner . ')';
            }, $type);
        }

        foreach ($cols as $ind => $col) {
            $columnType = isset($types[$ind]) ? $types[$ind] : null;
            $tmpCol = strtolower($col);
            $sqlFileTables[$tableName][$tmpCol] = strtolower($columnType);
        }

        $diffIndexDefis[$tableName]['file'] = array();
        $diffIndexDefis[$tableName]['db'] = array();
        $keyDefsFromFile = array();

        if (count($alterTableFile) && isset($alterTableFile[$tableName])) {
            $keyDefsFromFile = $alterTableFile[$tableName];
        }

        extractKeyDefsFromFile($table, $keyDefsFromFile, "INDEX|KEY");
        extractKeyDefsFromFile($table, $keyDefsFromFile, "PRIMARY");
        extractKeyDefsFromFile($table, $keyDefsFromFile, "UNIQUE");

        $keyDefsFromDb = getDbKeyDefinitions($tableName);

        compareKeyDefinitions($keyDefsFromFile, $keyDefsFromDb, $diffIndexDefis, $tableName);
    }

    $query = <<<SQL
    SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE
    FROM information_schema.columns
    WHERE table_schema = database()
    AND (TABLE_NAME NOT LIKE "index_%" OR TABLE_NAME LIKE "zzz_unused_%");
    SQL;

    $result = query($query);
    $diffFileTables = array_keys($sqlFileTables);
    $diffFileColumns = $sqlFileTables;
    $queriedTables = array();
    $diffDbTables = array();
    $tempDiffColDefs = array();

    foreach ($result as $tables) {
        $dbTable = strtolower($tables['TABLE_NAME']);
        $dbColumn = strtolower($tables['COLUMN_NAME']);
        $dbColDef = strtolower($tables['COLUMN_TYPE']);

        // Table in DB and SQL
        $key = array_search($dbTable, $diffFileTables);
        if ($key !== false) {
            unset($diffFileTables[$key]);
        }

        // Table in DB but not in SQL file
        if (! array_key_exists($dbTable, $sqlFileTables)) {
            if (! in_array($dbTable, $queriedTables)) {
                // Query to count the number of records in $dbTable
                $recordCountQuery = "SELECT COUNT(*) AS record_count FROM $dbTable";
                $recordCountResult = query($recordCountQuery);
                $recordCount = $recordCountResult[0]['record_count'];

                // Add table name and record count to $diffDbTables
                $diffDbTables[] = array(
                    'tableName' => $dbTable,
                    'tableSize' => $recordCount
                );

                // Add the table to the queriedTables array to avoid duplicate queries
                $queriedTables[] = $dbTable;
            }

            continue;
        }

        // Column in DB but not in SQL file
        if (! in_array($dbColumn, array_keys($sqlFileTables[$dbTable]))) {
            $diffDbColumns[$dbTable][] = $dbColumn;
        }

        if (isset($diffFileColumns[$dbTable])) {
            $tmpDiffFileColumns = array_keys($diffFileColumns[$dbTable]);
            $key = array_search($dbColumn, $tmpDiffFileColumns);
            unset($diffFileColumns[$dbTable][$tmpDiffFileColumns[$key]]);
        }

        if (isset($sqlFileTables[$dbTable]) && in_array($dbColumn, array_keys($sqlFileTables[$dbTable]))) {
            $sqlFileColDef = $sqlFileTables[$dbTable][$dbColumn];
            if ($sqlFileColDef !== $dbColDef) {
                $tempDiffColDefs[$dbTable][$dbColumn]['file'] = $sqlFileColDef;
                $tempDiffColDefs[$dbTable][$dbColumn]['db'] = $dbColDef;
            }
        }

        if (empty($diffFileColumns[$dbTable])) {
            unset($diffFileColumns[$dbTable]);
        }
    }

    if (count($tempDiffColDefs)) {
        foreach ($tempDiffColDefs as $tableName => $columns) {
            $columnNames = array();
            $fileDefs = array();
            $dbDefs = array();

            foreach ($columns as $columnName => $columnDef) {
                $columnNames[] = $columnName;
                $fileDefs[] = isset($columnDef['file']) ? $columnDef['file'] : '-';
                $dbDefs[] = isset($columnDef['db']) ? $columnDef['db'] : '-';
            }

            $diffColDefs[$tableName] = array(
                'columnNames' => '<li>' . implode('</li><li>', $columnNames) . '</li>',
                'fileDefs' => '<li>' . implode('</li><li>', $fileDefs) . '</li>',
                'dbDefs' => '<li>' . implode('</li><li>', $dbDefs) . '</li>',
            );
        }
    }

    if (count($diffIndexDefis)) {
        foreach ($diffIndexDefis as $tableName => &$item) {
            $formatIndex = function ($index) {
                return "<li><strong>INDEX_NAME:</strong> {$index['INDEX_NAME']}</li>" .
                       "<li><strong>INDEX_COLUMNS:</strong> {$index['INDEX_COLUMNS']}</li>" .
                       "<li><strong>INDEX_TYPE:</strong> {$index['INDEX_TYPE']}</li>";
            };

            if (! empty($item['file'])) {
                $item['file'] = array_map($formatIndex, $item['file']);
                $item['file'] = '<ul>' . implode('', $item['file']) . '</ul>';
            }

            if (! empty($item['db'])) {
                $item['db'] = array_map($formatIndex, $item['db']);
                $item['db'] = '<ul>' . implode('', $item['db']) . '</ul>';
            }
        }
        unset($item);
    }

    return array(
        'diffDbColumns' => $diffDbColumns,
        'diffFileTables' => $diffFileTables,
        'diffFileColumns' => $diffFileColumns,
        'diffDbTables' => $diffDbTables,
        'queriedTables' => $queriedTables,
        'diffColDefs' => $diffColDefs,
        'diffIndexDefis' => $diffIndexDefis
    );
}

function getDbKeyDefinitions($tableName)
{
    $query = <<<SQL
    SELECT 
        INDEX_NAME, 
        TABLE_NAME,
        GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) AS INDEX_COLUMNS,
    CASE 
        WHEN NON_UNIQUE = 0 AND INDEX_NAME = 'PRIMARY'
            THEN 'PRIMARY' 
        WHEN NON_UNIQUE = 0
            THEN 'UNIQUE'
        ELSE 'INDEX' 
    END AS INDEX_TYPE 
    FROM information_schema.statistics
    WHERE TABLE_SCHEMA = database() AND TABLE_NAME = "$tableName"
    GROUP BY INDEX_NAME, NON_UNIQUE;
    SQL;
    return query($query);
}

function cleanKeyDefinitionMatch($match, $case)
{
    switch ($case) {
        case 'INDEX|KEY':
        case 'UNIQUE':
            $match[2] = preg_replace('/\s+/', '', $match[2]);
            $match[3] = preg_replace('/\s+/', '', $match[3]);
            $match[3] = str_replace('`', '', $match[3]);
            if (! $match[2]) {
                $indColsArray = explode(',', $match[3]);
                $match[2] = $indColsArray[0];
            }
            break;
        case 'PRIMARY':
            $match[2] = preg_replace('/\s+/', '', $match[2]);
            $match[2] = str_replace('`', '', $match[2]);
            break;
        default:
            break;
    }
    return $match;
}

function processKeyMatches(&$keyDefsFromFile, $matches, $keyType, $nameIndex, $columnIndex)
{
    if (is_array($matches) && count($matches)) {
        foreach ($matches as $matchDefi) {
            $cleanedMatch = cleanKeyDefinitionMatch($matchDefi, $keyType);
            if ($keyType === 'PRIMARY') {
                $keyDefsFromFile[] = array(
                    'INDEX_NAME' => strtolower($cleanedMatch[$nameIndex]),
                    'INDEX_COLUMNS' => strtolower(preg_replace('/\([^)]*\)/', '', $cleanedMatch[$columnIndex])),
                    'INDEX_TYPE' => strtolower($cleanedMatch[$nameIndex])
                );
            } else {
                $cleanedMatch[$columnIndex] = preg_replace('/\([^)]*\)/', '', $cleanedMatch[$columnIndex]);
                $keyDefsFromFile[] = array(
                    'INDEX_NAME' => strtolower($cleanedMatch[$nameIndex]),
                    'INDEX_COLUMNS' => strtolower($cleanedMatch[$columnIndex]),
                    'INDEX_TYPE' => strtolower($cleanedMatch[1])
                );
            }
        }
    }
}

function extractKeyDefsFromFile($table, &$keyDefsFromFile, $case)
{
    switch ($case) {
        case 'UNIQUE':
            $matchUniqDefs1 = array();
            $matchUniqDefs2 = array();
            preg_match_all(
                "/\`?(\w+)\`?\s+\w+.*?(UNIQUE)\s+/i",
                $table,
                $matchUniqDefs1,
                PREG_SET_ORDER
            );
            if (count($matchUniqDefs1)) {
                foreach ($matchUniqDefs1 as $i => $e) {
                    $matchUniqDefs1[$i][0] = $e[0];
                    $matchUniqDefs1[$i][1] = 'UNIQUE';
                    $matchUniqDefs1[$i][2] = $e[1];
                    $matchUniqDefs1[$i][3] = $e[1];
                }
            }
            preg_match_all(
                "/(UNIQUE)(?:\s+KEY)?\s*`?(\w*)`?\s*\(((?:[^()]|\([^)]*\))*)\)/i",
                $table,
                $matchUniqDefs2,
                PREG_SET_ORDER
            );
            $matchedUniqDefs = array_merge($matchUniqDefs1, $matchUniqDefs2);
            processKeyMatches($keyDefsFromFile, $matchedUniqDefs, 'UNIQUE', 2, 3);
            break;
        case 'INDEX|KEY':
            preg_match_all(
                "/(?<!PRIMARY\s)(?<!UNIQUE\s)(?<![\w`])(INDEX|KEY)\s*`?([\w_-]+)?`?\s*\(((?:[^()]|\([^()]*\))+)\)/i",
                $table,
                $matchDefis,
                PREG_SET_ORDER
            );
            processKeyMatches($keyDefsFromFile, $matchDefis, 'INDEX|KEY', 2, 3);
            break;
        case 'PRIMARY':
            $matchPrimDefs1 = array();
            $matchPrimDefs2 = array();
            preg_match_all(
                "/\`?(\w+)\`?\s+\w+.*?(PRIMARY)\s+KEY/i",
                $table,
                $matchPrimDefs1,
                PREG_SET_ORDER
            );
            if (count($matchPrimDefs1)) {
                foreach ($matchPrimDefs1 as $i => $e) {
                    $matchPrimDefs1[$i][0] = $e[0];
                    $matchPrimDefs1[$i][1] = 'PRIMARY';
                    $matchPrimDefs1[$i][2] = $e[1];
                }
            }
            preg_match_all(
                '/(PRIMARY)\s+KEY\s*(?:\`\w+\`\s*)?\s*\(((?:[^()]|\([^()]*\))+)\)/i',
                $table,
                $matchPrimDefs2,
                PREG_SET_ORDER
            );
            $matchedPrimaryDefs = array_merge($matchPrimDefs1, $matchPrimDefs2);
            processKeyMatches($keyDefsFromFile, $matchedPrimaryDefs, 'PRIMARY', 1, 2);
            break;
        default:
            break;
    }
}

function compareKeyDefinitions($fileIndexes, $dbIndexes, &$diffIndexDefis, $tableName)
{
    foreach ($fileIndexes as $i => $fileIndex) {
        foreach ($dbIndexes as $k => $dbIndex) {
            $dbIndex['INDEX_COLUMNS'] = strtolower($dbIndex['INDEX_COLUMNS']);
            if (
                ($dbIndex['INDEX_TYPE'] === 'INDEX' && $fileIndex['INDEX_COLUMNS'] === $dbIndex['INDEX_COLUMNS']) ||
                ($dbIndex['INDEX_TYPE'] === 'PRIMARY' && $fileIndex['INDEX_COLUMNS'] === $dbIndex['INDEX_COLUMNS']) ||
                ($dbIndex['INDEX_TYPE'] === 'UNIQUE' && $fileIndex['INDEX_COLUMNS'] === $dbIndex['INDEX_COLUMNS'])
            ) {
                unset($dbIndexes[$k]);
                unset($fileIndexes[$i]);
            }
        }
    }

    $diffIndexDefis[$tableName]['file'] = array_values($fileIndexes);
    $diffIndexDefis[$tableName]['db'] = array_values($dbIndexes);
}

if (isset($_REQUEST['dbmismatches']) && ! $standalone && file_exists('db/tiki.sql')) {
    $diffDatabase = true;
    // Get the db_mismatches check result
    $checkResult = check_db_mismatches();
    $diffDbColumns = $checkResult['diffDbColumns'];
    $diffFileTables = $checkResult['diffFileTables'];
    $diffFileColumns = $checkResult['diffFileColumns'];
    $diffDbTables = $checkResult['diffDbTables'];
    $diffColDefs = $checkResult['diffColDefs'];
    $diffIndexDefis = $checkResult['diffIndexDefis'];

    // If table is missing, then all columns will be missing too (remove from columns diff)
    foreach ($diffFileTables as $table) {
        if (isset($diffFileColumns[$table])) {
            unset($diffFileColumns[$table]);
        }
    }

    $query = <<<SQL
SELECT TABLE_NAME
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = database()
  AND TABLE_NAME LIKE "index_%";
SQL;

    $result = query($query);
    foreach ($result as $tables) {
        $dynamicTables[] = $tables['TABLE_NAME'];
    }
}

/**
 * Tiki Manager Section
 **/
if (str_starts_with(strtoupper(PHP_OS), 'WIN')) {
    $trimCapable = false;
} else {
    $trimCapable = true;
}

if ($trimCapable) {
    $trimServerRequirements = array();
    $trimClientRequirements = array();

    $trimServerRequirements['Operating System Path'] = array(
        'fitness' => tra('info'),
        'fitness_status' => FITNESS_STATUS_INFO,
        'message' => $_SERVER['PATH'] ?? ''
    );

    $trimClientRequirements['Operating System Path'] = array(
        'fitness' => tra('info'),
        'fitness_status' => FITNESS_STATUS_INFO,
        'message' => $_SERVER['PATH'] ?? ''
    );

    $trimClientRequirements['SSH or FTP server'] = array(
        'fitness' => tra('info'),
        'fitness_status' => FITNESS_STATUS_INFO,
        'message' => tra('To manage this instance from a remote server you need SSH or FTP access to this server')
    );

    $serverCommands = array(
        'php-cli'     => array('command' => 'php'),
        'rsync'       => array('command' => 'rsync'),
        'nice'        => array('command' => 'nice'),
        'tar'         => array('command' => 'tar'),
        'bzip2'       => array('command' => 'bzip2'),
        'ssh'         => array('command' => 'ssh'),
        'ssh-copy-id' => array('command' => 'ssh-copy-id'),
        'scp'         => array('command' => 'scp'),
        'sqlite'      => array(
            'command' => 'sqlite3',
            'message' => 'Command not found, check if it is installed and available in one of the paths above.'
                . ' While this does not impact normal operations, will prevent you to be able to see/debug the'
                . ' internal db using "database:view"',
        ),
    );

    $serverPHPExtensions = array(
        'php-sqlite' => 'sqlite3',
    );

    $clientCommands = array(
        'php-cli' => 'php',
        'mysql' => 'mysql',
        'mysqldump' => 'mysqldump',
        'gzip' => 'gzip',
    );

    foreach ($serverCommands as $key => $commandData) {
        if (commandIsAvailable($commandData['command'])) {
            $trimServerRequirements[$key] = array(
                'fitness' => tra('good'),
                'fitness_status' => FITNESS_STATUS_GOOD,
                'message' => tra('Command found')
            );
        } else {
            $message = $commandData['message'] ?? tra('Command not found, check if it is installed and available in one of the paths above.');
            $trimServerRequirements[$key] = array(
                'fitness' => tra('unsure'),
                'fitness_status' => FITNESS_STATUS_UNSURE,
                'message' => $message
            );
        }
    }

    foreach ($serverPHPExtensions as $key => $extension) {
        if (extension_loaded($extension)) {
            $trimServerRequirements[$key] = array(
                'fitness' => tra('good'),
                'fitness_status' => FITNESS_STATUS_GOOD,
                'message' => tra('Extension loaded in PHP')
            );
        } else {
            $trimServerRequirements[$key] = array(
                'fitness' => tra('unsure'),
                'fitness_status' => FITNESS_STATUS_UNSURE,
                'message' => tra('Extension not loaded in PHP')
            );
        }
    }

    foreach ($clientCommands as $key => $command) {
        if (commandIsAvailable($command)) {
            $trimClientRequirements[$key] = array(
                'fitness' => tra('good'),
                'fitness_status' => FITNESS_STATUS_GOOD,
                'message' => tra('Command found')
            );
        } else {
            $trimClientRequirements[$key] = array(
                'fitness' => tra('unsure'),
                'fitness_status' => FITNESS_STATUS_UNSURE,
                'message' => tra('Command not found, check if it is installed and available in one of the paths above')
            );
        }
    }
}

$dbEngine = $dbVersion = null;
if ($connection || ! $standalone) {
    $dbEngine = $isMariaDB ? 'mariadb' : 'mysql';
    $dbVersion = ! empty($mysql_properties['Version']['setting']) ? $mysql_properties['Version']['setting'] : null;
} elseif (isset($_POST['db-engine'], $_POST['db-version'])) {
    $dbEngine = $_POST['db-engine'];
    $dbVersion = $_POST['db-version'];
}

$serverRequirements = checkServerRequirements(PHP_VERSION, $dbEngine, $dbVersion);
$available_tiki_properties = getCompatibleVersions($dbEngine, $dbVersion);

if (! $standalone) {
    $serverRequirements['Tiki Version'] = array(
        'value' => $tikiBaseVersion,
        'fitness' => tra('info'),
        'fitness_status' => FITNESS_STATUS_INFO,
        'message' => tra('Current Tiki version'),
    );
} else {
    $recTikiVersion = array_filter($available_tiki_properties, function ($details) {
        return $details['fitness_status'] == FITNESS_STATUS_GOOD;
    });

    if ($recTikiVersion = reset($recTikiVersion)) {
        $serverRequirements['Tiki Version'] = array(
            'value' => $recTikiVersion['name'],
            'fitness' => tra('info'),
            'fitness_status' => FITNESS_STATUS_INFO,
            'message' => tra('Recommended Tiki version'),
        );
    } else {
        $serverRequirements['Tiki Version'] = array(
            'value' => 'N/A',
            'fitness' => tra('unsure'),
            'fitness_status' => FITNESS_STATUS_UNSURE,
            'message' => tra('Unable to find a Tiki Version that uses the detected/selected PHP and Database versions.'),
        );
    }
}

if ($standalone) {
    function createPage($title, $content)
    {
        echo <<<END
<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <title>$title</title>
        <style type="text/css">
            body{
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", "Noto Sans", "Liberation Sans", Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";
            margin: 10px 100px 10px 100px;
            }
            table { border-collapse: collapse;}
            .button {
                border-radius: 3px 3px 3px 3px;
                font-size: 12.05px;
                font-weight: bold;
                padding: 2px 4px 3px;
                text-shadow: 0 -1px 0 rgba(0, 0, 0, 0.25);
                color: #FFF;
                text-transform: uppercase;
            }
            .unsure {background: #f89406;}
            .bad, .risky { background-color: #bd362f;}
            .good, .safe { background-color: #5bb75b;}
            .info {background-color: #2f96b4;}
           .sitetitle, h1, h2, h3, h4, h5 {
            font-family: BlinkMacSystemFont,"Segoe UI", Roboto, sans-serif;
           }
            table {
            border-spacing: 0;
            width: 100%;
            border-collapse: collapse;
            }

            td,
            th {
            padding: 0.5em;
            }

            th {
            font-weight: bold;
            text-align: left;
            }

            td > div {
            float: right;
            }

            .visible-on-mobile{
                visibility:hidden;
                display:none;
            }

            @media only screen and (max-width: 40em) {
            thead th:not(:first-child) {
                display: none;
            }
            td, th {
                display: block;
                clear: both;
            }
            td[data-th]:before {
                content: attr(data-th);
                float: left;
            }
            .visible-on-mobile{
                visibility:visible;
                display:inline;
            }
            }

            form {
                max-width: 100%;
                padding-right: 15px;
            }
            .tiki-form-group {
                margin-bottom: 15px;
            }

            .tiki-form-group label {
                display: block;
                margin-bottom: 5px;
                font-weight: 500;
            }

            .tiki-form-group input.form-control {
                width: 100%;
                padding: 10px;
                font-size: 14px;
                line-height: 1.5;
                border: 1px solid #ced4da;
                border-radius: 4px;
                background-color: #fff;
                box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.1);
            }

            .tiki-form-group input.form-control:focus {
                border-color: #80bdff;
                outline: 0;
                box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
            }

            input[type="submit"].btn {
                display: inline-block;
                font-weight: 400;
                text-align: center;
                white-space: nowrap;
                vertical-align: middle;
                user-select: none;
                padding: 6px 12px;
                font-size: 14px;
                line-height: 1.5;
                border: 1px solid transparent;
                border-radius: 4px;
                color: #fff;
                background-color: #007bff;
                border-color: #007bff;
                cursor: pointer;
                transition: background-color 0.15s, border-color 0.15s;
            }

            input[type="submit"].btn:hover {
                background-color: #0056b3;
                border-color: #004085;
            }
            .alert {
                position: relative;
                padding: 1rem 1rem;
                margin-bottom: 1rem;
                border: 1px solid transparent;
                border-radius: 0.375rem;
            }

            .alert-danger {
                color: #842029;
                background-color: #f8d7da;
                border-color: #f5c2c7;
            }

</style>
    </head>
    <body class="tiki_wiki ">
    <div class="container" >
    <div id="fixedwidth" >
        <div class="header_outer">
            <div class="header_container">
                <div class="clearfix ">
                    <header id="header" class="header">
                    <div class="content clearfix modules" id="top_modules" style="min-height: 168px;">
                        <div class="sitelogo" style="float: left">
END;
        echo tikiLogo();
        echo <<< END
                        </div>
                        <div class="sitetitles" style="float: left;">
                            <div class="sitetitle" style="font-size: 42px;">$title</div>
                        </div>
                    </div>
                    </header>
                </div>
            </div>
        </div>
        <div class="middle_outer">
            <div id="middle" >
                <div class="topbar clearfix">
                    <h1 style="font-size: 30px; line-height: 30px; color: #fff; text-shadow: 3px 2px 0 #781437; margin: 8px 0 0 10px; padding: 0;">
                    </h1>
                </div>
            </div>
            <div id="middle" >
                $content
            </div>
        </div>
    </div>
    <footer id="footer" class="footer" style="margin-top: 50px;">
    <div class="footer_liner">
        <div class="footerbgtrap" style="padding: 10px 0;">
            <a href="http://tiki.org" target="_blank" title="Powered by Tiki Wiki CMS Groupware">
END;
        echo tikiButton();
        echo <<< END
                <img src="img/tiki/tikibutton.png" alt="Powered by Tiki Wiki CMS Groupware" />
            </a>
        </div>
    </div>
</footer>
</div></div>
    </body>
</html>
END;
        die;
    }

    function tikiLogo()
    {
        return '<img alt="Tiki Logo" src="data:image/png;base64,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%3D" />';
    }

    function tikiButton()
    {
        return '<img alt="tikibutton" src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAFgAAAAfCAYAAABjyArgAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAC09JREFUeNrsmnlUFEcex7/TM4DggCiIxwBGPIgaBjwREGKMIiIm+jRoNrrGXX1RX7IBz5hDd5PVjb6Nxo2J8YzXJsYjicR4R8UDQzSKB4IixoByhVPua3qrfj3dziUaNn+M7pavrOrq7jo+9e1f/aoGjeegqa0AfMPiYDyiQaUyppRXGaMAtVoFtcBTARq1mkVjqpHy/J5KUEGQK2BBFEUYDCIaDQYpNhpQ39Co5Cmy+wZRyvPn70X2PvtnDMddXZzHgAE+tnTDHvF/OZRUVP/udS7buEdsGz71mMov6jUxc/+/2Gw00iw8rkFlolI57E35GW98eRK3CkohMoW+9Xww3hwd8sC6HsSJt6VmX4pXxDRo5EIDa+BxhmsJ5fLtIry07ggfOASNBiL73Jck/Ihvz2fii9di0MmzVbMh83scMA/C3YpqPM7BlnJ5eHV7EgS1Bio5aqT08p1ihC76HNtOpjarXrNn2D+NrRtXbmRj0ce7lOuosEBMGzfEbqCNm/UhYocPpNgcCMsOX0FqfrmkXKZg0cAUzk0ke9zQCNytbcD0jYeZmm9gzdThcG/Z4qG/DMvFV7B1g6v6zMUMREf0xisvDMUHm7+jaC+B9y3n15Jmv3/wWh7BJdUyj8JUwZKq1RS/u3ATIe9swYmrWc1TMrsnNNWRp7r6IDI0AL26etOgeFj48U50HDITT46ajS8PnDErs2yM3+fl5ZU1lOfv8Ou4pVvo/tj4FXQ94MW3KV3/1TGrNtbtOqrUJb8PiM02Ddw2umudFZimQOUo8FSQ8llFFYhash1ztxxBKRvHbzJPQNOAeSirqEJ2XhF8O3ji4OlLWL/7GL5eOZtMRvyyrbhbWU0mhIfUzNsKHJ7nk8InibfEn30ldiiu712BHQd/wM5Dydi9Ip7eC+jmi/zENRgfFYIDpy4qbaxcMBmLPtmFq5l3lPfzjn/afOVeL5B8ZAZPUTADrPfxJNAcfLi/jpkEPgHcRxYk6Ox5bi4i392GxNRbZmySUq7TWG2vZQ9Q8JjXP0CP5+YQ3PdejSXbzENIYDeKPPDBhwZ1lz7dlAwCRINhk8EbD+3dHVcypPeycguxZucRyt/OL773pXTzITfR1aUFdZaH0xeuYf/JFKmupEuU8nbueTuq36TeM1kl2HheqpugaSTlzo8MwIlZ0QRR79MW++JioPf1khRMUQK9YeZzWDR+MCL/ugWzN+5naq5Wvqwpb3+q9NvSBmuaAsxVJIPkA3NjMyvPnDxjrVxdFOhrd31P4EYMCiLQPB8W5A9X4yLBQXJFD+rzJHzae9hsU24jrLc/xRejw6i9/ybwRWvOoQz4tJL6IZAZYAscm6S1yT9jX1qOUamS3vjujudFDleU0jlbv0fJ3Up+Ex/tTcYPP6Vh39LpmPNyDH3h3IzaCjb9YHll5GlDQ4NSzu0xX+xe/8dmqpTD6umnI/VxwMu37KOy2KiBNKsyeJcWDvTc9n1JGB6mp9leMXeSYsO5Wgc85UfPym2s2XEEPbvoaJKWz50I73Zt8M5HOyiV9WvLd7e1srs7OyJ5eqhyvXviAKv3PN/cqQAGS8P9vZmaR5o9k5j6C55dtIkBV+FcdhEWrtqJFfMmQefV+j57CRM3ra6uTilu18YVsyePpMFYlu9dNRt7jl8gFY6LHIDqaknJo5/pQ4ML79sDgd10iJ80gu35NQSX1/H5+zNx9GwacgvLMDKiD+3Z+cTMnTKK3ndwkJ6T2zh2Nh0V1bXkyfCO8/cTElPQqWNbzJocDXfXlkrfLE2CfM3To9nlyCirp2tvrSNe6OmFHal5yC6tpk1GiG9rhHZqI5kDlQRY7+2B+cMDcSm7EPPYTo+7cAfnjzMedgjGsw7QWrIsfoLNjYeTk5O5iTAF6dVai2ljwq3KLe+hsR51jdbltbW1eGXs05SvqKigtIWjGtFhT5nUZEBMuN6sbrktXtf4yH5KeVVVFZVNHT3I6nlbcOWYW9WAN0/nMBPRyAkguIOWAO+6WoCkX4qZz9uAWWyiJcAMriDV9UZUEAGbsTURF2/lw2DyFdMXwiC3rK4k08e/cPmwxxI0z9oE/Cju0kzBytdxpwpQYRAgOAjSlti4fQUtXEyxjIBSh1GZtK4wk1JaVYuymnpppVLakfJOtVVwrK/F5LFDUV9fb3aiZg5ZtD/Ay7cdZuMXMGvisCbPR5qCy98/n1WMGxUic8ccaKB8x8ahSiyNLph4Dx5f2OT80kOXybtYPTEC0csTYPqRCMxcuJSXon+vzpg+bjApmPfT/NhStF7k7AVw2s85cHZ2JlhN9clUqZaQOeC60mLUpWWjhb4f7UtEVSPKG6SBxw3wxsCOrlh/Luueu0f1SPlTmflwO6HBjIgeWBDTF4u/SabywE5eOLLwJaC+Di3UBlKvDFcWg7mSTQDzh+0h8M5xf7v3i3+D1tkJrzHfMyzQDzHxqzH3j1GICeuB3Ucv4JNdJ3Bs7Ty2ANWZwZUBd9F5QpO2B/XubeDYuRtEBjetpBYrf8pDcPuWGKhzw47LDrhdVkP2mB/M3K2tx+mbBWQaFnx9Fj7uLgjvriPuJ9JvQ6ithsBA+ndqi8rKSivA1gq2QxPBO9pK64LTmxdh5nvrse3AOYwcFIBQfWfsP5NKgE+cz2DuXiB5KCUlFVZweerE7O5bE4fg7xv2ojzdFy4RUXyfjJXn82jRMjQw28kA7SiuwPYU6azhSm4Znl+XqCxq/DhTzo9guziX7Ezqy8JpMdRP7gU1BVi0RxPBO8c3ISVFBfDxcsPJlBvQarUY0rcrFm86guLyGly8cQdTX4hEYWEhDdISsBxDAp7AxoWTsOSzA7j4xVo4DxoGQecrfbui/OOO/L/0kw9MyhRzxNpwys1Cew83/GXCM6Rc3q78IwWHa1PBop0qmAe+ePBzDjetMw2kb/eOpNh//ls6/Onl1x5lhbk27a+c8oMdDzdnrIgfy8xKCjbtPYyaDk/AISiYPAm28kkw5AgjHFGCIxpTh8J8CEzxC14eDSeNQG7oQylYtFMF813iZ9+ewdGfbmDiqAiUlpZS/54OZGYi+TrblvrASWg022jcD7Cs5tFPB0DftSOWbj2Mmwe+gkNftrNr7Unum3QmLFIeMiBjVFWWQ1PyKyaPDEavzu2oTVm9snLv50WI9miD/xwzAIKTFr/kl+L9+JcQ0lOH9PR0ujesnx8BnjAiFDk5OTYBW5oJOXLY3OSsnheLrfvPsngIQvdeEPyeZEAMipoluEZlM9U63rkFP50H/hDZR4Frqt6mANulm+buzF2lSgR0YP5rQxEuXSqi8gsZeUhIuk4mI0zvh5vXrth00e4H2VTNE4YGIbiXL97dcBAFubehCugH0cGRlAwFlgHq/DtQMZcsLjaCzIKpck0BW5oIm4c99r6T69zeFfOnRKOLbwfkZmVabZMfRsWmoL09XfFh3PNYn5CMoz8mQuRK9mhnNBcMLltkNUX5GP9sIHSeWtTU1JiBtQRsS72PFGANY9lwNw/XruQ9cMNhaoctYcuAeapm92aMDkY/fx1WfZWESqZmg6sb1DXVUBf/ik7t3DEmvIcV3KYWuIc6TXsUzyPuZy5sQbaMAZ09sXjqUKxJOIv0LMkn9vfxQNy4EDottGVzm1zcTFK728n93gc/TUE2vad1EjAndiCq2C7O2UljBNbI1FtvE6wpXFtn6VYmIigoCOfOnXvsIZuCNi1XzAtLa2oaFJ/cciPRtN8rwtHREXq93gzw8WUbEwbP+9Nz6N+/P/4ffp/AmPLkuOpx+OvKe+oVlZ+TVMZzXFIlKVVOBcqrjalAvzIbU+NpmrxtNsh/adloNAui9FeXvMzK/pocshvDcf5Dz38EGAD34AT1F6wekAAAAABJRU5ErkJggg%3D%3D"';
    }
}

if ($standalone && ! $nagios) {
    $render .= '<style type="text/css">td, th { border: 1px solid #000000; vertical-align: baseline; padding: .5em; }</style>';
    $render .= '<h2>Server compatibility</h2>';

    renderTable($serverRequirements);

    if (! $locked) {
        if (! $connection) {
            $render .= '<p>Unable to check the server compatibility and the recommended Tiki version.<br>';
            $render .= 'Use the form below to select the Database engine and version, to detect the recommended version.</p>';
            $render .= '<form method="post" action="' . $_SERVER['SCRIPT_NAME'] . '">';
            $render .= '<div class="tiki-form-group mt-3"><label for="db-engine">Database Engine</label>:';
            $render .= '<select name="db-engine" class="form-control">';
            $render .= '<option value="mysql" ' . ($dbEngine == 'mysql' ? 'selected' : '') . '>MySQL</option>';
            $render .= '<option value="mariadb" ' . ($dbEngine == 'mariadb' ? 'selected' : '') . '>MariaDB</option>';
            $render .= '</select></div>';
            $render .= '<div class="tiki-form-group"><label for="db-engine">Database Version</label>: <input type="text"  class="form-control" id="db-version" name="db-version" value="' . $dbVersion . '"/></div>';
            $render .= '<div class="tiki-form-group"><input type="submit" class="btn btn-primary btn-sm" value="Check compatibility" /></div>';
            $render .= '</form>';
        }

        $render .= '<h3>Compatible Tiki Versions</h3>';

        renderAvailableTikiTable($available_tiki_properties);

        $render .= '<h2>MySQL or MariaDB Database Properties</h2>';
        renderTable($mysql_properties);
        $render .= '<h2>Test sending emails</h2>';
        if (isset($_REQUEST['email_test_to'])) {
            $email = filter_var($_POST['email_test_to'], FILTER_SANITIZE_EMAIL);
            $email_test_headers = 'From: noreply@tiki.org' . "\n";    // needs a valid sender
            $email_test_headers .= 'Reply-to: ' . $email . "\n";
            $email_test_headers .= "Content-type: text/plain; charset=utf-8\n";
            $email_test_headers .= 'X-Mailer: Tiki-Check - PHP/' . PHP_VERSION . "\n";
            $email_test_subject = tra('Test mail from Tiki Server Compatibility Test');
            $email_test_body = tra("Congratulations!\n\nThis server can send emails.\n\n");
            $email_test_body .= "\t" . tra('Server:') . ' ' . (empty($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_ADDR'] : $_SERVER['SERVER_NAME']) . "\n";
            $email_test_body .= "\t" . tra('Sent:') . ' ' . date(DATE_RFC822) . "\n";

            $sentmail = mail($email, $email_test_subject, $email_test_body, $email_test_headers);
            if ($sentmail) {
                $mail['Sending mail'] = array(
                    'setting' => 'Accepted',
                    'fitness' => tra('good'),
                    'fitness_status' => FITNESS_STATUS_GOOD,
                    'message' => tra('It was possible to send an e-mail. This only means that a mail server accepted the mail for delivery. This check can\;t verify if that server actually delivered the mail. Please check the inbox of %0 to see if the mail was delivered.', htmlspecialchars($email))
                );
            } else {
                $mail['Sending mail'] = array(
                    'setting' => 'Not accepted',
                    'fitness' => tra('bad'),
                    'fitness_status' => FITNESS_STATUS_BAD,
                    'message' => tra('It was not possible to send an e-mail. It may be that there is no mail server installed on this machine or that it is incorrectly configured. If the local mail server cannot be made to work, a regular mail account can be set up and its SMTP settings configured in tiki-admin.php.')
                );
            }
            renderTable($mail);
        } else {
            $render .= '<form method="post" action="' . $_SERVER['SCRIPT_NAME'] . '">';
            $render .= '<div class="tiki-form-group mt-3"><label for="e-mail">e-mail address to send test mail to</label>: <input type="text"  class="form-control" id="email_test_to" name="email_test_to" /></div>';
            $render .= '<div class="tiki-form-group"><input type="submit" class="btn btn-primary btn-sm" value=" Send e-mail " /></div>';
            $render .= '<p><input type="hidden" id="dbhost" name="dbhost" value="';
            if (isset($_POST['dbhost'])) {
                $render .= htmlentities(strip_tags($_POST['dbhost']), ENT_COMPAT);
            };
                $render .= '" /></p>';
                $render .= '<p><input type="hidden" id="dbuser" name="dbuser" value="';
            if (isset($_POST['dbuser'])) {
                $render .= htmlentities(strip_tags($_POST['dbuser']), ENT_COMPAT);
            };
                $render .= '"/></p>';
                $render .= '<p><input type="hidden" id="dbpass" name="dbpass" value="';
            if (isset($_POST['dbpass'])) {
                $render .= htmlentities(strip_tags($_POST['dbpass']), ENT_COMPAT);
            };
                $render .= '"/></p>';
            $render .= '</form>';
        }
    }

    $render .= '<h2>Server Information</h2>';
    renderTable($server_information);
    $render .= '<h2>Server Properties</h2>';
    renderTable($server_properties);
    $render .= '<h2>Apache properties</h2>';
    if ($apache_properties) {
        renderTable($apache_properties);
        if ($apache_server_info != 'nocurl' && $apache_server_info != false) {
            if (isset($_REQUEST['apacheinfo']) && $_REQUEST['apacheinfo'] == 'y') {
                $render .= $apache_server_info;
            } else {
                $render .= '<a href="' . $_SERVER['SCRIPT_NAME'] . '?apacheinfo=y">Append Apache /server-info;</a>';
            }
        } elseif ($apache_server_info == 'nocurl') {
            $render .= 'You don\'t have the Curl extension in PHP, so we can\'t append Apache\'s server-info.';
        } else {
            $render .= 'Apparently you have not enabled mod_info in your Apache, so we can\'t append more verbose information to this output.';
        }
    } else {
        $render .= 'You are either not running the preferred Apache web server or you are running PHP with a SAPI that does not allow checking Apache properties (for example, CGI or FPM).';
    }
    $render .= '<h2>IIS properties</h2>';
    if ($iis_properties) {
        renderTable($iis_properties);
    } else {
        $render .= tra("You are not running IIS web server.");
    }
    $render .= '<h2>' . tra('PHP scripting language properties') . '</h2>';
    renderTable($php_properties);

    $render_sapi_info = '';
    if (! empty($php_sapi_info)) {
        if (! empty($php_sapi_info['message'])) {
            $render_sapi_info .= $php_sapi_info['message'];
        }
        if (! empty($php_sapi_info['link'])) {
            $render_sapi_info .= '<a href="' . $php_sapi_info['link'] . '"> ' . $php_sapi_info['link'] . '</a>';
        }
        $render_sapi_info = '<p>' . $render_sapi_info . '</p>';
    }

    $render .= tr('Change PHP configuration values:%0 You can check the full documentation on how to change the configurations values in <a href="http://www.php.net/manual/en/configuration.php">http://www.php.net/manual/en/configuration.php</a>', $render_sapi_info);
    $render .= '<h2>' . tra('PHP security properties') . '</h2>';
    renderTable($security);
    $render .= '<h2>' . tra('Tiki Security') . '</h2>';
    renderTable($tiki_security);
    $render .= '<h2>' . tra('MySQL Variables') . '</h2>';
    renderTable($mysql_variables, 'wrap');

    $render .= '<h2>' . tra('File Gallery Search Indexing') . '</h2>';
    $render .= '<em>' . tra('More info') . ' <a href="https://doc.tiki.org/Search-within-files">' . tra('here') . '</a></em>';
    renderTable($file_handlers);

    $render .= '<h2>PHP Info</h2>';
    if (isset($_REQUEST['phpinfo']) && $_REQUEST['phpinfo'] == 'y') {
        ob_start();
        phpinfo();
        $info = ob_get_contents();
        ob_end_clean();
        $info = preg_replace('%^.*<body>(.*)</body>.*$%ms', '$1', $info);
        $render .= $info;
    } else {
        $render .= '<a href="' . $_SERVER['SCRIPT_NAME'] . '?phpinfo=y">Append phpinfo();</a>';
    }

    $render .= '<a name="benchmark"></a><h2>Benchmark PHP/MySQL</h2>';
    $render .= '<a href="tiki-check.php?benchmark=run&ts=' . time() . '#benchmark" style="margin-bottom: 10px;">Check</a>';
    if (! empty($benchmark)) {
        renderTable($benchmark);
    }

    $render .= '<h2>Tiki Manager</h2>';
    $render .= '<em>For more detailed information about Tiki Manager please check <a href="https://doc.tiki.org/Manager">doc.tiki.org/Manager</a></em>.';
    if ($trimCapable) {
        $render .= '<h3>Where Tiki Manager is installed</h3>';
        renderTable($trimServerRequirements);
        $render .= '<h3>Where Tiki instances are installed</h3>';
        renderTable($trimClientRequirements);
    } else {
        $render .= '<p>Apparently Tiki is running on a Windows based server. This feature is not supported natively.</p>';
    }

    createPage('Tiki Server Compatibility', $render);
} elseif ($nagios) {
//  0    OK
//  1    WARNING
//  2    CRITICAL
//  3    UNKNOWN
    $monitoring_info = array( 'state' => 0,
             'message' => '');

    function update_overall_status($check_group, $check_group_name)
    {
        global $monitoring_info;
        $state = 0;
        $message = '';

        foreach ($check_group as $property => $values) {
            if (! isset($values['ack']) || $values['ack'] != true) {
                switch ($values['fitness_status']) {
                    case FITNESS_STATUS_UNSURE:
                        $state = max($state, 1);
                        $message .= "$property" . "->unsure, ";
                        break;
                    case FITNESS_STATUS_RISKY:
                        $state = max($state, 1);
                        $message .= "$property" . "->risky, ";
                        break;
                    case FITNESS_STATUS_BAD:
                        $state = max($state, 2);
                        $message .= "$property" . "->BAD, ";
                        break;
                    case FITNESS_STATUS_INFO:
                        $state = max($state, 3);
                        $message .= "$property" . "->info, ";
                        break;
                    case FITNESS_STATUS_GOOD:
                    case FITNESS_STATUS_SAFE:
                        break;
                }
            }
        }
        $monitoring_info['state'] = max($monitoring_info['state'], $state);
        if ($state != 0) {
            $monitoring_info['message'] .= $check_group_name . ": " . trim($message, ' ,') . " -- ";
        }
    }

    // Might not be set, i.e. in standalone mode
    if ($mysql_properties) {
        update_overall_status($mysql_properties, "MySQL");
    }
    update_overall_status($server_properties, "Server");
    if ($apache_properties) {
        update_overall_status($apache_properties, "Apache");
    }
    if ($iis_properties) {
        update_overall_status($iis_properties, "IIS");
    }
    update_overall_status($php_properties, "PHP");
    update_overall_status($security, "PHP Security");
    update_overall_status($tiki_security, "Tiki Security");
    $return = json_encode($monitoring_info);
    echo $return;
} else {    // not stand-alone
    if (isset($_REQUEST['acknowledge']) || empty($last_state)) {
        $tiki_check_status = array();
        function process_acks(&$check_group, $check_group_name)
        {
            global $tiki_check_status;
            foreach ($check_group as $key => $value) {
                $formkey = str_replace(array('.',' '), '_', $key);
                if (
                    isset($check_group["$key"]['fitness']) && ($check_group["$key"]['fitness_status'] === FITNESS_STATUS_GOOD || $check_group["$key"]['fitness_status'] === FITNESS_STATUS_SAFE) ||
                    (isset($_REQUEST["$formkey"]) && $_REQUEST["$formkey"] === "on")
                ) {
                    $check_group["$key"]['ack'] = true;
                } else {
                    $check_group["$key"]['ack'] = false;
                }
            }
            $tiki_check_status["$check_group_name"] = $check_group;
        }
        process_acks($mysql_properties, 'MySQL');
        process_acks($server_properties, 'Server');
        if ($apache_properties) {
            process_acks($apache_properties, "Apache");
        }
        if ($iis_properties) {
            process_acks($iis_properties, "IIS");
        }
        process_acks($php_properties, "PHP");
        process_acks($security, "PHP Security");
        $json_tiki_check_status = json_encode($tiki_check_status);
        $query = "INSERT INTO tiki_preferences (`name`, `value`) values('tiki_check_status', ? ) on duplicate key update `value`=values(`value`)";
        $bindvars = array($json_tiki_check_status);
        $result = $tikilib->query($query, $bindvars);
    }

    $is_compatible = true;
    if ($serverRequirements) {
        foreach ($serverRequirements as $key => $value) {
            if ($value['fitness_status'] == FITNESS_STATUS_BAD) {
                $is_compatible = false;
                break;
            }
        }
    }

    $smarty->assign_by_ref('current_tiki_version', $tikiBaseVersion);
    $smarty->assign_by_ref('is_compatible', $is_compatible);
    $smarty->assign_by_ref('server_req', $serverRequirements);
    $smarty->assign_by_ref('available_tiki_properties', $available_tiki_properties);
    $smarty->assign_by_ref('server_information', $server_information);
    $smarty->assign_by_ref('server_properties', $server_properties);
    $smarty->assign_by_ref('mysql_properties', $mysql_properties);
    $smarty->assign_by_ref('php_properties', $php_properties);
    $smarty->assign_by_ref('php_sapi_info', $php_sapi_info);
    if ($apache_properties) {
        $smarty->assign_by_ref('apache_properties', $apache_properties);
    } else {
        $smarty->assign('no_apache_properties', tra('You are either not running the preferred Apache web server or you are running PHP with a SAPI that does not allow checking Apache properties (e.g. CGI or FPM).'));
    }
    if ($iis_properties) {
        $smarty->assign_by_ref('iis_properties', $iis_properties);
    } else {
        $smarty->assign('no_iis_properties', tra('You are not running IIS web server.'));
    }
    $smarty->assign_by_ref('security', $security);
    $smarty->assign_by_ref('tiki_security', $tiki_security);
    $smarty->assign_by_ref('mysql_variables', $mysql_variables);
    $smarty->assign_by_ref('mysql_crashed_tables', $mysql_crashed_tables);
    if ($prefs['fgal_enable_auto_indexing'] === 'y') {
        $smarty->assign_by_ref('file_handlers', $file_handlers);
    }
    // disallow robots to index page:

    $fmap = array(
        FITNESS_STATUS_GOOD => array('icon' => 'ok', 'class' => 'success'),
        FITNESS_STATUS_SAFE => array('icon' => 'ok', 'class' => 'success'),
        FITNESS_STATUS_BAD => array('icon' => 'ban', 'class' => 'danger'),
        FITNESS_STATUS_UNSAFE => array('icon' => 'ban', 'class' => 'danger'),
        FITNESS_STATUS_RISKY => array('icon' => 'warning', 'class' => 'warning'),
        FITNESS_STATUS_UNSURE => array('icon' => 'warning', 'class' => 'warning'),
        FITNESS_STATUS_INFO => array('icon' => 'information', 'class' => 'info'),
        FITNESS_STATUS_UNKNOWN => array('icon' => 'help', 'class' => 'muted'),
    );
    $smarty->assign('fmap', $fmap);
    $smarty->assign('FITNESS_STATUS_GOOD', FITNESS_STATUS_GOOD);
    $smarty->assign('FITNESS_STATUS_BAD', FITNESS_STATUS_BAD);
    $smarty->assign('FITNESS_STATUS_SAFE', FITNESS_STATUS_SAFE);
    $smarty->assign('FITNESS_STATUS_UNSAFE', FITNESS_STATUS_UNSAFE);
    $smarty->assign('FITNESS_STATUS_RISKY', FITNESS_STATUS_RISKY);
    $smarty->assign('FITNESS_STATUS_UNSURE', FITNESS_STATUS_UNSURE);
    $smarty->assign('FITNESS_STATUS_INFO', FITNESS_STATUS_INFO);
    $smarty->assign('FITNESS_STATUS_UNKNOWN', FITNESS_STATUS_UNKNOWN);


    if (isset($_REQUEST['bomscanner']) && class_exists('BOMChecker_Scanner')) {
        $timeoutLimit = ini_get('max_execution_time');
        if ($timeoutLimit < 120) {
            set_time_limit(120);
        }

        $BOMScanner = new BOMChecker_Scanner();
        $BOMFiles = $BOMScanner->scan();
        $BOMTotalScannedFiles = $BOMScanner->getScannedFiles();

        $smarty->assign('bom_total_files_scanned', $BOMTotalScannedFiles);
        $smarty->assign('bom_detected_files', $BOMFiles);
        $smarty->assign('bomscanner', true);
    }

    $smarty->assign('trim_capable', $trimCapable);
    if ($trimCapable) {
        $smarty->assign('trim_server_requirements', $trimServerRequirements);
        $smarty->assign('trim_client_requirements', $trimClientRequirements);
    }

    $smarty->assign('sensitive_data_detected_files', $sensitiveDataDetectedFiles);

    $smarty->assign('benchmark', $benchmark);
    $smarty->assign('diffDatabase', $diffDatabase);
    $smarty->assign('diffDbTables', $diffDbTables);
    $smarty->assign('diffDbColumns', $diffDbColumns);
    $smarty->assign('diffFileTables', $diffFileTables);
    $smarty->assign('diffFileColumns', $diffFileColumns);
    $smarty->assign('diffColDefs', $diffColDefs);
    $smarty->assign('diffIndexDefis', $diffIndexDefis);
    $smarty->assign('dynamicTables', $dynamicTables);

    $criptLib = TikiLib::lib('crypt');
    $smarty->assign('user_encryption_stats', array(
        'Sodium' => $criptLib->getUserCryptDataStats('sodium'),
        'OpenSSL' => $criptLib->getUserCryptDataStats('openssl'),
        'MCrypt' => $criptLib->getUserCryptDataStats('mcrypt'),
    ));
    $ws_port = $prefs['realtime_port'] ? $prefs['realtime_port'] : '8080';
    $websocket_full_base_url = $prefs['realtime_full_base_url'] ?? '';
    if (! empty($websocket_full_base_url)) {
        $parts = parse_url($websocket_full_base_url);
        $ws_port = $parts['port'] ?? null;
    }
    $ws_conn = @fsockopen('localhost', $ws_port);
    if (is_resource($ws_conn)) {
        $ws_listening = true;
        fclose($ws_conn);
    } else {
        $ws_listening = false;
    }
    $realtime = array(
        'feature_enabled' => array(
            'requirement' => tra('Feature enabled'),
            'status' => $prefs['feature_realtime'] === 'y' ? tra('good') : tra('bad'),
            'fitness_status' => $prefs['feature_realtime'] === 'y' ? FITNESS_STATUS_GOOD : FITNESS_STATUS_BAD,
            'message' => $prefs['feature_realtime'] === 'y' ? tra('Feature is enabled.') : tra('Feature is disabled in Tiki admin.'),
        ),
        'port_listening' => array(
            'requirement' => tra('Server listening'),
            'status' => $ws_listening ? tra('good') : tra('unsure'),
            'fitness_status' => $ws_listening ? FITNESS_STATUS_GOOD : FITNESS_STATUS_UNSURE,
            'message' => $ws_listening ? tra('Server is listening on local system port ') . $ws_port . '.' : tra('No server found listening on default port ') . $ws_port . tra('. Server might be running on a different port or not running at all.'),
        ),
        'connectivity' => array(
            'requirement' => tra('Connectivity'),
            'status' => 'js',
            'message_good' => tra('Connection to WS server established successfully.'),
            'message_bad' => tra('Could not establish connection to WS server. Check if server is listening and web server proxy configured correctly.'),
        ),
        'message_exchange' => array(
            'requirement' => tra('Message exchange'),
            'status' => 'js',
            'message_good' => tra('Successfully exchanged messages with realtime server.'),
            'message_bad' => tra('Could not exchange messages with realtime server. Check if server is running and configured correctly.'),
        )
    );
    $smarty->assign('realtime', $realtime);
    if (! empty($websocket_full_base_url)) {
        $smarty->assign('realtime_url', $websocket_full_base_url);
    } else {
        $smarty->assign('realtime_url', preg_replace('#http://#', 'ws://', preg_replace('#https://#', 'wss://', $base_url)) . 'ws/');
    }

    $output = array();
    $locales = null;
    exec("locale -a 2>&1", $output, $returnCode);
    // Verification of the return code.
    if ($returnCode === 0) {
        // The command was successfully executed, we filter it from the array.
        if (is_array($output)) {
            $locales = array_filter($output);
            sort($locales, SORT_STRING | SORT_FLAG_CASE);
        } else {
            if ($locales = preg_split("/\r ?\n/", $output)) {
                $locales = array_filter($locales);
                sort($locales, SORT_STRING | SORT_FLAG_CASE);
            } else {
                $locales = "Unexpected result";
            }
        }
    } else {
        // The command failed, we take the error array, and convert it to a string to display to the user
        foreach ($output as $errorLine) {
            $locales .= "$errorLine\n";
        }
    }

    $smarty->assign('locales', $locales);

    // Calculate dashboard statistics
    $critical_count = 0;
    $warning_count = 0;
    $info_count = 0;
    $good_count = 0;
    $critical_issues = array();

    // Count issues from different sections
    $all_properties = array_merge(
        $server_properties,
        $mysql_properties,
        $php_properties,
        $security,
        $tiki_security,
        isset($apache_properties) && is_array($apache_properties) ? $apache_properties : array(),
        isset($iis_properties) && is_array($iis_properties) ? $iis_properties : array()
    );

    foreach ($all_properties as $key => $item) {
        switch ($item['fitness_status']) {
            case FITNESS_STATUS_BAD:
            case FITNESS_STATUS_UNSAFE:
            case FITNESS_STATUS_RISKY:
                $critical_count++;

                // Determine the correct section based on which array the item came from
                $section = 'Server_Properties'; // default
                if (isset($server_properties[$key])) {
                    $section = 'Server_Properties';
                } elseif (isset($mysql_properties[$key])) {
                    $section = 'MySQL_or_MariaDB_Database_Properties';
                } elseif (isset($php_properties[$key])) {
                    $section = 'PHP_scripting_language_properties';
                } elseif (isset($security[$key])) {
                    $section = 'Tiki_Security';
                } elseif (isset($tiki_security[$key])) {
                    $section = 'Tiki_Security';
                } elseif (isset($apache_properties) && isset($apache_properties[$key])) {
                    $section = 'Apache_properties';
                } elseif (isset($iis_properties) && isset($iis_properties[$key])) {
                    $section = 'IIS_properties';
                }

                $critical_issues[] = array(
                    'title' => $key,
                    'message' => $item['message'],
                    'section' => $section
                );
                break;
            case FITNESS_STATUS_UNSURE:
                $warning_count++;
                break;
            case FITNESS_STATUS_INFO:
                $info_count++;
                break;
            case FITNESS_STATUS_GOOD:
            case FITNESS_STATUS_SAFE:
                $good_count++;
                break;
        }
    }

    // Count from packages
    if (isset($packagesToDisplay)) {
        foreach ($packagesToDisplay as $package) {
            switch ($package['fitness_status']) {
                case FITNESS_STATUS_BAD:
                case FITNESS_STATUS_UNSAFE:
                case FITNESS_STATUS_RISKY:
                    $critical_count++;
                    $critical_issues[] = array(
                        'title' => $package['name'],
                        'message' => implode(', ', $package['message']),
                        'section' => 'Tiki_Packages'
                    );
                    break;
                case FITNESS_STATUS_UNSURE:
                    $warning_count++;
                    break;
                case FITNESS_STATUS_INFO:
                    $info_count++;
                    break;
                case FITNESS_STATUS_GOOD:
                case FITNESS_STATUS_SAFE:
                    $good_count++;
                    break;
            }
        }
    }

    // Count from OCR
    if (isset($ocrToDisplay)) {
        foreach ($ocrToDisplay as $ocr) {
            switch ($ocr['fitness_status']) {
                case FITNESS_STATUS_BAD:
                case FITNESS_STATUS_UNSAFE:
                case FITNESS_STATUS_RISKY:
                    $critical_count++;
                    $critical_issues[] = array(
                        'title' => $ocr['name'],
                        'message' => $ocr['message'],
                        'section' => 'OCR_Status'
                    );
                    break;
                case FITNESS_STATUS_UNSURE:
                    $warning_count++;
                    break;
                case FITNESS_STATUS_INFO:
                    $info_count++;
                    break;
                case FITNESS_STATUS_GOOD:
                case FITNESS_STATUS_SAFE:
                    $good_count++;
                    break;
            }
        }
    }

    // Calculate health score (0-100)
    $total_checks = $critical_count + $warning_count + $info_count + $good_count;
    $health_score = $total_checks > 0 ? round((($good_count + $info_count * 0.5) / $total_checks) * 100) : 100;

    // Calculate percentages for progress bar
    $critical_percentage = $total_checks > 0 ? round(($critical_count / $total_checks) * 100) : 0;
    $warning_percentage = $total_checks > 0 ? round(($warning_count / $total_checks) * 100) : 0;
    $health_percentage = $total_checks > 0 ? round((($good_count + $info_count) / $total_checks) * 100) : 100;

    // Assign dashboard variables
    $smarty->assign('critical_count', $critical_count);
    $smarty->assign('warning_count', $warning_count);
    $smarty->assign('info_count', $info_count);
    $smarty->assign('good_count', $good_count);
    $smarty->assign('critical_issues', $critical_issues);
    $smarty->assign('health_score', $health_score);
    $smarty->assign('health_percentage', $health_percentage);
    $smarty->assign('critical_percentage', $critical_percentage);
    $smarty->assign('warning_percentage', $warning_percentage);
    $smarty->assign('total_checks', $total_checks);

    // Calculate source breakdowns for tooltips
    $main_critical = 0;
    $main_warning = 0;
    $main_info = 0;
    $main_good = 0;
    $packages_critical = 0;
    $packages_warning = 0;
    $packages_info = 0;
    $packages_good = 0;
    $ocr_critical = 0;
    $ocr_warning = 0;
    $ocr_info = 0;
    $ocr_good = 0;

    // Count main checks (from all_properties)
    foreach ($all_properties as $key => $item) {
        switch ($item['fitness_status']) {
            case FITNESS_STATUS_BAD:
            case FITNESS_STATUS_UNSAFE:
            case FITNESS_STATUS_RISKY:
                $main_critical++;
                break;
            case FITNESS_STATUS_UNSURE:
                $main_warning++;
                break;
            case FITNESS_STATUS_INFO:
                $main_info++;
                break;
            case FITNESS_STATUS_GOOD:
            case FITNESS_STATUS_SAFE:
                $main_good++;
                break;
        }
    }

    // Count packages
    if (isset($packagesToDisplay)) {
        foreach ($packagesToDisplay as $package) {
            switch ($package['fitness_status']) {
                case FITNESS_STATUS_BAD:
                case FITNESS_STATUS_UNSAFE:
                case FITNESS_STATUS_RISKY:
                    $packages_critical++;
                    break;
                case FITNESS_STATUS_UNSURE:
                    $packages_warning++;
                    break;
                case FITNESS_STATUS_INFO:
                    $packages_info++;
                    break;
                case FITNESS_STATUS_GOOD:
                case FITNESS_STATUS_SAFE:
                    $packages_good++;
                    break;
            }
        }
    }

    // Count OCR
    if (isset($ocrToDisplay)) {
        foreach ($ocrToDisplay as $ocr) {
            switch ($ocr['fitness_status']) {
                case FITNESS_STATUS_BAD:
                case FITNESS_STATUS_UNSAFE:
                case FITNESS_STATUS_RISKY:
                    $ocr_critical++;
                    break;
                case FITNESS_STATUS_UNSURE:
                    $ocr_warning++;
                    break;
                case FITNESS_STATUS_INFO:
                    $ocr_info++;
                    break;
                case FITNESS_STATUS_GOOD:
                case FITNESS_STATUS_SAFE:
                    $ocr_good++;
                    break;
            }
        }
    }

    // Create source breakdown array for tooltips
    $source_breakdown = array(
        'critical' => array(
            'main' => $main_critical,
            'packages' => $packages_critical,
            'ocr' => $ocr_critical
        ),
        'warning' => array(
            'main' => $main_warning,
            'packages' => $packages_warning,
            'ocr' => $ocr_warning
        ),
        'info' => array(
            'main' => $main_info,
            'packages' => $packages_info,
            'ocr' => $ocr_info
        ),
        'good' => array(
            'main' => $main_good,
            'packages' => $packages_good,
            'ocr' => $ocr_good
        )
    );

    // Assign source breakdown to Smarty
    $smarty->assign('source_breakdown', $source_breakdown);

    $smarty->assign('metatag_robots', 'NOINDEX, NOFOLLOW');
    $smarty->assign('mid', 'tiki-check.tpl');
    $smarty->display('tiki.tpl');
}

/**
 * Check package warnings based on specific nuances of each package
 * @param $messages
 * @param $package
 */
function checkPackageMessages($messages, $package)
{
    global $prefs;

    switch ($package['name']) {
        case 'tikiwiki/media-alchemyst':
        case 'media-alchemyst/media-alchemyst':
            try {
                if (! AlchemyLib::hasReadWritePolicies()) {
                    $messages['warnings'][] = tr(
                        'Alchemy requires "Read" and "Write" policy rights. More info: <a href="%0" target="_blank">%1</a>',
                        'https://doc.tiki.org/tiki-index.php?page=Media+Alchemyst#Document_to_Image_issues',
                        'Media Alchemyst - Document to Image issues'
                    );
                } else {
                    $messages['successes'][] = tr('Alchemy has "Read" and "Write" policy rights.');
                }
            } catch (\Exception $e) {
                $messages['warnings'][] = tr('Error when checking Alchemy "Read" and "Write" policy rights: %0', $e->getMessage());
            }

            // Check converter availability
            $converterType = $prefs['alchemy_converter_type'] ?: UnoconvStrategy::NAME;

            if ($converterType === UnoserverStrategy::NAME) {
                if (UnoserverStrategy::isLibraryAvailable()) {
                    $messages['successes'][] = tr('Unoserver (unoconvert) binary is available.');

                    if (UnoserverStrategy::isServerRunning()) {
                        $messages['successes'][] = tr(
                            'Unoserver daemon is running on port %0.',
                            $prefs['alchemy_unoserver_port'] ?: UnoserverStrategy::DEFAULT_PORT
                        );
                    } else {
                        $messages['warnings'][] = tr(
                            'Unoserver daemon is not running on port %0. Start the daemon with: unoserver --port %0',
                            $prefs['alchemy_unoserver_port'] ?: UnoserverStrategy::DEFAULT_PORT
                        );
                    }
                } else {
                    $messages['errors'][] = tr('Unoserver is selected but unoconvert binary is not found.');
                }
            } elseif ($converterType === UnoconvStrategy::NAME) {
                if (UnoconvStrategy::isLibraryAvailable()) {
                    $messages['successes'][] = tr('Unoconv library is available.');

                    if (! UnoconvStrategy::isPortAvailable()) {
                        $messages['successes'][] = tr(
                            'Unoconv listener is running on port %0.',
                            $prefs['alchemy_unoconv_port'] ?: UnoconvStrategy::DEFAULT_PORT
                        );
                    } else {
                        $messages['warnings'][] = tr(
                            'Unoconv listener is not running on port %0. Start with: unoconv --listener --port=%0',
                            $prefs['alchemy_unoconv_port'] ?: UnoconvStrategy::DEFAULT_PORT
                        );
                    }
                } else {
                    $messages['errors'][] = tr('Unoconv is selected but the library is not available.');
                }
            }

            if (! UnoserverStrategy::isLibraryAvailable() && ! UnoconvStrategy::isLibraryAvailable()) {
                $messages['errors'][] = tr('No document converter available. Please install unoserver or unoconv.');
            }

            break;
        default:
            $messages['successes'] = array();
            break;
    }

    return $messages;
}

/**
 * Check if paths set in preferences exist in the system, or if classes exist in project/system
 *
 * @param array $preferences An array with preference key and preference info
 *
 * @return array An array with warning messages.
 */
function checkPreferences(array $preferences)
{
    global $prefs;

    $messages = array(
        'successes' => array(),
        'warnings' => array()
    );

    foreach ($preferences as $prefKey => $pref) {
        if ($pref['type'] == 'path') {
            if (! empty($prefs[$prefKey])) {
                if (! file_exists($prefs[$prefKey])) {
                    $messages['warnings'][] = tr("The path '%0' on preference '%1' does not exist", $prefs[$prefKey], $pref['name']);
                } else {
                    $messages['successes'][] = tr("The path '%0' on preference '%1' exists", $prefs[$prefKey], $pref['name']);
                }
            }
        } elseif ($pref['type'] == 'classOptions') {
            if (isset($prefs[$prefKey])) {
                $options = $pref['options'][$prefs[$prefKey]];

                if (! empty($options['classLib'])) {
                    if (! class_exists($options['classLib'])) {
                        $messages['warnings'][] = tr("The lib '%0' on preference '%1', option '%2' does not exist", $options['classLib'], $pref['name'], $options['name']);
                    } else {
                        $messages['successes'][] = tr("The lib '%0' on preference '%1', option '%2' exists", $options['classLib'], $pref['name'], $options['name']);
                    }
                }

                if (! empty($options['className'])) {
                    if (! class_exists($options['className'])) {
                        $messages['warnings'][] = tr("The class '%0' needed for preference '%1', with option '%2' selected, does not exist", $options['className'], $pref['name'], $options['name']);
                    } else {
                        $messages['successes'][] = tr("The class '%0' needed for preference '%1', with option '%2' selected, exists", $options['className'], $pref['name'], $options['name']);
                    }
                }

                if (! empty($options['extension'])) {
                    if (! extension_loaded($options['extension'])) {
                        $messages['warnings'][] = tr("The extension '%0' on preference '%1', with option '%2' selected, is not loaded", $options['extension'], $pref['name'], $options['name']);
                    } else {
                        $messages['successes'][] = tr("The extension '%0' on preference '%1', with option '%2' selected, is loaded", $options['extension'], $pref['name'], $options['name']);
                    }
                }
            }
        }
    }

    return $messages;
}

/**
 * Check if a given command can be located in the system
 *
 * @param $command
 * @return bool true if available, false if not.
 */
function commandIsAvailable($command)
{
    if (str_starts_with(strtoupper(PHP_OS), 'WIN')) {
        $template = "where %s";
    } else {
        $template = "command -v %s 2>/dev/null";
    }

    $returnCode = '';
    if (function_exists('exec')) {
        exec(sprintf($template, escapeshellarg($command)), $output, $returnCode);
    }

    return $returnCode === 0;
}

/**
 * Check if a given url can be reach from the system
 *
 * @param string $url
 * @return bool true if available, false if not.
 */
function urlIsAvailable($url)
{
    $client = TikiLib::lib('tiki')->get_http_client($url);
    $response = $client->getResponse();

    return $response && $response->getStatusCode();
}

/**
 * Script to benchmark PHP and MySQL
 * @see https://github.com/odan/benchmark-php
 */
class BenchmarkPhp
{
    /**
     * Executes the benchmark and returns an array in the format expected by renderTable
     * @return array Benchmark results
     */
    public static function run()
    {
        set_time_limit(120); // 2 minutes

        $options = array();

        if (file_exists('db/local.php')) {
            require 'db/local.php';
            $options['db.host'] = $host_tiki;
            $options['db.user'] = $user_tiki;
            $options['db.pw'] = $pass_tiki;
            $options['db.name'] = $dbs_tiki;
        }

        $benchmarkResult = self::test_benchmark($options);

        $benchmark = $benchmarkResult['benchmark'];
        if (isset($benchmark['mysql'])) {
            foreach ($benchmark['mysql'] as $k => $v) {
                $benchmark['mysql.' . $k] = $v;
            }
            unset($benchmark['mysql']);
        }
        $benchmark['total'] = $benchmarkResult['total'];
        $benchmark = array_map(
            function ($v) {
                return array('value' => $v);
            },
            $benchmark
        );

        return $benchmark;
    }

    /**
     * Execute the benchmark
     * @param $settings database connection settings
     * @return array Benchmark results
     */
    protected static function test_benchmark($settings)
    {
        $timeStart = microtime(true);

        $result = array();
        $result['version'] = '1.1';
        $result['sysinfo']['time'] = date("Y-m-d H:i:s");
        $result['sysinfo']['php_version'] = PHP_VERSION;
        $result['sysinfo']['platform'] = PHP_OS;
        $result['sysinfo']['server_name'] = $_SERVER['SERVER_NAME'];
        $result['sysinfo']['server_addr'] = $_SERVER['SERVER_ADDR'];

        self::test_math($result);
        self::test_string($result);
        self::test_loops($result);
        self::test_ifelse($result);
        if (isset($settings['db.host']) && function_exists('mysqli_connect')) {
            self::test_mysql($result, $settings);
        }

        $result['total'] = self::timer_diff($timeStart);
        return $result;
    }

    /**
     * Benchmark the execution of multiple math functions
     * @param $result Benchmark results
     * @param int $count Number of iterations
     */
    protected static function test_math(&$result, $count = 400000)
    {
        $timeStart = microtime(true);

        for ($i = 0; $i < $count; $i++) {
            sin($i);
            asin($i);
            cos($i);
            acos($i);
            tan($i);
            atan($i);
            abs($i);
            floor($i);
            exp($i);
            is_finite($i);
            is_nan($i);
            sqrt($i);
            log10($i);
        }
        $result['benchmark']['math'] = self::timer_diff($timeStart);
    }

    /**
     * Benchmark the execution of multiple string functions
     * @param $result Benchmark results
     * @param int $count Number of iterations
     */
    protected static function test_string(&$result, $count = 400000)
    {
        $timeStart = microtime(true);

        $string = 'the quick brown fox jumps over the lazy dog';
        for ($i = 0; $i < $count; $i++) {
            addslashes($string);
            chunk_split($string);
            metaphone($string);
            strip_tags($string);
            md5($string);
            sha1($string);
            strtoupper($string);
            strtolower($string);
            strrev($string);
            strlen($string);
            soundex($string);
            ord($string);
        }
        $result['benchmark']['string'] = self::timer_diff($timeStart);
    }

    /**
     * Benchmark the execution of loops
     * @param $result Benchmark results
     * @param int $count Number of iterations
     */
    protected static function test_loops(&$result, $count = 4000000)
    {
        $timeStart = microtime(true);
        for ($i = 0; $i < $count; ++$i) {
        }
        $i = 0;
        while ($i < $count) {
            ++$i;
        }
        $result['benchmark']['loops'] = self::timer_diff($timeStart);
    }

    /**
     * Benchmark the execution of conditional operators
     * @param $result Benchmark results
     * @param int $count Number of iterations
     */
    protected static function test_ifelse(&$result, $count = 4000000)
    {
        $timeStart = microtime(true);
        for ($i = 0; $i < $count; $i++) {
            if ($i == -1) {
            } elseif ($i == -2) {
            } else {
                if ($i == -3) {
                }
            }
        }
        $result['benchmark']['ifelse'] = self::timer_diff($timeStart);
    }

    /**
     * Benchmark MySQL operations
     * @param $result Benchmark results
     * @param $settings MySQL connection information
     * @return array
     */
    protected static function test_mysql(&$result, $settings)
    {
        $timeStart = microtime(true);

        $link = mysqli_connect($settings['db.host'], $settings['db.user'], $settings['db.pw']);
        $result['benchmark']['mysql']['connect'] = self::timer_diff($timeStart);

        mysqli_select_db($link, $settings['db.name']);
        $result['benchmark']['mysql']['select_db'] = self::timer_diff($timeStart);

        $dbResult = mysqli_query($link, 'SELECT VERSION() as version;');
        $arr_row = mysqli_fetch_array($dbResult);
        $result['sysinfo']['mysql_version'] = $arr_row['version'];
        $result['benchmark']['mysql']['query_version'] = self::timer_diff($timeStart);

        $isMariaDB = stripos($arr_row['version'], 'mariadb') !== false;
        preg_match('/(\d+\.\d+)/', $arr_row['version'], $matches);
        $dbVersion = ! empty($matches[1]) ? trim($matches[1]) : '0.0';
        $useEncode = ($isMariaDB || version_compare($dbVersion, '8.0', '<'));
        $query = $useEncode
            ? "SELECT BENCHMARK(1000000, ENCODE('hello', RAND()));"
            : "SELECT BENCHMARK(1000000, AES_ENCRYPT('hello', 'benchmark_key'));";

        $dbResult = mysqli_query($link, $query);
        $result['benchmark']['mysql']['query_benchmark'] = self::timer_diff($timeStart);

        mysqli_close($link);

        $result['benchmark']['mysql']['total'] = self::timer_diff($timeStart);
        return $result;
    }

    /**
     * Helper to calculate time elapsed
     * @param $timeStart time to compare against now
     * @return string time elapsed
     */
    protected static function timer_diff($timeStart)
    {
        return number_format(microtime(true) - $timeStart, 3);
    }
}

/**
 * Identify files, like backup copies made by editors, or manual copies of the local.php files,
 * that may be accessed remotely and, because they are not interpreted as PHP, may expose the source,
 * which might contain credentials or other sensitive information.
 * Ref: http://feross.org/cmsploit/
 *
 * @param array $files Array of filenames. Suspicious files will be added to this array.
 * @param string $sourceDir Path of the directory to check
 */
function check_for_remote_readable_files(array &$files, $sourceDir = 'db')
{
    //fix dir slash
    $sourceDir = str_replace('\\', '/', $sourceDir);

    if (! str_ends_with($sourceDir, '/')) {
        $sourceDir .= '/';
    }

    if (! is_dir($sourceDir)) {
        return;
    }

    $sourceDirHandler = opendir($sourceDir);

    if ($sourceDirHandler === false) {
        return;
    }

    while ($file = readdir($sourceDirHandler)) {
        // Skip ".", ".."
        if ($file == '.' || $file == '..') {
            continue;
        }

        $sourceFilePath = $sourceDir . $file;

        if (is_dir($sourceFilePath)) {
            check_for_remote_readable_files($files, $sourceFilePath);
        }

        if (! is_file($sourceFilePath)) {
            continue;
        }

        $pattern = '/(^#.*#|~|.sw[op])$/';
        preg_match($pattern, $file, $matches);

        if (! empty($matches[1])) {
            $files[] = $file;
            continue;
        }

        // Match "local.php.bak", "local.php.bck", "local.php.save", "local.php." or "local.txt", for example
        $pattern = '/local(?!.*[.]php$).*$/'; // The negative lookahead prevents local.php and other files which will be interpreted as PHP from matching.
        preg_match($pattern, $file, $matches);

        if (! empty($matches[0])) {
            $files[] = $file;
            continue;
        }
    }
}

function check_isIIS()
{
    static $IIS;
    // Sample value Microsoft-IIS/7.5
    if (! isset($IIS) && isset($_SERVER['SERVER_SOFTWARE'])) {
        $IIS = str_starts_with($_SERVER['SERVER_SOFTWARE'], 'Microsoft-IIS');
    }
    return $IIS;
}

function check_hasIIS_UrlRewriteModule()
{
    return isset($_SERVER['IIS_UrlRewriteModule']) == true;
}

function get_content_from_url($url)
{
    if (function_exists('curl_init') && function_exists('curl_exec')) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
        if (isset($_SERVER) && isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {
            curl_setopt($curl, CURLOPT_USERPWD, $_SERVER['PHP_AUTH_USER'] . ":" . $_SERVER['PHP_AUTH_PW']);
        }
        $content = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if ($http_code != 200) {
            $content = "fail-http-" . $http_code;
        }
    } else {
        $content = "fail-no-request-done";
    }
    return $content;
}

/**
 * Check database configuration file permissions
 * @return array Array of security issues found
 */
function check_database_config_permissions()
{
    $issues = array();

    // Check db/local.php permissions
    if (file_exists('db/local.php')) {
        $perms = fileperms('db/local.php');
        // Check if world readable (others can read)
        if (($perms & 0x0004) || ($perms & 0x0002)) {
            $issues[] = 'db/local.php is world-readable';
        }
    }

    // Check for other potential config files
    $config_files = array('db/local.php', 'db/local.php.bak', 'db/local.php.backup');
    foreach ($config_files as $file) {
        if (file_exists($file)) {
            $perms = fileperms($file);
            if (($perms & 0x0004) || ($perms & 0x0002)) {
                $issues[] = $file . ' is world-readable';
            }
        }
    }

    return $issues;
}

/**
 * Check for phpMyAdmin installations
 * @return array Array of security issues found
 */
function check_phpmyadmin_installations()
{
    $issues = array();

    // Common phpMyAdmin paths to check
    $phpmyadmin_paths = array(
        'phpmyadmin',
        'pma',
        'mysql',
        'myadmin',
        'phpMyAdmin',
        'phpmyadmin2',
        'phpmyadmin3',
        'phpmyadmin4'
    );

    // Check for phpMyAdmin directories
    foreach ($phpmyadmin_paths as $path) {
        if (is_dir($path)) {
            $issues[] = 'phpMyAdmin directory found: ' . $path;
        }
    }

    // Check for phpMyAdmin files in document root
    $phpmyadmin_files = array(
        'phpmyadmin.php',
        'pma.php',
        'mysql.php',
        'myadmin.php'
    );

    foreach ($phpmyadmin_files as $file) {
        if (file_exists($file)) {
            $issues[] = 'phpMyAdmin file found: ' . $file;
        }
    }

    return $issues;
}

/**
 * Check for Adminer installations
 * @return array Array of security issues found
 */
function check_adminer_installations()
{
    $issues = array();

    // Check for Adminer files
    $adminer_files = array(
        'adminer.php',
        'adminer-*.php',
        'adminer/index.php',
        'adminer/adminer.php'
    );

    foreach ($adminer_files as $pattern) {
        if (str_contains($pattern, '*')) {
            // Handle wildcard patterns
            $files = glob($pattern);
            foreach ($files as $file) {
                $issues[] = 'Adminer file found: ' . $file;
            }
        } else {
            if (file_exists($pattern)) {
                $issues[] = 'Adminer file found: ' . $pattern;
            }
        }
    }

    // Check for Adminer directory
    if (is_dir('adminer')) {
        $issues[] = 'Adminer directory found: adminer/';
    }

    return $issues;
}

/**
 * Check for backup configuration files
 * @return array Array of security issues found
 */
function check_backup_configuration_files()
{
    $issues = array();

    // Directories to check for backup files
    $directories = array('.', 'db', 'lib', 'templates');

    // Backup file patterns
    $backup_patterns = array(
        '*.bak',
        '*.backup',
        '*.old',
        '*.tmp',
        '*~',
        '*.swp',
        '*.swo',
        '*.orig',
        '*.save'
    );

    foreach ($directories as $dir) {
        if (is_dir($dir)) {
            foreach ($backup_patterns as $pattern) {
                $files = glob($dir . '/' . $pattern);
                foreach ($files as $file) {
                    // Skip if it's a PHP file that will be interpreted
                    if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                        continue;
                    }
                    $issues[] = 'Backup file found: ' . $file;
                }
            }
        }
    }

    return $issues;
}

/**
 * Check for directory listing vulnerabilities
 * @return array Array of security issues found
 */
function check_directory_listing_vulnerabilities()
{
    $issues = array();

    // Check for .htaccess files that might enable directory listing
    $htaccess_files = array('.htaccess', 'htaccess.txt');

    foreach ($htaccess_files as $file) {
        if (file_exists($file)) {
            $content = file_get_contents($file);
            if (str_contains($content, 'Options +Indexes')) {
                $issues[] = 'Directory listing enabled in ' . $file;
            }
        }
    }

    // Check sensitive directories for index files
    $sensitive_dirs = array('db', 'lib', 'templates', 'temp');

    foreach ($sensitive_dirs as $dir) {
        if (is_dir($dir)) {
            $index_files = array('index.php', 'index.html', 'index.htm');
            foreach ($index_files as $index_file) {
                if (file_exists($dir . '/' . $index_file)) {
                    $issues[] = 'Index file found in sensitive directory: ' . $dir . '/' . $index_file;
                }
            }
        }
    }

    return $issues;
}

/**
 * Check SSL/TLS configuration
 * @return array Array of security issues found
 */
function check_ssl_configuration()
{
    $issues = array();

    // Check if HTTPS is being used
    if (! isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
        $issues[] = 'HTTPS not detected - connection may not be encrypted';
    }

    // Check for secure headers
    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        if (! isset($headers['X-Forwarded-Proto']) || $headers['X-Forwarded-Proto'] !== 'https') {
            // Only warn if we're not on HTTPS and no secure proxy is detected
            if (! isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
                $issues[] = 'No secure proxy headers detected';
            }
        }
    }

    return $issues;
}

function no_cache_found()
{
    global $php_properties;

    if (check_isIIS()) {
        $php_properties['ByteCode Cache'] = array(
            'fitness' => tra('info'),
            'fitness_status' => FITNESS_STATUS_INFO,
            'setting' => 'N/A',
            'message' => tra('WinCache is being used as the ByteCode Cache; if one of these were used and correctly configured, performance would be increased. See Admin->Performance in the Tiki for more details.')
        );
    } else {
        $php_properties['ByteCode Cache'] = array(
            'fitness' => tra('info'),
            'fitness_status' => FITNESS_STATUS_INFO,
            'setting' => 'N/A',
            'message' => tra('OPcache is being used as the ByteCode Cache; if one of these were used and correctly configured, performance would be increased. See Admin->Performance in the Tiki for more details.')
        );
    }
}

// Debug: Count FITNESS_STATUS constants
function countFitnessStatuses($arrays)
{
    $counts = array(
        'good' => 0,
        'bad' => 0,
        'unsure' => 0,
        'info' => 0,
        'N/A' => 0,
        'safe' => 0,
        'unsafe' => 0,
        'unknown' => 0,
        'risky' => 0
    );

    foreach ($arrays as $array_name => $array_data) {
        if (is_array($array_data)) {
            foreach ($array_data as $key => $item) {
                if (is_array($item) && isset($item['fitness'])) {
                    $status = $item['fitness'];
                    if (isset($counts[$status])) {
                        $counts[$status]++;
                    }
                }
            }
        }
    }

    return $counts;
}



// Initialize undefined arrays to prevent warnings
if (! isset($tiki_performance)) {
    $tiki_performance = array();
}
if (! isset($tiki_requirements)) {
    $tiki_requirements = array();
}
if (! isset($tiki_available)) {
    $tiki_available = array();
}
if (! isset($tiki_installed)) {
    $tiki_installed = array();
}
if (! isset($tiki_configured)) {
    $tiki_configured = array();
}
if (! isset($tiki_working)) {
    $tiki_working = array();
}
if (! isset($tiki_health)) {
    $tiki_health = array();
}
if (! isset($tiki_issues)) {
    $tiki_issues = array();
}
if (! isset($tiki_warnings)) {
    $tiki_warnings = array();
}
if (! isset($tiki_info)) {
    $tiki_info = array();
}
if (! isset($tiki_good)) {
    $tiki_good = array();
}
if (! isset($tiki_permissions)) {
    $tiki_permissions = array();
}
if (! isset($tiki_properties)) {
    $tiki_properties = array();
}
if (! isset($database_properties)) {
    $database_properties = array();
}

// Count statuses in all arrays
$all_arrays = array(
    'php_properties' => $php_properties,
    'server_properties' => $server_properties,
    'database_properties' => $database_properties,
    'tiki_properties' => $tiki_properties,
    'tiki_security' => $tiki_security,
    'tiki_permissions' => $tiki_permissions,
    'tiki_performance' => $tiki_performance,
    'tiki_requirements' => $tiki_requirements,
    'tiki_available' => $tiki_available,
    'tiki_installed' => $tiki_installed,
    'tiki_configured' => $tiki_configured,
    'tiki_working' => $tiki_working,
    'tiki_health' => $tiki_health,
    'tiki_issues' => $tiki_issues,
    'tiki_warnings' => $tiki_warnings,
    'tiki_info' => $tiki_info,
    'tiki_good' => $tiki_good
);

$fitness_counts = countFitnessStatuses($all_arrays);

// Calculate dashboard statistics for main template (same as AJAX section)
$main_critical = 0;
$main_warning = 0;
$main_info = 0;
$main_good = 0;
$critical_count = 0;
$warning_count = 0;
$info_count = 0;
$good_count = 0;
$critical_issues = array();

// Count issues from different sections (same logic as AJAX section)
$all_properties = array_merge(
    $server_properties,
    $mysql_properties,
    $php_properties,
    $security,
    $tiki_security,
    isset($apache_properties) && is_array($apache_properties) ? $apache_properties : array(),
    isset($iis_properties) && is_array($iis_properties) ? $iis_properties : array(),
    isset($tiki_properties) && is_array($tiki_properties) ? $tiki_properties : array(),
    isset($database_properties) && is_array($database_properties) ? $database_properties : array()
);

foreach ($all_properties as $key => $item) {
    switch ($item['fitness_status']) {
        case FITNESS_STATUS_BAD:
        case FITNESS_STATUS_UNSAFE:
        case FITNESS_STATUS_RISKY:
            $main_critical++;
            $critical_count++;

            // Determine the correct section based on which array the item came from
            $section = 'Server_Properties'; // default
            if (isset($server_properties[$key])) {
                $section = 'Server_Properties';
            } elseif (isset($mysql_properties[$key])) {
                $section = 'MySQL_or_MariaDB_Database_Properties';
            } elseif (isset($php_properties[$key])) {
                $section = 'PHP_scripting_language_properties';
            } elseif (isset($security[$key])) {
                $section = 'Tiki_Security';
            } elseif (isset($tiki_security[$key])) {
                $section = 'Tiki_Security';
            } elseif (isset($apache_properties) && isset($apache_properties[$key])) {
                $section = 'Apache_properties';
            } elseif (isset($iis_properties) && isset($iis_properties[$key])) {
                $section = 'IIS_properties';
            } elseif (isset($tiki_properties) && isset($tiki_properties[$key])) {
                $section = 'Tiki_Properties';
            } elseif (isset($database_properties) && isset($database_properties[$key])) {
                $section = 'Database_Properties';
            }

            $critical_issues[] = array(
                'title' => $key,
                'message' => $item['message'],
                'section' => $section
            );
            break;
        case FITNESS_STATUS_UNSURE:
            $main_warning++;
            $warning_count++;
            break;
        case FITNESS_STATUS_INFO:
            $main_info++;
            $info_count++;
            break;
        case FITNESS_STATUS_GOOD:
        case FITNESS_STATUS_SAFE:
            $main_good++;
            $good_count++;
            break;
    }
}

// Count from packages
$packages_critical = 0;
$packages_warning = 0;
$packages_info = 0;
$packages_good = 0;

if (isset($packagesToDisplay)) {
    foreach ($packagesToDisplay as $package) {
        switch ($package['fitness_status']) {
            case FITNESS_STATUS_BAD:
            case FITNESS_STATUS_UNSAFE:
            case FITNESS_STATUS_RISKY:
                $critical_count++;
                $packages_critical++;
                $critical_issues[] = array(
                    'title' => $package['name'],
                    'message' => implode(', ', $package['message']),
                    'section' => 'Tiki_Packages'
                );
                break;
            case FITNESS_STATUS_UNSURE:
                $warning_count++;
                $packages_warning++;
                break;
            case FITNESS_STATUS_INFO:
                $info_count++;
                $packages_info++;
                break;
            case FITNESS_STATUS_GOOD:
            case FITNESS_STATUS_SAFE:
                $good_count++;
                $packages_good++;
                break;
        }
    }
}

// Count from OCR
$ocr_critical = 0;
$ocr_warning = 0;
$ocr_info = 0;
$ocr_good = 0;

if (isset($ocrToDisplay)) {
    foreach ($ocrToDisplay as $ocr) {
        switch ($ocr['fitness_status']) {
            case FITNESS_STATUS_BAD:
            case FITNESS_STATUS_UNSAFE:
            case FITNESS_STATUS_RISKY:
                $critical_count++;
                $ocr_critical++;
                $critical_issues[] = array(
                    'title' => $ocr['name'],
                    'message' => $ocr['message'],
                    'section' => 'OCR_Status'
                );
                break;
            case FITNESS_STATUS_UNSURE:
                $warning_count++;
                $ocr_warning++;
                break;
            case FITNESS_STATUS_INFO:
                $info_count++;
                $ocr_info++;
                break;
            case FITNESS_STATUS_GOOD:
            case FITNESS_STATUS_SAFE:
                $good_count++;
                $ocr_good++;
                break;
        }
    }
}

// Calculate health score (0-100)
$total_checks = $critical_count + $warning_count + $info_count + $good_count;
$health_score = $total_checks > 0 ? round((($good_count + $info_count * 0.5) / $total_checks) * 100) : 100;

// Calculate percentages for progress bar
$critical_percentage = $total_checks > 0 ? round(($critical_count / $total_checks) * 100) : 0;
$warning_percentage = $total_checks > 0 ? round(($warning_count / $total_checks) * 100) : 0;
$health_percentage = $total_checks > 0 ? round((($good_count + $info_count) / $total_checks) * 100) : 100;

// Assign dashboard variables to Smarty
$smarty->assign('critical_count', $critical_count);
$smarty->assign('warning_count', $warning_count);
$smarty->assign('info_count', $info_count);
$smarty->assign('good_count', $good_count);
$smarty->assign('critical_issues', $critical_issues);
$smarty->assign('health_score', $health_score);
$smarty->assign('health_percentage', $health_percentage);
$smarty->assign('critical_percentage', $critical_percentage);
$smarty->assign('warning_percentage', $warning_percentage);

// Calculate source breakdowns for tooltips
$source_breakdown = array(
    'critical' => array(
        'main' => $main_critical,
        'packages' => $packages_critical,
        'ocr' => $ocr_critical
    ),
    'warning' => array(
        'main' => $main_warning,
        'packages' => $packages_warning,
        'ocr' => $ocr_warning
    ),
    'info' => array(
        'main' => $main_info,
        'packages' => $packages_info,
        'ocr' => $ocr_info
    ),
    'good' => array(
        'main' => $main_good,
        'packages' => $packages_good,
        'ocr' => $ocr_good
    )
);

// Also add to Smarty for template access
$smarty->assign('fitness_counts', $fitness_counts);

// Create formatted tooltip HTML for each status type
function formatTooltipHtml($type, $breakdown)
{
    $title = ucfirst($type) . ' Breakdown:';
    $main = $breakdown['main'] ?? 0;
    $packages = $breakdown['packages'] ?? 0;
    $ocr = $breakdown['ocr'] ?? 0;

    return "<strong>{$title}</strong><br>" .
           "• Main Checks: {$main}<br>" .
           "• Packages: {$packages}<br>" .
           "• OCR: {$ocr}";
}

$tooltip_html = array(
    'critical' => formatTooltipHtml('critical', $source_breakdown['critical']),
    'warning' => formatTooltipHtml('warning', $source_breakdown['warning']),
    'info' => formatTooltipHtml('info', $source_breakdown['info']),
    'good' => formatTooltipHtml('good', $source_breakdown['good'])
);

// Assign source breakdown to Smarty
$smarty->assign('source_breakdown', $source_breakdown);
$smarty->assign('tooltip_html', $tooltip_html);
