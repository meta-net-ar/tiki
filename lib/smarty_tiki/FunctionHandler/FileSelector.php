<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\FunctionHandler;

use Smarty\FunctionHandler\Base;
use Smarty\Template;

class FileSelector extends Base
{
    public function handle($params, Template $template)
    {
        static $uniqid = 0;

        $arguments = [
            'name' => null,
            'value' => null,
            'limit' => 1,
            'type' => null,
            'galleryId' => 0,
        ];

        $input = new \JitFilter(array_merge($arguments, $params));
        $input->replaceFilter('value', 'int');
        $smarty = \TikiLib::lib('smarty');

        $smarty->assign('file_selector', [
            'name' => $input->name->text(),
            'value' => array_filter($input->asArray('value', ',')),
            'limit' => $input->limit->digits() ?: 1,
            'type' => $input->type->text(),
            'galleryId' => $input->galleryId->int(),
        ]);
        return $smarty->fetch('file_selector.tpl');
    }
}
