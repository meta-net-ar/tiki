<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\GraphEngine;

class BarBasedGraphic extends GridBasedGraphic // {{{1
{
    public $columns;
    public $styleMap;
    public $columnMap;

    public function __construct() // {{{2
    {
        parent::__construct();
        $this->columns = [];
        $this->styleMap = [];
        $this->columnMap = [];
    }

    public function getRequiredSeries() // {{{2
    {
        return [
                        'label' => false,
                        'color' => false,
                        'style' => false,
                        'x' => true,
                        'y0' => true
        ];
    }

    public function _getMinValue($type) // {{{2
    {
        switch ($type) {
            case 'dependant':
                $extremes = [];
                foreach ($this->columns as $line) {
                    $extremes[] = min($line);
                }

                $min = min($extremes);
                break;
            case 'independant':
                $min = min(array_keys($this->columns));
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
                    $extremes[] = max($line);
                }

                return max($extremes);
            case 'independant':
                return max(array_keys($this->columns));
        }
    }

    public function _getLabels($type) // {{{2
    {
        switch ($type) {
            case 'dependant':
                return [];
            case 'independant':
                return array_keys($this->columns);
        }
    }

    public function _handleData($data) // {{{2
    {
        $columns = [];

        for ($i = 0; isset($data['y' . $i]); ++$i) {
            $columns[] = $data['y' . $i];
        }

        $count = count($columns);

        if (! isset($data['color'])) {
            $data['color'] = [];

            for ($i = 0; $count > $i; ++$i) {
                $data['color'][] = $this->_getColor();
            }
        }

        if (! isset($data['style'])) {
            for ($i = 0; $count > $i; ++$i) {
                $data['style'][] = 'FillStroke-' . $data['color'][$i];
            }
        }

        if (isset($data['label'])) {
            foreach ($data['label'] as $key => $label) {
                $this->addLegend(
                    $data['color'][$key],
                    $label,
                    (isset($data['link']) && isset($data['link'][$key])) ? $data['link'][$key] : 0
                );
            }
        }

        foreach ($columns as $key => $line) {
            $style = $data['style'][$key];
            $this->styleMap[$style] = "y$key";

            foreach ($line as $key => $value) {
                $x = $data['x'][$key];
                $this->columnMap[$x] = $key;

                if (! isset($this->columns[$x])) {
                    $this->columns[$x] = [];
                }

                if (! empty($value)) {
                    $this->columns[$x][$style] = $value;
                } else {
                    $this->columns[$x][$style] = 0;
                }
            }
        }

        return true;
    }

    public function _drawGridContent(&$renderer) // {{{2
    {
        $layout = $this->_layout();
        $zero = $this->dependant->getLocation(0);

        foreach ($this->columns as $label => $values) {
            $range = $this->independant->getRange($label);

            switch ($this->independant->orientation) {
                case 'vertical':
                    $ren = new FakeGRenderer($renderer, 0, $range[0], 1, $range[1]);
                    break;
                case 'horizontal':
                    $ren = new FakeGRenderer($renderer, $range[0], 0, $range[1], 1);
                    break;
            }

            $positions = $this->_drawColumn($ren, $values, $zero);

            if (is_array($positions)) {
                $index = $this->columnMap[$label];
                foreach ($positions as $style => $positionData) {
                    $series = $this->styleMap[$style];
                    $this->_notify($ren, $positionData, $series, $index);
                }
            }
        }
    }

    public function _drawColumn(&$renderer, $values, $zero)
    {
        die("Abstract Function Call");
    }

    public function _drawBox(&$renderer, $left, $top, $right, $bottom, $style)
    {
        $style = $renderer->getStyle($style);

        switch ($this->independant->orientation) {
            case 'vertical':
                $renderer->drawRectangle($bottom, $left, $top, $right, $style);
                break;
            case 'horizontal':
                $renderer->drawRectangle($left, $top, $right, $bottom, $style);
                break;
        }
    }

    public function _drawLegendBox(&$renderer, $color) // {{{2
    {
        $renderer->drawRectangle(0, 1, 1, 0, $renderer->getStyle("FillStroke-$color"));
    }

    public function _default() // {{{2
    {
        return array_merge(
            parent::_default(),
            [
                'grid-independant-scale' => 'static',
                'grid-independant-major-guide' => 'Thin-LineStroke-Black'
            ]
        );
    }
}
