<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Lib\GraphEngine;

class GridScale
{
    public $orientation;
    public $type;
    public $layout;

    public function __construct($type, $layout)
    {
        $this->type = $type;
        $this->layout = $layout;

        if ($type == 'independant') {
            $this->orientation = $layout['grid-independant-location'];
        } else {
            $this->orientation = ( $layout['grid-independant-location'] == 'vertical' ) ? 'horizontal' : 'vertical';
        }
    }

    public function drawScale(&$renderer)
    {
        die("Abstract Function Call");
    }

    public function drawGrid(&$renderer)
    {
        die("Abstract Function Call");
    }

    public function getLocation($value)
    {
        die("Abstract Function Call");
    }

    public function getRange($value)
    {
        die("Abstract Function Call");
    }

    public function getSize(&$renderer, $available)
    {
        die("Abstract Function Call");
    }
}
