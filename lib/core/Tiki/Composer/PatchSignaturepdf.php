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
        chmod($packagePath, 0755);

        copy($signaturepdf . 'config.ini', self::fixDS($packagePath . '/config/config.ini'));

        self::alterRoutes($packagePath);
    }

    public static function alterRoutes($packagePath)
    {
        $entryPoint = self::fixDS($packagePath . '/app.php');
        $content = file_get_contents($entryPoint);

        $content = preg_replace_callback(
            '/(\$f3->route\(\s*\'[A-Z]+\s+\/)([^,\'"]*)([\'"],)/',
            function ($matches) {
                return $matches[1] . 'tiki-signaturepdf.php' . ($matches[2] ? '/' : '') . $matches[2] . $matches[3];
            },
            $content
        );

        file_put_contents($entryPoint, $content);
    }
}
