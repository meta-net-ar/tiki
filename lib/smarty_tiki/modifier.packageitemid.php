<?php

function smarty_modifier_packageitemid($token)
{
    $smartyTikiExtension = new \SmartyTiki\Extension\SmartyTikiExtension();
    return $smartyTikiExtension->smarty_modifier_packageitemid($token);
}
