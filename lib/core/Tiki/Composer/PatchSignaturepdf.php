<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Composer;

use Composer\Script\Event;

class PatchSignaturepdf
{
    private static function fixDS(string $path): string
    {
        return str_replace('/', DIRECTORY_SEPARATOR, $path);
    }

    public static function setup(Event $event)
    {
        $signaturepdf = __DIR__ . self::fixDS('/../../../signaturepdf/');
        $vendors = $event->getComposer()->getConfig()->get('vendor-dir');

        $packagePath = $vendors . self::fixDS('/mercihabam/signaturepdf/');

        // When uninstalling, do nothing
        if (! is_dir($packagePath)) {
            return;
        }

        chmod($packagePath, 0755);

        copy($signaturepdf . 'config.ini', self::fixDS($packagePath . '/config/config.ini'));

        self::alterRoutes($packagePath);
        self::alterRootAssetSrc($packagePath);
        self::removeBootstrapDependency($packagePath);
        self::deferAllScripts($packagePath); // Ensure that js is executed after Tiki's dependencies are loaded
    }

    public static function alterRoutes($packagePath)
    {
        $entryPoint = self::fixDS($packagePath . '/app.php');
        $content = file_get_contents($entryPoint);

        $content = preg_replace_callback(
            '/(\$f3->route\s*\(\s*\'[A-Z]+\s+\/)([^,\'"]*)([\'"],)/',
            function ($matches) {
                return $matches[1] . 'tiki-signaturepdf_handler.php' . ($matches[2] ? '/' : '') . $matches[2] . $matches[3];
            },
            $content
        );

        file_put_contents($entryPoint, $content);
    }

    public static function alterRootAssetSrc($packagePath)
    {
        $indexFile = self::fixDS($packagePath . '/templates/index.html.php');
        $content = file_get_contents($indexFile);

        $content = preg_replace('/src=["\']logo\.svg["\']/', 'src="tiki-signaturepdf.php/root/logo.svg"', $content);
        file_put_contents($indexFile, $content);

        $navTabFile = self::fixDS($packagePath . '/templates/components/navtab.html.php');
        $navContent = file_get_contents($navTabFile);

        $navContent = preg_replace('/src=["\']logo-small\.svg["\']/', 'src="tiki-signaturepdf.php/root/logo-small.svg"', $navContent);
        file_put_contents($navTabFile, $navContent);
    }

    public static function removeBootstrapDependency($packagePath)
    {
        $indexFile = self::fixDS($packagePath . '/templates/index.html.php');
        $content = file_get_contents($indexFile);
        $content = preg_replace('/<script src="[^"]*bootstrap\.bundle\.min\.js\?[^"]*"><\/script>/', '', $content);
        file_put_contents($indexFile, $content);

        $headerFile = self::fixDS($packagePath . '/templates/components/header.html.php');
        $headerContent = file_get_contents($headerFile);
        $headerContent = preg_replace('/<link href="[^"]*bootstrap\.[^"]*\.min\.css\?[^"]*" rel="stylesheet">/', '', $headerContent);
        file_put_contents($headerFile, $headerContent);

        $commonFile = self::fixDS($packagePath . '/templates/components/common.html.php');
        $commonContent = file_get_contents($commonFile);
        $commonContent = preg_replace('/<script src="[^"]*bootstrap\.bundle\.min\.js\?[^"]*"><\/script>/', '', $commonContent);
        file_put_contents($commonFile, $commonContent);
    }

    public static function deferAllScripts($packagePath)
    {
        $templatesDir = self::fixDS($packagePath . "/templates/");
        $files = glob($templatesDir . '*.html.php');
        $files = array_merge($files, glob($templatesDir . 'components/*.html.php'));

        foreach ($files as $file) {
            $content = file_get_contents($file);

            $content = preg_replace_callback(
                '/(<script\s+)(.*?)/is',
                function ($matches) {
                    if (strpos($matches[2], 'defer') === false && strpos($matches[2], 'type="module"') === false) {
                        return $matches[1] . 'defer ' . $matches[2];
                    }
                    return $matches[0];
                },
                $content
            );
            file_put_contents($file, $content);
        }
    }
}
