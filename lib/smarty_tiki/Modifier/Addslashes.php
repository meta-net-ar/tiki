<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\Modifier;

use SmartyTiki\TikiSmartyExtensionInterface;

/**
 * Smarty modifier addslashes
 * --------------------------
 * Purpose: Quote string with slashes
 */
class Addslashes implements TikiSmartyExtensionInterface
{
    public static function getSmartyName(): string
    {
        return 'addslashes';
    }

    /**
     * @param string $string The string to be escaped.
     * @return string A string with backslashes added before characters that need to be escaped. These characters are: ',",\, NUL (the NUL byte)
     */
    public function handle($string)
    {
        return addslashes($string);
    }
}
