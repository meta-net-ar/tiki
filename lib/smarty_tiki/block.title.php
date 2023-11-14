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
 * smarty_block_title : add a title to a template.
 *
 * params:
 *    help: name of the doc page on doc.tiki.org
 *    admpage: admin panel name
 *    url: link on the title
 *
 * usage: {title help='Example' admpage='example'}{tr}Example{/tr}{/title}
 */
function smarty_block_title($params, $content, \Smarty\Template $template, &$repeat)
{
    $smartyBlockTitleHandler = new \SmartyTiki\BlockHandler\TikiModule();
    return $$smartyBlockTitleHandler->handle($params, $content, $template, $repeat);
}
