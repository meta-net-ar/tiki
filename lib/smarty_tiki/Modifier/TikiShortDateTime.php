<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\Modifier;

use TikiLib;
use SmartyTiki\TikiSmartyExtensionInterface;
use SmartyTiki\Traits\ModifierStaticFacadeTrait;

class TikiShortDateTime implements TikiSmartyExtensionInterface
{
    use ModifierStaticFacadeTrait;

    public static function getSmartyName(): string
    {
        return 'tiki_short_datetime';
    }

    /**
     * @param string $string
     * @param string $intro
     * @param string $same if set to 'n' will bypass timeago preferences. Useful when markup is illegal in date
     * @param $forceTimezone the time zone to be applied. Can be a timezone identifier or false, if user is not false forceTimezone must always be false
     *
     * @return string
     */
    public function handle($string, $intro = '', $same = 'y', $forceTimezone = false)
    {
        global $prefs;
        \TikiLib::lib('smarty'); //Load SmartyLib for side effects
        $date = \SmartyTiki\Modifier\TikiDateFormat::apply($string, $prefs['short_date_format'], false, $forceTimezone);
        $time = \SmartyTiki\Modifier\TikiDateFormat::apply($string, $prefs['short_time_format'], false, $forceTimezone);

        $intro = ! empty($intro) ? tra($intro) . ' ' : '';

        if ($prefs['jquery_timeago'] === 'y' && $same === 'y') {
            TikiLib::lib('header')->add_jq_onready('$("time.timeago").tikiTimeago();');
            return '<time class="timeago" datetime="' . TikiLib::date_format('c', $string, false, 5, false) . '">' . $date . ' ' . $time . '</time>';
        } elseif ($same != 'n' && $prefs['tiki_same_day_time_only'] == 'y' && $date == \SmartyTiki\Modifier\TikiDateFormat::apply(time(), $prefs['short_date_format'])) {
            //tra('on') tra('on:') tra('at') tra('at:')
            return str_replace(['on', 'On'], ['at', 'At'], $intro) . $time;
        } else {
            // if you change the separator do not forget to change the translation instruction in lib/prefs/short.php
            $time = $date . ' ' . $time;
            return $intro . ' ' . $time;
        }
    }
}
