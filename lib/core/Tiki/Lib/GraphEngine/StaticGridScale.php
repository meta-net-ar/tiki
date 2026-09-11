<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Lib\GraphEngine;

class StaticGridScale extends GridScale
{
    public $labels;
    public $width;
    public $layers;
    public $count;

    public function __construct($type, $layout, $labels)
    {
        parent::__construct($type, $layout);
        $this->labels = $labels;
        $this->width = 1 / count($labels);
        $this->count = 0;
    }

    public function drawGrid(&$renderer)
    {
        $base = "grid-{$this->type}";

        $major = null;
        if ($this->layout["$base-major-guide"] !== false) {
            $major = $renderer->getStyle($this->layout["$base-major-guide"]);
        }

        for ($i = 0; $i < 1; $i += $this->width) {
            if (! is_null($major)) {
                $this->_drawGridLine($renderer, $i, $major);
            }
        }
    }

    public function drawScale(&$renderer)
    {
        $base = "grid-{$this->type}";

        $major_font = null;
        $major_style = null;
        if ($this->layout["$base-major-font"] !== false) {
            $major_font = $renderer->getStyle($this->layout["$base-major-font"]);
        }

        if ($this->layout["$base-major-style"] !== false) {
            $major_style = $renderer->getStyle($this->layout["$base-major-style"]);
        }

        $major_size = $this->layout["$base-major-size"];

        for ($i = 0; $i < 1; $i += $this->width) {
            if (! is_null($major_style) || ! is_null($major_font)) {
                $this->_drawGridTick($renderer, $i, $major_style, $major_font, $major_size);
            }
        }
    }

    public function _calculateSkip($size, $space)
    {
        $space = abs($space);
        $skip = 0;
        if (empty($space)) {
            return $skip;
        }
        while ($size > $space * ++$skip) {
        }

        return $skip;
    }

    public function _drawGridLine(&$renderer, $pos, $style)
    {
        if ($this->orientation == 'vertical') {
            $renderer->drawLine(0, $pos, 1, $pos, $style);
        } else {
            $renderer->drawLine($pos, 0, $pos, 1, $style);
        }
    }

    public function _drawGridTick(&$renderer, $pos, $style, $font, $size)
    {
        if ($this->orientation == 'vertical') {
            $size = $size / $renderer->width;
            $width = (1 - $size) / $this->layers;

            if ($this->layout['grid-vertical-position'] == 'left') {
                if (! is_null($style)) {
                    $renderer->drawLine(1 - $size, $pos, 1, $pos, $style);
                }
                if (! is_null($font)) {
                    $height = $renderer->getTextHeight($font);
                    $offset = ++$this->count % $this->layers * $width;
                    $value = $this->_getValue($pos);
                    $renderer->drawText($value, $offset, $offset + $width, $pos + ( $this->width - $height ) / 2, $font);
                }
            } else {
                if (! is_null($style)) {
                    $renderer->drawLine(0, $pos, $size, $pos, $style);
                }
                if (! is_null($font)) {
                    $offset = ++$this->count % $this->layers * $width + $size;
                    $height = $renderer->getTextHeight($font);
                    $value = $this->_getValue($pos);
                    $renderer->drawText($value, $offset, $offset + $width, $pos + ( $this->width - $height ) / 2, $font);
                }
            }
        } else {
            $size = $size / $renderer->height;
            if ($this->layout['grid-horizontal-position'] == 'bottom') {
                if (! is_null($style)) {
                    $renderer->drawLine($pos, 0, $pos, $size, $style);
                }
                if (! is_null($font)) {
                    $y = $size + $renderer->getTextHeight($font) * ($this->count++ % $this->layers);
                    $value = $this->_getValue($pos);
                    $width = $renderer->getTextWidth($value, $font) * 0.55;
                    $pos += $this->width / 2;
                    $renderer->drawText($value, $pos - $width, $pos + $width, $y, $font);
                }
            } else {
                if (! is_null($style)) {
                    $renderer->drawLine(1 - $pos, 1 - $size, 1 - $pos, 1, $style);
                }
                if (! is_null($font)) {
                    $y = $renderer->getTextHeight($font) * (++$this->count % $this->layers);
                    $value = $this->_getValue($pos);
                    $width = $renderer->getTextWidth($value, $font) / 2;
                    $pos += $this->width / 2;
                    $renderer->drawText($value, $pos - $width, $pos + $width, $y, $font);
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

        $max = 0;
        if ($param !== false) {
            foreach ($this->labels as $label) {
                $max = max($max, $renderer->getTextWidth($label, $font));
            }
        }

        switch ($this->orientation) {
            case 'vertical':
                $this->layers = $this->_calculateSkip($renderer->getTextHeight($font), $available / count($this->labels));
                return $max * $this->layers + $size;
            break;
            case 'horizontal':
                $this->layers = $this->_calculateSkip($max, $available / count($this->labels));
                return (($param !== false) ? $renderer->getTextHeight($font) : 0) * $this->layers + $size;
            break;
        }
    }

    public function _getValue($pos)
    {
        $index = (int)round($pos / $this->width);
        if (isset($this->labels[ $index ])) {
            return $this->labels[ $index ];
        } else {
            return null;
        }
    }

    public function getLocation($value)
    {
        return array_sum($this->getRange($value)) / 2;
    }
    public function getRange($value)
    {
        $key = array_search($value, $this->labels);
        $begin = $key * $this->width;
        $end = $begin + $this->width;

        return [ $begin, $end ];
    }
}
