<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\FunctionHandler;

use Smarty\FunctionHandler\Base;
use Smarty\Template;

/**
 * Smarty plugin for Tiki using jQuery ClueTip instead of OverLib
 */

/**
 * Smarty {popup} function plugin
 *
 * Type:     function<br>
 * Name:     popup<br>
 * Purpose:  make text pop up in windows via ClueTip
 * @link     not very relevant anymore http://www.smarty.net/docsv2/fr/language.function.popup.tpl {popup}
 *           (Smarty 2 online manual)
 * @author   Jonny Bradley, replacing Smarty 2's original (by Monte Ohrt <monte at ohrt dot com>)
 * @param    array
 * @param    Smarty
 * @return   string now formatted to use popover natively
 *
 * params still relevant:
 *
 *     text        Required: the text/html to display in the popup window
 *     trigger     'onClick' and native bootstrap params: 'click', 'hover', 'focus', 'manual' ('hover' default)
 *     sticky      false/true - this is currently an alias for trigger['click'] which is wrong.
 *                              Sticky should define whether the popup should stay until clicked, not how it is triggered.
 *     width       in pixels?
 *     fullhtml
 *     delay       number of miliseconds to delay showing or hiding of popover. If just one number, then it will apply to both
 *                 show and hide, or use "500|1000" to have a 500 ms show delay and a 1000 ms hide delay
 */
class Popup extends Base
{
    public function handle($params, Template $template)
    {
        // Defaults
        $options = [
            'data-bs-toggle' => 'popover',
            'data-bs-container' => 'body',
            'data-bs-trigger' => 'hover focus',
            'data-bs-content' => '',
        ];

        foreach ($params as $key => $value) {
            switch ($key) {
                case 'text':
                    $options['data-bs-content'] = $value;
                    break;
                case 'trigger':
                    switch ($value) {
                            // is this legacy? should not be used anywhere
                        case 'onclick':
                        case 'onClick':
                            $options['data-bs-trigger'] = 'click';
                            break;
                            // support native bootstrap params - could be moved to default but not sure whether it breaks something
                        case 'hover focus':
                        case 'focus hover':
                        case 'click':
                        case 'hover':
                        case 'focus':
                        case 'manual':
                            $options['data-bs-trigger'] = $value;
                            break;
                        default:
                            break;
                    }
                    break;
                case 'caption':
                    $options['title'] = $value;
                    break;
                case 'width':
                case 'height':
                    $options[$key] = $value;
                    break;
                case 'sticky':
                    $options['data-bs-trigger'] = 'click';
                    break;
                case 'fullhtml':
                    $options['data-bs-html'] = true;
                    break;
                case 'background':
                    if (! empty($params['width'])) {
                        if (! isset($params["height"])) {
                            $params["height"] = 300;
                        }
                        $options['data-bs-content'] = "<div style='background-image:url(" . $value . ");background-repeat:no-repeat;width:" . $params["width"] . "px;height:" . $params["height"] . "px;'>" . $options['data-bs-content'] . "</div>";
                    } else {
                        $options['data-bs-content'] = "<div style='background-image:url(" . $value . ");width:100%;height:100%;'>" . $options['data-bs-content'] . "</div>";
                    }
                    $options['data-bs-html'] = true;
                    break;
            }
        }

        if (empty($options['title']) && empty($options['data-bs-content'])) {
            trigger_error("popover: attribute 'text' or 'caption' required");
            return false;
        }


        $options['data-bs-content'] = preg_replace(['/\\\\r\n/', '/\\\\n/', '/\\\\r/', '/\\t/'], '', $options['data-bs-content']);

        $retval = '';
        foreach ($options as $k => $v) {
            $retval .= $k . '="' . (new \Laminas\Escaper\Escaper())->escapeHtmlAttr($v) . '" ';
        }

        //handle delay param here since slashes added by the above break the code
        if (! empty($params['delay'])) {
            $explode = explode('|', $params['delay']);
            if (count($explode) == 1) {
                $delay = (int) $explode[0];
            } else {
                $delay = '{"show":' . (int) $explode[0] . ', "hide":' . (int) $explode[1] . '}';
            }
            $retval .= ' data-bs-delay=\'' . $delay . '\'';
        } else {
            // add a short default open and close delay so they don't appear by accident and you can hover over the popover
            $retval .= ' data-bs-delay=\'{"show":500,"hide":250}\'';
        }

        return $retval;
    }
}
