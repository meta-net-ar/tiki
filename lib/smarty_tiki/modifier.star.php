<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function smarty_modifier_star($score)
{
    $startModifier = new \SmartyTiki\Modifier\Star();
    return $startModifier->handle($score);
}
