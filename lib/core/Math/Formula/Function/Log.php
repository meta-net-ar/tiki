<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Math_Formula_Function_Log extends Math_Formula_Function
{
    public function evaluate($element)
    {
        $x = $this->evaluateChild($element[0]);
        if (! is_numeric($x)) {
            $this->error(tr('Trying to execute log on a non-numeric value "%0"', $x));
        } elseif ($x <= 0) {
            $this->error(tr('Trying to execute log on a non-positive value "%0"', $x));
        }
        if (isset($element[1])) {
            $base = $this->evaluateChild($element[1]);
            if (! is_numeric($base) || $base <= 0 || $base == 1) {
                $this->error(tr('Invalid base "%0" for log', $base));
            }
            return log((float) $x, (float) $base);
        }
        return log((float) $x);
    }
}
