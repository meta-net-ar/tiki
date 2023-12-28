<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

function smarty_modifier_username($user, $login_fallback = true, $check_user_show_realnames = true, $html_encoding = true)
{
    $usernameModifier = new \SmartyTiki\Modifier\Username();
    return $usernameModifier->handle($user, $login_fallback, $check_user_show_realnames, $html_encoding);
}
