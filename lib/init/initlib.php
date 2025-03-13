<?php

/**
 * Tiki initialization functions and classes
 *
 * @package TikiWiki
 * @subpackage lib\init
 * @copyright (c) Copyright by authors of the Tiki Wiki CMS Groupware Project. All Rights Reserved. See copyright.txt for details and a complete list of authors.
 * @licence Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
 */

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Symfony\Component\Yaml\Yaml;
use Tiki\Package\ExtensionManager as PackageExtensionManager;
use Symfony\Component\Config\Exception\FileLocatorFileNotFoundException;

//this script may only be included - so its better to die if called directly.
if (strpos($_SERVER['SCRIPT_NAME'], basename(__FILE__)) !== false) {
    header('location: index.php');
    exit;
}

require_once('lib/enforce_php_version_constraints.php');

if (! file_exists(__DIR__ . '/../../vendor_bundled/vendor/autoload.php')) {
    $error = "Your Tiki is not completely installed because Composer has not been run to fetch package dependencies.\n" .
        "You need to run 'sh setup.sh' from the command line.\n" .
        "See https://doc.tiki.org/Composer for details.\n";

    if (http_response_code() === false) { // if running in cli
        $error = "\033[31m" . $error . "\e[0m\n";
    }
    echo $error;
    exit(1);
}

require_once __DIR__ . '/../../vendor_bundled/vendor/autoload.php'; // vendor libs bundled into tiki

// vendor libs managed by the user using composer (if any)
$legacyVendorPath = __DIR__ . '/../../' . TIKI_VENDOR_NONBUNDLED_PATH;
$legacyVendorAutoloaderPath = $legacyVendorPath . '/autoload.php';
if (file_exists($legacyVendorAutoloaderPath)) {
    // In some cases, the vendor folder may contain the files from the old vendor folder before migrating to
    // vendor_bundled. In these cases eg. when unzipping a Tiki => 17.x on top of an existing Tiki <= 16.x instance,
    // loading the autoload from the vendor folder will cause issues.
    // We check for some core libraries (ZendFramework, Smarty and Adodb), if they are all present in the
    // vendor folder we will consider that there is a old vendor folder, and skip loading the autoload.php unless
    // there is a file called do_not_clean.txt inside the vendor folder (we will only check the file exists)
    if (
        file_exists($legacyVendorPath . '/do_not_clean.txt')
        || ! ( // check the existence of critical files denoting a legacy vendor folder
            (file_exists($legacyVendorPath . '/zendframework/zend-config/src/Config.php') //ZF2
                || file_exists($legacyVendorPath . '/bombayworks/zendframework1/library/Zend/Config.php')) //ZF1
            && (file_exists($legacyVendorPath . '/smarty/smarty/libs/Smarty.class.php') //Smarty
                || file_exists($legacyVendorPath . '/smarty/smarty/distribution/libs/Smarty.class.php')) //Smarty
            && file_exists($legacyVendorPath . '/adodb/adodb/adodb.inc.php') //Adodb
        )
    ) {
        $autoloader = require_once($legacyVendorAutoloaderPath);
        // Autoload extension packages libs
        foreach (\Tiki\Package\ExtensionManager::getEnabledPackageExtensions(false) as $package) {
            if (is_dir($package['path'] . '/lib/') && strpos($package['path'], TIKI_VENDOR_CUSTOM_PATH) === false) {
                $autoloader->addPsr4(str_replace('/', '\\', $package['name']) . '\\', $package['path'] . '/lib/');
            }
        }
    }
}

// vendor libraries managed by the user, packaged (if any)
if (is_dir(__DIR__ . '/../../' . TIKI_VENDOR_CUSTOM_PATH)) {
    foreach (new DirectoryIterator(__DIR__ . '/../../' . TIKI_VENDOR_CUSTOM_PATH) as $fileInfo) {
        if (! $fileInfo->isDir() || $fileInfo->isDot()) {
            continue;
        }
        if (file_exists($fileInfo->getPathname() . '/autoload.php')) {
            require_once $fileInfo->getPathname() . '/autoload.php';
             // Autoload extension packages libs
            $packagePath = $fileInfo->getPathname();
            if (is_dir($packagePath . '/lib/') && $composerJson = json_decode(file_get_contents($packagePath . '/composer.json'), true)) {
                $packageName = $composerJson['name'] ?? '';
                if ($packageName && \Tiki\Package\ExtensionManager::isExtension($packageName, $packagePath) && \Tiki\Package\ExtensionManager::isEnabled($packageName)) {
                    $autoloader->addPsr4(str_replace('/', '\\', $packageName) . '\\', $packagePath . '/lib/');
                }
            }
        }
    }
}

spl_autoload_register('Tiki\PSR12Migration\Autoload::autoloadAlias');
spl_autoload_register('Tiki_Autoload::autoload');

// Inclusion of all files inside lib/smarty_tiki, so all smarty_* functions are defined
$smartyTikiDir = __DIR__ . '/../smarty_tiki/';
$smartyTikiFiles = scandir($smartyTikiDir);

