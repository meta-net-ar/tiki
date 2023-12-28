<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\Modifier;

/**
 * Smarty plugin
 * -------------------------------------------------------------
 * Type:     modifier
 * Prepends an "a " or "an " depending on whether word starts with vowel.
 * @param caps, if set will cause "A " or "An "
 * -------------------------------------------------------------
 */
class AorAn
{
    public function handle($string, $caps = false)
    {
        global $prefs;
        if (substr($prefs['language'], 0, 2) != 'en') {
            return $string;
        }
        $vowels = ['a', 'e', 'i', 'o', 'u'];
        $initial = strtolower(substr($string, 0, 1));
        if (in_array($initial, $vowels)) {
            $prefix = $caps ? 'An ' : 'an ';
        } else {
            $prefix = $caps ? 'A ' : 'a ';
        }
        return $prefix . $string;
    }
}
