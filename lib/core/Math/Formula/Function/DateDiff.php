<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Math_Formula_Function_DateDiff extends Math_Formula_Function
{
    public function evaluate($args)
    {
        $elements = [];

        if (count($args) > 3) {
            $this->error(tr('Too many arguments on date-diff.'));
        }

        if (count($args) < 2) {
            $this->error(tr('Too few arguments on date-diff.'));
        }

        foreach ($args as $child) {
            $elements[] = $this->evaluateChild($child);
        }

        $ts1 = intval($elements[0]);
        $ts2 = intval($elements[1]);
        $diff = abs($ts1 - $ts2);

        if (! empty($elements[2])) {
            $nonWorking = TikiLib::lib('calendar')->countNonWorkingDaysBetweenDates($ts1, $ts2);
            $diff -= $nonWorking * 86400;
        }

        return $diff;
    }
}
