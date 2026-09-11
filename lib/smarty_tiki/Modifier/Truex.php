<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\Modifier;

use SmartyTiki\TikiSmartyExtensionInterface;

/**
 * Smarty plugin
 * -------------------------------------------------------------
 * Type:     modifier
 * Replaces 1 (=TRUE) with a 'x'. Anything else with '-'
 * used for example to output file permissions in
 * tiki-admin_security
 * -------------------------------------------------------------
 */
class Truex implements TikiSmartyExtensionInterface
{
    public static function getSmartyName(): string
    {
        return 'truex';
    }

    public function handle($string)
    {
        if ((int) $string == 1) {
            return ('x');
        }
        return ('-');
    }
}
