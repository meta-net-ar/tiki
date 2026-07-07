<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\FunctionHandler;

use Smarty\FunctionHandler\Base;
use Smarty\Template;

/** \file
 * \author zaufi <zaufi@sendmail.ru>
 */

/**
 * \brief Smarty plugin to add variable dump to debug console log
 * Usage format {var_dump var="var_name_2_dump"}
 *
 * Adapted to do more than string for tiki 5
 */
class VarDump extends Base
{
    public function handle($params, Template $template)
    {
        global $debugger;
        require_once('lib/debug/debugger.php');
        $smarty = \TikiLib::lib('smarty');
        $v = $params['var'];
        if (! empty($v)) {
            $tmp = $smarty->getTemplateVars();
            if (is_array($tmp) && isset($tmp["$v"])) {
                if (is_string($tmp[$v])) {
                    $debugger->msg("Smarty var_dump(" . $v . ') = ' . print_r($tmp[$v], true)); // @phpstan-ignore disallowedFunctions.printR (returns variable value as string for Smarty debugger message)
                } else {
                    ob_start();
                    // @phpstan-ignore disallowedFunctions.varDump (captures output via ob_start for the Smarty {var_dump} template function)
                    var_dump($tmp[$v]);
                    $d = ob_get_clean();
                    $debugger->msg("Smarty var_dump(" . $v . ') = ' . $d);
                }
            } else {
                $debugger->msg("Smarty var_dump(" . $v . "): Variable not found");
            }
        } else {
            $debugger->msg("Smarty var_dump: Parameter 'var' not specified");
        }
        return '<!-- var_dump(' . $v . ') -->';
    }
}
