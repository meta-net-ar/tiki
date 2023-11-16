<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\FunctionHandler;

use Smarty\FunctionHandler\Base;
use Smarty\Template;

// Param: 'id' or 'label'
class Content extends Base
{
    public function handle($params, Template $template)
    {
        $dcslib = \TikiLib::lib('dcs');

        if (isset($params['id'])) {
            $data = $dcslib->get_actual_content($params['id']);
        } elseif (isset($params['label'])) {
            $data = $dcslib->get_actual_content_by_label($params['label']);
        } else {
            trigger_error("assign: missing 'id' or 'label' parameter");
            return false;
        }

        return $data;
    }
}
