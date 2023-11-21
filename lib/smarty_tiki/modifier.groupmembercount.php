<?php

function smarty_modifier_groupmembercount($group)
{
    $smartyTikiExtension = new \SmartyTiki\Extension\SmartyTikiExtension();
    return $smartyTikiExtension->smarty_modifier_groupmembercount($group);
}
