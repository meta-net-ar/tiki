<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

function smarty_function_filegal_uploader($params, \Smarty\Template $template)
{
    $smartyFunctionFileGalUploaderHandler = new \SmartyTiki\FunctionHandler\FileGalUploader();
    return $smartyFunctionFileGalUploaderHandler->handle($params, $template);
}
