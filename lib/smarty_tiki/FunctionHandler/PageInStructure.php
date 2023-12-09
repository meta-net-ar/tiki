<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\FunctionHandler;

use Smarty\FunctionHandler\Base;
use Smarty\Template;
use TikiLib;

/**
 * Smarty function plugin
 * -------------------------------------------------------------
 * Type:         function
 * Name:         page_in_structure
 * Purpose:      returns true if a pag eis in a structure
 * Parameters:   pagechecked - mandatory
 * -------------------------------------------------------------
 */
class PageInStructure extends Base
{
    public function handle($params, Template $template)
    {
        $structlib = TikiLib::lib('struct');
        $smarty = TikiLib::lib('smarty');
        extract($params, EXTR_SKIP);

        if (! isset($pagechecked)) {
            return ('<b>missing pagechecked parameter for Smarty function testing whether page is in a structure</b><br/>');
        }

        if ($structlib->page_is_in_structure($pagechecked)) {
            $result = true;
            $smarty->assign('page_in_structure', $result);
            return;
        }
        $result = false;
        $smarty->assign('page_in_structure', $result);
    }
}
