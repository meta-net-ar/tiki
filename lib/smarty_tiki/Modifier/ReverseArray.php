<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\Modifier;

use SmartyTiki\TikiSmartyExtensionInterface;

/**
 * Smarty reverse_array modifier plugin
 *
 * Type:     modifier
 * Name:     reverse_array
 * Purpose:  reverse arrays
 * @param array
 * @return array
 */
class ReverseArray implements TikiSmartyExtensionInterface
{
    public static function getSmartyName(): string
    {
        return 'reverse_array';
    }

    public function handle($array)
    {
        return array_reverse($array);
    }
}
