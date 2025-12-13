<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function wikiplugin_chart_info()
{
    return [
        'name' => tra('Chart'),
        'documentation' => 'PluginChart',
        'description' => tra('Display a chart from TikiSheet.'),
        'prefs' => [ 'feature_sheet', 'wikiplugin_chart' ],
        'iconname' => 'chart',
        'body' => tra('Chart caption.'),
        'introduced' => 2,
        'params' => [
            'id' => [
                'required' => true,
                'name' => tra('Spreadsheet ID'),
                'description' => tra('Data sheet ID'),
                'since' => '2.0',
                'filter' => 'digits',
                'profile_reference' => 'sheet',
            ],
            'type' => [
                'required' => true,
                'name' => tra('Chart Type'),
                'description' => tra('Specify a valid chart type'),
                'accepted' => 'BarStackGraphic | MultibarGraphic | MultilineGraphic | PieChartGraphic',
                'since' => '2.0',
                'filter' => 'word',
            ],
            'width' => [
                'required' => false,
                'name' => tra('Chart Width'),
                'description' => tra('Width in pixels.'),
                'since' => '2.0',
                'filter' => 'digits',
            ],
            'height' => [
                'required' => false,
                'name' => tra('Chart Height'),
                'description' => tra('Height in pixels.'),
                'since' => '2.0',
                'filter' => 'digits',
            ],
            'value' => [
                'required' => false,
                'name' => tra('Value series'),
                'description' => tra('Required for pie charts'),
                'since' => '2.0',
                'filter' => 'text',
            ],
            'x' => [
                'required' => false,
                'name' => tra('Independent series'),
                'description' => tra('Required for types other than pie chart'),
                'since' => '2.0',
                'filter' => 'text',
            ],
            'y0' => [
                'required' => false,
                'name' => tra('Dependent series'),
                'description' => tra('Required for types other than pie chart'),
                'since' => '2.0',
                'filter' => 'text',
            ],
            'y1' => [
                'required' => false,
                'name' => tra('Dependent series'),
                'description' => tra('Description needed'),
                'since' => '2.0',
                'filter' => 'text',
            ],
            'y2' => [
                'required' => false,
                'name' => tra('Dependent series'),
                'description' => tra('Description needed'),
                'since' => '2.0',
                'filter' => 'text',
            ],
            'y3' => [
                'required' => false,
                'name' => tra('Dependent series'),
                'description' => tra('Description needed'),
                'since' => '2.0',
                'filter' => 'text',
            ],
            'y4' => [
                'required' => false,
                'name' => tra('Dependent series'),
                'description' => tra('Description needed'),
                'since' => '2.0',
                'filter' => 'text',
            ],
            'color' => [
                'required' => false,
                'name' => tra('Colors'),
                'description' => tra('List of colors to use.'),
                'since' => '2.0',
                'filter' => 'text',
            ],
            'style' => [
                'required' => false,
                'name' => tra('Styles'),
                'description' => tra('List of styles to use.'),
                'since' => '2.0',
                'filter' => 'text',
            ],
            'label' => [
                'required' => false,
                'name' => tra('Labels'),
                'description' => tra('Labels for the series or values in the legend.'),
                'since' => '2.0',
                'filter' => 'text',
            ],
        ],
    ];
}

function wikiplugin_chart($data, $params)
{
    extract($params, EXTR_SKIP);

    $params = [ "sheetId" => $id, "graphic" => $type, "title" => $data ];
    switch ($type) {
        case 'PieChartGraphic':
            if (is_null($value)) {
                return WikiParser_PluginOutput::argumentError(['value']);
            }

            $params['series[value]'] = $value;
            break;

        default:
            $params['independant'] = $independant ?? 'horizontal';
            $params['horizontal'] = $horizontal ?? 'bottom';
            $params['vertical'] = $vertical ?? 'left';

            if (is_null($x)) {
                return WikiParser_PluginOutput::argumentError(['x']);
            }

            $params['series[x]'] = $x;

            for ($i = 0; isset(${'y' . $i}); $i++) {
                $params['series[y' . $i . ']'] = ${'y' . $i};
            }

            break;
    }

    $params['series[color]'] = $color ?? '';
    $params['series[style]'] = $style ?? '';
    $params['series[label]'] = $label ?? '';

    if (function_exists('imagepng')) {
        if (is_null($width)) {
            return WikiParser_PluginOutput::argumentError(['width']);
        }
        if (is_null($height)) {
            return WikiParser_PluginOutput::argumentError(['height']);
        }

        $params['width'] = $width;
        $params['height'] = $height;

        $disp = '<img src="' . _wikiplugin_chart_uri($params, 'PNG') . '"/>';
    } elseif (function_exists('image_jpeg')) {
        if (is_null($width)) {
            return WikiParser_PluginOutput::argumentError(['width']);
        }
        if (is_null($height)) {
            return WikiParser_PluginOutput::argumentError(['height']);
        }

        $params['width'] = $width;
        $params['height'] = $height;

        $disp = '<img src="' . _wikiplugin_chart_uri($params, 'JPEG') . '"/>';
    } elseif (function_exists('pdf_new')) {
        $disp = tra("Chart as PDF");
    } elseif (function_exists('ps_new')) {
        $disp = tra("Chart as PostScript");
    } else {
        return "<b>no valid renderer for plugin</b><br />";
    }

    if (function_exists('pdf_new')) {
        $params['format'] = $format ?? 'A4';
        $params['orientation'] = $orientation ?? 'landscape';

        $disp = '<a href="' . _wikiplugin_chart_uri($params, 'PDF') . '">' . $disp . '</a>';
    } elseif (function_exists('ps_new')) {
        $params['format'] = $format ?? 'A4';
        $params['orientation'] = $orientation ?? 'landscape';

        $disp = '<a href="' . _wikiplugin_chart_uri($params, 'PS') . '">' . $disp . '</a>';
    }

    return $disp;
}

function _wikiplugin_chart_uri($params, $renderer)
{
    $params['renderer'] = $renderer;
    $array = [];
    foreach ($params as $key => $value) {
        $array[] = rawurlencode($key) . '=' . rawurlencode($value);
    }

    return 'tiki-graph_sheet.php?' . implode('&', $array);
}
