<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Lib\GraphEngine;

class BarStackGraphic extends BarBasedGraphic // {{{1
{
    public function __construct() // {{{2
    {
        parent::__construct();
    }

    public function _getMinValue($type) // {{{2
    {
        switch ($type) {
            case 'dependant':
                $extremes = [];
                foreach ($this->columns as $line) {
                    $extremes[] = array_sum($line);
                }

                $min = min($extremes);
                break;
            case 'independant':
                $min = min(array_keys($this->columns));
                break;
        }

        if ($min > 0) {
            $min = 0;
        }

        return $min;
    }

    public function _getMaxValue($type) // {{{2
    {
        switch ($type) {
            case 'dependant':
                $extremes = [];
                foreach ($this->columns as $line) {
                    $extremes[] = array_sum($line);
                }

                return max($extremes);

            case 'independant':
                return max(array_keys($this->columns));
        }
    }

    public function _drawColumn(&$renderer, $values, $zero) // {{{2
    {
        $layout = $this->_layout();
        $begin = ( 1 - $layout['stack-column-width'] ) / 2;
        $end = $begin + $layout['stack-column-width'];

        $positive = 0;
        $negative = 0;
        foreach ($values as $style => $value) {
            if ($value == 0) {
                continue;
            }

            if ($value > 0) {
                $bottom = $positive;
                $positive += $value;
                $top = $positive;
            } else {
                $top = $negative;
                $negative += $value;
                $bottom = $negative;
            }

            $this->_drawBox(
                $renderer,
                $begin,
                $this->dependant->getLocation($top),
                $end,
                $this->dependant->getLocation($bottom),
                $style
            );
        }
    }

    public function _default() // {{{2
    {
        return array_merge(
            parent::_default(),
            ['stack-column-width' => 0.6]
        );
    }
}
