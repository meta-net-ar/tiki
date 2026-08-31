<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\Modifier;

use SmartyTiki\TikiSmartyExtensionInterface;

/**
 * Smarty modifier urlencode
 * -------------------------
 * Purpose: URL-encodes string
 */
class Urlencode implements TikiSmartyExtensionInterface
{
    public static function getSmartyName(): string
    {
        return 'urlencode';
    }

    /**
     * @param string $string The string to be encoded.
     * @return string A string in which all non-alphanumeric characters except -_. have been replaced with a percent (%) sign followed by two hex digits and spaces encoded as plus (+) signs.
     */
    public function handle($string)
    {
        return urlencode($string);
    }
}
