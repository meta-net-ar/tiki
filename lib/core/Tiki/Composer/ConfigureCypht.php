<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Composer;

use Composer\Script\Event;

// TODO : Refactor the way paths are being handled here, see https://gitlab.com/tikiwiki/tiki/-/merge_requests/4565#note_1794038774

class ConfigureCypht
{
    public static function setup(Event $event)
    {
        $fixDS = function (string $path): string {
            return str_replace('/', DIRECTORY_SEPARATOR, $path);
        };

        $cypht = __DIR__ . $fixDS('/../../../cypht/');
        $vendors = $event->getComposer()->getConfig()->get('vendor-dir');
        $io = $event->getIO();

        if (! str_ends_with($vendors, DIRECTORY_SEPARATOR)) {
            if (DIRECTORY_SEPARATOR == "/") {
                $vendors .= DIRECTORY_SEPARATOR;
            } else {
                $vendors .= "\\\\\\";
            }
        }

        copy($cypht . '.env', $vendors . $fixDS('cypht-org/cypht/.env'));

        // generate Cypht config
        $php_binary = preg_replace("/(?<!\\\) /", '\ ', PHP_BINARY);
        $cypthFolder = $fixDS('cypht-org/cypht');
        $genScript = $fixDS('scripts/config_gen.php');
        $output = shell_exec("cd {$vendors}{$cypthFolder} && {$php_binary} {$genScript}");

        if (! is_string($output)  || ! str_contains($output, 'dynamic.php file written')) {
            $io->write('Could not build Cypht package configuration. Check the output below and make sure minimum PHP version is available and executable as CLI.');
            if ($output === false) {
                $io->write('The pipe cannot be established.');
            } elseif ($output === null) {
                $lastError = error_get_last();
                $io->write($lastError['message'] ?? '');
            } else {
                $io->write($output);
            }
        }
    }
}
