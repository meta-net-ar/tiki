<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\Modifier;

use SmartyTiki\TikiSmartyExtensionInterface;

/**
 * Smarty modifier var_dump
 * ------------------------
 * Purpose: Dumps information about a variable
 */
class VarDump implements TikiSmartyExtensionInterface
{
    public static function getSmartyName(): string
    {
        return 'var_dump';
    }

    /**
     * @param mixed $value
     * @return void
     */
    public function handle($value)
    {
        // @phpstan-ignore disallowedFunctions.varDump (implements the {$var|var_dump} Smarty modifier for dev debugging)
        return var_dump($value);
    }
}
