<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Lib\GraphEngine;

class LinearGridScale extends GridScale
{
    public $min;
    public $max;

    public $majorScaleCount;
    public $majorScaleRound;
    public $minorScaleCount;
    public $minorScaleRound;

    public $zero;
    public $value;

    public $skip;
    public $count;

    public function __construct($type, $layout, $min, $max)
    {
        parent::__construct($type, $layout);
        $this->min = $min;
        $this->max = $max;
        $this->count = 0;
        $this->skip = 1;

        $this->_adjustScale();
    }

    public function _adjustScale()
    {
        $max = $this->max;
        $min = $this->min;

        $base = "grid-{$this->type}";
        $default = $this->layout["$base-linear-count"];

        $maj = ceil(( $max - $min ) / $default);
        switch (true) {
            case $max >= 0 && $min == 0:
                $this->majorScaleCount = ceil($max / $maj);
                break;
            case $max >= 0 && $min > 0:
                $this->majorScaleCount = ceil(($max - $min) / $maj);
                break;
            case $max >= 0 && $min < 0:
                $this->majorScaleCount = ceil($max / $maj) + ceil(abs($min) / $maj);
                break;
            case $max < 0:
                $this->majorScaleCount = ceil((abs($min) - abs($max)) / $maj);
                break;
        }

        $this->majorScaleRound = 0; // Need to be changed.

        $this->minorScaleCount = 5;
        while ($this->minorScaleCount > 1) {
            if (round($maj / $this->minorScaleCount) == $maj / $this->minorScaleCount) {
                break;
            } else {
                --$this->minorScaleCount;
            }
        }

        $this->minorScaleRound = 0;

        $this->zero = $this->_getZeroLocation();
        $this->value = $maj / (1 / $this->majorScaleCount);
    }

    public function drawGrid(&$renderer)
    {
        $base = "grid-{$this->type}";

        $major = null;
        $minor = null;
        if ($this->layout["$base-major-guide"] !== false) {
            $major = $renderer->getStyle($this->layout["$base-major-guide"]);
        }
        if ($this->layout["$base-minor-guide"] !== false) {
            $minor = $renderer->getStyle($this->layout["$base-minor-guide"]);
        }

        $start = $this->zero;
        if ($start != 0 && $start != 1 && $this->layout["$base-zero-style"] !== false) {
            $this->_drawGridLine($renderer, $start, $renderer->getStyle($this->layout["$base-zero-style"]));
        }

        $major_int = $this->_getMajorInterval();
        $minor_int = $this->_getMinorInterval();
        for ($i = $start; $i > 0; $i -= $major_int) {
            if (! is_null($major)) {
                $this->_drawGridLine($renderer, $i, $major);
            }

            if (! is_null($minor)) {
                for ($j = $i - $minor_int; $i - $major_int < $j && 0 < $j; $j -= $minor_int) {
                    $this->_drawGridLine($renderer, $j, $minor);
                }
            }
        }

        for ($i = $start; $i < 1; $i += $major_int) {
            if (! is_null($major)) {
                $this->_drawGridLine($renderer, $i, $major);
            }

            if (! is_null($minor)) {
                for ($j = $i + $minor_int; $i + $major_int > $j && 1 > $j; $j += $minor_int) {
                    $this->_drawGridLine($renderer, $j, $minor);
                }
            }
        }
    }

    public function drawScale(&$renderer)
    {
        $base = "grid-{$this->type}";

        $major_font = null;
        $minor_font = null;
        $major_style = null;
        $minor_style = null;
        if ($this->layout["$base-major-font"] !== false) {
            $major_font = $renderer->getStyle($this->layout["$base-major-font"]);
        }
        if ($this->layout["$base-minor-font"] !== false) {
            $minor_font = $renderer->getStyle($this->layout["$base-minor-font"]);
        }

        if ($this->layout["$base-major-style"] !== false) {
            $major_style = $renderer->getStyle($this->layout["$base-major-style"]);
        }
        if ($this->layout["$base-minor-style"] !== false) {
            $minor_style = $renderer->getStyle($this->layout["$base-minor-style"]);
        }

        $minor_size = $this->layout["$base-minor-size"];
        $major_size = $this->layout["$base-major-size"];

        $start = $this->zero;
        if ($start != 0 && $start != 1) {
            $this->_drawGridTick($renderer, $start, $major_style, $major_font, $major_size);
        }

        $major_int = $this->_getMajorInterval();
        $minor_int = $this->_getMinorInterval();

        for ($i = $start; $i > 0; $i -= $major_int) {
            if (! is_null($major_style) || ! is_null($major_font)) {
                $this->_drawGridTick($renderer, $i, $major_style, $major_font, $major_size, $this->majorScaleRound);
            }

            if (! is_null($minor_style) || ! is_null($minor_font)) {
                for ($j = $i - $minor_int; $i - $major_int < $j && 0 < $j; $j -= $minor_int) {
                    $this->_drawGridTick($renderer, $j, $minor_style, $minor_font, $minor_size, $this->minorScaleRound);
                }
            }
        }

        for ($i = $start; $i < 1; $i += $major_int) {
            if (! is_null($major_style) || ! is_null($major_font)) {
                $this->_drawGridTick($renderer, $i, $major_style, $major_font, $major_size, $this->majorScaleRound);
            }

            if (! is_null($minor_style) || ! is_null($minor_font)) {
                for ($j = $i + $minor_int; $i + $major_int > $j && 1 > $j; $j += $minor_int) {
                    $this->_drawGridTick($renderer, $j, $minor_style, $minor_font, $minor_size, $this->minorScaleRound);
                }
            }
        }
    }

