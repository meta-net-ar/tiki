<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\FunctionHandler;

use Smarty\FunctionHandler\Base;
use Smarty\Template;

/**
 * Smarty function obj_in_cat handler
 * -------------------------------------------------------------
 * Type:         function
 * Name:         obj_in_cat
 * Purpose:      returns true if an object is in a category
 * Parameters:   all 3 parameters are mandatory
 *               object is reference to the specific object to be tested eg object=$page
 *               type is the content type eg type='wiki page'
 *               catnumber is the category Id# eg catnumber=3
 * -------------------------------------------------------------
 */
class ObjInCat extends Base
{
    public function handle($params, Template $template)
    {
        $categlib = \TikiLib::lib('categ');

        extract($params, EXTR_SKIP);
        if (! isset($object)) {
            return ('<b>missing object parameter for Smarty function testing whether object is in a category</b><br/>');
        }

        if (! isset($type)) {
            return ('<b>missing type parameter for Smarty function testing whether object is in a category</b><br/>');
        }

        if (! isset($catnumber)) {
            return ('<b>missing catnumber parameter for Smarty function testing whether object is in a category</b><br/>');
        }

        $smarty = \TikiLib::lib('smarty');
        $categories = $categlib->get_object_categories($type, $object);
        $result = false;

        foreach ($categories as $cat) {
            if ($cat == $catnumber) {
                $result = true;
                $smarty->assign('obj_in_cat', $result);
                return;
            }
        }
        $smarty->assign('obj_in_cat', $result);
    }
}
