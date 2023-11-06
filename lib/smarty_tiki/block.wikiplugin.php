<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

function smarty_block_wikiplugin($params, $content, \Smarty\Template $template, &$repeat = false)
{
    $smartyBlockWikipluginHandler = new \SmartyTiki\BlockHandler\Wikiplugin();
    return $smartyBlockWikipluginHandler->handle($params, $content, $template, $repeat);
}
