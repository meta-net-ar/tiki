<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

function wikiplugin_iframe_info()
{
    return [
        'name' => tra('Iframe'),
        'documentation' => 'PluginIframe',
        'description' => tra('Include the body of another web page in a scrollable frame within a page'),
        'prefs' => [ 'wikiplugin_iframe' ],
        'body' => tra('URL'),
        'format' => 'html',
        'validate' => 'all',
        'tags' => [ 'basic' ],
        'iconname' => 'copy',
        'introduced' => 3,
        'params' => [
            'name' => [
                'safe' => true,
                'required' => false,
                'name' => tra('Name'),
                'description' => tra('Name'),
                'since' => '3.0',
                'filter' => 'text',
            ],
            'title' => [
                'safe' => true,
                'required' => false,
                'name' => tra('Title'),
                'description' => tra('Frame title'),
                'since' => '3.2',
                'filter' => 'text',
            ],
            'id' => [
                'required' => false,
                'name' => tra('Id'),
                'description' => tra('HTML id for the iframe.'),
                'filter' => 'text',
                'since' => '26.1',
            ],
            'class' => [
                'required' => false,
                'name' => tra('Class'),
                'description' => tra('Class for the iframe.'),
                'filter' => 'text',
                'since' => '26.1',
            ],
            'width' => [
                'safe' => true,
                'required' => false,
                'name' => tra('Width'),
                'description' => tra('Width in pixels or %'),
                'since' => '3.0',
                'filter' => 'text',
            ],
            'height' => [
                'safe' => true,
                'required' => false,
                'name' => tra('Height'),
                'description' => tra('Pixels or %'),
                'since' => '3.0',
                'filter' => 'text',
            ],
            'align' => [
                'safe' => true,
                'required' => false,
                'name' => tra('Alignment'),
                'description' => tra('Align the iframe on the page'),
                'since' => '3.0',
                'filter' => 'word',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Top'), 'value' => 'top'],
                    ['text' => tra('Middle'), 'value' => 'middle'],
                    ['text' => tra('Bottom'), 'value' => 'bottom'],
                    ['text' => tra('Left'), 'value' => 'left'],
                    ['text' => tra('Right'), 'value' => 'right']
                ]
            ],
            'frameborder' => [
                'safe' => true,
                'required' => false,
                'name' => tra('Frame Border'),
                'description' => tra('Choose whether to show a border around the iframe'),
                'since' => '3.0',
                'filter' => 'digits',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 1],
                    ['text' => tra('No'), 'value' => 0]
                ]
            ],
            'marginheight' => [
                'safe' => true,
                'required' => false,
                'name' => tra('Margin Height'),
                'description' => tra('Margin height in pixels'),
                'since' => '3.0',
                'filter' => 'digits',
            ],
            'marginwidth' => [
                'safe' => true,
                'required' => false,
                'name' => tra('Margin Width'),
                'description' => tra('Margin width in pixels'),
                'since' => '3.0',
                'filter' => 'digits',
            ],
            'scrolling' => [
                'safe' => true,
                'required' => false,
                'name' => tra('Scrolling'),
                'description' => tra('Choose whether to add a scroll bar'),
                'since' => '3.0',
                'filter' => 'word',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'yes'],
                    ['text' => tra('No'), 'value' => 'no'],
                    ['text' => tra('Auto'), 'value' => 'auto'],
                ]
            ],
            'src' => [
                'required' => false,
                'name' => tra('URL'),
                'description' => tra('URL'),
                'filter' => 'url',
                'since' => '3.0',
            ],
            'responsive' => [
                'safe' => true,
                'required' => false,
                'name' => tra('Responsive'),
                'description' => tra('Make the display responsive so that browsers determine dimensions based on the width of their containing block by creating an intrinsic ratio that will properly scale on any device.'),
                'since' => '16.0',
                'filter' => 'word',
                'default' => '16by9',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('16 by 9'), 'value' => '16by9'],
                    ['text' => tra('4 by 3'), 'value' => '4by3'],
                    ['text' => tra('no'), 'value' => 'no'],
                ]
            ],
        ],
    ];
}

function wikiplugin_iframe($data, $params)
{
    if (is_null($params['src']) && ! empty($data)) {
        $params['src'] = $data;
    }

    return renderIframe($params);
}

// This function is used by the pluginAjaxLoad and pluginIFrame to render the iframe
function renderIframe($params)
{
    extract($params, EXTR_SKIP);
    if ($responsive != 'no' and $responsive != 'n') {
        if ($responsive == '4by3') {
            $ret = '<div class="embed-responsive embed-responsive-4by3"><iframe class="embed-responsive-item ';
        } else {
            $ret = '<div class="embed-responsive embed-responsive-16by9"><iframe class="embed-responsive-item ';
        }

        if (! is_null($class)) {
            $ret .= $class . '"';
        } else {
            $ret .= '"';
        }
    } else {
        $ret = '<iframe ';
        if (! is_null($class)) {
            $ret .= " class=\"$class\"";
        }
    }

    if (! is_null($id)) {
        $ret .= " id=\"$id\"";
    }
    if (! is_null($name)) {
        $ret .= " name=\"$name\"";
    }
    if (! is_null($title)) {
        $ret .= " title=\"$title\"";
    }
    if (! is_null($width)) {
        $ret .= " width=\"$width\"";
    }
    if (! is_null($height)) {
        $ret .= " height=\"$height\"";
    }
    if (! is_null($align)) {
        $ret .= " align=\"$align\"";
    }
    if (! is_null($frameborder)) {
        $ret .= " frameborder=\"$frameborder\"";
    }
    if (! is_null($marginheight)) {
        $ret .= " marginheight=\"$marginheight\"";
    }
    if (! is_null($marginwidth)) {
        $ret .= " marginwidth=\"$marginwidth\"";
    }
    if (! is_null($scrolling)) {
        $ret .= " scrolling=\"$scrolling\"";
    }
    if (! is_null($src)) {
        $ret .= " src=\"$src\"";
    }
    if ($responsive != 'no' and $responsive != 'n') {
        $ret .= "></iframe></div>";
    } else {
        $ret .= "></iframe>";
    }
    return $ret;
}
