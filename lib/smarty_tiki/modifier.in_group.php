<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for deta

function smarty_modifier_in_group($group, $auser = '')
{
    $inGroupModifier = new \SmartyTiki\Modifier\InGroup();
    return $inGroupModifier->handle($group, $auser);
}
