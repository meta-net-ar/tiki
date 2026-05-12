<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function prefs_kaltura_list()
{
    global $prefs;
    $players = [];

    if (isset($prefs['feature_kaltura']) && $prefs['feature_kaltura'] === 'y') {
        $kalturaadminlib = TikiLib::lib('kalturaadmin');
        $playerList = $kalturaadminlib->getPlayersUiConfs();
        foreach ($playerList as $pl) {
            $players[$pl['id']] = tra($pl['name']);
        }
    }

    $kalturaHelp = 'Kaltura';

    return [
        'kaltura_partnerId' => [
            'name' => tra('Partner ID'),
            'description' => tra('Kaltura Partner ID'),
            'type' => 'text',
            'filter' => 'digits',
            'size' => 10,
            'help' => $kalturaHelp,
            'default' => '',
            'tags' => ['basic'],
        ],
        'kaltura_secret' => [
            'name' => tra('User secret'),
            'description' => tra('Kaltura partner-setting user secret.'),
            'type' => 'text',
            'size' => 45,
            'filter' => 'alnum',
            'help' => $kalturaHelp,
            'default' => '',
            'tags' => ['basic'],
        ],
        'kaltura_adminSecret' => [
            'name' => tra('Admin secret'),
            'description' => tra('Kaltura partner-setting admin secret.'),
            'type' => 'text',
            'size' => 45,
            'filter' => 'alnum',
            'default' => '',
            'tags' => ['basic'],
            'help' => $kalturaHelp,
        ],
        'kaltura_kdpUIConf' => [
            'name' => tra('Kaltura video player ID'),
            'description' => tra('Kaltura Dynamic Player (KDP) user interface configuration ID'),
            'type' => empty($players) ? 'text' : 'list',
            'options' => $players,
            'size' => 20,
            'default' => '',
            'help' => $kalturaHelp,
            'tags' => ['basic'],
        ],
        'kaltura_kdpEditUIConf' => [
            'name' => tra('Kaltura video player ID (in entry edit mode)'),
            'description' => tra('Kaltura Dynamic Player (KDP) user interface configuration ID for use when editing. You can use a player which also has an option to select a frame as video thumbnail'),
            'type' => empty($players) ? 'text' : 'list',
            'options' => $players,
            'size' => 20,
            'default' => '',
            'help' => $kalturaHelp,
            'tags' => ['basic'],
        ],
        'kaltura_kServiceUrl' => [
            'name' => tra('Kaltura service URL'),
            'description' => tra('for example, https://www.kaltura.com/'),
            'type' => 'text',
            'size' => 40,
            'default' => 'https://www.kaltura.com/',
            'tags' => ['basic'],
            'help' => $kalturaHelp,
        ],
        'kaltura_legacyremix' => [
            'name' => tra('Show remixes from old versions of Kaltura'),
            'description' => tra('Show remixes from old versions of Kaltura (remixing is no longer supported)'),
            'type' => 'flag',
            'default' => 'n',
            'help' => $kalturaHelp,
            'view' => 'tiki-list_kaltura_entries.php?list=mix',
        ],
    ];
}
