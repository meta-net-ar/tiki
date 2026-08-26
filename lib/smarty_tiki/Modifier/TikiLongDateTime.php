<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\Modifier;

use SmartyTiki\TikiSmartyExtensionInterface;
use SmartyTiki\Traits\ModifierStaticFacadeTrait;

class TikiLongDateTime implements TikiSmartyExtensionInterface
{
    use ModifierStaticFacadeTrait;

    public static function getSmartyName(): string
    {
        return 'tiki_long_datetime';
    }

    public function handle($string)
    {
        global $prefs;
        \TikiLib::lib('smarty'); //Load SmartyLib for side effects
        // if you change the separator do not forget to change the translation instruction in lib/prefs/long.php
        return \SmartyTiki\Modifier\TikiDateFormat::apply($string, $prefs['long_date_format'] . ' ' . $prefs['long_time_format']);
    }
}
