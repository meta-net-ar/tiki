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
 * \brief Smarty fn to contain generate a ui-predicate-vue component for tracker fields
 *
 * Usage:
 *
 * Examples:
 *

 * @param $params     array  [ app = n|y, name = string ]
 * @param $content    string body of the Vue componenet
 * @param $smarty     Smarty
 * @param $repeat     boolean
 *
 * @return string
 * @throws Exception
 */
class TrackerRules extends Base
{
    public function handle($params, Template $template)
    {

        $headerlib = TikiLib::lib('header');

        $headerlib->add_jsfile('lib/vue/lib/ui-predicate-vue.js')
            // FIXME temporary workaround for chosen which seems to lose the event bindings
            ->add_js('jqueryTiki.select2 = false; jqueryTiki.select2_sortable = false;');
        // possible route towards a fix is here: https://stackoverflow.com/q/38716371/2459703

        return '<link rel="stylesheet" href="lib/vue/lib/ui-predicate-vue.css" type="text/css">' .
            TikiLib::lib('vuejs')->getFieldRules($params);
    }
}
