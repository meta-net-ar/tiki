<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\FunctionHandler;

use Smarty\FunctionHandler\Base;
use Smarty\Template;
use SmartyTiki\TikiSmartyExtensionInterface;

/**
 * Render the inputs that will allow to customize a custom route
 *
 * @param $params
 * @param $smarty
 */
class RouterParams extends Base implements TikiSmartyExtensionInterface
{
    public static function getSmartyName(): string
    {
        return 'router_params';
    }

    public function handle($params, Template $template)
    {
        if (empty($params['name'])) {
            return;
        }

        $className = 'Tiki\\CustomRoute\\Type\\' . $params['name'];

        if (! class_exists($className)) {
            return;
        }

        $routerParams = $params['params'];

        $class = new $className();
        $inputParams = $class->getParams();
        $routerName = strtolower($class->getRouteType());
        $html = '';

        foreach ($inputParams as $key => $param) {
            $escapedParam = \SmartyTiki\Modifier\Escape::apply($routerParams[$key]);
            $inputKey = $routerName . '_' . $key;

            switch ($param['type']) {
                case 'text':
                    $input = '<input type="text" id="' . $inputKey . '" class="form-control" name="' . $inputKey . '" value="' . $escapedParam . '">';
                    break;
                case 'select':
                    $input = '<select id="' . $inputKey . '" class="form-control" name="' . $inputKey . '">';

                    if (! empty($param['function'])) {
                        $args = [];
                        if ($param['args']) {
                            foreach ($param['args'] as $value) {
                                $args[] = \SmartyTiki\Modifier\Escape::apply($routerParams[$value]);
                            }
                        }

                        $objects = call_user_func_array([$className, $param['function']], $args);
                        $param['options'] = $objects;
                    }

                    if (! empty($param['options'])) {
                        foreach ($param['options'] as $optionKey => $optionValue) {
                            $selected = $optionKey == $params['params'][$key] ? 'selected' : '';
                            $input .= '<option value="' . $optionKey . '" ' . $selected . '>' . $optionValue . '</option>';
                        }
                    }

                    $input .= '</select>';
                    break;
            }

            $required = ! empty($param['required']) ? ' *' : '';

            $infoHtml = '';
            if (! empty($param['description'])) {
                $description = \SmartyTiki\Modifier\Escape::apply($param['description']);
                $icon = \SmartyTiki\FunctionHandler\Icon::render(['name' => 'information'], $template);

                $infoHtml = <<<HTML
    <a class="tikihelp text-info" title="{$param['name']}: {$description}">
        {$icon}
    </a>
    HTML;
            }

            $html .= <<<HTML
    <div class="tiki-form-group row" data-task-name="{$params['name']}" style="display:none">
        <label class="col-sm-3 col-md-2 col-form-label" for="{$inputKey}">{$param['name']}{$required}</label>
        <div class="col-sm-9 col-md-10">
            {$input}
            {$infoHtml}
        </div>
    </div>
    HTML;
        }

        echo $html;
    }
}
