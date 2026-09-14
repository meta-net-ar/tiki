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

class Feedback extends Base implements TikiSmartyExtensionInterface
{
    use FunctionHandlerStaticFacadeTrait;

    public static function getSmartyName(): string
    {
        return 'feedback';
    }

    public function handle($params, Template $template)
    {
        $smarty = \TikiLib::lib('smarty');
        $result = \Feedback::get();
        if (is_array($result)) {
            $smarty->assign('tikifeedback', $result);
        }
        $ret = $smarty->fetch('feedback/default.tpl');
        return $ret;
    }
}
