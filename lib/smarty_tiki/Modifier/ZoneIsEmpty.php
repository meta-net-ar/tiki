<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\Modifier;

use SmartyTiki\TikiSmartyExtensionInterface;

class ZoneIsEmpty implements TikiSmartyExtensionInterface
{
    public static function getSmartyName(): string
    {
        return 'zone_is_empty';
    }

    public function handle($zoneName)
    {
        return zone_is_empty($zoneName);
    }
}
