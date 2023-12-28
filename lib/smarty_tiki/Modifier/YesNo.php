<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\Modifier;

class YesNo
{
    public function handle($string)
    {
        switch ($string) {
            case 'y':
                return tra('Yes');
                break;

            case 'n':
                return tra('No');
                break;

            default:
                return $string;
        }
    }
}
