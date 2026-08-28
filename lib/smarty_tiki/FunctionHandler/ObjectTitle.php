<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\FunctionHandler;

use Smarty\FunctionHandler\Base;
use Smarty\Template;
use SmartyTiki\TikiSmartyExtensionInterface;

class ObjectTitle extends Base implements TikiSmartyExtensionInterface
{
    public static function getSmartyName(): string
    {
        return 'object_title';
    }

    public function handle($params, Template $template)
    {
        if (! isset($params['type'], $params['id']) && ! isset($params['identifier'])) {
            return tra('No object information provided.');
        }

        if (isset($params['type'], $params['id'])) {
            $type = $params['type'];
            $object = $params['id'];
            if (str_ends_with($type, 'comment')) {
                $type = substr($type, 0, strlen($type) - 8);
                $info = \TikiLib::lib('comments')->get_comment((int)$object);
                $object = $info['object'];
            }
        } else {
            list($type, $object) = explode(':', $params['identifier'], 2);
        }

        return \SmartyTiki\Modifier\Escape::apply(\TikiLib::lib('object')->get_title($type, $object));
    }
}
