<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function wikiplugin_kaltura_info()
{
    global $prefs;
    $players = [];
    if ($prefs['feature_kaltura'] === 'y') {
        $kalturaadminlib = TikiLib::lib('kalturaadmin');

        $playerList = $kalturaadminlib->getPlayersUiConfs();
        foreach ($playerList as $pl) {
            $players[] = ['value' => $pl['id'], 'text' => tra($pl['name'])];
        }

        if (count($players)) {
            array_unshift($players, ['value' => '', 'text' => tra('Default')]);
        }
    }

    return [
        'name' => tra('Kaltura Video'),
        'documentation' => 'PluginKaltura',
        'description' => tra('Display a video created through the Kaltura feature'),
        'prefs' => ['wikiplugin_kaltura', 'feature_kaltura'],
        'format' => 'html',
        'iconname' => 'video',
        'introduced' => 4,
        'params' => [
            'id' => [
                'required' => true,
                'name' => tra('Kaltura Entry ID'),
                'description' => tra('Kaltura ID of the video to be displayed'),
                'since' => '4.0',
                'tags' => ['basic'],
                'area' => 'kaltura_uploader_id',
                'type' => 'kaltura',
                'iconname' => 'video',
            ],
            'player_id' => [
                'required' => false,
                'name' => tra('Kaltura Video Player ID'),
                'description' => tra('Kaltura Dynamic Player (KDP) user interface configuration ID'),
                'since' => '10.0',
                'type' => empty($players) ? 'text' : 'list',
                'options' => $players,
                'size' => 20,
                'default' => '',
                'tags' => ['basic'],
            ],
            'width' => [
                'required' => false,
                'name' => tra('Width'),
                'description' => tra('Max width of the player (px, %, etc). Leave empty for full width.'),
                'since' => '10.0',
                'default' => '',
                'filter' => 'text',
            ],
            'height' => [
                'required' => false,
                'name' => tra('Height'),
                'description' => tra('Height of the player in pixels. Leave empty to use the player ratio.'),
                'since' => '10.0',
                'default' => '',
                'filter' => 'text',
            ],
            'align' => [
                'required' => false,
                'name' => tra('Align'),
                'description' => tra('Alignment of the player'),
                'since' => '10.0',
                'default' => '',
                'filter' => 'word',
                'options' => [
                    ['text' => tra('Not set'), 'value' => ''],
                    ['text' => tra('Left'), 'value' => 'left'],
                    ['text' => tra('Centre'), 'value' => 'center'],
                    ['text' => tra('Right'), 'value' => 'right'],
                ],
            ],
            'float' => [
                'required' => false,
                'name' => tra('Float'),
                'description' => tra('Alignment of the player using CSS float'),
                'since' => '10.0',
                'default' => '',
                'filter' => 'word',
                'options' => [
                    ['text' => tra('Not set'), 'value' => ''],
                    ['text' => tra('Left'), 'value' => 'left'],
                    ['text' => tra('Right'), 'value' => 'right'],
                ],
            ],
            'bg' => [
                'required' => false,
                'name' => tra('Background'),
                'description' => tra('Object background color. Example:') . ' <code>#ffffff</code>, <code>rgb(255, 255, 255)</code>, <code>white</code>',
                'accepted' => tra('Any valid CSS color value, e.g., hex, rgb(a), or color names'),
                'filter' => 'text',
                'default' => '',
                'advanced' => true,
            ],
            'border' => [
                'required' => false,
                'name' => tra('Borders'),
                'description' => tra('Object border color. Example:') . ' <code>#ffffff</code>, <code>rgb(255, 255, 255)</code>, <code>white</code>',
                'accepted' => tra('Any valid CSS color value, e.g., hex, rgb(a), or color names'),
                'filter' => 'text',
                'default' => '',
                'advanced' => true,
            ],
            'borderRadius' => [
                'required' => false,
                'name' => tra('Border Radius'),
                'description' => tra('Apply rounded corners to the container. Default: ') . '<code>Yes</code>',
                'default' => 'y',
                'filter' => 'alpha',
                'options' => [
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n'],
                ],
                'advanced' => true,
            ],
        ],
    ];
}

