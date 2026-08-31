<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\Modifier;

use SmartyTiki\TikiSmartyExtensionInterface;

/**
 *  Fake modifier for pretty tracker fields. Sets template to use to display
 */
class Template implements TikiSmartyExtensionInterface
{
    public static function getSmartyName(): string
    {
        return 'template';
    }

    public function handle($string)
    {
        return $string;
    }
}
