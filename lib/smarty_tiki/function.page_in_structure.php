<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

//this script may only be included - so its better to die if called directly.
if (strpos($_SERVER['SCRIPT_NAME'], basename(__FILE__)) !== false) {
    header('location: index.php');
    exit;
}

function smarty_function_page_in_structure($params, \Smarty\Template $template)
{
    $smartyFunctionPageInStructureHandler = new \SmartyTiki\FunctionHandler\PageInStructure();
    return $smartyFunctionPageInStructureHandler->handle($params, $template);
}
