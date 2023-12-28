<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\Modifier;

class ForumName
{
    public function handle($commentid, $retrun_forumid = 'n')
    {
        global $tikilib;
        $cachelib = \TikiLib::lib('cache');
        require_once 'lib/comments/commentslib.php';
        $comments = new \Comments();

        if ($retrun_forumid == 'y') {
            $cacheItem = "retrun_forumid" . $commentid;
        } else {
            $cacheItem = "retrun_forumname" . $commentid;
        }

        if ($cached = $cachelib->getCached($cacheItem)) {
            return $cached;
        }

        $forum_id = $comments->get_comment_forum_id($commentid);
        $cachelib->cacheItem($cacheItem, $forum_id);
        if ($retrun_forumid == 'y') {
            return $forum_id;
        }
        $ret = $comments->get_forum($forum_id);
        $cachelib->cacheItem($cacheItem, $ret['name']);
        return $ret['name'];
    }
}
