<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\FunctionHandler;

use Smarty\FunctionHandler\Base;
use Smarty\Template;

class Service extends Base
{
    public function handle($params, Template $template)
    {
        $servicelib = \TikiLib::lib('service');
        $smartyTikiExtension = new \SmartyTiki\Extension\SmartyTikiExtension();

        if (!isset($params['controller'])) {
            return 'missing-controller';
        }

        if (isset($params['_params'])) {
            $params += $params['_params'];
            unset($params['_params']);
        }

        $url = $servicelib->getUrl($params);
        return $smartyTikiExtension->smarty_modifier_escape($url);
    }
}
