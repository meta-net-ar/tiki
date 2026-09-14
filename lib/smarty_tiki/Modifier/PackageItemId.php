<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\Modifier;

use SmartyTiki\TikiSmartyExtensionInterface;

class PackageItemId implements TikiSmartyExtensionInterface
{
    public static function getSmartyName(): string
    {
        return 'packageitemid';
    }

    public function handle($token)
    {
        $api = new \Tiki\Package\Extension\Api();
        return $api->getItemIdFromToken($token);
    }
}
