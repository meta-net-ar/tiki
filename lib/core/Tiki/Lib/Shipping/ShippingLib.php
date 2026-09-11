<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Shipping;

use Feedback;
use Tiki\Lib\Shipping\Provider\ShippingProviderInterface as ShippingProvider;
use Tiki\Lib\Shipping\Provider\Fedex;
use Tiki\Lib\Shipping\Provider\Ups;

class ShippingLib
{
    private $providers = [];
    private $formats = [
        '/^[A-Z][0-9][A-Z]\s?[0-9][A-Z][0-9]$/' => 'CA',
        '/^[0-9]{5}$/' => 'US',
    ];

    public function __construct()
    {
        global $prefs;

        if (! empty($prefs['shipping_fedex_enable']) && $prefs['shipping_fedex_enable'] === 'y') {
            $this->addProvider(
                new Fedex([
                    'key' => $prefs['shipping_fedex_key'],
                    'password' => $prefs['shipping_fedex_password'],
                    'meter' => $prefs['shipping_fedex_meter'],
                ])
            );
        }

        if (! empty($prefs['shipping_ups_enable']) && $prefs['shipping_ups_enable'] === 'y') {
            $this->addProvider(
                new Ups([
                    'username' => $prefs['shipping_ups_username'],
                    'password' => $prefs['shipping_ups_password'],
                    'license' => $prefs['shipping_ups_license'],
                ])
            );
        }

        if (! empty($prefs['shipping_custom_provider'])) {
            $customProvider = self::getCustomShippingProvider($prefs['shipping_custom_provider']);
            if ($customProvider !== null) {
                $this->addProvider($customProvider);
            }
        }
    }

    public function addProvider(ShippingProvider $provider)
    {
        $this->providers[] = $provider;
    }

    public function getRates(array $from, array $to, array $packages)
    {
        $rates = [];

        $from = $this->completeAddressInformation($from);
        $to = $this->completeAddressInformation($to);

        $packages = $this->expandPackages($packages);

        foreach ($this->providers as $provider) {
            $rates = array_merge($rates, $provider->getRates($from, $to, $packages));
        }

        return $rates;
    }

    private function completeAddressInformation($address)
    {
        if (isset($address['zip'])) {
            $address['zip'] = strtoupper($address['zip']);
        }

        if (! isset($address['country'])) {
            foreach ($this->formats as $pattern => $country) {
                if (preg_match($pattern, $address['zip'])) {
                    $address['country'] = $country;
                    break;
                }
            }
        }

        return $address;
    }

    private function expandPackages($packages)
    {
        $out = [];

        foreach ($packages as $package) {
            if (isset($package['count'])) {
                $c = $package['count'];
                unset($package['count']);
            } else {
                $c = 1;
            }

            for ($i = 0; $c > $i; ++$i) {
                $out[] = $package;
            }
        }

        return $out;
    }

    public static function getCustomShippingProvider($name)
    {
        $file = __DIR__ . '/Provider/Custom/' . $name . '.php';
        $customShippingProvider = ucfirst($name);
        $class = "\\Tiki\\Lib\\Shipping\\Provider\\Custom\\$customShippingProvider";

        if (! is_readable($file)) {
            Feedback::error(tr('Custom Shipping Provider file "%0" does not exist.', $file));
            return null;
        }

        if (! class_exists($class)) {
            Feedback::error(tr('Custom Shipping Provider "%0" does not exist.', $name));
            return null;
        }

        if (! method_exists($class, 'getName')) {
            Feedback::error(tr('Custom Shipping Provider "%0" does not contains getName() method.', $name));
            return null;
        }

        $provider = new $class();

        return $provider;
    }
}
