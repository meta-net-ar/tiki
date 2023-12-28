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
 * Name:     how_many_user_inscriptions
 * Purpose:  to use with the tracker field type "User inscription"
 *           if $text="12[13], 14[15], 16[17]"
 *           then return 48 (=13+1+15+1+17+1)
 * -------------------------------------------------------------
 */
class HowManyUserInscriptions
{
    public function handle($text)
    {

        $pattern = "/\d+\[(\d+)\]/";
        $out = preg_match_all($pattern, $text, $match);

        $nb = 0;

        foreach ($match[1] as $n) {
            $nb += ($n + 1);
        }

        return $nb;
    }
}
