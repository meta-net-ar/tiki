<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Math_Formula_Function_Atanh extends Math_Formula_Function
{
    public function evaluate($element)
    {
        $child = $this->evaluateChild($element[0]);
        if (! is_numeric($child)) {
            $this->error(tr('Trying to execute atanh on a non-numeric value "%0"', $child));
        } elseif ($child <= -1 || $child >= 1) {
            $this->error(tr('Trying to execute atanh on a value outside (-1, 1): "%0"', $child));
        }
        return atanh((float) $child);
    }
}
