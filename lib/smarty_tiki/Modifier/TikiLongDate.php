<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\Modifier;

use SmartyTiki\TikiSmartyExtensionInterface;

class TikiLongDate implements TikiSmartyExtensionInterface
{
    public static function getSmartyName(): string
    {
        return 'tiki_long_date';
    }

    public function handle($string)
    {
        global $prefs;
        \TikiLib::lib('smarty'); //Load SmartyLib for side effects
        return \SmartyTiki\Modifier\TikiDateFormat::apply($string, $prefs['long_date_format']);
    }
}
