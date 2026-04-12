<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function wikiplugin_font_getfontoptions()
{
    global $prefs;

    $names = preg_split('/;/', $prefs['wysiwyg_fonts']);
    $fonts = [];
    $fonts[] = ['text' => '', 'value' => ''];

    foreach ($names as $n) {
        $fonts[] = ['text' => $n, 'value' => $n];
    }
    return $fonts;
}


/*
 * Note:
 *
 * This plugin is needed to save font definitions when the editor is switched:
 * - size unit is 'px' (compatible with the CKE)
 * - fonts are embedded in <span> (compatible with the CKE)
  */
function wikiplugin_font_info()
{
    // Enqueue jsfontpicker assets for the plugin parameter UI
    $initScript = <<<'JS'
        import initFontPicker from "@jquery-tiki/tiki-fontpicker_init";
        initFontPicker(".tiki-fontpicker");
JS;
    TikiLib::lib('header')
        ->add_cssfile('lib/jsfontpicker/fontpicker.css')
        ->add_js_module($initScript);

    return [
        'searchable_by_default' => true,
        'name' => tra('Font'),
        'format' => 'wiki',
        'documentation' => 'PluginFont',
        'description' => tra('Format the font type and size of text'),
        'prefs' => ['wikiplugin_font'],
        'body' => tra('Content'),
        'tags' => [ 'basic' ],
        'iconname' => 'font',
        'introduced' => 8,
        'params' => [
            'family' => [
                'required' => false,
                'name' => tra('Font Family'),
                'default' => '',
                'description' => tra('Select the font family to display the content. You can pick from available system or web fonts.'),
                'since' => '8.0',
                'filter' => 'text',
                'class' => 'tiki-fontpicker',
            ],
            'size' => [
                'required' => false,
                'name' => tra('Font Size'),
                'since' => '8.0',
                'default' => '',
                'filter' => 'text',
                'description' => tr(
                    'The size of the font. This can be pixels, percentage or ' .
                    'any other specification that is supported by the HTML/CSS ' .
                    'standard. See <a href="https://www.w3.org/TR/CSS2/fonts.html' .
                    '#propdef-font-size">here</a> for details. The "px" suffix ' .
                    'can be omitted. For instance, use <code>size=15</code> or ' .
                    '<code>size=15px</code> for a font size of 15 pixels.'
                ), // 'px' is compatible with the CKE UI
            ],
        ],
    ];
}


function wikiplugin_font($data, $params)
{
    global $prefs;

    $tag = 'span'; // fonts defined in divs are not shown in the CKE UI

    $all_fonts = preg_split('/;/', $prefs['wysiwyg_fonts']);
    foreach ($all_fonts as &$f) {
        $f = strtolower($f);
    }

    $family = $params['family'];
    $size = $params['size'];

    if ((string)(int)$size == $size and $size > 0) {
        $size .= "px";
    }

    $style  = '';
    if (! empty($family)) {
        // Sanitize and apply the selected font family
        $clean_family = htmlentities($family, ENT_QUOTES, 'UTF-8');
        $style .= "font-family: '{$clean_family}'; ";
    }
    $style .= (($params['size']) ? "font-size: $size;" : '');

    if ($style) {
        return "<$tag style=\"$style\">$data</$tag>";
    } else {
        return $data;
    }
}
