<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\BlockHandler;

use Smarty\BlockHandler\Base;
use Smarty\Template;

/**
 * Smarty plugin modules_list
 *
 * \brief smarty_block_modules_list : show unordered or ordered list or nothing if there is nothing to show
 *
 * params: list
 * params: nonums
 *
 * usage:
 * \code
 *  {modules_list list=$last_commit}
 *      {section name=ix loop=$last_commit}
 *  <li>
 *          <a class="linkmodule" href="#">{$last_commit[ix].id}</a>
 *      </li>
 *      {/section}
 *  {/modules_list}
 * \endcode
 *
 */
class ModulesList extends Base
{
    public function handle($params, $content, Template $template, &$repeat)
    {
        if ($repeat) {
            return;
        }

        if (! empty($params["list"]) && count($params["list"]) > 0) {
            if ($params["nonums"] == "y") {
                $ret = '<ul>' . $content . '</ul>';
            } else {
                $ret = '<ol>' . $content . '</ol>';
            }
        } else {
            if (empty($params['none'])) {
                $params['none'] = 'No records to display'; //get_strings tra('No records to display');
            }
            $ret = '<em>' . tra($params['none']) . '</em>';
        }
        return $ret;
    }
}
