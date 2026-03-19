<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Math_Formula_Function_Acosh extends Math_Formula_Function
{
    public function evaluate($element)
    {
        $child = $this->evaluateChild($element[0]);
        if (! is_numeric($child)) {
            $this->error(tr('Trying to execute acosh on a non-numeric value "%0"', $child));
        } elseif ($child < 1) {
            $this->error(tr('Trying to execute acosh on a value less than 1: "%0"', $child));
        }
        return acosh((float) $child);
    }
}
