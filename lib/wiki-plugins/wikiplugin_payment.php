<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function wikiplugin_payment_info()
{
    return [
        'name' => tra('Payment'),
        'documentaion' => 'PluginPayment',
        'description' => tra('Show the details of a payment request or invoice.'),
        'prefs' => [ 'wikiplugin_payment', 'payment_feature' ],
        'iconname' => 'money',
        'introduced' => 5,
        'params' => [
            'id' => [
                'required' => true,
                'name' => tra('Payment Request Number'),
                'description' => tra('Unique identifier of the payment request'),
                'since' => '5.0',
                'filter' => 'digits',
                'default' => '',
            ]
        ]
    ];
}

function wikiplugin_payment($data, $params)
{
    $smarty = TikiLib::lib('smarty');
    if (empty($params['id'])) {
        return WikiParser_PluginOutput::error(tr('Plugin Payment error'), tr('Param id is required'));
    }
    return '^~np~' . \SmartyTiki\FunctionHandler\Payment::render($params, $smarty->getEmptyInternalTemplate()) . '~/np~^';
}
