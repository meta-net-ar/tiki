<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\BlockHandler;

use Smarty\BlockHandler\Base;
use Smarty\Template;

class ItemField extends Base
{
    public function handle($params, $content, Template $template, &$repeat)
    {
        include_once('lib/wiki-plugins/wikiplugin_trackeritemfield.php');
        if (! $repeat) { // only on closing tag
            if (($res = wikiplugin_trackeritemfield($content, $params)) !== false) {
                if (is_a($res, 'WikiParser_PluginOutput')) {
                    $res = $res->toHtml();
                }
                echo $res;
            }
        }
    }
}
