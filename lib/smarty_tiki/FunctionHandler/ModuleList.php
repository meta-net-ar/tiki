<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\FunctionHandler;

use Smarty\FunctionHandler\Base;
use Smarty\Template;

class ModuleList extends Base
{
    public function handle($params, Template $template)
    {
        $smarty = \TikiLib::lib('smarty');
        $moduleZones = $smarty->getTemplateVars('module_zones');

        global $prefs;
        if (empty($params['zone'])) {
            return tr("Missing %0 parameter", 'zone');
        }

        $zone = $params['zone'];

        $tag = "div";
        $class = 'modules';
        if (! empty($params['class'])) {
            $class .= ' ' . $params['class'];
            if (strpos($class, 'navbar') !== false) {
                $tag = 'nav';
            }
            if (strpos($class, 'aside') !== false) {
                $tag = 'aside';
            }
        }

        $id = $zone . '_modules';
        if (! empty($params['id'])) {
            $id = $params['id'];
        }

        $dir = '';
        if (\Language::isRTL()) {
            $dir = ' dir="rtl"';
        }

        $heading_id = $id . '_heading';
        if (! empty($params['heading'])) {
            $id = $params['heading'];
        }

        $heading_text = '';
        if (! empty($params['heading_text'])) {
            $heading_text = $params['heading_text'];
        }

        $role = '';
        if (! empty($params['role'])) {
            $role = $params['role'];
        }

        $content = '';
        $key = $zone . '_modules';

        if (isset($moduleZones[$key]) && is_array($moduleZones[$key])) {
            $content = implode(
                '',
                array_map(
                    function ($module) {
                        $devices = $module["params"]["device"] ?? null;
                        $moduleContent = (isset($module['data']) ? $module['data'] : '');
                        $device_classes = '';

                        if (isset($devices) && is_array($devices) && ! empty($devices)) {
                            $device_map = [
                                'MOBILE' => 'd-xs-none d-sm-block',
                                'TABLET' => 'd-sm-none d-md-none d-lg-block',
                                'LAPTOP' => 'd-lg-none d-xl-none d-xxl-block',
                                'DESKTOP' => 'd-xxl-none',
                                'PRINT' => 'd-print-none'
                            ];

                            $device_classes = [];

                            foreach ($device_map as $device => $class) {
                                if (! in_array($device, $devices)) {
                                    $device_classes[] = $class;
                                }
                            }

                            $device_classes = implode(' ', $device_classes);
                        } else {
                            $device_classes = ' d-print-block';
                        }

                        $moduleContent = preg_replace('/ class="([^"]*\bmodule\b[^"]*)"/', ' class="$1 ' . $device_classes . '"', $moduleContent);
                        return $moduleContent;
                    },
                    $moduleZones[$key]
                )
            );
        }

        return <<<OUT
    <$tag class="$class" id="$id"$dir aria-labelledby="$heading_id" role="$role">
        <h2 class="visually-hidden-focusable" id="$heading_id">$heading_text</h2>
        $content
    </$tag>
    OUT;
    }
}
