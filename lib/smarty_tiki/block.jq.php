<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

function smarty_block_jq(array $params, ?string $content, \Smarty\Template $template, bool &$repeat): string
{
    $smartyBlockJqHandler = new \SmartyTiki\BlockHandler\Jq();
    return $smartyBlockJqHandler->handle($params, $content, $template, $repeat);
}
