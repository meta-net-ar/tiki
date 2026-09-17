<?php

function smarty_block_toast($params, $content, \Smarty\Template $template, &$repeat)
{
    $smartyBlockToastHandler = new \SmartyTiki\BlockHandler\Toast();
    return $smartyBlockToastHandler->handle($params, $content, $template, $repeat);
}
