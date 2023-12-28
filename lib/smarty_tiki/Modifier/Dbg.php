<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\Modifier;

/**
 * \brief Smarty modifier plugin to add string to debug console log w/o modify output
 * Usage format {$smarty_var|dbg}
 */
class Dbg
{
    public function handle($string, $label = '')
    {
        global $debugger;
        require_once('lib/debug/debugger.php');
        //
        $debugger->msg('Smarty log' . ((strlen($label) > 0) ? ': ' . $label : '') . ': ' . $string);
        return $string;
    }
}
