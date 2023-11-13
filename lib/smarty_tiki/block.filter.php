<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 *
 */
function smarty_block_filter($params, $content, \Smarty\Template $template, &$repeat)
{
    $smartyBlockFilterHandler = new \SmartyTiki\BlockHandler\Filter();
    return $smartyBlockFilterHandler->handle($params, $content, $template, $repeat);
}
