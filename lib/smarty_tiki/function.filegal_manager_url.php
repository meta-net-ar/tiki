<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

function smarty_function_filegal_manager_url($params, \Smarty\Template $template)
{
    $smartyFunctionFileGalManagerUrlHandler = new \SmartyTiki\FunctionHandler\FileGalManagerUrl();
    return $smartyFunctionFileGalManagerUrlHandler->handle($params, $template);
}
