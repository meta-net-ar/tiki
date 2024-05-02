<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

class Search_Formatter_ValueFormatter_Count extends Search_Formatter_ValueFormatter_Abstract
{
    public function render($name, $value, array $entry)
    {
        if (is_array($value)) {
            return count($value);
        } else {
            return 0;
        }
    }
}
