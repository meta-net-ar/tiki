<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Composer;

use Composer\Script\Event;
use Composer\Util\FileSystem;

class PatchCypht
{
    public static function setup(Event $event)
    {
        $fixDS = function (string $path): string {
            return str_replace('/', DIRECTORY_SEPARATOR, $path);
        };

        $cypht = __DIR__ . $fixDS('/../../../cypht/');
        $vendors = $event->getComposer()->getConfig()->get('vendor-dir');
        $io = $event->getIO();

        if (substr($vendors, -1, 1) !== DIRECTORY_SEPARATOR) {
            $vendors .= DIRECTORY_SEPARATOR;
        }

        $fs = new FileSystem();
        umask(0);

        // setup stock version with missing files
        copy($cypht . '.env', $vendors . $fixDS('jason-munro/cypht/.env'));
        $tiki_module = $vendors . $fixDS('jason-munro/cypht/modules/tiki');
        if (! is_dir($tiki_module)) {
            mkdir($tiki_module, 0755);
        }
        $fs->copy($cypht . $fixDS('modules/tiki'), $tiki_module);
        chdir($cypht . $fixDS('../../'));

        // generate storage dirs
        if (! is_dir($fixDS('temp/cypht'))) {
            mkdir($fixDS('temp/cypht'), 0777);
            mkdir($fixDS('temp/cypht/app_data'), 0777);
            mkdir($fixDS('temp/cypht/attachments'), 0777);
            mkdir($fixDS('temp/cypht/users'), 0777);
        }

        // generate Cypht config
        $php_binary = preg_replace("/(?<!\\\) /", '\ ', PHP_BINARY);
        $cypthFolder = $fixDS('jason-munro/cypht');
        $genScript = $fixDS('scripts/config_gen.php');
        $output = `cd {$vendors}{$cypthFolder} && {$php_binary} {$genScript}`;

        if (! is_string($output)  || ! strstr($output, 'dynamic.php file written')) {
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
        // copy site.js and site.css
        copy($vendors . $fixDS('jason-munro/cypht/site/site.js'), $cypht . 'site.js');
        copy($vendors . $fixDS('jason-munro/cypht/site/site.css'), $cypht . 'site.css');

        // js custom pacthes
        $js = file_get_contents($cypht . 'site.js');
        $js = str_replace("url: ''", "url: 'tiki-ajax_services.php?controller=cypht&action=ajax&'+window.location.search.substr(1)", $js);
        $js = str_replace("xhr.open('POST', window.location.href)", "xhr.open('POST', 'tiki-ajax_services.php?controller=cypht&action=ajax&'+window.location.search.substr(1))", $js);
        $js = str_replace("xhr.open('POST', '', true);", "xhr.open('POST', 'tiki-ajax_services.php?controller=cypht&action=ajax&'+window.location.search.substr(1), true);", $js);
        $js = str_replace("var ajax = new Hm_Ajax_Request", "var ajax = new tiki_Hm_Ajax_Request", $js);
        $js = preg_replace("#^.*/\* swipe event handler \*/#s", "", $js);
        $js = file_get_contents($vendors . $fixDS("jason-munro/cypht/third_party/resumable.min.js")) . "\n\n" . $js;
        $js = file_get_contents($vendors . $fixDS("jason-munro/cypht/third_party/tingle.min.js")) . "\n\n" . $js;
        file_put_contents($cypht . 'site.js', $js);
    }
}
