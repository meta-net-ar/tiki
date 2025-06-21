<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Composer;

use Composer\Script\Event;

class PatchSignaturepdf
{
    public static function setup(Event $event)
    {
        $signaturepdf = __DIR__ . str_replace('/', DIRECTORY_SEPARATOR, '/../../../signaturepdf/');
        $vendors = $event->getComposer()->getConfig()->get('vendor-dir');

        copy($signaturepdf . 'config.ini', $vendors . str_replace('/', DIRECTORY_SEPARATOR, 'mercihabam/signaturepdf/config.ini'));
    }
}