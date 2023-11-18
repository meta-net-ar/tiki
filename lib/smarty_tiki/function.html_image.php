<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

function smarty_function_html_image($params, \Smarty\Template $template)
{
    $smartyFunctionHtmlImageHandler = new \Smarty\FunctionHandler\HtmlImage();
    return $smartyFunctionHtmlImageHandler->handle($params, $template);
}
