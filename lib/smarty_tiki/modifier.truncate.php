<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

function smarty_modifier_truncate(
    $string,
    $length = 80,
    $etc = '...',
    $break_words = false,
    $middle = false
) {
    $truncateModifier = new \SmartyTiki\Modifier\Truncate();
    return $truncateModifier->handle($string, $length, $etc, $break_words, $middle);
}
