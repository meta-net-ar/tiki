<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Lib\GraphEngine;

class MultibarGraphic extends BarBasedGraphic // {{{1
{
    public function __construct()
    {
        parent::__construct();
    }

    public function _drawColumn(&$renderer, $values, $zero)
    {
        $layout = $this->_layout();
        $count = count($values);
        $width = $layout['multi-columns-width'] / $count;
        $pad = ( 1 - $layout['multi-columns-width'] ) / 2;

        $positions = [];
        $i = 0;

        foreach ($values as $style => $value) {
            $base = $pad + $width * $i++;

            if ($value == 0) {
                continue;
            }

            $bottom = $this->dependant->getLocation($value);
            $this->_drawBox($renderer, $base, $zero, $base + $width, $bottom, $style);
            $positions[$style] = [
                            'left' => $base,
                            'top' => $zero,
                            'right' => $base + $width,
                            'bottom' => $bottom
            ];
        }

        return $positions;
    }

    public function _default()
    {
        return array_merge(
            parent::_default(),
            ['multi-columns-width' => 0.8]
        );
    }
}
