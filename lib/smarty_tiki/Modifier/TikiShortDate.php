<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\Modifier;

use TikiLib;

class TikiShortDate
{
    /**
     * @param string  $string
     * @param string $same   if set to 'n' will bypass timeago preferences. Useful when markup is illegal in date
     *
     * @return string
     */
    public function handle($string, $same = 'y')
    {
        global $prefs;
        $date = smarty_modifier_tiki_date_format($string, $prefs['short_date_format']);

        if ($prefs['jquery_timeago'] === 'y' && $same === 'y') {
            TikiLib::lib('header')->add_jq_onready('$("time.timeago").timeago();');
            return '<time class="timeago" datetime="' . TikiLib::date_format('c', $string, false, 5, false) . '">' . $date . '</time>';
        } else {
            return $date;
        }
    }
}
