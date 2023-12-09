<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\FunctionHandler;

use Smarty\FunctionHandler\Base;
use Smarty\Template;

class CategoryName extends Base
{
    public function handle($params, Template $template)
    {
        if (! isset($params['id'])) {
            trigger_error("categoryName: missing 'id' parameter");
            return;
        }

        $categlib = \TikiLib::lib('categ');
        return $categlib->get_category_name($params['id']);
    }
}
