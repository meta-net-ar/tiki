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
    * Name:     ternary
    * Purpose:  map true and false to first and second parameter respectively
    * -------------------------------------------------------------
    */
class Ternary implements TikiSmartyExtensionInterface
{
    public static function getSmartyName(): string
    {
        return 'ternary';
    }

    public function handle($input, $true = '', $false = '')
    {
        return $input ? $true : $false;
    }
}
