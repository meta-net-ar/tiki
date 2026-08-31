<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\Modifier;

use SmartyTiki\TikiSmartyExtensionInterface;

/**
 * Smarty modifier ucfirst
 * -----------------------
 * Purpose: Make a string's first character uppercase
 */
class Ucfirst implements TikiSmartyExtensionInterface
{
    public static function getSmartyName(): string
    {
        return 'ucfirst';
    }

    /**
     * @param string $string The input string.
     * @return string A string with the first character of string capitalized, if that character is an ASCII character in the range from "a" (0x61) to "z" (0x7a).
     */
    public function handle($string)
    {
        return ucfirst($string);
    }
}
