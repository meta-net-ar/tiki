<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Lib\GraphEngine;

class FakeGRenderer extends GRenderer // {{{1
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
