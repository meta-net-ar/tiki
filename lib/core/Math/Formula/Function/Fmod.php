<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Math_Formula_Function_Fmod extends Math_Formula_Function
{
    public function evaluate($element)
    {
        $x = $this->evaluateChild($element[0]);
        $y = $this->evaluateChild($element[1]);
        if (! is_numeric($x) || ! is_numeric($y)) {
            $this->error(tr('Trying to execute fmod on non-numeric values "%0", "%1"', $x, $y));
        } elseif ((float) $y == 0.0) {
            $this->error(tr('Trying to execute fmod with divisor zero'));
        }
        return fmod((float) $x, (float) $y);
    }
}
