<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

function smarty_modifier_kbsize($string, $bytes = false, $nb_decimals = 2, $unit_separator = '&nbsp;')
{
    $kbSizeModifier = new \SmartyTiki\Modifier\KbSize();
    return $kbSizeModifier->handle($string, $bytes, $nb_decimals, $unit_separator);
}
