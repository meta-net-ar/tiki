<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\Modifier;

use SmartyTiki\TikiSmartyExtensionInterface;

/**
 * Smarty modifier ucwords
 * -----------------------
 * Purpose: Uppercase the first character of each word in a string
 */
class Ucwords implements TikiSmartyExtensionInterface
{
    public static function getSmartyName(): string
    {
        return 'ucwords';
    }

    /**
     * @param string $string The input string.
     * @param string $separator Optional. contains the word separator characters.
     * @return string A string with the first character of each word in string capitalized, if that character is an ASCII character between "a" (0x61) and "z" (0x7a)
     */
    public function handle($string, $separator = " \t\r\n\f\v")
    {
        return ucwords($string, $separator);
    }
}
