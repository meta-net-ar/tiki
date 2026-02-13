<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
$inputConfiguration = [
    [
        'staticKeyFilters'                     => [
            'forumId'                          => 'int',        //post
            'parentId'                         => 'int',        //post
            'lock'                             => 'alpha',      //post
            'useMail'                          => 'bool',        //post
            'usePruneUnreplied'                => 'bool',        //post
            'controlFlood'                     => 'bool',        //post
            'usePruneOld'                      => 'bool',        //post
            'vote_threads'                     => 'bool',        //post
            'outbound_mails_for_inbound_mails' => 'bool',        //post
            'outbound_mails_reply_link'        => 'bool',        //post
            'topics_list_reads'                => 'bool',        //post
            'topics_list_replies'              => 'bool',        //post
            'show_description'                 => 'bool',        //post
            'is_flat'                          => 'bool',        //post
            'topic_summary'                    => 'bool',        //post
            'topic_smileys'                    => 'bool',        //post
            'ui_avatar'                        => 'bool',        //post
            'ui_rating_choice_topic'           => 'bool',        //post
            'ui_flag'                          => 'bool',        //post
            'ui_email'                         => 'bool',        //post
            'ui_posts'                         => 'bool',        //post
            'ui_level'                         => 'bool',        //post
            'ui_online'                        => 'bool',        //post
            'topics_list_pts'                  => 'bool',        //post
            'topics_list_lastpost'             => 'bool',        //post
            'topics_list_lastpost_title'       => 'bool',        //post
            'topics_list_lastpost_avatar'      => 'bool',        //post
            'topics_list_author'               => 'bool',        //post
            'topics_list_author_avatar'        => 'bool',        //post
            'att_list_nb'                      => 'bool',        //post
            'threadOrdering'                   => 'striptags',   //post
            'threadStyle'                      => 'striptags',   //post
            'commentsPerPage'                  => 'striptags',   //post
            'image'                            => 'striptags',   //post
            'section'                          => 'striptags',   //post
            'new_section'                      => 'striptags',   //post
            'att_store_dir'                    => 'striptags',   //post
            'forumLanguage'                    => 'lang',        //post
            'floodInterval'                    => 'int',         //post
            'mail'                             => 'email',       //post
            'pruneUnrepliedAge'                => 'striptags',   //post
            'pruneMaxAge'                      => 'int',         //post
            'topicsPerPage'                    => 'int',         //post
            'topicOrdering'                    => 'striptags',   //post
            'approval_type'                    => 'striptags',   //post
            'att'                              => 'striptags',   //post
            'att_store'                        => 'striptags',   //post
            'att_max_size'                     => 'int',         //post
            'forum_last_n'                     => 'int',         //post
            'duplicate'                        => 'bool',        //post
            'duplicate_name'                   => 'striptags',   //post
            'duplicate_forumId'                => 'int',         //post
            'dupCateg'                         => 'bool',        //post
            'offset'                           => 'int',         //post
            'numrows'                          => 'int',         //post
            'dupPerms'                         => 'bool',        //post
            'name'                             => 'striptags',   //post
            'description'                      => 'xss',         //post
            'sort_mode'                        => 'striptags',   //get
            'dup_mode'                         => 'bool',        //post
            'find'                             => 'text',        //post
            'moderator'                        => 'text',        //post
            'moderator_group'                  => 'groupname',   //post
            'forum_password'                   => 'password',    //post
            'outbound_address'                 => 'text',        //post
            'outbound_from'                    => 'text',        //post
            'inbound_imap_server'              => 'text',        //post
            'inbound_imap_port'                => 'text',        //post
            'inbound_imap_user'                => 'text',        //post
            'inbound_imap_password'            => 'password',    //post
            'inbound_imap_ssl'                 => 'text',    //post
            'save'                             => 'bool',        //post
        ],
    ],
];
require_once('tiki-setup.php');
use Tiki\Sections;
$section = Sections::SECTION_ADMIN;
Sections::setCurrentSection($section);
if (! isset($_REQUEST['forumId'])) {
    $_REQUEST['forumId'] = 0;
}
if (! isset($_REQUEST['parentId'])) {
    $_REQUEST['parentId'] = 0;
}
$access->check_feature('feature_forums');

$objectperms = Perms::get('forum', $_REQUEST['forumId']);
if (! $objectperms->admin_forum) {
    $access->display_error('', tra('Permission denied') . ": " . 'tiki_p_admin_forum', '403');
}
$smarty->assign('permsType', $objectperms->from());

$auto_query_args = [
            'forumId',
            'offset',
            'sort_mode',
            'find',
];

