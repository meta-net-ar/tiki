<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function prefs_server_list($partial = false)
{

    // Skipping the getTimeZoneList() from tikidate which just emulates the pear date format
    // Generating it is extremely costly in terms of memory.
    if (class_exists('DateTimeZone')) {
        $timezones = DateTimeZone::listIdentifiers();
    } elseif (class_exists('DateTime')) {
        $timezones = array_keys(DateTime::getTimeZoneList());
    } else {
        $timezones = TikiDate::getTimeZoneList();
        $timezones = array_keys($timezones);
    }

    sort($timezones);

    $tikidate = TikiLib::lib('tikidate');

    return [
        'server_timezone' => [
            'name' => tra('Time zone'),
            'description' => tra('Indicates the default time zone to use for the server.'),
            'type' => 'list',
            'options' => array_combine($timezones, $timezones),
            'default' => isset($tikidate) ? $tikidate->getTimezoneId() : 'UTC',
            'tags' => ['basic'],
        ],
        'server_domain' => [
            'name' => tra('Server domain name'),
            'description' => tra('The value that will be used by default as the server domain name of your Tiki site.'),
            'warning' => tra('Leave this blank if you are not entirely sure what you are doing. ' .
                'If this value is incorrect or your DNS is not set up properly it will lock you out of your Tiki and require database or shell access to recover it.'),
            'type' => 'text',
            'default' => '',
            'public' => true,
        ],
    ];
}
