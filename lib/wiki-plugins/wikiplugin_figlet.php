<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function wikiplugin_figlet_info(): array
{
    return [
        'name' => tra('Figlet'),
        'format' => 'html',
        'documentation' => 'PluginFiglet',
        'description' => tra('Generate FIGlet text banners'),
        'prefs' => ['wikiplugin_figlet'],
        'iconname' => 'heading',
        'introduced' => 24,
        'body' => tra('Content'),
        'params' => [
            'font' => [
                'required' => true,
                'name' => tra('Font face'),
                'description' => tra('Path to "fif" font file. Find more fonts here http://www.figlet.org/fontdb.cgi'),
                'filter' => 'text',
            ],
            'width' => [
                'required' => false,
                'name' => tra('Output width'),
                'description' => tra('Defines the maximum width of the output string in characters.'),
                'filter' => 'int',
                'default' => 500,
            ]
        ]
    ];
}

/**
 * @param string $data
 * @param array  $params
 *
 * @return string html
 * @throws Exception
 */
function wikiplugin_figlet(string $data, array $params): string | WikiParser_PluginOutput
{
    if (empty($data)) {
        return '';
    }
    $fontPath = $params['font'];
    $width = $params['width'];

    if (! is_file($fontPath)) {
        return WikiParser_PluginOutput::error(tr('Error'), tr('%0 is not a file', $fontPath));
    }
    $uniqueId = 'elem_' . uniqid();
    $jsData = json_encode($data);
    $jsFontPath = json_encode($fontPath);
    static $figlet_module_imported = false;
    $script = '';
    if (! $figlet_module_imported) {
        $script .= <<<JS_IMPORT
import { generateFiglet } from "@tiki-figlet";
JS_IMPORT;
        $figlet_module_imported = true;
    }
    $script .= <<<JS_CALL
generateFiglet($jsFontPath, "$width", "$uniqueId", $jsData);
JS_CALL;
    TikiLib::lib('header')->add_js_module($script);
    return "<pre id='$uniqueId'></pre>";
}
