<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\Modifier;

use SmartyTiki\TikiSmartyExtensionInterface;

/**
 * Smarty modifier strtolower
 * --------------------------
 * Purpose: Make a string lowercase - using default PHP function
 */
class Strtolower implements TikiSmartyExtensionInterface
{
    public static function getSmartyName(): string
    {
        return 'strtolower';
    }

    /**
     * @param string $string The string to be lowercased.
     * @return string
     */
    public function handle($string)
    {
        return strtolower($string);
    }
}
