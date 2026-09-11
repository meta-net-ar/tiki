<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function prefs_ids_list()
{
    $siteAccessHelp = 'Site-Access';

    return [
        'ids_enabled' => [
            'name' => tra('Enable intrusion detection system'),
            'description' => tra('An intrusion detection system (IDS) is a device or software application that monitors a network or systems for malicious activity or policy violations.'),
            'type' => 'flag',
            'default' => 'n',
            'help' => $siteAccessHelp,
            'packages_required' => ['enygma/expose' => 'Expose\Manager'],
        ],
        'ids_mode' => [
            'name' => tra('Intrusion detection system mode'),
            'description' => tra('Define IDS operation mode, log only, or log and block with impact over a given threshold.'),
            'type' => 'list',
            'help' => $siteAccessHelp,
            'options' => [
                'log_only' => tra('Log only'),
                'log_block' => tra('Log and block requests'),
            ],
            'default' => 'log_only',
            'dependencies' => [
                'ids_enabled',
            ],
        ],
        'ids_threshold' => [
            'name' => tra('Intrusion detection system threshold'),
            'description' => tra('Define IDS threshold, when configured in "Log and block requests" more.'),
            'help' => $siteAccessHelp,
            'type' => 'text',
            'size' => 5,
            'filter' => 'digits',
            'default' => '0',
            'dependencies' => [
                'ids_enabled',
            ],
        ],
        'ids_custom_rules_file' => [
            'name' => tra('Custom rules file'),
            'description' => tra('Filesystem path to the JSON file containing custom intrusion detection rules loaded in addition to the default rules.'),
            'help' => $siteAccessHelp,
            'type' => 'text',
            'default' => 'temp/ids_custom_rules.json',
            'dependencies' => [
                'ids_enabled',
            ],
        ],
        'ids_log_to_file' => [
            'name' => tra('Log to file'),
            'description' => tra('Filesystem path to the log file where intrusion detection events are recorded.'),
            'type' => 'text',
            'help' => $siteAccessHelp,
            'default' => 'ids.log',
            'dependencies' => [
                'ids_enabled',
            ],
        ],
        'ids_log_to_database' => [
            'name' => tra('Log to database'),
            'description' => tra('Store intrusion detection events in the database.'),
            'type' => 'flag',
            'default' => 'n',
            'help' => $siteAccessHelp,
            'dependencies' => [
                'ids_enabled',
            ],
        ],
    ];
}
