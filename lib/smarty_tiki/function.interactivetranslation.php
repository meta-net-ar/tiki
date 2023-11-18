<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// Param: 'id' or 'label'
function smarty_function_interactivetranslation($params, \Smarty\Template $template)
{
    $smartyFunctionInteractiveTranslationHandler = new \SmartyTiki\FunctionHandler\InteractiveTranslation();
    return $smartyFunctionInteractiveTranslationHandler->handle($params, $template);
}
