<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

function smarty_function_trackerheader($params, \Smarty\Template $template)
{
    $smartyFunctionTrackerHeaderHandler = new \SmartyTiki\FunctionHandler\TrackerHeader();
    return $smartyFunctionTrackerHeaderHandler->handle($params, $template);
}
