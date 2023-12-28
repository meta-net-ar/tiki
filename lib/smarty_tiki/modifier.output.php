<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 *  Fake modifier for pretty tracker fields
 */
function smarty_modifier_output($string)
{
    $outputModifier = new \SmartyTiki\Modifier\Output();
    return $outputModifier->handle($string);
}
