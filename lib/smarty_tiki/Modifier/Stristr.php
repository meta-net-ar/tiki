<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\Modifier;

use SmartyTiki\TikiSmartyExtensionInterface;

/**
 * Smarty modifier stristr
 * -----------------------
 * Purpose: Returns a haystack substring, from the first occurrence case insensitive of needle (inclusive) to the end of the string.
 */
class Stristr implements TikiSmartyExtensionInterface
{
    public static function getSmartyName(): string
    {
        return 'stristr';
    }

    /**
     * @param string $haystack The string in which to search.
     * @param string $needle The string to look for.
     * @param bool $before_needle Optional. If true, stristr() returns the part of haystack before the first occurrence of needle (needle excluded).
     * @return string|false Returns the matching part of the string. If needle is not found, the function returns false.
     */
    public function handle($haystack, $needle, $before_needle = false)
    {
        return stristr($haystack, $needle, $before_needle);
    }
}
