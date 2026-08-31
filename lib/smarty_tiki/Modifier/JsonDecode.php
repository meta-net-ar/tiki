<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\Modifier;

use SmartyTiki\TikiSmartyExtensionInterface;

/**
 * Smarty modifier json_decode
 * --------------------------
 * Purpose: Decode a JSON string. Get a string JSON encoded and convert it into a PHP value.
 */
class JsonDecode implements TikiSmartyExtensionInterface
{
    public static function getSmartyName(): string
    {
        return 'json_decode';
    }

    /**
     * @param string $json The JSON string
     * @param bool $associative
     * @param int $depth
     * @param int $flags
     * @return mixed
     * @see https://php.net/manual/en/function.json-decode.php for more details about params
     */
    public function handle($json, $associative = null, $depth = 512, $flags = 0)
    {
        return json_decode($json, $associative, $depth, $flags);
    }
}
