<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function wikiplugin_coil_info()
{
    return [
        'name' => tra('Coil'),
        'description' => tra('Includes coil web monetization'),
        'prefs' => ['webmonetization_enabled', 'wikiplugin_coil'],
        'iconname' => 'money',
        'params' => [
            'user' => [
                'name' => tra('User'),
                'description' => tra('Tiki Wiki Username'),
                'filter' => 'username',
                'default' => '',
                'required' => false,
            ],
        ],
    ];
}

function wikiplugin_coil($data, $params)
{
    global $prefs;

    $pointer = $prefs['webmonetization_default_payment_pointer'] ?: '';
    $paywall = $prefs['webmonetization_default_paywall_text'] ?? '';
    $alwaysDefaultPointer = $prefs['webmonetization_always_default'];

    $user = $params['user'];

    if ($alwaysDefaultPointer !== 'y' && $user && TikiLib::lib('user')->get_user_id($user) > 0) {
        $tikilib = TikiLib::lib('tiki');
        $userPointer = $tikilib->get_user_preference($user, 'webmonetization_payment_pointer');
        $userPaywall = $tikilib->get_user_preference($user, 'webmonetization_paywall_text');
    }

    $headerLib = TikiLib::lib('header');
    if (! empty($headerLib->metatags['monetization']) && $pointer !== $headerLib->metatags['monetization']) {
        $pointer = $headerLib->metatags['monetization'];
    }

    $pointer = ! empty($userPointer) ? $userPointer : $pointer;
    $paywall = ! empty($userPaywall) ? $userPaywall : $paywall;

    $pos = strpos($data, '{ELSE}');
    if ($pos !== false) {
        $paywall = substr($data, $pos + 6);
        $data = substr($data, 0, $pos);
    }

    if ($pointer) {
        $headerLib->add_meta('monetization', $pointer);
        $headerLib->add_jsfile('lib/jquery_tiki/wikiplugin-coil.js');
    }

    // Ensure that content is only displayed when webmonetization is valid
    $monetizing = isset($_REQUEST['getcoildata']) && $pointer;

    $smarty = TikiLib::lib('smarty');
    $smarty->assign('dataPayed', $monetizing ? $data : '');
    $smarty->assign('dataPaywall', $paywall);

    return $smarty->fetch('wiki-plugins/wikiplugin_coil.tpl');
}