$commentslib = TikiLib::lib('comments');
$smarty->assign('MAX_FORUM_NAME_LENGTH', Comments::MAX_FORUM_NAME_LENGTH);
$smarty->assign('MAX_FORUM_DESCRIPTION_LENGTH', Comments::MAX_FORUM_DESCRIPTION_LENGTH);
$smarty->assign('MAX_FORUM_SECTION_LENGTH', Comments::MAX_FORUM_SECTION_LENGTH);
$smarty->assign('MAX_FORUM_PASSWORD_LENGTH', Comments::MAX_FORUM_PASSWORD_LENGTH);
if (isset($_REQUEST['lock']) && isset($_REQUEST['forumId'])) {
    if ($_REQUEST['lock'] == 'y' && $access->checkCsrf()) {
        $commentslib->lock_object_thread('forum:' . ((int)$_REQUEST['forumId']));
    } elseif ($_REQUEST['lock'] == 'n' && $access->checkCsrf()) {
        $commentslib->unlock_object_thread('forum:' . ((int)$_REQUEST['forumId']));
    }
}
if ($prefs['feature_multilingual'] === 'y') {
    $langLib = TikiLib::lib('language');
    $languages = $langLib->list_languages();
    $smarty->assign_by_ref('languages', $languages);
} else {
    $_REQUEST["forumLanguage"] = '';
}

