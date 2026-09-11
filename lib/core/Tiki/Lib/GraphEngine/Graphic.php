<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Lib\GraphEngine;

class Graphic
{
    public $legend;
    public $title;
    public $subTitle;
    public $parameters;
    public $dataHandlers;

    public function __construct()
    {
        $this->legend = [];
        $this->parameters = [];
        $this->dataHandlers = [];
    }

    public function setSubtitle($subTitle)
    {
        $this->subTitle = $subTitle;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function addLegend($color, $value, $url = null)
    {
        // $color name
        // $value label
        $this->legend[] = [ $color, $value, $url ];
    }

    public function addDataHandler($handler)
    {
        if (is_a($handler, 'Tiki\\Lib\\GraphEngine\\DataHandler')) {
            $this->dataHandlers[] = $handler;
        }
    }

    public function getRequiredSeries()
    {
        // Returns an associative array with series name as key
        // Value has to be true if the series is required.
        die("Abstract Function Call");
    }

    public function draw(&$renderer)
    {
        $top = 0;
        $left = 0;
        $bottom = 1;
        $right = 1;

        $layout = $this->_layout();

        if ($layout['title-active'] && ! empty($this->title)) {
            $top += 0.1;
            $renderer->drawText($this->title, 0, 1, 0.04, $renderer->getStyle($layout['title-font']));
        }

        if ($layout['subtitle-active'] && ! empty($this->subTitle)) {
            $top += 0.1;
            $renderer->drawText($this->subTitle, 0, 1, 0.1, $renderer->getStyle($layout['subtitle-font']));
        }

        if ($layout['legend-active'] && count($this->legend) > 0 && $layout['legend-location'] != 'static') {
            $this->_drawLegend($renderer, $left, $top, $right, $bottom, $layout);
        }

        $left += $layout['content-margin'];
        $right -= $layout['content-margin'];
        $top += $layout['content-margin'];
        $bottom -= $layout['content-margin'];
        $fake_grender = new FakeGRenderer($renderer, $left, $top, $right, $bottom);
        $this->_drawContent($fake_grender);

        if ($layout['legend-active'] && count($this->legend) > 0 && $layout['legend-location'] == 'static') {
            $this->_drawLegend($renderer, $left, $top, $right, $bottom, $layout);
        }
    }

    public function setData($data)
    {
        if (! is_array($data)) {
            return false;
        }

        foreach ($this->getRequiredSeries() as $key => $value) {
            if ($value && ( ! array_key_exists($key, $data) || ! is_array($data[$key]) )) {
                return false;
            }
        }

        foreach ($data as $key => $values) {
            if (! is_array($values)) {
                return false;
            }

            $data[$key] = array_values($values);
        }

        return $this->_handleData($data);
    }

    public function setParam($name, $value)
    {
        $this->parameters[$name] = $value;
    }

    public function _getColor()
    {
        static $index = 0;
        $colors = [
            'Red',
            'Green',
            'Blue',
            'Yellow',
            'Orange',
            'LightGreen',
            'LightBlue'
        ];

        return $colors[$index++ % count($colors)];
    }

    public function _drawLegend(&$renderer, &$left, &$top, &$right, &$bottom, $layout)
    {
        $box_size = $layout['legend-box-size'];
        $padding = $layout['legend-padding'];
        $margin = $layout['legend-margin'];

        $legend_font = $renderer->getStyle($layout['legend-font']);
        $font_height = $renderer->getTextHeight($legend_font);

        // Calculate size {{{3
        $item_size = [];
        foreach ($this->legend as $key => $value) {
            $item_size[$key] = $renderer->getTextWidth($value[1], $legend_font);
        }

        $width = 0;
        $height = 0;
        $single_height = max($font_height, $box_size);
        switch ($layout['legend-orientation']) {
            case 'horizontal':
                $height = $single_height + 2 * $padding;
                $width =
                array_sum($item_size) // text width
                + (1 + count($item_size) ) * $padding // padding between items
                + ( $box_size + $padding ) * count($item_size); // box and box padding
                break;
            case 'vertical':
                $height = $single_height * count($item_size) + (1 + count($item_size) ) * $padding;
                $width = max($item_size) + 3 * $padding + $box_size;
                break;
        }

        // Calculate position {{{3
        $x = 0;
        $y = 0;
        switch ($layout['legend-location']) {
            case 'left':
                $y = $top + ( $bottom - $top ) / 2 - $height / 2;
                $x = $left + $margin;
                $left += 2 * $margin + $width;
                break;
            case 'right':
                $y = $top + ( $bottom - $top ) / 2 - $height / 2;
                $x = $right - $margin - $width;
                $right -= 2 * $margin + $width;
                break;
            case 'bottom':
                $x = $left + ( $right - $left ) / 2 - $width / 2;
                $y = $bottom - $margin - $height;
                $bottom -= 2 * $margin + $height;
                break;
            case 'top':
                $x = $left + ( $right - $left ) / 2 - $width / 2;
                $y = $top + $margin;
                $top += 2 * $margin + $height;
                break;
            case 'static':
                switch ($layout['legend-location-rel']) {
                    case 'top-left':
                        $x = $layout['legend-location-x'] + $margin;
                        $y = $layout['legend-location-y'] + $margin;
                        break;
                    case 'top-right':
                        $x = $layout['legend-location-x'] - $width - $margin;
                        $y = $layout['legend-location-y'] + $margin;
                        break;
                    case 'bottom-left':
                        $x = $layout['legend-location-x'] + $margin;
                        $y = $layout['legend-location-y'] - $height - $margin;
                        break;
                    case 'bottom-right':
                        $x = $layout['legend-location-x'] - $width - $margin;
                        $y = $layout['legend-location-y'] - $height - $margin;
                        break;
                    case 'center':
                        $x = $layout['legend-location-x'] - $width / 2;
                        $y = $layout['legend-location-y'] - $height / 2;
                        break;
                }
                break;
        }

        // Draw the thing {{{3
        $renderer->drawRectangle($x, $y, $x + $width, $y + $height, $renderer->getStyle($layout['legend-style']));
        $box_offset = 0;
        $text_offset = 0;
        if ($box_size > $font_height) {
            $text_offset = ( $box_size - $font_height ) / 2;
        } else {
            $box_offset = ( $font_height - $box_size ) / 2;
        }

        $x += $padding;
        $y += $padding;

        foreach ($this->legend as $key => $info) {
            list( $color, $value, $url ) = $info;
            $topValue = $y + $box_offset;
            $rightValue = $x + $box_size;
            $bottomValue = $y + $box_size + $box_offset;
            $this->_drawLegendBox(
                new FakeGRenderer(
                    $renderer,
                    $x,
                    $topValue,
                    $rightValue,
                    $bottomValue
                ),
                $color
            );

            switch ($layout['legend-orientation']) {
                case 'horizontal':
                    $renderer->drawText(
                        $value,
                        $x + $box_size + $padding,
                        $x + $box_size + $padding + $item_size[$key],
                        $y + $text_offset,
                        $legend_font
                    );

                    if (! empty($url)) {
                        $renderer->addLink(
                            $url,
                            $x + $padding,
                            $y + $box_offset,
                            $x + $box_size + $padding + $item_size[$key],
                            $y + $box_offset + $box_size,
                            $value
                        );
                    }

                    $x += $box_size + $padding * 2 + $item_size[$key];

                    break;
                case 'vertical':
                    $renderer->drawText(
                        $value,
                        $x + $box_size + $padding,
                        $x - $padding + $width,
                        $y + $text_offset,
                        $legend_font
                    );

                    if (! empty($url)) {
                        $renderer->addLink(
                            $url,
                            $x,
                            $y + $box_offset,
                            $x + $width,
                            $y + $box_offset + $box_size,
                            $value
                        );
                    }

                    $y += $padding + $single_height;
                    break;
            }
        }
        // }}}3
    }

    public function _drawContent(&$renderer)
    {
        die("Abstract Function Call");
    }

    public function _drawLegendBox(&$renderer, $color)
    {
        die("Abstract Function Call");
    }

    public function _handleData($data)
    {
        die("Abstract Function Call");
    }

    public function _default()
    {
        return [
            'title-active' => true,
            'title-font' => 'Normal-Text-Center', // Any Text style name
            'subtitle-active' => true,
            'subtitle-font' => 'Normal-Text-Center',
            'legend-active' => true,
            'legend-font' => 'Large-Text', // Any Text style name
            'legend-location' => 'right', // left, right, bottom, top, static
            'legend-location-x' => 1, // static only, x location
            'legend-location-y' => 1, // static only, y location
            'legend-location-rel' => 'bottom-right', // static only, relative postion (top-left, top-right, bottom-left, bottom-right, center
            'legend-orientation' => 'vertical', // horizontal, vertical
            'legend-style' => 'FillStroke-White', // Any FillStroke style name
            'legend-box-size' => 0.03,
            'legend-margin' => 0.01,
            'legend-padding' => 0.01,
            'content-margin' => 0.02
        ];
    }

    public function _layout()
    {
        // Planning some user-preferences, until then, defaults.
        return array_merge($this->_default(), $this->parameters);
    }

    public function _notify($renderer, $positionData, $series, $index)
    {
        foreach ($this->dataHandlers as $handler) {
            $handler->handle($renderer, $positionData, $series, $index);
        }
    }
}
