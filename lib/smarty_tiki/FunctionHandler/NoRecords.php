<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\FunctionHandler;

use Smarty\FunctionHandler\Base;
use Smarty\Template;

/**
 *    function norecords
 *
 *    Param list :
 *        _colspan : How much column need to be covered
 *        _text : text to display, bu default => No records found.
 */
class NoRecords extends Base
{
    public function handle($params, Template $template)
    {
        $html = '<tr class="even">';
        if (is_int($params["_colspan"])) {
            $html .= '<td colspan="' . $params["_colspan"] . '" class="norecords">';
        } else {
            $html .= '<td class="norecords">';
        }
        if (isset($params["_text"])) {
            $html .= tra($params["_text"]);
        } else {
            $html .= tra("No records found.");
        }
        $html .= "</td></tr>";
        return $html;
    }
}
