<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\Modifier;

use SmartyTiki\TikiSmartyExtensionInterface;

/**
 * Smarty modifier array_key_exists
 * --------------------------------
 * Purpose: Checks if the given key or index exists in the array
 */
class ArrayKeyExists implements TikiSmartyExtensionInterface
{
    public static function getSmartyName(): string
    {
        return 'array_key_exists';
    }

    /**
     * @param int|string $key Value to check.
     * @param array|\ArrayObject $array An array with keys to check.
     * @return bool true on success or false on failure.
     */
    public function handle($key, $array)
    {
        return array_key_exists($key, $array);
    }
}
