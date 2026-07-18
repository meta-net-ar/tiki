<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function prefs_bigbluebutton_list()
{
    $bigBlueButtonHelp = 'BigBlueButton';
    $bigBlueButtonKeywords = 'big blue button web conferencing audio video chat screensharing whiteboard';
    $bigBlueButtonBasicKeywords = 'big blue button';

    return [
        'bigbluebutton_feature' => [
            'name' => tra('BigBlueButton web conferencing'),
            'description' => tra('Integration with the BigBlueButton collaboration server for web conference and screen sharing.'),
            'type' => 'flag',
            'keywords' => $bigBlueButtonKeywords,
            'help' => $bigBlueButtonHelp,
            'tags' => ['basic'],
            'default' => 'n',
            'extensions' => [
                'dom',
            ],
        ],
        'bigbluebutton_server_location' => [
            'name' => tra('BigBlueButton server location'),
            'description' => tra('Full URL to the BigBlueButton installation.'),
            'type' => 'text',
            'filter' => 'url',
            'hint' => tra('http://host.example.org/'),
            'keywords' => $bigBlueButtonKeywords,
            'help' => $bigBlueButtonHelp,
            'size' => 40,
            'tags' => ['basic'],
            'default' => '',
        ],
        'bigbluebutton_shared_secret' => [
            'name' => tra('BigBlueButton shared secret'),
            'description' => tra('A secret key used to generate checksums for the BigBlueButton server to assure that requests are authentic.'),
            'keywords' => $bigBlueButtonKeywords,
            'type' => 'text',
            'size' => 40,
            'filter' => 'text',
            'help' => $bigBlueButtonHelp,
            'tags' => ['basic'],
            'default' => '',
        ],
        'bigbluebutton_recording_max_duration' => [
            'name' => tr('BigBlueButton recording maximum duration'),
            'description' => tr('A maximum duration for the meetings must be submitted to BigBlueButton to prevent the recordings from being excessively long if a user leaves the conference window open.'),
            'units' => tra('minutes'),
            'keywords' => $bigBlueButtonBasicKeywords,
            'type' => 'text',
            'filter' => 'digits',
            'size' => 6,
            'help' => $bigBlueButtonHelp,
            'default' => 5 * 60,
            'tags' => ['basic'],
        ],
        'bigbluebutton_dynamic_configuration' => [
            'name' => tr('BigBlueButton dynamic configuration'),
            'description' => tr('Uses the advanced options of BigBlueButton to configure the XML per room.'),
            'keywords' => $bigBlueButtonBasicKeywords,
            'type' => 'flag',
            'help' => $bigBlueButtonHelp,
            'default' => 'n',
            'tags' => ['advanced', 'experimental'],
        ],
        'bigbluebutton_use_iframe' => [
            'name' => tr('Use iframe to join BigBlueButton meetings'),
            'description' => tr('If enabled, meetings will be opened inside an iframe instead of a new browser tab. ' .
                       'Note: The BBB server must allow your Tiki site to embed meetings in an iframe. ' .
                       'This may require configuring X-Frame-Options or CORS on the BBB server.'),
            'keywords' => 'big blue button iframe',
            'type' => 'flag',
            'default' => 'n',
            'tags' => ['advanced', 'experimental'],
            'help' => $bigBlueButtonHelp,
        ],
    ];
}
