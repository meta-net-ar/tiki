<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\Traits;

/**
 * @method mixed handle(...$args)
 */
trait ModifierStaticFacadeTrait
{
    /**
     * Static facade for calling this modifier from PHP code.
     */
    public static function apply(...$args)
    {
        $className = static::class;

        return (new $className())->handle(...$args);
    }
}
