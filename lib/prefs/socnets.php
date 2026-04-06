<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
//author : aris002@yahoo.co.uk
use Tiki\Lib\Socnets\PrefsGen;

/**
* @return array
**/
function prefs_socnets_list()
{
    $prefix = substr(basename(__FILE__), 0, -4) . '_';

    return PrefsGen::getSocPrefs($prefix);
}
