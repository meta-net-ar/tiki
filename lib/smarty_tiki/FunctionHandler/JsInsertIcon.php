<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\FunctionHandler;

use Smarty\FunctionHandler\Base;
use Smarty\Template;

/**
 * Function to load jQuery code to insert an iconset icon into an element
 * Useful for when there's no other way to make 3rd party code consistent with the Tiki iconsets
 *
 * type     - determines the js string that will be returned
 * iconname - set the icon to override the default
 * return   - return the js code rather than add to the header
 */
class JsInsertIcon extends Base
{
    public function handle($params, Template $template)
    {
        $smarty = \Tikilib::lib("smarty");
        if (! empty($params['type'])) {
            //set icon
            $iconmap = [
                'jscalendar' => 'calendar'
            ];
            $iconname = ! empty($params['iconname']) ? $params['iconname'] : $iconmap[$params['type']];
            $icon = smarty_function_icon(['name' => $iconname], $smarty->getEmptyInternalTemplate());
            //set js
            switch ($params['type']) {
                case 'jscalendar':
                    $js = "$('div.jscal > button.ui-datepicker-trigger').empty().append('$icon').addClass('btn btn-sm btn-link').css({'padding' : '0px', 'font-size': '16px'});";
                    break;
            }
            //load js
            if (! empty($js)) {
                if (isset($params['return']) && $params['return'] === 'y') {
                    return $js;
                } else {
                    $headerlib = \TikiLib::lib('header');
                    $headerlib->add_jq_onready($js);
                }
            }
        }
    }
}
