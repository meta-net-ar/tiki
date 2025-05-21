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
    /**
     * @var array Files or directories to remove anywhere in vendor files. Case-insensitive. Must specify as lower case.
     */
    private static array $standardFiles = [
        'development', 'demo', 'demo1', 'demo2', 'demos', 'demo.html', 'demos.html', 'demo.js',
        'doc', 'docs', 'documentation', 'sample', 'samples', 'example', 'examples', 'example.html', 'example.md',
        'test', 'tests', 'test.html',
        'www', '.gitattributes', '.gitignore', '.gitmodules', '.jshintrc', 'bower.json', 'changes.txt', 'changelog.txt',
        'changelog', 'changelog.md', 'composer.json', 'composer.lock', 'gruntfile.js', 'gruntfile.coffee', 'package.json',
        '.npmignore', '.github', '.scrutinizer.yml', '.travis.yml', '.travis.install.sh', '.editorconfig', '.jscsrc',
        '.jshintignore', '.eslintignore', '.eslintrc', '.hound.yml', '.coveralls.yml', '.php_cs', '.php_cs.dist', '.empty',
        '.mailmap', '.styleci.yml', '.eslintrc.json', 'contributing.md', 'changes.md', 'changes.md~', 'gemfile', 'gemfile.lock',
        'readme.txt', 'readme', 'readme.php', 'readme.rst', 'readme.textile', 'readme.markdown', 'readme.mdown', 'readme.md',
        'history.md', 'todo', 'todo.md', 'news', 'building.md', 'code_of_conduct.md', 'conduct.md', 'security.md', 'support.md',
        'upgrading.md', '_translationstatus.txt', 'info.txt', 'robots.txt', 'install', 'appveyor.yml', 'phpunit.xml.dist',
        'makefile', 'cname', 'devtools', 'psalm.xml', 'authors.txt', 'authors', 'credits.md', 'notice', 'index.html',
    ];

    public static function clean(Event $event): void
    {
        $vendors = rtrim($event->getComposer()->getConfig()->get('vendor-dir'), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        // self::remove($vendors . 'jquery/jquery-sheet', [
        //     'jquery-1.10.2.min.js', 'jquery-ui', 'parser.php', 'parser/formula/formula.php'
        // ]);

        // self::remove($vendors . 'jquery-plugins/reflection-jquery', 'src');
        self::remove($vendors . 'studio-42/elfinder', ['files', 'elfinder.html']);

        self::remove($vendors . 'jason-munro/cypht', 'hm3.sample.ini');
        self::remove($vendors . 'league/commonmark', 'CHANGELOG-0.x.md');
        self::remove($vendors . 'pear/pear/', ['tests', 'docs']);

        self::remove($vendors . 'smarty/smarty', [
            'change_log.txt', 'INHERITANCE_RELEASE_NOTES.txt', 'SMARTY_2_BC_NOTES.txt',
            'SMARTY_3.0_BC_NOTES.txt', 'SMARTY_3.1_NOTES.txt'
        ]);

        self::remove($vendors . 'laminas/', [
            'laminas-feed/test', 'laminas-feed/docs', 'laminas-validator/test', 'laminas-validator/docs',
            'laminas-i18n/test', 'laminas-i18n/docs', 'laminas-filter/test', 'laminas-filter/docs',
            'laminas-ldap/test', 'laminas-ldap/docs', 'laminas-servicemanager/test', 'laminas-servicemanager/docs'
        ]);

        self::remove($vendors . 'symfony/', [
            'dependency-injection/Tests', 'console/Tests', 'routing/Tests',
            'http-foundation/Tests', 'http-foundation/Test', 'mime/Tests', 'mime/Test',
            'config/Tests', 'http-client/Test'
        ]);

        self::remove($vendors . 'wamania/php-stemmer', 'test');
        self::remove($vendors . 'ezyang/htmlpurifier', [
            'INSTALL.fr.utf8', 'release1-update.php', 'release2-tag.php',
            'test-settings.sample.php', 'test-settings.travis.php', 'VERSION',
            'WHATSNEW', 'WYSIWYG'
        ]);

        $fs = new FileSystem();
        $fs->remove($vendors . 'components/jquery-timeago');
        $fs->remove($vendors . 'components/moment');
        $fs->remove($vendors . 'components/smartmenus');

        self::removeStandard($vendors);
        self::addIndexFiles($vendors);
    }

    private static function addIndexFiles(string $path): void
    {
        $excludeDirs = [
            'rector/rector',
            'phpseclib/phpseclib/phpseclib/Crypt',
            'phpunit/phpunit/schema'
        ];

        $path = rtrim(str_replace('/', DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        // Check if directory is empty
        if (empty(glob($path . '*', GLOB_NOSORT | GLOB_NOESCAPE | GLOB_BRACE))) {
            $indexPath = $path . 'index.php';
            if (! file_exists($indexPath)) {
                file_put_contents($indexPath, "<?php\nexit;\n");
            }
        }

        $dirs = glob($path . '{,.}*[!.]', GLOB_MARK | GLOB_BRACE | GLOB_ONLYDIR);
        foreach ($dirs as $dir) {
            $dir = rtrim(str_replace('/', DIRECTORY_SEPARATOR, $dir), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
            if (! array_filter($excludeDirs, fn($item) => str_contains($dir, $item))) {
                self::addIndexFiles($dir);
            }
        }
    }

    private static function removeStandard(string $base): void
    {
        $fs = new FileSystem();
        $files = glob($base . '{,.}*[!.]', GLOB_MARK | GLOB_BRACE);
        foreach ($files as $file) {
            $file = rtrim(str_replace('/', DIRECTORY_SEPARATOR, $file), DIRECTORY_SEPARATOR);
            $filename = strtolower(basename($file));
            if (in_array($filename, self::$standardFiles, true)) {
                $fs->remove($file);
            } elseif (is_dir($file)) {
                self::removeStandard($file);
            }
        }
    }
    private static function remove(string $base, array|string $files): void
    {
        $files = (array) $files;
        $base = rtrim(str_replace('/', DIRECTORY_SEPARATOR, $base), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $fs = new FileSystem();

        if (! is_dir($base)) {
            echo "Error: Directory $base not found\n";
            return;
        }
        foreach ($files as $file) {
            $file = str_replace('/', DIRECTORY_SEPARATOR, $file);
            $path = $base . $file;
            if (! file_exists($path)) {
                continue;
            }
            try {
                if (is_link($path) || is_file($path)) {
                    $fs->remove($path);
                } elseif (is_dir($path)) {
                    $fs->removeDirectory($path);
                }

                if (file_exists($path)) {
                    echo "Error: Failed to delete '$path'\n";
                }
            } catch (Exception $e) {
                echo "Error: {$e->getMessage()}\n";
            }
        }
    }
}
