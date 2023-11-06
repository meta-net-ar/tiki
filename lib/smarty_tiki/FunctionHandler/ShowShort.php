<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\FunctionHandler;

use Smarty\FunctionHandler\Base;
use Smarty\Template;

class ShowShort extends Base
{
    public function handle($params, Template $template)
    {
        global $url_path;
        $smarty = \TikiLib::lib('smarty');

        if (isset($_REQUEST[$params['sort']])) {
            $p = $_REQUEST[$params['sort']];
        } elseif ($s = $smarty->getTemplateVars($params['sort'])) {
            $p = $s;
        }

        if (isset($params['sort']) and isset($params['var']) and isset($p)) {
            $prop = substr($p, 0, strrpos($p, '_'));
            $order = substr($p, strrpos($p, '_') + 1);

            if (strtolower($prop) == strtolower(trim($params['var']))) {
                switch ($order) {
                    case 'asc':
                    case 'nasc':
                        return ' ' . smarty_function_icon(['name' => 'sort-up'], $template);
                    case 'desc':
                    case 'ndesc':
                        return ' ' . smarty_function_icon(['name' => 'sort-down'], $template);
                }
            }
        }
    }
}
