<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Symfony\Component\Config\Exception\FileLocatorFileNotFoundException;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Tiki\Package\ExtensionManager as PackageExtensionManager;
use TikiCachedContainer;
use TikiDb;
use TWVersion;

use function file_put_contents;
use function iconv;

use const TIKI_PATH;

/**
 * performs some checks on the underlying system, before initializing Tiki.
 * @package TikiWiki\lib\init
 */
class TikiInit
{
    /**
     * dummy constructor
     */
    public function __construct()
    {
    }

    public static function getContainer()
    {
        /** @var ContainerBuilder $container */
        static $container;

        if ($container) {
            return $container;
        }

        $TWV = new TWVersion();
        $version = $TWV->getVersion();

        $cache = TIKI_PATH . '/' . TEMP_CACHE_PATH . '/container.php';
        if (is_readable($cache)) {
            require_once $cache;

            if (! class_exists('TikiCachedContainer')) {
                // mangled or otherwise invalid container
                unlink($cache);
            } else {
                $container = new TikiCachedContainer();

                /* If the server moved or was upgraded, the container must be recreated */
                if (
                    TIKI_PATH == $container->getParameter('kernel.root_dir') &&
                    $container->hasParameter('tiki.version') &&                    // no version before 15.0
                    $container->getParameter('tiki.version') === $version
                ) {
                    if (TikiDb::isAvailable()) {
                        $container->set('tiki.lib.db', TikiDb::get());
                    }

                    return $container;
                } else {
                    /* This server moved or was upgraded, container must be recreated */
                    unlink($cache);
                }
            }
        }

        $container = new ContainerBuilder();
        $container->addCompilerPass(new \Tiki\MailIn\Provider\CompilerPass());
        $container->addCompilerPass(new \Tiki\Recommendation\Engine\CompilerPass());
        $container->addCompilerPass(new \Tiki\Wiki\SlugManager\CompilerPass());
        $container->addCompilerPass(new \Search\Federated\CompilerPass());
        $container->addCompilerPass(new \Tracker\CompilerPass());

        $container->setParameter('kernel.root_dir', TIKI_PATH);
        $container->setParameter('tiki.version', $version);
        $loadPaths = [TIKI_PATH . '/' . TIKI_MAIN_CLASS_LOADER_XML_PATH,
        TIKI_PATH . '/' . TIKI_CUSTOMIZATIONS_CLASS_LOADER_XML_PATH];
        $loader = new XmlFileLoader($container, new FileLocator($loadPaths));

        $tempDir = sys_get_temp_dir();
        $tmpfile = tempnam($tempDir, 'symfony');

        if (! is_writable($tmpfile) || empty($tmpfile)) {
            throw new \RuntimeException("Temporary folder is set to $tempDir, but it is not accessible by Tiki.");
        }

        $loader->load('tiki.xml');
        $loader->load('controllers.xml');
        $loader->load('mailin.xml');

        try {
            $loader->load('custom.xml');
        } catch (FileLocatorFileNotFoundException $e) {
            // Do nothing, the absence of custom.xml file is expected
        }

        $extensionPackagesDefinition = PackageExtensionManager::getEnabledPackageExtensions(false);
        $container->setParameter('tiki.packages.extensions', $extensionPackagesDefinition);
        foreach ($extensionPackagesDefinition as $packageDefinition) {
            try {
                $path = sprintf('%s/%s/config/services.xml', TIKI_PATH, $packageDefinition['path']);
                $loader->load($path);
            } catch (FileLocatorFileNotFoundException $e) {
                // Do nothing, absence of services.xml file is expected
            }
        }

        $container->compile();

        $dumper = new PhpDumper($container);
        file_put_contents(
            $cache,
            $dumper->dump(
                [
                    'class' => 'TikiCachedContainer',
                ]
            )
        );

        if (TikiDb::isAvailable()) {
            $container->set('tiki.lib.db', TikiDb::get());
        }

        return $container;
    }

    /** Return 'windows' if windows, otherwise 'unix'
     * \static
     */
    public function os()
    {
        static $os;
        if (! isset($os)) {
            if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {
                $os = 'windows';
            } else {
                $os = 'unix';
            }
        }

        return $os;
    }


    /** Return true if windows, otherwise false
     * @static
     */
    public static function isWindows()
    {
        static $windows;
        if (! isset($windows)) {
            $windows = strtoupper(substr(PHP_OS, 0, 3)) == 'WIN';
        }

        return $windows;
    }