if (isset($_REQUEST["save"]) && $access->checkCsrf()) {
    if (empty($_REQUEST['image'])) {
        $_REQUEST['image'] = '';
    }
    if (isset($_REQUEST["section"]) && $_REQUEST["section"] == '__new__') {
        $_REQUEST["section"] = $_REQUEST["new_section"];
    }
    // Check for last character being a / or a \
    if (! str_ends_with($_REQUEST["att_store_dir"], "\\") && ! str_ends_with($_REQUEST["att_store_dir"], "/") && $_REQUEST["att_store_dir"] != "") {
        $_REQUEST["att_store_dir"] .= "/";
    }
    $_REQUEST['forumLanguage'] = htmlspecialchars($_REQUEST["forumLanguage"] ?? '');
    $tx = TikiDb::get()->begin();

    $input = [];
    foreach ($commentslib->getForumDefaultsInfo() as $key => $default) {
        $value = $_REQUEST[$key] ?? $default;
        if ($key == 'outbound_address') {
            $value = trim($value);
        }
        $input[$key] = $value ;
    }
    if (isset($input["name"], $input["description"], $input["section"], $input["forum_password"])) {
        $validateNameFieldLength = Feedback::validateFieldLength("Name", $input["name"], Comments::MAX_FORUM_NAME_LENGTH);
        $validateDescriptionFieldLength = Feedback::validateFieldLength("Description", $input["description"], Comments::MAX_FORUM_DESCRIPTION_LENGTH);
        $validateSectionFieldLength = Feedback::validateFieldLength("Section", $input["section"], Comments::MAX_FORUM_SECTION_LENGTH);
        $validateForumPasswordFieldLength = Feedback::validateFieldLength("Section", $input["forum_password"], Comments::MAX_FORUM_PASSWORD_LENGTH);
        if (! $validateNameFieldLength || ! $validateDescriptionFieldLength || ! $validateSectionFieldLength) {
            $smarty->assign('mid', 'tiki-admin_forums.tpl');
            $smarty->display("tiki.tpl");
            die;
        }
    }
    $fid = $commentslib->replace_forum($input);

    if ($fid) {
        Feedback::success(tr('Forum saved'));
    } else {
        Feedback::error(tr('Forum not saved'));
    }

    $attributelib = TikiLib::lib('attribute');
    $attributelib->set_attribute('forum', $fid, 'tiki.object.image', (int) $_REQUEST['image']);
    $cat_type = 'forum';
    $cat_objid = $fid;
    $cat_desc = substr($_REQUEST["description"], 0, 200);
    $cat_name = $_REQUEST["name"];
    $cat_href = "tiki-view_forum.php?forumId=" . $cat_objid;
    include_once("categorize.php");
    $_REQUEST["forumId"] = $fid;

    $tx->commit();

    $cookietab = 1;
}
if (
    ! empty($_REQUEST['duplicate']) && ! empty($_REQUEST['duplicate_name']) && ! empty($_REQUEST['duplicate_forumId'])
    && $access->checkCsrf()
) {
    $newForumId = $commentslib->duplicate_forum(
        $_REQUEST['duplicate_forumId'],
        $_REQUEST['duplicate_name'],
        $_REQUEST['description'] ?? ''
    );
    if ($newForumId) {
        Feedback::success(tr('Forum duplicated'));
    } else {
        Feedback::error(tr('Forum not duplicated'));
    }
    if (isset($_REQUEST['dupCateg']) && $_REQUEST['dupCateg'] == 'on' && $prefs['feature_categories'] == 'y') {
        $categlib = TikiLib::lib('categ');
        $cats = $categlib->get_object_categories('forum', $_REQUEST['forumId']);
        $catObjectId = $categlib->add_categorized_object('forum', $newForumId, $_REQUEST['description'] ?? '', $_REQUEST['name'], "tiki-view_forum.php?forumId=$newForumId");
        foreach ($cats as $cat) {
            $categlib->categorize($catObjectId, $cat);
        }
    }
    if (isset($_REQUEST['dupPerms']) && $_REQUEST['dupPerms'] == 'on') {
        $userlib = TikiLib::lib('user');
        $userlib->copy_object_permissions($_REQUEST['forumId'], $newForumId, 'forum');
    }
    $_REQUEST['forumId'] = $newForumId;
}
if ($_REQUEST["forumId"]) {
    $info = $commentslib->get_forum($_REQUEST["forumId"]);
    $attributelib = TikiLib::lib('attribute');
    $attributes = $attributelib->get_attributes('forum', $_REQUEST['forumId']);
    $info['image'] = $attributes['tiki.object.image'] ?? '';
} else {
    $info = [];
    foreach ($commentslib->getForumDefaultsInfo() as $key => $default) {
        if (in_array($key, ['parentId', 'forumId'])) {
            continue;
        }
        $info[$key] = $default ;
    }
    $info['image'] = '';
}
$smarty->assign('forumId', $_REQUEST["forumId"]);
$smarty->assign('parentId', $_REQUEST["parentId"]);
foreach ($info as $key => $value) {
    if ($key == "section") {
        // conflict with section management
        $smarty->assign("forumSection", $value);
    } else {
        $smarty->assign($key, $value);
    }
}
$sort_mode = $_REQUEST["sort_mode"] ?? $prefs['forums_ordering'];
$offset = $_REQUEST["offset"] ?? 0;
$smarty->assign_by_ref('offset', $offset);
$find = $_REQUEST["find"] ?? '';
$smarty->assign('find', $find);
$smarty->assign_by_ref('sort_mode', $sort_mode);
$maxRecords = $_REQUEST['numrows'] ?? $prefs['maxRecords'];
$channels = $commentslib->list_forums($offset, $maxRecords, $sort_mode, $find, $_REQUEST['parentId']);
if ($_REQUEST['parentId'] > 0) {
    $forumParent = $commentslib->get_forum($_REQUEST['parentId']);
    $smarty->assign('parent', $forumParent);
}
$max = count($channels["data"]);
for ($i = 0; $i < $max; $i++) {
    if ($userlib->object_has_one_permission($channels["data"][$i]["forumId"], 'forum')) {
        $channels["data"][$i]["individual"] = 'y';
        if ($tiki_p_admin == 'y' || $userlib->object_has_permission($user, $channels["data"][$i]["forumId"], 'forum', 'tiki_p_admin_forum')) {
            $channels["data"][$i]["individual_tiki_p_admin_forum"] = 'y';
        }
    } elseif ($userlib->user_has_perm_on_object($user, $channels["data"][$i]["forumId"], 'forum', 'tiki_p_admin_forum')) {
        $channels["data"][$i]["individual_tiki_p_admin_forum"] = 'y';
    } else {
        $channels["data"][$i]["individual"] = 'n';
    }
}
$smarty->assign_by_ref('channels', $channels["data"]);
$smarty->assign_by_ref('count', $channels["count"]);
$cat_type = 'forum';
$cat_objid = $_REQUEST["forumId"];
$categories = [];
include_once("categorize_list.php");
if (! empty($_REQUEST['dup_mode'])) {
    if ($offset == 0 && ($maxRecords == - 1 || $channels['count'] <= $maxRecords)) {
        $smarty->assign_by_ref('allForums', $channels['data']);
    } else {
        $allForums = $commentslib->list_forums(0, -1, 'name_asc');
        $smarty->assign_by_ref('allForums', $allForums['data']);
    }
    $smarty->assign_by_ref('dup_mode', $_REQUEST['dup_mode']);
    $cookitab = 2;
}
$users = $userlib->list_all_users();
$smarty->assign_by_ref('users', $users);
$groups = $userlib->list_all_groups();
$smarty->assign_by_ref('groups', $groups);
$maxAttachSize = ini_get('upload_max_filesize');
if (preg_match('/^([\d\.]+)([gmk])?$/i', $maxAttachSize, $matches) && ! empty($matches[2])) {
    $maxAttachSize = $matches[1];
    switch (strtolower($matches[0][strlen($matches[0]) - 1])) {
        case 'g':
            $maxAttachSize *= 1024;
            // no break
        case 'm':
            $maxAttachSize *= 1024;
            // no break
        case 'k':
            $maxAttachSize *= 1024;
    }
}

