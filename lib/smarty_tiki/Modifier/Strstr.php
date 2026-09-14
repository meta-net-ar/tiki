<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\Modifier;

use SmartyTiki\TikiSmartyExtensionInterface;

/**
 * Smarty modifier strstr
 * -----------------------
 * Purpose: Find the first occurrence of a string. Returns part of haystack string starting from and including the first occurrence of needle to the end of haystack.
 */
class Strstr implements TikiSmartyExtensionInterface
{
    public static function getSmartyName(): string
    {
        return 'strstr';
    }

    /**
     * @param string $haystack The input string in which to search.
     * @param string $needle The string to search for.
     * @param bool $before_needle Optional. If true, strstr modifier returns the part of haystack before the first occurrence of needle (needle excluded).
     * @return string|false Returns the matching part of the string. If needle is not found, the function returns false.
     */
    public function handle($haystack, $needle, $before_needle = false)
    {
        return strstr($haystack, $needle ?? '', $before_needle);
    }
}
