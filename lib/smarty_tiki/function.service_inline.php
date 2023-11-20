<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function smarty_function_service_inline($params, \Smarty\Template $template)
{
    $smartyFunctionServiceInlineHandler = new \SmartyTiki\FunctionHandler\ServiceInline();
    return $smartyFunctionServiceInlineHandler->handle($params, $template);
}
