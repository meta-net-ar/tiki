<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Tiki_Profile_InstallHandler_Forum extends Tiki_Profile_InstallHandler
{
    public function getData()
    {
        if ($this->data) {
            return $this->data;
        }

        $data = $this->obj->getData();

        $defaults = [
            'parentId' => 0,
            'description' => '',
            'flood_interval' => 120,
            'moderator' => 'admin',
            'per_page' => 10,
            'prune_max_age' => 3 * 24 * 3600,
            'prune_unreplied_max_age' => 30 * 24 * 3600,
            'topic_order' => 'lastPost_desc',
            'thread_order' => '',
            'section' => '',
            'inbound_pop_server' => '',
            'inbound_pop_port' => 110,
            'inbound_pop_user' => '',
            'inbound_pop_password' => '',
            'outbound_address' => '',
            'outbound_from' => '',
            'approval_type' => 'all_posted',
            'moderator_group' => '',
            'forum_password' => '',
            'attachments' => 'none',
            'attachments_store' => 'db',
            'attachments_store_dir' => '',
            'attachments_max_size' => 10000000,
            'list_att_nb' => 'n',
            'forum_last_n' => 0,
            'comments_per_page' => '',
            'thread_style' => '',
            'is_flat' => 'n',

            'list_topic_reads' => 'n',
            'list_topic_replies' => 'n',
            'list_topic_points' => 'n',
            'list_topic_last_post' => 'n',
            'list_topic_last_post_title' => 'n',
            'list_topic_last_post_avatar' => 'n',
            'list_topic_author' => 'n',
            'list_topic_author_avatar' => 'n',

            'show_description' => 'n',

            'enable_flood_control' => 'n',
            'mail' => '',
            'enable_inbound_mail' => 'n',
            'enable_prune_unreplied' => 'n',
            'enable_prune_old' => 'n',
            'enable_vote_threads' => 'n',
            'enable_outbound_for_inbound' => 'n',
            'enable_outbound_reply_link' => 'n',
            'enable_topic_smiley' => 'n',
            'enable_topic_summary' => 'n',
            'enable_ui_avatar' => 'n',
            'enable_ui_rating_choice_topic' => 'n',
            'enable_ui_flag' => 'n',
            'enable_ui_posts' => 'n',
            'enable_ui_level' => 'n',
            'enable_ui_email' => 'n',
            'enable_ui_online' => 'n',
            'enable_password_protection' => 'n',
            'forum_language' => '',
        ];

        $data = Tiki_Profile::convertLists($data, ['enable' => 'y', 'list' => 'y',  'show' => 'y'], true);

        $data = array_merge($defaults, $data);

        $data = Tiki_Profile::convertYesNo($data);

        return $this->data = $data;
    }

    public function canInstall()
    {
        $data = $this->getData();

        if (! isset($data['name'])) {
            return false;
        }

        return true;
    }

    private static function getAttConverter()
    {
        return new Tiki_Profile_ValueMapConverter(
            [
                'none' => 'att_no',
                'everyone' => 'att_all',
                'allowed' => 'att_perm',
                'admin' => 'att_admin',
            ]
        );
    }

    public function doInstall()
    {
        $comments = TikiLib::lib('comments');

        $data = $this->getData();
        $this->replaceReferences($data);

        $attConverter = self::getAttConverter();

        $id = $comments->replace_forum(
            [
                'forumId' => 0,
                'name' => $data['name'],
                'description' => $data['description'],
                'controlFlood' => $data['enable_flood_control'],
                'floodInterval' => $data['flood_interval'],
                'moderator' => $data['moderator'],
                'mail' => $data['mail'],
                'useMail' => $data['enable_inbound_mail'],
                'usePruneUnreplied' => $data['enable_prune_unreplied'],
                'pruneUnrepliedAge' => $data['prune_unreplied_max_age'],
                'usePruneOld' => $data['enable_prune_old'],
                'pruneMaxAge' => $data['prune_max_age'],
                'topicsPerPage' => $data['per_page'],
                'topicOrdering' => $data['topic_order'],
                'threadOrdering' => $data['thread_order'],
                'section' => $data['section'],
                'topics_list_reads' => $data['list_topic_reads'],
                'topics_list_replies' => $data['list_topic_replies'],
                'topics_list_pts' => $data['list_topic_points'],
                'topics_list_lastpost' => $data['list_topic_last_post'],
                'topics_list_author' => $data['list_topic_author'],
                'vote_threads' => $data['enable_vote_threads'],
                'show_description' => $data['show_description'],
                'inbound_pop_server' => $data['inbound_pop_server'],
                'inbound_pop_port' => $data['inbound_pop_port'],
                'inbound_pop_user' => $data['inbound_pop_user'],
                'inbound_pop_password' => $data['inbound_pop_password'],
                'outbound_address' => $data['outbound_address'],
                'outbound_mails_for_inbound_mails' => $data['enable_outbound_for_inbound'],
                'outbound_mails_reply_link' => $data['enable_outbound_reply_link'],
                'outbound_from' => $data['outbound_from'],
                'topic_smileys' => $data['enable_topic_smiley'],
                'topic_summary' => $data['enable_topic_summary'],
                'ui_avatar' => $data['enable_ui_avatar'],
                'ui_rating_choice_topic' => $data['enable_ui_rating_choice_topic'],
                'ui_flag' => $data['enable_ui_flag'],
                'ui_posts' => $data['enable_ui_posts'],
                'ui_level' => $data['enable_ui_level'],
                'ui_email' => $data['enable_ui_email'],
                'ui_online' => $data['enable_ui_online'],
                'approval_type' => $data['approval_type'],
                'moderator_group' => $data['moderator_group'],
                'forum_password' => $data['forum_password'],
                'forum_use_password' => $data['enable_password_protection'],
                'att' => $attConverter->convert($data['attachments']),
                'att_store' => $data['attachments_store'],
                'att_store_dir' => $data['attachments_store_dir'],
                'att_max_size' => $data['attachments_max_size'],
                'forum_last_n' => $data['forum_last_n'],
                'commentsPerPage' => $data['comments_per_page'],
                'threadStyle' => $data['thread_style'],
                'is_flat' => $data['is_flat'],
                'att_list_nb' => $data['list_att_nb'],
                'topics_list_lastpost_title' => $data['list_topic_last_post_title'],
                'topics_list_lastpost_avatar' => $data['list_topic_last_post_avatar'],
                'topics_list_author_avatar' => $data['list_topic_author_avatar'],
                'forumLanguage' => $data['forum_language'],
                'parentId' => $data['parentId']
            ]
        );

        return $id;
    }

    /**
     * Export forums
     *
     * @param Tiki_Profile_Writer $writer
     * @param int $forumId
     * @param bool $all
     * @return bool
     */
    public static function export(Tiki_Profile_Writer $writer, $forumId, $all = false)
    {
        $forumlib = TikiLib::lib('comments');

        if (isset($forumId) && ! $all) {
            $listForums = [];
            $listForums[] = $forumlib->get_forum($forumId);
        } else {
            $listForums = $listForums = $forumlib->list_forums();
            $listForums = $listForums['data'];
        }

        if (empty($listForums[0]['forumId'])) {
            return false;
        }

        foreach ($listForums as $forum) {
            $writer->addObject(
                'forum',
                $forum['forumId'],
                [
                    'name' => $forum['name'],
                    'description' => $forum['description'],
                    'enable_flood_control' => $forum['controlFlood'],
                    'flood_interval' => $forum['floodInterval'],
                    'moderator' => $forum['moderator'],
                    'mail' => $forum['mail'],
                    'enable_inbound_mail' => $forum['useMail'],
                    'section' => $forum['section'],
                    'enable_prune_unreplied' => $forum['usePruneUnreplied'],
                    'prune_unreplied_max_age' => $forum['pruneUnrepliedAge'],
                    'enable_prune_old' => $forum['usePruneOld'],
                    'prune_max_age' => $forum['pruneMaxAge'],
                    'per_page' => $forum['topicsPerPage'],
                    'topic_order' => $forum['topicOrdering'],
                    'thread_order' => $forum['threadOrdering'],
                    'attachments' => self::getAttConverter()->reverse($forum['att']),
                    'attachments_store' => $forum['att_store'],
                    'attachments_store_dir' => $forum['att_store_dir'],
                    'attachments_max_size' => $forum['att_max_size'],
                    'list_att_nb' => $forum['att_list_nb'],
                    'enable_ui_level' => $forum['ui_level'],
                    'enable_password_protection' => $forum['forum_use_password'],
                    'forum_password' => $forum['forum_password'],
                    'moderator_group' => $forum['moderator_group'],
                    'approval_type' => $forum['approval_type'],
                    'outbound_address' => $forum['outbound_address'],
                    'enable_outbound_for_inbound' => $forum['outbound_mails_for_inbound_mails'],
                    'enable_outbound_mail_reply_link' => $forum['outbound_mails_reply_link'],
                    'outbound_from' => $forum['outbound_from'],
                    'inbound_pop_server' => $forum['inbound_pop_server'],
                    'inbound_pop_port' => $forum['inbound_pop_port'],
                    'inbound_pop_user' => $forum['inbound_pop_user'],
                    'inbound_pop_password' => $forum['inbound_pop_password'],
                    'enable_topic_smiley' => $forum['topic_smileys'],
                    'enable_ui_avatar' => $forum['ui_avatar'],
                    'enable_ui_rating_choice_topic' => $forum['ui_rating_choice_topic'],
                    'enable_ui_flag' => $forum['ui_flag'],
                    'enable_ui_posts' => $forum['ui_posts'],
                    'enable_ui_email' => $forum['ui_email'],
                    'enable_ui_online' => $forum['ui_online'],
                    'enable_topic_summary' => $forum['topic_summary'],
                    'show_description' => $forum['show_description'],
                    'list_topic_replies' => $forum['topics_list_replies'],
                    'list_topic_reads' => $forum['topics_list_reads'],
                    'list_topic_points' => $forum['topics_list_pts'],
                    'list_topic_last_post' => $forum['topics_list_lastpost'],
                    'list_topic_last_post_title' => $forum['topics_list_lastpost_title'],
                    'list_topic_last_post_avatar' => $forum['topics_list_lastpost_avatar'],
                    'list_topic_author_avatar' => $forum['topics_list_author_avatar'],
                    'list_topic_author' => $forum['topics_list_author'],
                    'enable_vote_threads' => $forum['vote_threads'],
                    'forum_last_n' => $forum['forum_last_n'],
                    'thread_style' => $forum['threadStyle'],
                    'comments_per_page' => $forum['commentsPerPage'],
                    'is_flat' => $forum['is_flat'],
                ]
            );
        }
        return true;
    }

    /**
     * Remove forum
     *
     * @param string $forumName
     * @return bool
     */
    public function remove($forumName)
    {
        if (! empty($forumName)) {
            $comments = TikiLib::lib('comments');
            $forum = $comments->list_forums(0, 1, 'forumId_desc', $forumName);
            $forumId = ! empty($forum['data'][0]['forumId']) ? $forum['data'][0]['forumId'] : null;
            if ($forumId && $comments->remove_forum($forumId)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get current forum data
     *
     * @param array $forum
     * @return mixed
     */
    public function getCurrentData($forum)
    {
        $forumName = ! empty($forum['name']) ? $forum['name'] : '';
        if (! empty($forumName)) {
            $comments = TikiLib::lib('comments');
            $forum = $comments->list_forums(0, 1, 'forumId_desc', $forumName);
            $forumData = ! empty($forum['data'][0]) ? $forum['data'][0] : false;
            return $forumData;
        }
        return false;
    }
}
