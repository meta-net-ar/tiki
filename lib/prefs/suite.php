<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function prefs_suite_list()
{
    $jitsiHelp = 'Jitsi';

    return [
        'suite_jitsi_provision' => [
            'name' => tr('Expose Jitsi provision URL'),
            'description' => tr('Provide connection configuration information for Jitsi users to connect to a community/organization instant messaging server.'),
            'help' => $jitsiHelp,
            'type' => 'flag',
            'default' => 'n',
        ],
        'suite_jitsi_configuration' => [
            'name' => tr('Jitsi configuration'),
            'description' => tr('Content of a Jitsi-format Java properties file'),
            'type' => 'textarea',
            'size' => 10,
            'default' => '',
            'help' => $jitsiHelp,
        ],
    ];
}