    /**
     * Copes with Windows permissions
     *
     * @param string $path directory to test
     *
     * @return bool
     */
    public static function is_writeable($path)
    {
        if (self::isWindows()) {
            return self::is__writable($path);
        } else {
            return is_writeable($path);
        }
    }

    /**
     * From the php is_writable manual (thanks legolas558 d0t users dot sf dot net)
     * Note the two underscores and no "e".
     *
     * will work in despite of Windows ACLs bug
     * NOTE: use a trailing slash for folders!!!
     * {@see http://bugs.php.net/bug.php?id=27609}
     * {@see http://bugs.php.net/bug.php?id=30931}
     *
     * @param string $path directory to test    NOTE: use a trailing slash for folders!!!
     * @return bool
     */
    public static function is__writable($path)
    {
        if ($path[strlen($path) - 1] == '/') { // recursively return a temporary file path
            return self::is__writable($path . uniqid(mt_rand()) . '.tmp');
        } elseif (is_dir($path)) {
            return self::is__writable($path . '/' . uniqid(mt_rand()) . '.tmp');
        }
        // check tmp file for read/write capabilities
        $rm = file_exists($path);
        $f = @fopen($path, 'a');
        if ($f === false) {
            return false;
        }
        fclose($f);
        if (! $rm) {
            unlink($path);
        }

        return true;
    }


    /** Prepend $path to the include path
     * @static
     * @param string $path the path to prepend
     * @return string
     */
    public static function prependIncludePath($path)
    {
        $include_path = ini_get('include_path');
        $paths = explode(PATH_SEPARATOR, $include_path);

        if ($include_path && ! in_array($path, $paths)) {
            $include_path = $path . PATH_SEPARATOR . $include_path;
        } elseif (! $include_path) {
            $include_path = $path;
        }

        return set_include_path($include_path);
    }


    /** Append $path to the include path
     * @static
     * @param mixed $path
     */
    public static function appendIncludePath($path)
    {
        $include_path = ini_get('include_path');
        $paths = explode(PATH_SEPARATOR, $include_path);

        if ($include_path && ! in_array($path, $paths)) {
            $include_path .= PATH_SEPARATOR . $path;
        } elseif (! $include_path) {
            $include_path = $path;
        }

        return set_include_path($include_path);
    }

    /**
     * Convert a string to UTF-8. Fixes a bug in PHP decode
     * From http://w3.org/International/questions/qa-forms-utf-8.html
     * @static
     * @param string String to be converted
     * @return UTF-8 representation of the string
     */
    public static function to_utf8($string)
    {
        if (
            preg_match(
                '%^(?:
                 [\x09\x0A\x0D\x20-\x7E]            # ASCII
            | [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
            | \xE0[\xA0-\xBF][\x80-\xBF]         # excluding overlongs
            | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
            | \xED[\x80-\x9F][\x80-\xBF]         # excluding surrogates
             | \xF0[\x90-\xBF][\x80-\xBF]{2}      # planes 1-3
             | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
            | \xF4[\x80-\x8F][\x80-\xBF]{2}      # plane 16
            )*$%xs',
                $string
            )
        ) {
            return $string;
        } else {
            return iconv('CP1252', 'UTF-8', $string);
        }
    }

    /**
     * Determine if the web server is an IIS server
     * @return true if IIS server, else false
     * @static
     */
    public static function isIIS()
    {
        static $IIS;

        // Sample value Microsoft-IIS/7.5
        if (! isset($IIS) && isset($_SERVER['SERVER_SOFTWARE'])) {
            $IIS = substr($_SERVER['SERVER_SOFTWARE'], 0, 13) == 'Microsoft-IIS';
        }

        return $IIS;
    }

    /**
     * Determine if the web server is an IIS server
     * @return true if IIS server, else false
     * \static
     */
    public static function hasIIS_UrlRewriteModule()
    {
        return isset($_SERVER['IIS_UrlRewriteModule']) == true;
    }

