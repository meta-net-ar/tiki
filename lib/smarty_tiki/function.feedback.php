<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

function smarty_function_feedback($params, \Smarty\Template $template)
{
    $smartyFunctionFeedbackHandler = new \SmartyTiki\FunctionHandler\Feedback();
    return $smartyFunctionFeedbackHandler->handle($params, $template);
}
