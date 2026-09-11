<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\Modifier;

use SmartyTiki\TikiSmartyExtensionInterface;

/**
 * Smarty "sizeof" modifier
 * ------------------------
 * Purpose: Same as "count" modifier. Counts all elements in an array or in a Countable object
 */
class Sizeof implements TikiSmartyExtensionInterface
{
    public static function getSmartyName(): string
    {
        return 'sizeof';
    }

    /**
     * @param \Countable|array $value
     * @param int $mode [optional]
     * @return int<0, max>
     */
    public function handle($value, $mode = COUNT_NORMAL)
    {
        return sizeof($value, $mode);
    }
}
