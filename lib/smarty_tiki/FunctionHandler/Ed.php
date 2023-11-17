<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\FunctionHandler;

use Smarty\FunctionHandler\Base;
use Smarty\Template;

class Ed extends Base
{
    public function handle($params, Template $template)
    {
        global $tikilib;
        extract($params);
        // Param = zone

        if (empty($id)) {
            trigger_error("ed: missing 'id' parameter");
            return;
        }

        print($banner);
    }
}
