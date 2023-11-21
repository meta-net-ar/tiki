<?php

function smarty_modifier_forumtopiccount($forumId)
{
    $smartyTikiExtension = new \SmartyTiki\Extension\SmartyTikiExtension();
    return $smartyTikiExtension->smarty_modifier_forumtopiccount($forumId);
}
