<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\FunctionHandler;

use Smarty\FunctionHandler\Base;
use Smarty\Template;
use SmartyTiki\TikiSmartyExtensionInterface;
use SmartyTiki\Traits\FunctionHandlerStaticFacadeTrait;

/*
 * smarty_function_select_all: Display a checkbox that allows users with javascript to select multiple checkboxes in one click
 *
 * params:
 *  - checkbox_names: Values of the 'name' HTML attribute of the checkboxes to check/uncheck, either as an array or as a comma-separated list
 *    - label: text to display on the right side of the checkbox. If empty, no default text is displayed
 *  - hidden_too: switch the hidden checkboxes too (n/y)
 *  - tablesorter: excludes the onclick function if set to 1, since tablesorter handles select all/none functions
 *                     and having both causes conflicts that result in the unselect all to nor work in some cases.
 *                     Used 1 to enable since that is the value of {$ts.enabled} that would typically be used
 *                     to set this parameter in the relevant smarty template
 */
class SelectAll extends Base implements TikiSmartyExtensionInterface
{
    use FunctionHandlerStaticFacadeTrait;

    public static function getSmartyName(): string
    {
        return 'select_all';
    }

    public function handle($params, Template $template)
    {
        global $prefs;
        static $checkbox_count = -1;

        if (! is_array($params) || empty($params['checkbox_names'])) {
            return;
        }

        $checkbox_count++;
        if ($checkbox_count > 0) {
            $id = '_' . $checkbox_count;
        } else {
            $id = '';
        }
        $onclick = '';
        $checkbox_names = $params['checkbox_names'];
        if (! is_array($checkbox_names)) {
            $checkbox_names = explode(',', $checkbox_names);
        }
        if (isset($params['hidden_too']) && $params['hidden_too'] === 'y') {
            $hidden_too = ',true';
        } else {
            $hidden_too = '';
        }

        if (isset($params['tablesorter']) &&  $params['tablesorter'] == '1') {
            $onclick = '';
        } else {
            foreach ($checkbox_names as $cn) {
                $onclick .= "switchCheckboxes(this.form,'" . htmlspecialchars(\SmartyTiki\Modifier\Escape::apply($cn, 'javascript')) . "',this.checked$hidden_too);";
            }
            $onclick = ' onclick="' . $onclick . '"';
        }
        $names_list = is_array($checkbox_names) ? implode(',', $checkbox_names) : (string)$checkbox_names;
        return '<input name="switcher' . $id . '" id="clickall' . $id . '" class="form-check-input position-static js-select-all" type="checkbox" data-select-all-target="' . htmlspecialchars($names_list) . '" ' . $onclick .
            (empty($params['label']) ? ' aria-label="' . tra('Select All') . '"' : '') .
            '/>' . "\n" .
            (! empty($params['label']) ? '<label class="form-check-label" for="clickall' . $id . '">' . $params['label'] . "</label>\n" : '');
    }
}
