<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

function smarty_modifier_userlink($other_user, $class = 'userlink', $idletime = 'not_set', $fullname = '', $max_length = 0, $popup = '')
{
    $userLinkModifier = new \SmartyTiki\Modifier\UserLink();
    return $userLinkModifier->handle($other_user, $class, $idletime, $fullname, $popup);
}
