<?php

function smarty_modifier_forumtopiccount($forumId)
{
    $forumTopicCountModifier = new \SmartyTiki\Modifier\ForumTopicCount();
    return $forumTopicCountModifier->handle($forumId);
}
