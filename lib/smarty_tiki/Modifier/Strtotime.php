<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\Modifier;

use SmartyTiki\TikiSmartyExtensionInterface;

/**
 * Smarty modifier strtotime
 * -------------------------
 * Purpose: Parse about any English textual datetime description into a Unix timestamp.
 *
 * @param string $datetime The date/time string to convert.
 * @param int|null $baseTimestamp The base timestamp to use for relative calculations.
 * @return int|false The Unix timestamp representing the given date/time string, or false on failure.
 */
class Strtotime implements TikiSmartyExtensionInterface
{
    public static function getSmartyName(): string
    {
        return 'strtotime';
    }

    public function handle($datetime, $baseTimestamp = null)
    {
        return strtotime($datetime, $baseTimestamp);
    }
}
