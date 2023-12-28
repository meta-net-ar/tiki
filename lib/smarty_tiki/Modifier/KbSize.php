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
 * Name:     kbsize
 * Purpose:  returns size in Mb, Kb or bytes.
 * -------------------------------------------------------------
 */
class KbSize
{
    public function handle($string, $bytes = false, $nb_decimals = 2, $unit_separator = '&nbsp;')
    {
        if ($string == '') {
            return '';
        }

        if ($string > 1099511627776) { // 1024 x 1024 x 1024 x 1024 = 1099511627776
            $string = number_format($string / 1099511627776, $nb_decimals);
            $kb_string = 'T';
        } elseif ($string > 1073741824) { // 1024 x 1024 x 1024 = 1073741824
            $string = number_format($string / 1073741824, $nb_decimals);
            $kb_string = 'G';
        } elseif ($string > 1048576) { // 1024 x 1024 = 1048576
            $string = number_format($string / 1048576, $nb_decimals);
            $kb_string = 'M';
        } elseif ($string > 1024) {
            $string = number_format($string / 1024, $nb_decimals);
            $kb_string = 'K';
        } else {
            $string = $string;
            $kb_string = '';
        };

        $kb_string = $kb_string . (($bytes) ? 'B' : 'b');

        return $string . $unit_separator . tra($kb_string);
    }
}
