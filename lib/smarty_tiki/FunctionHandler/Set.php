<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\FunctionHandler;

use Smarty\FunctionHandler\Base;
use Smarty\Template;

/** {set var=$name value=$value}
 * do the same than assign but accept a varaible as var name
 */
class Set extends Base
{
    public function handle($params, Template $template)
    {
        $smarty = \TikiLib::lib('smarty');
        $smarty->assign($params['var'], $params['value']);
    }
}