//add tablesorter sorting and filtering
$ts = Table_Check::setVars('adminforums', true);
if ($ts['enabled'] && ! $ts['ajax']) {
    //set tablesorter code
    Table_Factory::build(
        'TikiAdminForums',
        [
            'id' => $ts['tableid'],
            'total' => $channels['count'],
        ]
    );
}

$smarty->assign_by_ref('maxAttachSize', $maxAttachSize);

$oneday = 60 * 60 * 24;

$prune_values = [
    $oneday      => '1' . ' ' . tra('day'),
    2 * $oneday  => '2' . ' ' . tra('days'),
    5 * $oneday  => '5' . ' ' . tra('days'),
    7 * $oneday  => '7' . ' ' . tra('days'),
    15 * $oneday => '15' . ' ' . tra('days'),
    30 * $oneday => '30' . ' ' . tra('days'),
    60 * $oneday => '60' . ' ' . tra('days'),
    90 * $oneday => '90' . ' ' . tra('days'),
];

$smarty->assign('pruneUnrepliedAge_options', $prune_values);
$smarty->assign('pruneMaxAge_options', $prune_values);

$flood_values = [
    15 => '15' . ' ' . tra('secs'),
    30 => '30' . ' ' . tra('secs'),
    60 => '1' . ' ' . tra('min'),
    120 => '2' . ' ' . tra('mins'),
];

$smarty->assign('flood_options', $flood_values);

$smarty->assign(
    'approval_options',
    [
        'all_posted' => tra('All posted'),
        'queue_anon' => tra('Queue anonymous posts'),
        'queue_all' => tra('Queue all posts')
    ]
);

$smarty->assign(
    'attachment_options',
    [
        'att_no' => tra('No attachments'),
        'att_all' => tra('Everybody can attach'),
        'att_perm' => tra('Only users with attach permission'),
        'att_admin' => tra('Moderators and admin can attach')
    ]
);

$smarty->assign(
    'forum_use_password_options',
    [
        'n' => tra('No'),
        't' => tra('Topics only'),
        'a' => tra('All posts')
    ]
);

$smarty->assign(
    'forum_last_n_options',
    [
        0 => tra('No display'),
        5 => '5',
        10 => '10',
        20 => '20'
    ]
);

$smarty->assign(
    'topicOrdering_options',
    [
        'commentDate_desc' => tra('Date (desc)'),
        'commentDate_asc' => tra('Date (asc)'),
        'average_desc' => tra('Score (desc)'),
        'replies_desc' => tra('Replies (desc)'),
        'hits_desc' => tra('Reads (desc)'),
        'lastPost_desc' => tra('Latest post (desc)'),
        'title_desc' => tra('Title (desc)'),
        'title_asc' => tra('Title (asc)')
    ]
);

$smarty->assign(
    'threadOrdering_options',
    [
        '' => tra('Default'),
        'commentDate_desc' => tra('Newest first'),
        'commentDate_asc' => tra('Oldest first'),
        'points_desc' => tra('Score'),
        'title_desc' => tra('Title (desc)'),
        'title_asc' => tra('Title (asc)')
    ]
);

$smarty->assign(
    'threadStyle_options',
    [
        '' => tra('Default'),
        'commentStyle_plain' => tra('Plain'),
        'commentStyle_threaded' => tra('Threaded'),
        'commentStyle_headers' => tra('Headers only')
    ]
);

$smarty->assign(
    'commentsPerPage_options',
    [
        '' => tra('Default'),
        10 => '10',
        20 => '20',
        30 => '30',
        999999 => tra('All')
    ]
);

$sections = $tikilib->get_forum_sections();
$smarty->assign_by_ref('sections', $sections);
include_once('tiki-section_options.php');
// disallow robots to index page:
$smarty->assign('metatag_robots', 'NOINDEX, NOFOLLOW');
// Display the template
if ($ts['ajax']) {
    $smarty->display('tiki-admin_forums.tpl');
} else {
    $smarty->assign('mid', 'tiki-admin_forums.tpl');
    $smarty->display("tiki.tpl");
}
