<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
use Tiki\Package\VendorHelper;

//this script may only be included - so its better to die if called directly.
if (str_contains($_SERVER['SCRIPT_NAME'], basename(__FILE__))) {
    header('location: index.php');
    exit;
}

/**
 * @return array
 */
function module_recordrtc_info()
{
    return [
        'name' => tra('Record RTC'),
        'description' => tra('Capture audio and video in real-time for seamless collaboration'),
        'prefs' => ['fgal_use_record_rtc_screen'],
        'params' => [
            'gallery_id' => [
                'name' => tra('Gallery Id'),
                'description' => tra('Id of the gallery to be used when saving recordings'),
                'filter' => 'int',
                'default' => '',
            ],
        ]
    ];
}

function module_recordrtc_recording_types(): array
{
    return [
        'screen'            => tr('Screen'),
        'microphone'        => tr('Microphone'),
        'screen,microphone' => tr('Screen and microphone'),
        'camera,microphone' => tr('Camera and microphone'),
    ];
}

/**
 * @param $mod_reference
 * @param $smod_params
 */
function module_recordrtc($mod_reference, &$module_params)    // modifies $smod_params so uses & reference
{
    $smarty = TikiLib::lib('smarty');
    global $prefs, $user;

    $info = module_recordrtc_info();
    $defaults = [];
    foreach ($info['params'] as $key => $param) {
        $defaults[$key] = $param['default'];
    }

    $module_params = array_merge($defaults, array_filter($module_params));

    $smarty->assign('show_recordrtc_module', true);
    if (
        ! isset($user) ||
        // getDisplayMedia is not supported on mobile devices (check https://caniuse.com/?search=getDisplayMedia)
        (isset($_SERVER['HTTP_USER_AGENT']) && preg_match("/(iphone|ipod|ipad|android|blackberry|webos|opera mini)/i", $_SERVER['HTTP_USER_AGENT']))
    ) {
        $smarty->assign('show_recordrtc_module', false);
        return;
    }

    $https = TikiLib::httpScheme() === 'https';
    if (! $https) {
        $smarty->assign('module_error', tra('Record RTC requires https connection over SSL'));
        return;
    }

    $recordRtcService = new Services_RecordRtc_Controller();
    $recordRtcService->setUp();

    if ($prefs['fgal_use_record_rtc_screen'] !== 'y') {
        $smarty->assign('module_error', tra('Record RTC is not available.'));
        return;
    }

    $recordingTypes = module_recordrtc_recording_types();
    $smarty->assign('mod_recordrtc_recording_types', $recordingTypes);
    $smarty->assign('module_error', '');
}
