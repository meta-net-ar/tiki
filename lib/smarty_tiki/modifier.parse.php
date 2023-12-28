<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

function smarty_modifier_parse($string, $simple = false)
{
    $parseModifier = new \SmartyTiki\Modifier\Parse();
    return $parseModifier->handle($string, $simple);
}
