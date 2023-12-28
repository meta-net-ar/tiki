<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

function smarty_modifier_escape($string, $esc_type = 'html', $char_set = 'UTF-8', $double_encode = true)
{
    $escapeModifier = new \SmartyTiki\Modifier\Escape();
    return $escapeModifier->handle($string, $esc_type, $char_set, $double_encode);
}
