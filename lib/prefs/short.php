<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function prefs_short_list()
{
    return [
        'short_date_format' => [
            'name' => tra('Short date format'),
            'description' => tra('Specify how Tiki displays the date (shorter version)'),
            'help' => 'Date-and-Time#Date_and_Time_Formats',
            'type' => 'text',
            'size' => '30',
            'default' => '%Y-%m-%d',
            //get_strings tra("%Y-%m-%d");
            'tags' => ['basic'],
        ],
        'short_time_format' => [
            'name' => tra('Short time format'),
            'description' => tra('Specify how Tiki displays the time (shorter version)'),
            'help' => 'Date-and-Time#Date_and_Time_Formats',
            'type' => 'text',
            'size' => '30',
            'default' => '%H:%M',
            //get_strings tra("%H:%M");
            'tags' => ['basic'],
        ],
        'short_date_format_js' => [
            'name' => tra('Short JavaScript date format'),
            'description' => tra('Used in the date picker fields'),
            'help' => 'https://day.js.org/docs/en/display/format#list-of-all-available-formats',
            'type' => 'text',
            'size' => '30',
            'default' => 'YYYY-MM-DD',
        ],
        'short_time_format_js' => [
            'name' => tra('Short JavaScript time format'),
            'description' => tra('Used in the datetime picker fields'),
            'help' => 'https://day.js.org/docs/en/display/format#list-of-all-available-formats',
            'type' => 'text',
            'size' => '30',
            'default' => 'HH:mm',
        ],
    ];
}
