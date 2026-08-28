<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\Modifier;

use SmartyTiki\TikiSmartyExtensionInterface;

/**
 * @param string $string - Required. Specifies the string to check
 * @param string $chars - Optional. Specifies which characters to remove from the string. If omitted, the following characters will be removed: " \t\n\r\0\x0B"
 *
 * @return string - String trimed
 */
class Trim implements TikiSmartyExtensionInterface
{
    public static function getSmartyName(): string
    {
        return 'trim';
    }

    public function handle($string, $chars = null)
    {
        return empty($chars) ? trim($string) : trim($string, $chars);
    }
}
