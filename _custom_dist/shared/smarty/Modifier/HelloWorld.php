<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTikiCustom\Modifier;

use SmartyTiki\TikiSmartyExtensionInterface;

/**
 * Sample custom Smarty modifier for _custom/shared/smarty.
 *
 * Usage in a template: {$var|helloworld}
 *
 * After copying this file (and the rest of _custom_dist/shared/smarty) into
 * _custom/shared/smarty, run `php console.php smarty:generate-mapping` to pick it up.
 */
class HelloWorld implements TikiSmartyExtensionInterface
{
    public static function getSmartyName(): string
    {
        return 'helloworld';
    }

    public function handle($string)
    {
        return "Hello world modifier: $string (_custom/shared/smarty/Modifier/HelloWorld)";
    }
}
