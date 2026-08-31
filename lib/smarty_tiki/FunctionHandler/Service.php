<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\FunctionHandler;

use Smarty\FunctionHandler\Base;
use Smarty\Template;
use SmartyTiki\TikiSmartyExtensionInterface;
use SmartyTiki\Traits\FunctionHandlerStaticFacadeTrait;

class Service extends Base implements TikiSmartyExtensionInterface
{
    use FunctionHandlerStaticFacadeTrait;

    public static function getSmartyName(): string
    {
        return 'service';
    }

    public function handle($params, Template $template)
    {
        $servicelib = \TikiLib::lib('service');

        if (! isset($params['controller'])) {
            return 'missing-controller';
        }

        if (isset($params['_params'])) {
            $params += $params['_params'];
            unset($params['_params']);
        }

        if (isset($params['external']) && $params['external']) {
            \TikiLib::setExternalContext(true);
        }

        $url = $servicelib->getUrl($params);
        return \SmartyTiki\Modifier\Escape::apply($url);
    }
}
