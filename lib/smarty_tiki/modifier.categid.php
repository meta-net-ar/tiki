<?php

/**
 * Gets Category Id from the Category name
 */

function smarty_modifier_categid($category)
{
    $smartyTikiExtension = new \SmartyTiki\Extension\SmartyTikiExtension();
    return $smartyTikiExtension->smarty_modifier_categid($category);
}
