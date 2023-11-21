<?php

function smarty_modifier_forumname($commentid, $retrun_forumid = 'n')
{
    $smartyTikiExtension = new \SmartyTiki\Extension\SmartyTikiExtension();
    return $smartyTikiExtension->smarty_modifier_forumname($commentid, $retrun_forumid);
}