    public static function getCredentialsFile()
    {
        global $default_api_tiki, $api_tiki, $dbversion_tiki, $host_tiki, $user_tiki, $pass_tiki, $dbs_tiki, $tikidomain, $tikidomainslash, $dbfail_url;
        // Please use the local.php file instead containing these variables
        // If you set sessions to store in the database, you will need a local.php file
        // Otherwise you will be ok.
        //$api_tiki     = 'pear';
        //$api_tiki         = 'pdo';
        $api_tiki = 'pdo';
        $dbversion_tiki = '2.0';
        $host_tiki = 'localhost';
        $user_tiki = 'root';
        $pass_tiki = '';
        $dbs_tiki = 'tiki';
        $tikidomain = '';
        $dbfail_url = '';

        /*
        SVN Developers: Do not change any of the above.
        Instead, create a file, called db/local.php, containing any of
        the variables listed above that are different for your
        development environment.  This will protect you from
        accidentally committing your username/password to SVN!

        example of db/local.php
        <?php
        $host_tiki   = 'myhost';
        $user_tiki   = 'myuser';
        $pass_tiki   = 'mypass';
        $dbs_tiki    = 'mytiki';
        $api_tiki    = 'adodb';

        ** Multi-tiki
        **************************************
        see https://tiki.org/MultiTiki19

        Setup of virtual tikis is done using setup.sh script
        -----------------------------------------------------------
        -> Multi-tiki trick for virtualhosting

        $tikidomain variable is set to :
        or TIKI_VIRTUAL
            That is set in apache virtual conf : SetEnv TIKI_VIRTUAL myvirtual
        or SERVER_NAME
            From apache directive ServerName set for that virtualhost block
        or HTTP_HOST
            From the real domain name called in the browser
            (can be ServerAlias from apache conf)

        */

        if (! isset($local_php) or ! is_file($local_php)) {
            $local_php = TIKI_CONFIG_FILE_PATH;
        } else {
            $local_php = preg_replace(['/\.\./', '/^db\//'], ['', ''], $local_php);
        }
        $tikidomain = '';
        if (is_file(TIKI_CONFIG_PATH . '/virtuals.inc')) {
            if (isset($_SERVER['TIKI_VIRTUAL']) and is_file(TIKI_CONFIG_PATH . '/' . $_SERVER['TIKI_VIRTUAL'] . '/local.php')) {
                $tikidomain = $_SERVER['TIKI_VIRTUAL'];
            } elseif (isset($_SERVER['SERVER_NAME']) and is_file(TIKI_CONFIG_PATH . '/' . $_SERVER['SERVER_NAME'] . '/local.php')) {
                $tikidomain = $_SERVER['SERVER_NAME'];
            } elseif (isset($_REQUEST['multi']) && is_file(TIKI_CONFIG_PATH . '/' . $_REQUEST['multi'] . '/local.php')) {
                $tikidomain = $_REQUEST['multi'];
            } elseif (isset($_SERVER['HTTP_HOST'])) {
                if (is_file(TIKI_CONFIG_PATH . '/' . $_SERVER['HTTP_HOST'] . '/local.php')) {
                    $tikidomain = $_SERVER['HTTP_HOST'];
                } elseif (is_file(TIKI_CONFIG_PATH . '/' . preg_replace('/^www\./', '', $_SERVER['HTTP_HOST']) . '/local.php')) {
                    $tikidomain = preg_replace('/^www\./', '', $_SERVER['HTTP_HOST']);
                }
            }
            if (! empty($tikidomain)) {
                $local_php = TIKI_CONFIG_PATH . "/$tikidomain/local.php";
            }
        }
        $tikidomainslash = (! empty($tikidomain) ? $tikidomain . '/' : '');

        $default_api_tiki = $api_tiki;
        $api_tiki = '';

        if (getenv('TIKI_TEST_HOST') && getenv('TIKI_TEST_HOST_A') && getenv('TIKI_TEST_HOST_B')) {
            $local_php = 'lib/test/local.php';
        }

        return $local_php;
    }

    public static function getEnvironmentCredentials()
    {
        // Load connection strings from environment variables, as used by Azure and possibly other hosts
        $connectionString = null;
        foreach (['MYSQLCONNSTR_Tiki', 'MYSQLCONNSTR_DefaultConnection'] as $envVar) {
            if (isset($_SERVER[$envVar])) {
                $connectionString = $_SERVER[$envVar];
                continue;
            }
        }

        if (
            $connectionString && preg_match(
                '/^Database=(?P<dbs>.+);Data Source=(?P<host>.+);User Id=(?P<user>.+);Password=(?P<pass>.+)$/',
                $connectionString,
                $parts
            )
        ) {
            $parts['charset'] = 'utf8';
            $parts['socket'] = null;

            return $parts;
        }

        return null;
    }
}
