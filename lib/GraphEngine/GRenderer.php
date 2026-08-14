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

namespace Tiki\Lib\GraphEngine;

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
