<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\Modifier;

use SmartyTiki\TikiSmartyExtensionInterface;

class IsNumeric implements TikiSmartyExtensionInterface
{
    public static function getSmartyName(): string
    {
        return 'is_numeric';
    }

    public function handle($value)
    {
        return is_numeric($value);
    }
}
