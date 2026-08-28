<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\Modifier;

use SmartyTiki\TikiSmartyExtensionInterface;

/*
    * Smarty plugin
    * -------------------------------------------------------------
    * Type:     modifier
    *
    * -------------------------------------------------------------
    */
class Div implements TikiSmartyExtensionInterface
{
    public static function getSmartyName(): string
    {
        return 'div';
    }

    public function handle($string, $num, $max = 10)
    {
        if ($num == 0) {
            return 0;
        }

        if (ceil(strlen($string) / $num) > $max) {
            return $max;
        }

        return ceil(strlen($string) / $num);
    }
}
