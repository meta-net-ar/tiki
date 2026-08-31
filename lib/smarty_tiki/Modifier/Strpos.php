<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\Modifier;

use SmartyTiki\TikiSmartyExtensionInterface;

/**
 * Smarty modifier strpos
 * ----------------------
 * Purpose: Find the position of the first occurrence in a string
 *
 * Syntax: {$haystack|strpos:$needle:$offset}
 */
class Strpos implements TikiSmartyExtensionInterface
{
    public static function getSmartyName(): string
    {
        return 'strpos';
    }

    /**
     * @param string $haystack The string in which to search.
     * @param string $needle The string to search for.
     * @param int<0, max> $offset Optional. The position from which to start the search
     * @return int|false
     */
    public function handle($haystack, $needle, $offset = 0)
    {
        return strpos($haystack, $needle, $offset);
    }
}
