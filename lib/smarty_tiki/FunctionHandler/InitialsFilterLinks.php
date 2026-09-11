<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\FunctionHandler;

use Smarty\FunctionHandler\Base;
use Smarty\Template;
use SmartyTiki\TikiSmartyExtensionInterface;

class InitialsFilterLinks extends Base implements TikiSmartyExtensionInterface
{
    public static function getSmartyName(): string
    {
        return 'initials_filter_links';
    }

    public function handle($params, Template $template)
    {
        $html = '';
        $sep = ' . ';
        $default_type = 'absolute_path';
        if (! isset($params['_initial'])) {
            $params['_initial'] = 'initial';
        }
        $current_initial = $_REQUEST[$params['_initial']] ?? '';
        if (! isset($params['_htmlelement'])) {
            $params['_htmlelement'] = 'tiki-center';
        }
        if (! isset($params['_template'])) {
            $params['_template'] = basename($_SERVER['PHP_SELF'], '.php') . '.tpl';
        }
        if (! isset($params['_class'])) {
            $params['_class'] = 'prevnext';
        }

        // Include smarty functions used below
        $smarty = \TikiLib::lib('smarty');

        $repeat = false;
        $tag_start = "\n" . '<a class="' . $params['_class'] . '" ' . \SmartyTiki\BlockHandler\AjaxHref::render(
            ['template' => $params['_template'], 'htmlelement' => $params['_htmlelement']],
            \SmartyTiki\FunctionHandler\Query::render(
                [
                    '_type' => $default_type,
                    $params['_initial'] => 'X',
                    'offset' => 'NULL',
                    'reloff' => 'NULL'
                ],
                $template
            ),
            $template,
            $repeat
        ) . '>';

        $alpha = explode(',', tra('a,b,c,d,e,f,g,h,i,j,k,l,m,n,o,p,q,r,s,t,u,v,w,x,y,z'));
        foreach ($alpha as $i) {
            if ($current_initial == $i) {
                $html .= "\n" . '<span class="highlight">' . strtoupper($i) . '</span>' . $sep;
            } else {
                $html .= "\n" . str_replace($params['_initial'] . '=X', $params['_initial'] . '=' . $i, $tag_start) . strtoupper($i) . '</a>' . $sep;
            }
        }
        $html .= "\n" . str_replace($params['_initial'] . '=X', $params['_initial'] . '=', $tag_start) . tra('All') . '</a>';

        return '<div class="alphafilter">' . $html . '</div>';
    }
}
