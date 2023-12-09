<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\BlockHandler;

use Smarty\BlockHandler\Base;
use Smarty\Template;

/**
 * Smarty plugin
 * Type: block
 * Name: repeat
 * Purpose: repeat a template block a given number of times
 * Parameters: count [required] - number of times to repeat
 * assign [optional] - variable to collect output
 */
class Repeat extends Base
{
    public function handle($params, $content, Template $template, &$repeat)
    {
        if ($repeat || ! empty($content)) {
            $intCount = (int)$params['count'];
            if ($intCount < 0) {
                trigger_error("block: negative 'count' parameter");
                return;
            }

            $strRepeat = str_repeat($content, $intCount);
            if (! empty($params['assign'])) {
                $smarty = \TikiLib::lib('smarty');
                $smarty->assign($params['assign'], $strRepeat);
            } else {
                return $strRepeat;
            }
        }
    }
}
