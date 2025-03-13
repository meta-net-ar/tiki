<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Composer;

use Composer\Script\Event;
use Composer\Util\FileSystem;
use Exception;

class CleanVendors
{
/** @var array Files or directories to remove anywhere in vendor files. Case-insensitive. Must specify as lower case.  */
    private static $standardFiles = [];
    /*private static $standardFiles = [
        'development',
        'demo',
        'demo1',
        'demo2',
        'demos',
        'demo.html',
        'demos.html',
        'demo.js',
        'doc',
        'docs',
        'documentation',
        'sample',
        'samples',
        'example',
        'examples',
        'example.html',
        'example.md',
        'test',
//      'testing',  //  needed by rector/rector
        'tests',
        'test.html',
//      'vendor',   // needed by twbs/bootstrap
        'www',
        '.gitattributes',
        '.gitignore',
        '.gitmodules',
        '.jshintrc',
        'bower.json',
        'changes.txt',
        'changelog.txt',
        'changelog',
        'changelog.md',
        'composer.json',
        'composer.lock',
        'gruntfile.js',
        'gruntfile.coffee',
        'package.json',
        '.npmignore',
        '.github',
        '.scrutinizer.yml',
        '.travis.yml',
        '.travis.install.sh',
        '.editorconfig',
        '.jscsrc',
        '.jshintignore',
        '.eslintignore',
        '.eslintrc',
        '.hound.yml',
        '.coveralls.yml',
        '.php_cs',
        '.php_cs.dist',
        '.empty',
        '.mailmap',
        '.styleci.yml',
        '.eslintrc.json',
        '.styleci.yml',
        'contributing.md',
        'changes.md',
        'changes.md~',
        'gemfile',
        'gemfile.lock',
        'readme.txt',
        'readme',
        'readme.php',
        'readme.rst',
        'readme.texttile',
        'readme.markdown',
        'readme.mdown',
        'readme.md',
        'history.md',
        'history.md',
        'todo',
        'todo.md',
        'news',
        'building.md',
        'code_of_conduct.md',
        'conduct.md',
        'security.md',
        'support.md',
        'upgrading.md',
        '_translationstatus.txt',
        'info.txt',
        'robots.txt',
        'install',
        'appveyor.yml',
        'phpunit.xml.dist',
        'makefile',
        'cname',
        'devtools',
        'psalm.xml',
        'authors.txt',
        'authors',
        'credits.md',
        'notice',
        'index.html',
    ];*/

    /**
     * Performs post-composer cleanup routines on vendor files.
     * @param Event $event
     */
    public static function clean(Event $event): void
    {
        $vendors = $event->getComposer()->getConfig()->get('vendor-dir');
        if (substr($vendors, -1, 1) !== DIRECTORY_SEPARATOR) {
            $vendors .= DIRECTORY_SEPARATOR;
        }

        self::remove(
            $vendors . 'jquery/jquery-sheet',
            [
                'jquery-1.10.2.min.js',
                'jquery-ui',
                'parser.php',
                'parser/formula/formula.php'
            ]
        );
        self::remove(
            $vendors . 'jquery/jquery-timepicker-addon',
            [
                'lib',
                'src',
                'jquery-ui-timepicker-addon.json',
                'jquery-ui-timepicker-addon.min.css',
                'jquery-ui-timepicker-addon.min.js'
            ]
        );
        self::remove($vendors . 'jquery-plugins/reflection-jquery', 'src');
        self::remove(
            $vendors . 'studio-42/elfinder',
            [
                'files',
                'elfinder.html',
            ]
        );

        self::remove(
            $vendors . 'adodb/adodb-php',
            [
                'cute_icons_for_site',
                'session/adodb-sess.txt',
                'scripts',
                'pear/readme.Auth.txt',
                // Below are removed to avoid composer warnings caused by classes declared in multiple locations
                'datadict/datadict',
                'session/session',
                'adodb/adodb/perf/perf',
                'adodb/adodb/drivers/drivers',
                'adodb-active-recordx.inc.php',
                'drivers/adodb-informix.inc.php',
                'perf/perf-informix.inc.php',
                'datadict/datadict-informix.inc.php',
            ]
        );
        self::remove($vendors . 'adodb/adodb-php', 'session/adodb-sess.txt');
        self::remove($vendors . 'jason-munro/cypht', 'hm3.sample.ini');
        self::remove($vendors . 'league/commonmark', 'CHANGELOG-0.x.md');
        self::remove(
            $vendors . 'pear/pear/',
            [
                'tests',
                'docs'
            ]
        );
        self::remove($vendors . 'Sam152/Javascript-Equal-Height-Responsive-Rows', 'grids.js');
        self::remove(
            $vendors . 'npm-asset/prefixfree',
            [
            'index.js',
            'css',
            'fonts',
            'img',
            'minify'
            ]
        );
        self::remove(
            $vendors . 'smarty/smarty',
            [
                'change_log.txt',
                'INHERITANCE_RELEASE_NOTES.txt',
                'SMARTY_2_BC_NOTES.txt',
                'SMARTY_3.0_BC_NOTES.txt',
                'SMARTY_3.1_NOTES.txt',
            ]
        );
        self::remove(
            $vendors . 'laminas/',
            [
                'laminas-feed/test',
                'laminas-feed/docs',
                'laminas-validator/test',
                'laminas-validator/docs',
                'laminas-i18n/test',
                'laminas-i18n/docs',
                'laminas-filter/test',
                'laminas-filter/docs',
                'laminas-ldap/test',
                'laminas-ldap/docs',
                'laminas-servicemanager/test',
                'laminas-servicemanager/docs',

            ]
        );
        self::remove(
            $vendors . 'symfony/',
            [
                'dependency-injection/Tests',
                'console/Tests',
                'routing/Tests',
                'http-foundation/Tests',
                'http-foundation/Test',
                'mime/Tests',
                'mime/Test',
                'config/Tests',
                'http-client/Test',
            ]
        );
        self::remove($vendors . 'wamania/php-stemmer', 'test');
        self::remove(
            $vendors . 'ezyang/htmlpurifier',
            [
                'INSTALL.fr.utf8',
                'release1-update.php',
                'release2-tag.php',
                'test-settings.sample.php',
                'test-settings.travis.php',
                'VERSION',
                'WHATSNEW',
                'WYSIWYG'
            ]
        );

        // remove entire packages
        $fs = new FileSystem();

        // duplicate with rmm5t/jquery-timeago
        $fs->remove($vendors . 'components/jquery-timeago');
        // duplicate with moment/moment
        $fs->remove($vendors . 'components/moment');
        // duplicate with drmonty/smartmenus
        $fs->remove($vendors . 'components/smartmenus');

        // we remove standard files afterward so we have less files to search through
        self::removeStandard($vendors);
        self::addIndexFiles($vendors);
    }

