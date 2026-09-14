<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\Modifier;

use SmartyTiki\TikiSmartyExtensionInterface;

/**
 * Smarty plugin
 * -------------------------------------------------------------
 * Type:     modifier
 * Name:     lcfirst
 * Purpose:  lowercase the initial character in a string
 * -------------------------------------------------------------
 */
class Lcfirst implements TikiSmartyExtensionInterface
{
    public static function getSmartyName(): string
    {
        return 'lcfirst';
    }

    public function handle($s)
    {
        return strtolower($s[0]) . substr($s, 1);
    }
}
