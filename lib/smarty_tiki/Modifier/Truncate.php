<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\Modifier;

/**
 * Smarty truncate modifier
 *
 * Type:     modifier<br>
 * Name:     truncate<br>
 * Purpose:  Truncate a string to a certain length if necessary,
 *           optionally splitting in the middle of a word, and
 *           appending the $etc string or inserting $etc into the middle.
 * @param string
 * @param integer
 * @param string
 * @param boolean
 * @param boolean
 * @return string
 */
class Truncate
{
    public function handle($string, $length = 80, $etc = '...', $break_words = false, $middle = false)
    {
        if (! isset($string) || $length == 0) {
            return '';
        }

        $strlength = (function_exists('mb_strlen') ? 'mb_strlen' : 'strlen');
        if ($strlength($string) > $length) {
            $length -= min($length, strlen($etc));
            if (function_exists('mb_substr')) {
                $func = 'mb_substr';
            } else {
                $func = 'substr';
            }
            if (! $break_words && ! $middle) {
                $string = preg_replace('/\s+?(\S+)?$/', '', $func($string, 0, $length + 1));
            }
            if (! $middle) {
                return $func($string, 0, $length) . $etc;
            } else {
                return $func($string, 0, $length / 2) . $etc . $func($string, -$length / 2);
            }
        } else {
            return $string;
        }
    }
}
