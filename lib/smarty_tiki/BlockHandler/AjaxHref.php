<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\BlockHandler;

use Smarty\BlockHandler\Base;
use Smarty\Template;

/**
 * smarty block ajax_href creates the href for a link in Smarty according to AJAX prefs
 *
 * Params:
 *
 *  _onclick    -   extra JS to run first onclick
 *
 */
class AjaxHref extends Base
{
    public function handle($params, $content, Template $template, &$repeat)
    {
        if ($repeat) {
            return;
        }

        if (! empty($params['_onclick'])) {
            $onclick = $params['_onclick'];
            if (substr($onclick, -1) != ';') {
                $onclick .= ';';
            }
        } else {
            $onclick = '';
        }

        $attributes = " href=\"" . $content . '" ';
        if (! empty($onclick)) {
            $attributes .= "onclick=\"$onclick\" ";
        }

        return $attributes;
    }
}
