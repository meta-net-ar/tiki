<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/* This library is LGPL
 * written by Louis-Philippe Huberdeau
 *
 * vim: fdm=marker tabstop=4 shiftwidth=4 noet:
 *
 * This file contains the base elements of the graphic
 * rendering engine.
 */

/** Rules for implementers!
 * You must support all methods.
 * Size and locations are float values from 0 to 1
 * (0,0) is top left
 * Angles are given in degrees, are counter-clockwise and start from (1,0)
 * Pie radius size is calculated against the smallest size (width or height)
 * All styles located in testRenderer must be supported and have the expected behavior
 * Styles are implementation specific and are only generated via getStyle
 * Unknown style should return GRenderer::getStyle( $name )
 * Use VI fold markers and include the config line in the header
 * Prefix internal functions (private or protected) using an underscore
 * Text coordinates are top-left corner of the string (bottom left when vertical)
 *
 * Detailed list in GraphEngineDev
 */

class GRenderer // {{{1
{
    public function addLink($target, $left, $top, $right, $bottom, $title = null)
    {
        die("Abstract Function Call");
    }

    public function drawLine($x1, $y1, $x2, $y2, $style)
    {
        die("Abstract Function Call");
    }

    public function drawRectangle($left, $top, $right, $bottom, $style)
    {
        die("Abstract Function Call");
    }

    public function drawPie($centerX, $centerY, $radius, $begin, $end, $style)
    {
        die("Abstract Function Call");
    }

    public function drawText($text, $left, $right, $height, $style)
    {
        die("Abstract Function Call");
    }

    public function getTextWidth($text, $style)
    {
        die("Abstract Function Call");
    }

    public function getTextHeight($style)
    {
        die("Abstract Function Call");
    }

    public function getStyle($name)
    {
        return null;
    }

    public function httpOutput($filename)
    {
        die("Abstract Function Call");
    }

    public function writeToStream($stream)
    {
        die("Abstract Function Call");
    }

    public function _getRawColor($name)
    {
        switch ($name) {
            case 'red':
                return [ 'r' => 0xCC, 'g' => 0x00, 'b' => 0x00 ];
            case 'green':
                return [ 'r' => 0x00, 'g' => 0xCC, 'b' => 0x00 ];
            case 'blue':
                return [ 'r' => 0x00, 'g' => 0x00, 'b' => 0xCC ];
            case 'yellow':
                return [ 'r' => 0xFF, 'g' => 0xFF, 'b' => 0x00 ];
            case 'orange':
                return [ 'r' => 0xFF, 'g' => 0x99, 'b' => 0x00 ];
            case 'lightgreen':
                return [ 'r' => 0x99, 'g' => 0xFF, 'b' => 0x99 ];
            case 'lightblue':
                return [ 'r' => 0x66, 'g' => 0x99, 'b' => 0xFF ];

            case 'black':
                return [ 'r' => 0x00, 'g' => 0x00, 'b' => 0x00 ];
            case 'white':
                return [ 'r' => 0xFF, 'g' => 0xFF, 'b' => 0xFF ];

            case 'gray':
                return [ 'r' => 0xDD, 'g' => 0xDD, 'b' => 0xDD ];

            default:
                return [ 'r' => 0x00, 'g' => 0x00, 'b' => 0x00 ];
        }
    }
}

class Fake_GRenderer extends GRenderer // {{{1
{
    public $renderer;
    public $left;
    public $top;
    public $width;
    public $height;

    public function __construct(&$renderer, $left, $top, $right, $bottom)
    {
        $this->renderer = &$renderer;
        $this->left = $left;
        $this->top = $top;
        $this->width = $right - $left;
        $this->height = $bottom - $top;
    }

    public function addLink($target, $left, $top, $right, $bottom, $title = null)
    {
        $this->renderer->addLink(
            $target,
            $left * $this->width + $this->left,
            $top * $this->height + $this->top,
            $right * $this->width + $this->left,
            $bottom * $this->height + $this->top,
            $title
        );
    }

    public function drawLine($x1, $y1, $x2, $y2, $style)
    {
        $this->renderer->drawLine(
            $x1 * $this->width + $this->left,
            $y1 * $this->height + $this->top,
            $x2 * $this->width + $this->left,
            $y2 * $this->height + $this->top,
            $style
        );
    }

    public function drawRectangle($left, $top, $right, $bottom, $style)
    {
        $this->renderer->drawRectangle(
            $left * $this->width + $this->left,
            $top * $this->height + $this->top,
            $right * $this->width + $this->left,
            $bottom * $this->height + $this->top,
            $style
        );
    }

    public function drawPie($centerX, $centerY, $radius, $begin, $end, $style)
    {
        $this->renderer->drawPie(
            $centerX * $this->width + $this->left,
            $centerY * $this->height + $this->top,
            $radius * $this->width,
            $begin,
            $end,
            $style
        );
    }

    public function drawText($text, $left, $right, $height, $style)
    {
        $this->renderer->drawText(
            $text,
            $left * $this->width + $this->left,
            $right * $this->width + $this->left,
            $height * $this->height + $this->top,
            $style
        );
    }

    public function getTextWidth($text, $style)
    {
        // Make sure the font size does not get smaller with scale
        return $this->renderer->getTextWidth($text, $style) / $this->width;
    }

    public function getTextHeight($style)
    {
        // Make sure the font size does not get smaller with scale
        return $this->renderer->getTextHeight($style) / $this->height;
    }

    public function getStyle($name)
    {
        return $this->renderer->getStyle($name);
    }

    public function httpOutput($filename)
    {
        $this->renderer->httpOutput($filename);
    }

    public function writeToStream($stream)
    {
        $this->renderer->writeToStream($stream);
    }
}

class DataHandler
{
    /**
     * Provides means to hook into the data display sequence to add decorations
     * or external behaviour.
     *
     * Called every time a data entry is displayed by the engine. Handlers are
     * registered by Graphic::addDataHandler(). No implementations are provided
     * with the distribution.
     *
     * @param renderer      The renderer instance on which the rendering is performed.
     *                      This instance may not be cached for other purposes.
     * @param positionData  Indicates the coordinate of the data to be rendered.
     *                      Coordinates are renderer-specific. This value is provided
     *                      as an associative array. (x,y) is provided for single-point
     *                      data. (top,left,bottom,right) is provided for regions.
     * @param series        The key of the source series of the data.
     * @param entryIndex    The zero-based index of the entry in the series.
     */
    public function handle($renderer, $positionData, $series, $entryIndex)
    {
        die("Abstract Function Call");
    }
}

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
        if (is_a($handler, 'DataHandler')) {
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
        $fake_grender = new Fake_GRenderer($renderer, $left, $top, $right, $bottom);
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
                new Fake_GRenderer(
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
