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

class ShowSort extends Base implements TikiSmartyExtensionInterface
{
    use FunctionHandlerStaticFacadeTrait;

    public static function getSmartyName(): string
    {
        return 'show_sort';
    }

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
            if (is_array($p)) {
                $p_list = $p;
            } else {
                $p_list = preg_split('/\s*,\s*/', $p);
            }
            foreach ($p_list as $value) {
                $prop = substr($value, 0, strrpos($value, '_'));
                $order = substr($value, strrpos($value, '_') + 1);

                if (strtolower($prop) == strtolower(trim($params['var']))) {
                    switch ($order) {
                        case 'asc':
                        case 'nasc':
                            return ' ' . \SmartyTiki\FunctionHandler\Icon::render(['name' => 'sort-up'], $template);
                        case 'desc':
                        case 'ndesc':
                            return ' ' . \SmartyTiki\FunctionHandler\Icon::render(['name' => 'sort-down'], $template);
                    }
                }
            }
        }
    }
}
