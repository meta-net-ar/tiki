<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Math_Formula_Function_Atan2 extends Math_Formula_Function
{
    public function evaluate($element)
    {
        $y = $this->evaluateChild($element[0]);
        $x = $this->evaluateChild($element[1]);
        if (! is_numeric($y) || ! is_numeric($x)) {
            $this->error(tr('Trying to execute atan2 on non-numeric values "%0", "%1"', $y, $x));
        }
        return atan2((float) $y, (float) $x);
    }
}
