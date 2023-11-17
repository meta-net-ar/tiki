<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function smarty_function_debugger($params, \Smarty\Template $template): string
{
    $smartyFunctionDebuggerHandler = new \SmartyTiki\FunctionHandler\DatetimeRange();
    return $smartyFunctionDebuggerHandler->handle($params, $template);
}
