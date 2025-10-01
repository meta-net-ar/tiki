<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function wikiplugin_box_info()
{
    return [
        'searchable_by_default' => true,
        'name' => tra('Box'),
        'documentation' => 'PluginBox',
        'description' => tra('Create a formatted box with a title bar'),
        'prefs' => ['wikiplugin_box'],
        'body' => tra('text'),
        'introduced' => 1,
        'iconname' => 'box',
        'tags' => [ 'basic' ],
        'validate' => 'arguments',
        'params' => [
            'title' => [
                'required' => false,
                'safe' => true,
                'name' => tra('Box Title'),
                'description' => tra('Displayed above the content'),
                'since' => '1',
                'filter' => 'text',
                'default' => '',
            ],
            'bg' => [
                'required' => false,
                'safe' => true,
                'name' => tra('Background Color'),
                'description' => tra('As defined by CSS, name, or color hex code.'),
                'since' => '1',
                'filter' => 'text',
                'accepted' => tra('Valid CSS color name or code'),
            ],
            'width' => [
                'required' => false,
                'safe' => true,
                'name' => tra('Box Width'),
                'description' => tr('In pixels or percentage. Default value is %0.', '<code>100%</code>'),
                'since' => '1',
                'filter' => 'text',
            ],
            'align' => [
                'required' => false,
                'safe' => true,
                'name' => tra('Text Alignment'),
                'description' => tra('Aligns the text within the box (left-aligned by default)'),
                'since' => '1',
                'filter' => 'alpha',
                'default' => 'left',
                'options' => [
                    ['text' => tra('Left'), 'value' => 'left'],
                    ['text' => tra('Right'), 'value' => 'right'],
                    ['text' => tra('Center'), 'value' => 'center'],
                ],
            ],
            'float' => [
                'required' => false,
                'safe' => true,
                'name' => tra('Float Position'),
                'description' => tr(
                    'Set the alignment for the entire box. For elements with a width of less than 100%, other elements
                    will wrap around it unless the %0 parameter is appropriately set.)',
                    '<code>clear</code>'
                ),
                'since' => '1',
                'filter' => 'alpha',
                'default' => '',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Left'), 'value' => 'left'],
                    ['text' => tra('Right'), 'value' => 'right'],
                ],
            ],
            'clear' => [
                'required' => false,
                'safe' => true,
                'name' => tra('Clear'),
                'description' => tr(
                    'Text, etc. is not allowed to wrap around the box if this parameter is set to %0 (Yes)',
                    '<code>1</code>'
                ),
                'since' => '1',
                'filter' => 'digits',
                'default' => '',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 1],
                    ['text' => tra('No'), 'value' => 0]
                ],
            ],
            'class' => [
                'required' => false,
                'safe' => true,
                'name' => tra('CSS Class'),
                'description' => tra('Apply custom CSS class to the box.'),
                'since' => '1',
                'filter' => 'text',
                'default' => '',
                'accepted' => tra('Valid CSS class'),
            ],
            'style' => [
                'required' => false,
                'safe' => true,
                'name' => tra('CSS Style'),
                'description' => tra('Enter CSS styling tags for the div type used e.g. padding: 5px'),
                'since' => '13.0',
                'filter' => 'text',
                'default' => '',
            ],
            'id' => [
                'required' => false,
                'safe' => true,
                'name' => tra('ID'),
                'description' => tra('ID'),
                'since' => '1',
                'filter' => 'text',
                'default' => '',
            ],
        ],
    ];
}

function wikiplugin_box($data, $params)
{
    $parserlib = TikiLib::lib('parser');
    extract($params, EXTR_SKIP);
    $bg   = isset($bg) ? " background: $bg;" : "";
    $align = " text-align: $align;";
    $id = ! empty($id) ? " id=\"$id\" " : '';
    $class = ! empty($class) ? " $class" : ' ';
    $w = isset($width) ? " width: $width;" : "";
    $f = ($float == "left" || $float == "right") ? " float:$float;" : "";
    $c = ! empty($clear) ? " clear:both;" : "";
    $style = ! empty($style) ? "$style;" : "";

    if (empty($float)) {
        $append = "margin:0;";
    } else {
        $append = "margin:1em; margin-$float:0; $f";
    }
    $begin = "<div class='card$class' $id style='$bg $append $w $c $style $align'>";

    $isMarkdown = $parserlib->option['is_markdown'];

    if (! empty($title)) {
        if ($isMarkdown) {
            $title = $parserlib->parse_data($title);
            $title = str_replace(['<p>', '</p>'], '', $title);
        }
        $begin .= "<div class='card-header'>$title</div>";
    }
    $begin .= "<div class='card-body'" . (strlen($bg) > 0 ? " style=\"$bg\"" : "") . ">";
    $end = "</div></div>";
    // Prepend any newline char with br
    //$data = preg_replace("/\\n/", "<br />", $data);
    // Insert "\n" at data begin if absent (so start-of-line-sensitive syntaxes will be parsed OK)
    //if (substr($data, 0, 1) != "\n") $data = "\n".$data;
    //$data = TikiLib::lib('parser')->parse_data($data);
    if (! $isMarkdown) {
        $data = $parserlib->protectSpecialChars($data); //they are unprotected before calling the plugins
    }
    return $begin . $data . $end;
}
