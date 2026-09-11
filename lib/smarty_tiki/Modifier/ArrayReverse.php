<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\Modifier;

use SmartyTiki\TikiSmartyExtensionInterface;

/**
 * Smarty modifier array_reverse
 * -----------------------------
 * Purpose: Reverse the order of array elements
 */
class ArrayReverse implements TikiSmartyExtensionInterface
{
    public static function getSmartyName(): string
    {
        return 'array_reverse';
    }

    /**
     * @param array $array The entry table.
     * @param bool $preserve_keys Optional. If set to true, numeric keys will be preserved. Non-numeric keys will not be affected by this configuration, and will always be preserved.
     * @return array The array in reverse order.
     */
    public function handle($array, $preserve_keys = false)
    {
        return array_reverse($array, $preserve_keys);
    }
}
