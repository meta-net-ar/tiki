<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Lib\GraphEngine;

class DataHandler
{
    /**
     * Provides means to hook into the data display sequence to add decorations
     * or external behaviour.
     *
     * Called every time a data entry is displayed by the engine. Handlers are
     * registered by Tiki\Lib\GraphEngine\Graphic::addDataHandler(). No implementations are provided
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
