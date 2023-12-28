<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\Modifier;

class Percent
{
    /**
     *
     * returns a percentage instead of a fraction
     * @param float $string fraction to format
     */
    public function handle($string)
    {
        return number_format($string * 100, 1);
    }
}
