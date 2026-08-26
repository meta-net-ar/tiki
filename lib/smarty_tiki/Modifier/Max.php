<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\Modifier;

use SmartyTiki\TikiSmartyExtensionInterface;

/**
 * Smarty modifier max
 * -------------------------------------------------------------
 * Purpose: Find highest value from given values. This modifier implements the PHP max function.
 */
class Max implements TikiSmartyExtensionInterface
{
    public static function getSmartyName(): string
    {
        return 'max';
    }

    /**
     * @param mixed $value Any comparable value
     * @param mixed $values Any comparable value
     * @return mixed
     * @see https://php.net/manual/en/function.max.php for more details about params, returned values and how it works.
     */
    public function handle(mixed $value, mixed ...$values): mixed
    {
        return max($value, $values);
    }
}
