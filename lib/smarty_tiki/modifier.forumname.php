<?php

function smarty_modifier_forumname($commentid, $retrun_forumid = 'n')
{
    $forumNameModifier = new \SmartyTiki\Modifier\ForumName();
    return $forumNameModifier->handle($commentid, $retrun_forumid);
}
