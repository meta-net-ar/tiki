<?php

function smarty_modifier_groupmembercount($group)
{
    $groupMemeberCountModifier = new \SmartyTiki\Modifier\GroupMemberCount();
    return $groupMemeberCountModifier->handle($group);
}
