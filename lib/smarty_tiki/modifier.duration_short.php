<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

function smarty_modifier_duration_short($string)
{
    $durationShortModifier = new \SmartyTiki\Modifier\DurationShort();
    return $durationShortModifier->handle($string);
}
