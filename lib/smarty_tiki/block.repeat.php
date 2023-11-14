<?php

function smarty_block_repeat($params, $content, \Smarty\Template $template, &$repeat)
{
    $smartyBlockRepeatHandler = new \SmartyTiki\BlockHandler\Repeat();
    return $smartyBlockRepeatHandler->handle($params, $content, $template, $repeat);
}
