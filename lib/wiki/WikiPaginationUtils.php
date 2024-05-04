<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
//this script may only be included - so its better to die if called directly.

namespace Tiki\Lib\wiki;

/**
* Contains utility functions for pagination
*/
class WikiPaginationUtils
{
    //Special parsing for multipage articles
    public static function getNumberOfPages($data)
    {
        global $prefs;
        // Temporary remove <PRE></PRE> secions to protect
        // from broke <PRE> tags and leave well known <PRE>
        // behaviour (i.e. type all text inside AS IS w/o
        // any interpretation)
        $preparsed = [];

        preg_match_all("/(<[Pp][Rr][Ee]>)(.*?)(<\/[Pp][Rr][Ee]>)/s", $data, $preparse);
        $idx = 0;

        foreach (array_unique($preparse[2]) as $pp) {
            $key = md5(Tikilib::genPass());

            $aux['key'] = $key;
            $aux['data'] = $pp;
            $preparsed[] = $aux;
            $data = str_replace($preparse[1][$idx] . $pp . $preparse[3][$idx], $key, $data);
            $idx = $idx + 1;
        }

        $parts = empty($prefs['wiki_page_separator']) ? [$data] : explode($prefs['wiki_page_separator'], $data);
        return count($parts);
    }

    public static function getPage($data, $i)
    {
        // Get slides
        global $prefs;
        $parts = empty($prefs['wiki_page_separator']) ? [$data] : explode($prefs['wiki_page_separator'], $data);

        if (isset($parts[$i - 1])) {
            $ret = $parts[$i - 1];

            if (substr($parts[$i - 1], 1, 5) == '<br/>') {
                $ret = substr($parts[$i - 1], 6);
            }

            if (substr($parts[$i - 1], 1, 6) == '<br />') {
                $ret = substr($parts[$i - 1], 7);
            }
        } else {
            //The case where $parts[$i - 1] is not set (null or out of range).
            Feedback::errorPage(['mes' => "Page not found"]);
        }

        return $ret;
    }
}
