<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
use Tiki\Package\Extension\Utilities as PackageUtilities;
use Tiki\TikiInit;

class ServiceLib
{
    private $broker;
    private $extensionPackageBrokers = [];

    public function getBroker($ExtensionPackage = '')
    {
        if ($ExtensionPackage) {
            $utilities = new PackageUtilities();
            if (! $utilities->isInstalled(str_replace('.', '/', $ExtensionPackage))) {
                $ExtensionPackage = '';
            }
        }

        if ($ExtensionPackage && ! isset($this->extensionPackageBrokers[$ExtensionPackage])) {
            $this->extensionPackageBrokers[$ExtensionPackage] = new Services_Broker(TikiInit::getContainer(), $ExtensionPackage);
        } elseif (! $this->broker) {
            $this->broker = new Services_Broker(TikiInit::getContainer());
        }

        if ($ExtensionPackage) {
            return $this->extensionPackageBrokers[$ExtensionPackage];
        } else {
            return $this->broker;
        }
    }

    public function internal($controller, $action, $request = [], $extensionPackage = '')
    {
        return $this->getBroker($extensionPackage)->internal($controller, $action, $request);
    }

    public function render($controller, $action, $request = [], $extensionPackage = '')
    {
        return $this->getBroker($extensionPackage)->internalRender($controller, $action, $request);
    }

    public function getUrl($params)
    {
        global $prefs;
        if (is_string($params) && ! empty($params)) {
            return TikiLib::tikiUrlOpt($params);
        }

        $url = '';
        if (isset($prefs['feature_sefurl']) && $prefs['feature_sefurl'] == 'y') {
            if (! isset($params['controller'])) {
                trigger_error(
                    'getUrl() called with array missing "controller" key. Use string parameter for direct URLs. Parameters: ' . json_encode($params),
                    E_USER_NOTICE
                );
                $url = 'tiki-ajax_services.php';
            } else {
                $url = "tiki-{$params['controller']}";

                if (isset($params['action'])) {
                    $url .= "-{$params['action']}";
                } else {
                    $url .= "-x";
                }

                unset($params['controller']);
                unset($params['action']);
            }
        } else {
            $url = 'tiki-ajax_services.php';
        }

        if (! empty($params) && count($params)) {
            $url .= '?' . http_build_query($params, '', '&');
        }

        return TikiLib::tikiUrlOpt($url);
    }
}