    /**
     * Add index.php files to prevent directory browsing to a directory and its subdirectories
     * @param string $path      Root directory to add index.php files in.
     */

    private static function addIndexFiles(string $path): void
    {
        $excludeDirs = [
            'rector/rector',  //It breaks rector, and it's a devtool, so won't be bundled anyway.
            'phpseclib/phpseclib/phpseclib/Crypt', //it breaks phpseclib as it thinks there are classes called index due to the index files
            'phpunit/phpunit/schema', //It breaks phpunit as it thinks there are classes called index due to the index files
        ];
        // add index file if needed
        $index = glob($path . '[iI][nN][dD][eE][xX].[pP][hH][pP]'); // index.php case-insensitive
        if (empty($index)) {
            file_put_contents($path . 'index.php', '');
        }

        // recursive call to all sub-directories
        $dirs = glob($path . '{,.}*[!.]', GLOB_MARK | GLOB_BRACE | GLOB_ONLYDIR);
        foreach ($dirs as $dir) {
            if (empty(array_filter($excludeDirs, fn($item) => strpos($dir, $item) !== false))) {
                self::addIndexFiles($dir);
            } else {
                //echo "Skiping adding index.php from $dir matching excludeDirs\n";
            }
        }
    }

    /**
     * Removes all files exactly matching $standardFiles entries.
     * Case-insensitive evaluation. Will search subdirectories.
     * @param string $base  The base directory to search from.
     */
    private static function removeStandard(string $base): void
    {
        $fs = new FileSystem();
        $files = glob($base . '/{,.}*[!.]', GLOB_MARK | GLOB_BRACE);
        foreach ($files as $file) {
            if (in_array(strtolower(basename($file)), self::$standardFiles, true)) {
                //echo "Deleting file: $file\n";
                $fs->remove($file);
            } elseif (is_dir($file)) {
                self::removeStandard($file);
            }
        }
    }

    /**
     * Remove multiple files. Must provide case sensitive params
     * @param string        $base   The base directory(path) to use.
     * @param array|string  $files  File/Directory names (omitting the base directory)
     */
    private static function remove(string $base, $files): void
    {
        $files = (array)$files;

        // make sure all directory separators are OS specific (windows compatibility)
        if ('/' !== DIRECTORY_SEPARATOR) {
            $base = str_replace('/', DIRECTORY_SEPARATOR, $base);
            foreach ($files as &$file) {
                $file = str_replace('/', DIRECTORY_SEPARATOR, $file);
            }
            unset($file); // we remove $file after passing by reference to avoid unexpected errors.
        }
        $fs = new FileSystem();
        if (is_dir($base)) {
            foreach ($files as $file) {
                $path = $base . DIRECTORY_SEPARATOR . $file;
                if (file_exists($path) || is_dir($path)) {
                    // if windows indexing or antivirus is locking a file, then complain
                    try {
                        $removeResult = $fs->remove($path);
                        //It's the return value of remove($path) that determines if the file was deleted or not, the exception is not necessarily thrown in case of deletion failure.
                        // Or check if the file does not still exist after the remove operation
                        clearstatcache();
                        if (! $removeResult || file_exists($path) || is_dir($path)) {
                            echo "\e[031mError \e[0m ", "Failed to delete '$path'. Possible causes: file/directory permissions, etc.\n";
                        }
                    } catch (Exception $e) {
                        echo "\e[031mError \e[0m ",  $e->getMessage(), "\n";
                    }
                }
            }
        } else {
            echo "\e[031mError \e[0m $base not found\n";
        }
    }
}
