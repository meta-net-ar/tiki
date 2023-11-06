<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/* return the attributes for a standard tiki page body tag
 * jonnyb refactoring for tiki5
 * eromneg adding additional File Gallery popup body class
 */

function smarty_function_html_body_attributes($params, \Smarty\Template $template)
{
    $smartyFunctionHmlBodyAttributesHandler = new \smartytiki\FunctionHandler\HtmlBodyAttributes();
    return $smartyFunctionHmlBodyAttributesHandler->handle($params, $template);
}
