<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

function smarty_modifier_adjust(
    $string,
    $length = 80,
    $pad = '&nbsp;',
    $etc = '...',
    $break_words = false
) {

    $adjustModifier = new \SmartyTiki\Modifier\Adjust();
    return $adjustModifier->handle($string, $length, $pad, $etc, $break_words);
}
