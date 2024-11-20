<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Math_Formula_Function_StrToTime extends Math_Formula_Function
{
    protected $calendarlib;

    public function setCalenarLibForTests($calendarlib)
    {
        $this->calendarlib = $calendarlib;
    }

    public function evaluate($args)
    {
        $elements = [];

        if (count($args) > 2) {
            $this->error(tr('Too many arguments on strtotime.'));
        }

        if (count($args) < 1) {
            $this->error(tr('Too few arguments on strtotime.'));
        }

        foreach ($args as $child) {
            $elements[] = $this->evaluateChild($child);
        }

        $tikilib = TikiLib::lib('tiki');
        $tz = $tikilib->get_display_timezone();
        $oldTz = date_default_timezone_get();
        if ($tz) {
            date_default_timezone_set($tz);
        }

        $time = array_shift($elements);
        $now = (int)array_shift($elements);
        if (empty($now)) {
            $now = time();  // Seconds since the Unix Epoch (January 1 1970 00:00:00 GMT)
        }

        if (! empty($time) && preg_match('/(work(ing)|business) (days?)/', $time, $m)) {
            $time = str_replace($m[0], $m[3], $time);
            $newTime = strtotime($time, $now);
            if (is_numeric($newTime)) {
                // skip the non-working days via this algorithm:
                // 1. calculate the new time by the given formula
                // 2. extend it with the number of non-working days between start/end time
                // 3. check if there are more non-working days added by this extension
                // 4. go to step 2 to repeat the extension until there are no more non-working days to add
                $inc = $newTime < $now ? -1 : 1;
                $tempNewTime = $newTime;
                $nonWorking = $this->countNonWorkingDaysBetweenDates($now, $tempNewTime);
                while (($tempNewTime - $now) * $inc != ($newTime + $inc * $nonWorking * 86400 - $now) * $inc) {
                    $tempNewTime = $newTime + $inc * $nonWorking * 86400;
                    $nonWorking = $this->countNonWorkingDaysBetweenDates($now, $tempNewTime);
                }
                $newTime = $tempNewTime;
            }
        } else {
            $newTime = strtotime($time, $now);
        }

        date_default_timezone_set($oldTz);

        return $newTime;
    }

    private function countNonWorkingDaysBetweenDates($time1, $time2)
    {
        global $prefs;
        if (empty($prefs['calendar_holidays'])) {
            return 0;
        }
        if (! $this->calendarlib) {
            $this->calendarlib = TikiLib::lib('calendar');
        }
        $events = $this->calendarlib->get_events($prefs['calendar_holidays'], [], null, min($time1, $time2), max($time1, $time2));
        $dates = [];
        foreach ($events as $event) {
            $start = $event['start'];
            while ($start <= $event['end']) {
                $dates[] = date('Y-m-d', $start);
                $start = strtotime('+1 day', $start);
            }
        }
        return count(array_unique($dates));
    }
}
