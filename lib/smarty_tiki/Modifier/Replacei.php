<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\Modifier;

use SmartyTiki\TikiSmartyExtensionInterface;

/**
 * Smarty replacei modifier plugin
 *
 * Type:     modifier
 * Name:     replacei
 * Purpose:  Returns a case insensitive replaced string.
 *           Same arguments as PHP str_ireplace function.
 */
class Replacei implements TikiSmartyExtensionInterface
{
    public static function getSmartyName(): string
    {
        return 'replacei';
    }

    public function handle($string, $find, $replacement)
    {
        return str_ireplace($find, $replacement, $string);
    }
}
