<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\Modifier;

use SmartyTiki\TikiSmartyExtensionInterface;

/**
 * Tests whether a user is in a specific group, usage:
 *
 *      {if 'Admins'|in_group}...
 * or
 *      {if 'Group Name'|in_group:'testuser'}...
 *
 * @param string $group     group name to test (string being "modified")
 * @param string $auser     user name to check if not current logged-in user
 * @return bool
 * @throws Exception
 */
class InGroup implements TikiSmartyExtensionInterface
{
    public static function getSmartyName(): string
    {
        return 'in_group';
    }

    public function handle($group, $auser = '')
    {
        global $user;

        if (! $auser) {
            $auser = $user;
        }
        return \TikiLib::lib('user')->user_is_in_group($auser, $group);
    }
}
