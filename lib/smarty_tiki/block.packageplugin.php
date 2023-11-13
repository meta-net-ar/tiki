<?php

function smarty_block_packageplugin($params, $content, \Smarty\Template $template, &$repeat)
{
    $smartyBlockPackagePluginHandler = new \SmartyTiki\BlockHandler\PackagePlugin();
    return $smartyBlockPackagePluginHandler->handle($params, $content, $template, $repeat);
}