foreach ($smartyTikiFiles as $file) {
    if (is_file($smartyTikiDir . $file) && pathinfo($smartyTikiDir . $file, PATHINFO_EXTENSION) === 'php' && $file !== 'index.php') {
        require_once($smartyTikiDir . $file);
    }
}

/**
 * A callback for PHP set_error_handler()
 * Set how Tiki will report Errors
 * @param $errno
 * @param $errstr
 * @param $errfile
 * @param $errline
 *
 * @return bool Skip the built-in php error handler after this.
 */
function tiki_error_handling($errno, $errstr, $errfile, $errline): bool
{
    global $prefs, $phpErrors, $tiki_p_admin;

    $includeSmartyNotice = isset($prefs['smarty_notice_reporting']) && $prefs['smarty_notice_reporting'] === 'y';

    /**
     * @see http://ca3.php.net/manual/en/language.operators.errorcontrol.php#98895
     * @see http://php.net/set_error_handler
     * Errors that are suppressed by the @ operator or not included in PHP's `error_reporting()` level are ignored by Tiki's error handler.
     * maintaining independence from PHP's error reporting settings.
     * However, if `smarty_notice_reporting` is enabled, Tiki will handle and report Smarty notices
     * even if PHP is configured to suppress them (via the @ operator or `error_reporting()` settings).
     * By using this check, we ensure that:
     * - Tiki respects PHP's native error suppression for all non-Smart notices.
     * - Tiki includes Smarty notices in the UI or external logs when `smarty_notice_reporting` is enabled,
     *   making Smarty notice handling independent of PHP's configuration.
     */

    if (0 === (error_reporting() & $errno) && ! $includeSmartyNotice) {
        return true;
    }

    // FIXME: Optionally return false so errors are still logged
    $err[E_ERROR]           = 'E_ERROR';
    $err[E_CORE_ERROR]      = 'E_CORE_ERROR';
    $err[E_USER_ERROR]      = 'E_USER_ERROR';
    $err[E_COMPILE_ERROR]   = 'E_COMPILE_ERROR';
    $err[E_WARNING]         = 'E_WARNING';
    $err[E_CORE_WARNING]    = 'E_CORE_WARNING';
    $err[E_USER_WARNING]    = 'E_USER_WARNING';
    $err[E_COMPILE_WARNING] = 'E_COMPILE_WARNING';
    $err[E_PARSE]           = 'E_PARSE';
    $err[E_NOTICE]          = 'E_NOTICE';
    $err[E_USER_NOTICE]     = 'E_USER_NOTICE';
    $err[E_DEPRECATED]      = 'E_DEPRECATED';
    $err[E_USER_DEPRECATED] = 'E_USER_DEPRECATED';

    global $tikipath;
    $errfile = str_replace($tikipath, '', $errfile);
    switch ($errno) {
        case E_ERROR:
        case E_CORE_ERROR:
        case E_USER_ERROR:
        case E_COMPILE_ERROR:
        case E_WARNING:
        case E_CORE_WARNING:
        case E_USER_WARNING:
        case E_COMPILE_WARNING:
        case E_PARSE:
        case E_RECOVERABLE_ERROR:
            $type = 'ERROR';
            break;
        case E_NOTICE:
        case E_USER_NOTICE:
        case E_DEPRECATED:
        case E_USER_DEPRECATED:
            //Ok, the following should no longer be needed.  There was a bug were we set smarty error level bitmask incorrectly.  Then another where we clobbered what we set in the constructor.  Leaving it only if there are cases we missed . benoitg - 2023-07-03
            /*if (! defined('THIRD_PARTY_LIBS_PATTERN') ||  ! preg_match(THIRD_PARTY_LIBS_PATTERN, $errfile)) {
                if (! empty($prefs['smarty_notice_reporting']) && $prefs['smarty_notice_reporting'] != 'y' && strstr($errfile, '.tpl.php')) {
                    return true;
                }
            }*/
            $type = 'NOTICE';
            break;
        default:
            //Unknow error type, we still want to display/report it
            $type = 'UNKNOWN';
    }
    //This will log to glitchtip, and also call any handlers that were registered BEFORE tiki.  So we don't care about what they tell us to do in their return value.
    $retval = TikiLib::lib('errortracking')->handleError($errno, $errstr, $errfile, $errline);
    $errno = $err[$errno] ?? 'UNKNOWN';

    $back = "<div class='rbox-data p-3 mb-3' style='font-size: 12px; border: 1px solid'>";
    $back .= $type . " ($errno): <b>" . $errstr . "</b><br />";
    $back .= "At line $errline in $errfile"; // $errfile comes after $errline to ease selection for copy-pasting.
    $back .= "</div>";

    $phpErrors[] = $back;

    return false;
}

// Patch missing $_SERVER['REQUEST_URI'] on IIS6
if (empty($_SERVER['REQUEST_URI'])) {
    if (Tiki\TikiInit::isIIS()) {
        $_SERVER['REQUEST_URI'] = $_SERVER['SCRIPT_NAME'];
    }
}
