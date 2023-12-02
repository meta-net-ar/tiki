<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

function smarty_block_remarksbox($params, $content, \Smarty\Template $template, &$repeat)
{
    $smartyBlockRemarksboxHandler = new \SmartyTiki\BlockHandler\Remarksbox();
    return $smartyBlockRemarksboxHandler->handle($params, $content, $template, $repeat);
}