function wikiplugin_kaltura($data, $params)
{
    global $prefs;

    static $instance = 0;

    $instance++;

    if (empty($params['player_id'])) {
        $params['player_id'] = $prefs['kaltura_kdpUIConf'];
    }

    $rawWidth = isset($params['width']) ? trim($params['width']) : '';
    $rawHeight = isset($params['height']) ? trim($params['height']) : '';

    /*
     * Determine aspect ratio:
     * 1) user width/height
     * 2) Kaltura player config
     * 3) fallback 16:9
     */
    $aspectRatio = '16 / 9';
    if (is_numeric($rawWidth) && is_numeric($rawHeight) && $rawHeight > 0) {
        $aspectRatio = $rawWidth . ' / ' . $rawHeight;
    } else {
        $kalturaadminlib = TikiLib::lib('kalturaadmin');
        $player = $kalturaadminlib->getPlayersUiConf($params['player_id']);
        if (! empty($player)) {
            if (is_numeric($player['width']) && is_numeric($player['height']) && $player['height'] > 0) {
                $aspectRatio = $player['width'] . ' / ' . $player['height'];
            }
        } else {
            return '<span class="alert-warning">' . tra('Player not found') . '</span>';
        }
    }

    // Prepare Kaltura session
    $kalturalib = TikiLib::lib('kalturauser');
    $kalturalib->getSessionKey();

    try {
        $playlistObject = $kalturalib->getPlaylist($params['id']);
    } catch (Exception) {
        $playlistObject = null;
    }

    // Responsive container styles
    $containerStyles = [
        'position' => 'relative',
        'width' => '100%',
        'max-width' => '100%',
        'aspect-ratio' => $aspectRatio,
        'overflow' => 'hidden',
        'margin' => '0 auto',
        'max-height' => '90vh',
    ];

    if ($rawWidth !== '') {
        $containerStyles['max-width'] = is_numeric($rawWidth) ? $rawWidth . 'px' : $rawWidth;
    }

    if (! empty($params['align'])) {
        $containerStyles['text-align'] = $params['align'];
    }
    if (! empty($params['float'])) {
        $containerStyles['float'] = $params['float'];
    }
    if (($params['borderRadius'] ?? 'y') === 'y') {
        $containerStyles['border-radius'] = '12px';
    }
    if (! empty($params['bg'])) {
        $containerStyles['background-color'] = $params['bg'];
    }
    if (! empty($params['border'])) {
        $containerStyles['border'] = '1px solid ' . $params['border'];
    }

    $containerStyle = implode(';', array_map(fn($k, $v) => "$k:$v", array_keys($containerStyles), $containerStyles));

    $playerStyles = [
        'position' => 'absolute',
        'top' => '0',
        'left' => '0',
        'width' => '100%',
        'height' => '100%',
    ];
    $playerStyle = implode(';', array_map(fn($k, $v) => "$k:$v", array_keys($playerStyles), $playerStyles));

    $embedIframeJs = '/embedIframeJs';  // TODO add as params?

    if ($playlistObject) {
        parse_str(str_replace(['k_pl_0_u', 'k_pl_0_n'], ['kpl0U', 'kpl0N'], $playlistObject->executeUrl), $playlistAPI);
        $playlistAPI['kpl0Id'] = $params['id'];
        $playlistAPI = '"playlistAPI": ' . json_encode($playlistAPI);
    } else {
        $playlistAPI = '';
    }

    TikiLib::lib('header')
        ->add_jsfile_cdn("{$prefs['kaltura_kServiceUrl']}/p/{$prefs['kaltura_partnerId']}/sp/{$prefs['kaltura_partnerId']}00{$embedIframeJs}/uiconf_id/{$params['player_id']}/partner_id/{$prefs['kaltura_partnerId']}")
        ->add_jq_onready(
            "
mw.setConfig('Kaltura.LeadWithHTML5', true);

kWidget.embed({
    targetId: 'kaltura_player$instance',
    wid: '_{$prefs['kaltura_partnerId']}',
    uiconf_id: '{$params['player_id']}',
    entry_id: '{$params['id']}',
    flashvars: { // flashvars allows you to set runtime uiVar configuration overrides.
        $playlistAPI
    },
    params: { // params allows you to set flash embed params such as wmode, allowFullScreen etc
        wmode: 'transparent'
    }
});"
        );
    return "
<div style=\"$containerStyle\">
    <div id=\"kaltura_player$instance\" style=\"$playerStyle\"></div>
</div>
";
}
