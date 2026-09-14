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

class ModuleList extends Base implements TikiSmartyExtensionInterface
{
    use FunctionHandlerStaticFacadeTrait;

    public static function getSmartyName(): string
    {
        return 'modulelist';
    }

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
            if (str_contains($class, 'navbar')) {
                $tag = 'nav';
            }
            if (str_contains($class, 'aside')) {
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

        $legacy_to_new = [
            'MOBILE'  => ['SHOW_XS'],
            'TABLET'  => ['SHOW_SM', 'SHOW_MD'],
            'LAPTOP'  => ['SHOW_LG', 'SHOW_XL'],
            'DESKTOP' => ['SHOW_XXL'],
            'PRINT'   => ['SHOW_PRINT'],
        ];

        if (isset($moduleZones[$key]) && is_array($moduleZones[$key])) {
            $content = implode(
                '',
                array_map(
                    function ($module) use ($legacy_to_new) {
                        $devices = $module["params"]["device"] ?? null;
                        $moduleContent = ($module['data'] ?? '');
                        $device_classes = '';

                        if (isset($devices) && is_array($devices) && ! empty($devices)) {
                            $device_map = [
                                'HIDE_ALL'    => 'd-none',
                                'HIDE_XS'     => 'd-none d-sm-block',
                                'HIDE_SM'     => 'd-sm-none d-md-block',
                                'HIDE_MD'     => 'd-md-none d-lg-block',
                                'HIDE_LG'     => 'd-lg-none d-xl-block',
                                'HIDE_XL'     => 'd-xl-none d-xxl-block',
                                'HIDE_XXL'    => 'd-xxl-none',
                                'SHOW_ALL'    => 'd-block',
                                'SHOW_XS'     => 'd-block d-sm-none',
                                'SHOW_SM'     => 'd-none d-sm-block d-md-none',
                                'SHOW_MD'     => 'd-none d-md-block d-lg-none',
                                'SHOW_LG'     => 'd-none d-lg-block d-xl-none',
                                'SHOW_XL'     => 'd-none d-xl-block d-xxl-none',
                                'SHOW_XXL'    => 'd-none d-xxl-block',
                                'SHOW_PRINT'  => 'd-none d-print-block',
                                'SHOW_SCREEN' => 'd-print-none',
                            ];

                            $device_classes = [];

                            foreach ($devices as $device) {
                                if (isset($legacy_to_new[$device])) {
                                    foreach ($legacy_to_new[$device] as $new_device) {
                                        if (isset($device_map[$new_device])) {
                                            $device_classes[] = $device_map[$new_device];
                                        }
                                    }
                                } elseif (isset($device_map[$device])) {
                                    $device_classes[] = $device_map[$device];
                                }
                            }

                            $device_classes = implode(' ', $device_classes);
                        } else {
                            $device_classes = 'd-flex';
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
