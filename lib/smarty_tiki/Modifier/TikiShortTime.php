<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\Modifier;

class TikiShortTime
{
    public function handle($string)
    {
        global $prefs;
        $smarty = \TikiLib::lib('smarty');
        return smarty_modifier_tiki_date_format($string, $prefs['short_time_format']);
    }
}
