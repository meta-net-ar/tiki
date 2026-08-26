<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\Modifier;

use SmartyTiki\TikiSmartyExtensionInterface;

/**
 * Gets Category Id from the Category name
 */
class CategId implements TikiSmartyExtensionInterface
{
    public static function getSmartyName(): string
    {
        return 'categid';
    }

    public function handle($category)
    {
        return \TikiLib::lib('categ')->get_category_id($category);
    }
}
