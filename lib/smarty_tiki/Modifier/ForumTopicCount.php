<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\Modifier;

class ForumTopicCount
{
    public function handle($forumId)
    {
        return \TikiLib::lib('comments')->count_forum_topics($forumId);
    }
}
