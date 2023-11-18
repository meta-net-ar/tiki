<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

function smarty_function_html_select_date($params, \Smarty\Template $template)
{
    $smartyFunctionHtmlSelectDateHandler = new \SmartyTiki\FunctionHandler\HtmlSelectDate();
    return $smartyFunctionHtmlSelectDateHandler->handle($params, $template);
}
