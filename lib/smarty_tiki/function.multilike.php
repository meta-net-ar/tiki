<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function smarty_function_multilike($params, \Smarty\Template $template)
{
    $smartyFunctionMultiLikeHandler = new \SmartyTiki\FunctionHandler\MultiLike();
    return $smartyFunctionMultiLikeHandler->handle($params, $template);
}
