<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

use Tiki\Lib\Shipping\ShippingLib;

function prefs_shipping_list()
{
    $all = glob('lib/Shipping/Provider/Custom/*.php');

    $custom_providers = [ '' => tra('None')];

    foreach ($all as $file) {
        if ($file === "lib/Shipping/Provider/Custom/index.php") {
            continue;
        }
        $name = basename($file, '.php');
        $provider = ShippingLib::getCustomShippingProvider($name);
        $custom_providers[$name] = $provider->getName();
    }

    $shippingHelp = 'Shipping';

    return [
        'shipping_service' => [
            'name' => tra('Shipping service'),
            'description' => tra('Expose a JSON shipping rate estimation service. Accounts from providers may be required (FedEx, UPS, ...).'),
            'type' => 'flag',
            'help' => $shippingHelp,
            'default' => 'n',
        ],
        'shipping_fedex_enable' => [
            'name' => tra('FedEx API'),
            'description' => tra('Enable shipping rate calculation through FedEx APIs'),
            'type' => 'flag',
            'help' => $shippingHelp,
            'default' => 'n',
        ],
        'shipping_fedex_key' => [
            'name' => tra('FedEx key'),
            'description' => tra('Developer key'),
            'type' => 'text',
            'size' => 16,
            'filter' => 'alnum',
            'default' => '',
            'help' => $shippingHelp,
        ],
        'shipping_fedex_password' => [
            'name' => tra('FedEx password'),
            'description' => tra('Developer password for FedEx Web Services authentication.'),
            'type' => 'text',
            'size' => 25,
            'filter' => 'rawhtml_unsafe',
            'default' => '',
            'help' => $shippingHelp,
        ],
        'shipping_fedex_meter' => [
            'name' => tra('FedEx meter number'),
            'description' => tra('FedEx Web Services meter number assigned to your developer account.'),
            'type' => 'text',
            'size' => 10,
            'filter' => 'digits',
            'default' => '',
            'help' => $shippingHelp,
        ],
        'shipping_fedex_account' => [
            'name' => tra('FedEx account number'),
            'description' => tra('FedEx shipping account number sent in rate requests to identify the billing account.'),
            'type' => 'text',
            'size' => 10,
            'filter' => 'digits',
            'default' => '',
            'help' => $shippingHelp,
        ],
        'shipping_ups_enable' => [
            'name' => tra('UPS API'),
            'description' => tra('Enable shipping rate calculation using the UPS carrier.'),
            'type' => 'flag',
            'help' => $shippingHelp,
            'default' => 'n',
        ],
        'shipping_ups_username' => [
            'name' => tra('UPS username'),
            'description' => tra('UPS credentials'),
            'type' => 'text',
            'size' => 15,
            'default' => '',
            'help' => $shippingHelp,
        ],
        'shipping_ups_password' => [
            'name' => tra('UPS password'),
            'description' => tra('UPS credentials'),
            'type' => 'text',
            'size' => 25,
            'default' => '',
            'help' => $shippingHelp,
        ],
        'shipping_ups_license' => [
            'name' => tra('UPS access key'),
            'description' => tra('UPS Rating API access license number used with username and password for authentication.'),
            'type' => 'text',
            'size' => 25,
            'default' => '',
            'help' => $shippingHelp,
        ],
        'shipping_custom_provider' => [
            'name' => tra('Custom shipping provider'),
            'description' => tra('Select a custom rate provider from lib/Shipping/Provider/Custom/ to include in the shipping rate service.'),
            'type' => 'list',
            'size' => 25,
            'default' => '',
            'options' => $custom_providers,
            'dependencies' => ['shipping_service'],
            'help' => $shippingHelp,
        ],
    ];
}
