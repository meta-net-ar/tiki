<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// Handle special actions of the smarty_function_attachments smarty plugin

function smarty_function_attachments($params, \Smarty\Template $template)
{
    $smartyFunctionAttachmentsHandler = new \smartytiki\FunctionHandler\Attachments();
    return $smartyFunctionAttachmentsHandler->handle($params, $template);
}
