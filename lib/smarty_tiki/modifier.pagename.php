<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// Translate only if feature_multilingual is on

function smarty_modifier_pagename($source)
{
    $pageNameModifier = new \SmartyTiki\Modifier\PageName();
    return $pageNameModifier->handle($source);
}
