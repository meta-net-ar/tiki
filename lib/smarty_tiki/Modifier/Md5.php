<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\Modifier;

use SmartyTiki\TikiSmartyExtensionInterface;

/**
 * Smarty modifier md5
 * -------------------
 * Purpose: Calculate the md5 of a string
 */
class Md5 implements TikiSmartyExtensionInterface
{
    public static function getSmartyName(): string
    {
        return 'md5';
    }

    /**
     * @param string $string The string
     * @param bool $binary Optional. If set to true, then the md5 is returned in raw binary format with a length of 16.
     * @return string Returns the md5 of the string, as a 32-character hexadecimal number.
     */
    public function handle($string, $binary = false)
    {
        return md5($string, $binary);
    }
}