    public function _drawGridLine(&$renderer, $pos, $style)
    {
        if ($this->orientation == 'vertical') {
            $renderer->drawLine(0, $pos, 1, $pos, $style);
        } else {
            $renderer->drawLine($pos, 0, $pos, 1, $style);
        }
    }

    public function _drawGridTick(&$renderer, $pos, $style, $font, $size, $round = false)
    {
        if ($this->orientation == 'vertical') {
            $size = $size / $renderer->width;
            if ($this->layout['grid-vertical-position'] == 'left') {
                if (! is_null($style)) {
                    $renderer->drawLine(1 - $size, $pos, 1, $pos, $style);
                }
                if (! is_null($font) && $this->count++ % $this->skip == 0) {
                    $height = $renderer->getTextHeight($font);
                    $value = $this->_getValue($pos, $round);
                    $renderer->drawText($value, 0, 1, $pos - $height / 2, $font);
                }
            } else {
                if (! is_null($style)) {
                    $renderer->drawLine(0, $pos, $size, $pos, $style);
                }
                if (! is_null($font) && $this->count++ % $this->skip == 0) {
                    $height = $renderer->getTextHeight($font);
                    $value = $this->_getValue($pos, $round);
                    $renderer->drawText($value, $size, 1, $pos - $height / 2, $font);
                }
            }
        } else {
            $size = $size / $renderer->height;
            if ($this->layout['grid-horizontal-position'] == 'bottom') {
                if (! is_null($style)) {
                     $renderer->drawLine($pos, 0, $pos, $size, $style);
                }
                if (! is_null($font) && $this->count++ % $this->skip == 0) {
                    $value = $this->_getValue($pos, $round);
                    $width = $renderer->getTextWidth($value, $font) * 0.55;
                    $renderer->drawText($value, $pos - $width, $pos + $width, $size, $font);
                }
            } else {
                if (! is_null($style)) {
                    $renderer->drawLine($pos, 1 - $size, $pos, 1, $style);
                }
                if (! is_null($font)) {
                    $value = $this->_getValue($pos, $round);
                    $width = $renderer->getTextWidth($value, $font) * 0.55;
                    $renderer->drawText($value, $pos - $width, $pos + $width, 0, $font);
                }
            }
        }
    }

    public function getSize(&$renderer, $available)
    {
        $param = $this->layout["grid-{$this->type}-major-font"];
        if ($param !== false) {
            $font = $renderer->getStyle($param);
        }
        $size = $this->layout["grid-{$this->type}-major-size"];
        switch ($this->orientation) {
            case 'vertical':
                $this->skip = $this->_calculateSkip($renderer->getTextHeight($font), $available / $this->majorScaleCount);
                return (($param !== false) ? $this->_getLargest($renderer, $font) : 0) + $size;
            break;
            case 'horizontal':
                $this->skip = $this->_calculateSkip($this->_getLargest($renderer, $font), $available / $this->majorScaleCount);
                return (($param !== false) ? $renderer->getTextHeight($font) : 0) + $size;
            break;
        }
    }

    public function _calculateSkip($size, $space)
    {
        $skip = 0;
        while ($size > $space * ++$skip) {
        }

        return $skip;
    }

    public function _getLargest(&$renderer, $font)
    {
        return  max(
            $renderer->getTextWidth($this->min, $font),
            $renderer->getTextWidth($this->max, $font)
        );
    }

    public function _getMajorInterval()
    {
        return 1 / $this->majorScaleCount;
    }

    public function _getMinorInterval()
    {
        return 1 / $this->majorScaleCount / $this->minorScaleCount;
    }

    public function _getZeroLocation()
    {
        $loc = $this->max / ($this->max - $this->min);

        if ($this->orientation != 'vertical') {
            $loc = 1 - $loc;
        }

        return $loc;
    }

    public function _getValue($pos, $round = false)
    {
        $zpos = $this->zero;

        if ($this->orientation == 'vertical') {
            $pos = 1 - $pos;
            $zpos = 1 - $zpos;
        }
        $v = $this->value * ($pos - $zpos);

        if ($round === false) {
            return $v;
        } else {
            return round($v, $round);
        }
    }

    public function getLocation($value)
    {
        $pos = $value / $this->value;

        if ($this->orientation == 'vertical') {
            $pos += 1 - $this->zero;
            $pos = 1 - $pos;
        } else {
            $pos += $this->zero;
        }

        return $pos;
    }
    public function getRange($value)
    {
        $width = $this->_getMinorInterval() / 2;
        $pos = $this->getLocation($value);
        $locs = [ $pos - $width, $pos + $width ];
        sort($locs);
        return $locs;
    }
}
