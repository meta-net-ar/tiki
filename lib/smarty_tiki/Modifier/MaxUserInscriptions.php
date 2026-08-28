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
 * Name:     max_user_inscriptions
 * Purpose:  to use with the tracker field type "User inscription"
 * -------------------------------------------------------------
 */
class MaxUserInscriptions implements TikiSmartyExtensionInterface
{
    public static function getSmartyName(): string
    {
        return 'max_user_inscriptions';
    }

    public function handle($text)
    {
        return substr($text, 0, strpos($text, '#'));
    }
}
