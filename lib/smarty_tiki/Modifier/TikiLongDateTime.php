<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\Modifier;

class TikiLongDateTime
{
    public function handle($string)
    {
        global $prefs;
        $smarty = \TikiLib::lib('smarty');
        // if you change the separator do not forget to change the translation instruction in lib/prefs/long.php
        return smarty_modifier_tiki_date_format($string, $prefs['long_date_format'] . ' ' . $prefs['long_time_format']);
    }
}
