<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\Modifier;

class Star
{
    public function handle($score)
    {
        global $prefs, $tikilib;

        if ($prefs['feature_score'] != 'y') {
            return '';
        }

        return $tikilib->get_star($score);
    }
}
