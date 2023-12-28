<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\Modifier;

class IsoDate
{
    public function handle($string)
    {
        global $tikilib;
        return $tikilib->get_iso8601_datetime($string);
    }
}
