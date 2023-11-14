<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

function smarty_block_tikimodule($params, $content, \Smarty\Template $template, &$repeat)
{
    $smartyBlockTikiModuleHandler = new \SmartyTiki\BlockHandler\TikiModule();
    return $smartyBlockTikiModuleHandler->handle($params, $content, $template, $repeat);
}
