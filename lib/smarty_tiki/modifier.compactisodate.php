<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// this returns the compact ISO 8601 date for microformats
function smarty_modifier_compactisodate($string)
{
    $compactIsoDateModifier = new \SmartyTiki\Modifier\CompactIsoDate();
    return $compactIsoDateModifier->handle($string);
}
