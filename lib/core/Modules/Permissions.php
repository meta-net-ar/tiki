<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Modules;

use TikiLib;
use Tracker_Item;

/**
 * Class responsible for all modules permission logic
 */
class Permissions
{
    /**
     * Return page permissions
     *
     * @return array
     */
    public function getPagePermissions()
    {
        global $jitRequest;

        $url = $_SERVER['SCRIPT_NAME'];

        if (null == $objectType = $this->findObjectType($url)) {
            return null;
        }

        $objectId = null;

        switch ($objectType) {
            case 'wiki page':
                $objectId = ! empty($_REQUEST['page']) ? $_REQUEST['page'] : null;
                break;
            case 'file gallery':
                $objectId = ! empty($_REQUEST['galleryId']) ? $_REQUEST['galleryId'] : null;
                if (is_array($objectId)) {
                    $objectId = reset($objectId);
                }
                $fileId = ! empty($_REQUEST['fileId']) ? $_REQUEST['fileId'] : null;
                if ($fileId) {
                    $objectId = $fileId;
                    $objectType = 'file';
                }
                break;
            case 'tracker':
                $objectId = ! empty($_REQUEST['trackerId']) ? $_REQUEST['trackerId'] : null;
                $itemId = ! empty($_REQUEST['itemId']) ? $_REQUEST['itemId'] : null;
                if ($itemId) {
                    $item = Tracker_Item::fromId($itemId);
                    if ($item && $item->getInfo()) {
                        $objectId = $itemId;
                        $objectType = 'trackeritem';
                    }
                }
                break;
            case 'forum':
                $objectId = ! empty($_REQUEST['forumId']) ? $_REQUEST['forumId'] : null;
                $threadId = ! empty($_REQUEST['comments_parentId']) ? $_REQUEST['comments_parentId'] : null;
                if (! $threadId) {
                    $threadId = ! empty($_REQUEST['threadId']) ? $_REQUEST['threadId'] : null;
                }
                if ($threadId) {
                    $objectId = $threadId;
                    $objectType = 'thread';
                }
                break;
            case 'group':
                $objectId = ! empty($_REQUEST['group']) ? $_REQUEST['group'] : null;
                break;
            case 'article':
                $objectId = ! empty($_REQUEST['articleId']) ? $_REQUEST['articleId'] : null;
                $topicId = ! empty($_REQUEST['topic']) ? $_REQUEST['topic'] : null;
                if (! $objectId && $topicId) {
                    $objectId = $topicId;
                    $objectType = 'topic';
                }
                break;
            case 'blog':
                $objectId = ! empty($_REQUEST['blogId']) ? $_REQUEST['blogId'] : null;
                $postId = ! empty($_REQUEST['postId']) ? $_REQUEST['postId'] : null;
                if ($postId) {
                    $objectId = $postId;
                    $objectType = 'blog post';
                }
                break;
            case 'calendar':
                $objectId = ! empty($_REQUEST['calendarId']) ? $_REQUEST['calendarId'] : null;
                if (! $objectId && isset($_REQUEST['calIds']) && is_array($_REQUEST['calIds']) && count($_REQUEST['calIds']) == 1) {
                    $objectId = reset($_REQUEST['calIds']);
                }
                $calitemId = ! empty($_REQUEST['calitemId']) ? $_REQUEST['calitemId'] : $jitRequest->calitemId->int();
                if ($calitemId) {
                    $objectId = $calitemId;
                    $objectType = 'calendaritem';
                }
                break;
            case 'sheet':
                $objectId = ! empty($_REQUEST['sheetId']) ? $_REQUEST['sheetId'] : null;
                break;
        }

        if ($objectId && ! $this->objectExists($objectType, $objectId)) {
            return null;
        }

        return $this->getObjectPermissions($objectType, $objectId);
    }

    /**
     * Return the permissions applied to an object
     *
     * @param string $objectType
     * @param mixed $objectId
     * @return array
     */
    public function getObjectPermissions($objectType, $objectId)
    {
        $parentId = $parentType = $permType = $additional = null;

        switch ($objectType) {
            case 'wiki page':
                $permType = 'wiki';
                break;
            case 'file gallery':
                $permType = 'file galleries';
                break;
            case 'file':
                $file = TikiLib::lib('filegal')->get_file_info($objectId, false, false);
                $parentId = $file['galleryId'] ?? null;
                $parentType = 'file gallery';
                $permType = 'file galleries';
                break;
            case 'tracker':
                $permType = 'trackers';
                break;
            case 'trackeritem':
                $item = Tracker_Item::fromId($objectId);
                if ($item && ($info = $item->getInfo())) {
                    $parentId = $info['trackerId'];
                    $parentType = 'tracker';
                    $additional = ['Special Tracker Permissions:'];
                    $definition = $item->getDefinition();
                    $special_perms = ['userCanSeeOwn', 'groupCanSeeOwn', 'writerCanModify', 'writerCanRemove', 'userCanTakeOwnership', 'oneUserItem', 'writerGroupCanModify', 'writerGroupCanRemove', 'adminOnlyViewEditItem'];
                    foreach ($special_perms as $perm) {
                        if ($definition->getConfiguration($perm) == 'y') {
                            $additional[] = '* ' . $perm;
                        }
                    }
                    if (count($additional) == 1) {
                        $additional = '';
                    } else {
                        if ($fields = $definition->getItemOwnerFields()) {
                            $additional[] = 'Item Owner Fields: ' . implode(', ', $fields);
                        }
                        if ($fields = $definition->getItemGroupOwnerFields()) {
                            $additional[] = 'Item Group Owner Fields: ' . implode(', ', $fields);
                        }
                        if ($fields = $definition->getWriterGroupField()) {
                            $additional[] = 'Item Group Writer Fields: ' . implode(', ', $fields);
                        }
                    }
                    if ($additional) {
                        $additional = implode('<br>', $additional);
                    }
                }
                $permType = 'trackers';
                break;
            case 'forum':
                $permType = 'forums';
                break;
            case 'thread':
                $thread_info = TikiLib::lib('comments')->get_comment($objectId);
                $parentId = $thread_info['object'];
                $parentType = 'forum';
                $permType = 'forums';
                break;
            case 'article':
                if ($objectId) {
                    $parentId = \TikiDb::get()->table('tiki_articles')
                        ->fetchOne('topicId', ['articleId' => (int) $objectId]);
                    $parentType = 'topic';
                }
                $permType = 'articles';
                break;
            case 'topic':
                $permType = 'articles';
                break;
            case 'blog':
                $permType = 'blogs';
                break;
            case 'blog post':
                $post_info = TikiLib::lib('blog')->get_post($objectId);
                $parentId = $post_info['blogId'];
                $parentType = 'blog';
                $permType = 'blogs';
                break;
            case 'calendar':
                $permType = 'calendar';
                break;
            case 'calendaritem':
                $calitem = TikiLib::lib('calendar')->get_item($objectId);
                $parentId = $calitem['calendarId'];
                $parentType = 'calendar';
                $permType = 'calendar';
                break;
        }

        $all = TikiLib::lib('user')->get_permissions(0, -1, 'permName_asc', '', $this->findPermType($objectType), '', true);

        $accessor = \Perms::get(['type' => $objectType, 'object' => $objectId]);
        $loaded = $accessor->getResolver()->dump();

        $results = [];
        $count = 0;

        foreach ($all['data'] as $permDef) {
            foreach ($loaded['perms'] as $perm => $groups) {
                if ($perm != str_replace('tiki_p_', '', $permDef['name'])) {
                    continue;
                }
                sort($groups);
                $results[$permDef['type']][$perm] = [
                    'description' => $permDef['description'],
                    'groups' => $groups,
                ];
                $count++;
            }
        }

        ksort($results);
        foreach ($results as &$perms) {
            ksort($perms);
        }
        unset($perms);

        $loaded['perms'] = $results;
        $loaded['count'] = $count;
        $loaded['type'] = $objectType;
        $loaded['type_label'] = $this->getObjectTypeLabel($objectType);
        $loaded['object'] = $objectId;

        $smarty = TikiLib::lib('smarty');
        if ($loaded['from'] == 'parent object' && $parentId && $parentType) {
            $loaded['edit_link'] = \SmartyTiki\FunctionHandler\PermissionLink::render([
                'mode' => 'icon',
                'type' => $parentType,
                'permType' => $permType,
                'id' => $parentId,
            ], $smarty->getEmptyInternalTemplate());
        } elseif ($loaded['from'] == 'object' && $permType) {
            $loaded['edit_link'] = \SmartyTiki\FunctionHandler\PermissionLink::render([
                'mode' => 'icon',
                'type' => $objectType,
                'permType' => $permType,
                'id' => $objectId,
            ], $smarty->getEmptyInternalTemplate());
        } elseif ($loaded['from'] == 'category') {
            $affecting_categories = TikiLib::lib('categ')->get_object_categories($objectType, $objectId);
            $links = [];
            foreach ($affecting_categories as $categId) {
                $links[] = \SmartyTiki\FunctionHandler\PermissionLink::render([
                    'mode' => 'icon',
                    'type' => 'category',
                    'permType' => $permType,
                    'id' => $categId,
                ], $smarty->getEmptyInternalTemplate());
            }
            $loaded['edit_link'] = implode(' ', $links);
        } elseif ($loaded['from'] == 'parent category' && $parentId && $parentType) {
            $affecting_categories = TikiLib::lib('categ')->get_object_categories($parentType, $parentId);
            $links = [];
            foreach ($affecting_categories as $categId) {
                $links[] = \SmartyTiki\FunctionHandler\PermissionLink::render([
                    'mode' => 'icon',
                    'type' => 'category',
                    'permType' => $permType,
                    'id' => $categId,
                ], $smarty->getEmptyInternalTemplate());
            }
            $loaded['edit_link'] = implode(' ', $links);
        } elseif ($loaded['from'] == 'global') {
            $loaded['edit_link'] = \SmartyTiki\FunctionHandler\PermissionLink::render([
                'mode' => 'icon',
                'type' => 'global',
                'permType' => $permType
            ], $smarty->getEmptyInternalTemplate());
        } else {
            $loaded['edit_link'] = '';
        }

        $loaded['additional'] = $additional;

        return $loaded;
    }

    /**
     * Check if the object exists
     *
     * @param string $objectType
     * @param mixed $objectId
     * @return bool
     */
    public function objectExists($objectType, $objectId)
    {
        $tables = [
            'wiki page' => ['tiki_pages', 'pageName'],
            'file gallery' => ['tiki_file_galleries', 'galleryId'],
            'file' => ['tiki_files', 'fileId'],
            'tracker' => ['tiki_trackers', 'trackerId'],
            'trackeritem' => ['tiki_tracker_items', 'itemId'],
            'forum' => ['tiki_forums', 'forumId'],
            'thread' => ['tiki_comments', 'threadId'],
            'group' => ['users_groups', 'groupName'],
            'article' => ['tiki_articles', 'articleId'],
            'topic' => ['tiki_topics', 'topicId'],
            'blog' => ['tiki_blogs', 'blogId'],
            'blog post' => ['tiki_blog_posts', 'postId'],
            'calendar' => ['tiki_calendars', 'calendarId'],
            'calendaritem' => ['tiki_calendar_items', 'calitemId'],
            'sheet' => ['tiki_sheets', 'sheetId'],
        ];

        if (! isset($tables[$objectType])) {
            return false;
        }

        [$table, $column] = $tables[$objectType];

        return (bool) \TikiDb::get()->table($table)->fetchCount([$column => $objectId]);
    }

    /**
     * Check if the current user can view the object
     *
     * @param string $objectType
     * @param mixed $objectId
     * @return bool
     */
    public function canViewObject($objectType, $objectId)
    {
        if ($objectType == 'trackeritem') {
            $item = Tracker_Item::fromId($objectId);
            return $item && $item->canView();
        }

        $viewPerms = [
            'wiki page' => 'view',
            'file gallery' => 'view_file_gallery',
            'file' => 'view_file_gallery',
            'tracker' => 'view_trackers',
            'forum' => 'forum_read',
            'thread' => 'forum_read',
            'group' => 'group_view',
            'article' => 'read_article',
            'topic' => 'read_article',
            'blog' => 'read_blog',
            'blog post' => 'read_blog',
            'calendar' => 'view_calendar',
            'calendaritem' => 'view_events',
            'sheet' => 'view_sheet',
        ];

        if (! isset($viewPerms[$objectType])) {
            return false;
        }

        $perm = $viewPerms[$objectType];
        return \Perms::get(['type' => $objectType, 'object' => $objectId])->$perm;
    }

    /**
     * Return the translated label of an object type
     *
     * @param string $objectType
     * @return string
     */
    private function getObjectTypeLabel($objectType)
    {
        $labels = [
            'wiki page' => tra('wiki page'),
            'file gallery' => tra('file gallery'),
            'file' => tra('file'),
            'tracker' => tra('tracker'),
            'trackeritem' => tra('tracker item'),
            'forum' => tra('forum'),
            'thread' => tra('forum post'),
            'group' => tra('group'),
            'article' => tra('article'),
            'topic' => tra('topic'),
            'blog' => tra('blog'),
            'blog post' => tra('blog post'),
            'calendar' => tra('calendar'),
            'calendaritem' => tra('calendar item'),
            'sheet' => tra('spreadsheet'),
        ];

        return $labels[$objectType] ?? $objectType;
    }

    protected function findObjectType($url)
    {

        $objectPaths = [
            'wiki page' => [
                'tiki-index.php',
                'tiki-listpages.php',
                'tiki-editpage.php',
                'tiki-copypage.php',
                'tiki-pagehistory.php'
            ],
            'file gallery' => [
                'tiki-list_file_gallery.php',
                'tiki-upload_file.php'
            ],
            'tracker' => [
                'tiki-list_trackers.php',
                'tiki-view_tracker.php',
                'tiki-view_tracker_item.php',
                'tiki-admin_tracker_fields.php'
            ],
            'forum' => [
                'tiki-forums.php',
                'tiki-view_forum.php',
                'tiki-admin_forums.php',
                'tiki-forum_import.php',
                'tiki-view_forum_thread.php'
            ],
            'group' => [
                'tiki-admingroups.php'
            ],
            'article' => [
                'tiki-list_articles.php',
                'tiki-view_articles.php',
                'tiki-edit_article.php',
                'tiki-read_article.php'
            ],
            'blog' => [
                'tiki-list_blogs.php',
                'tiki-edit_blog.php',
                'tiki-blog_post.php',
                'tiki-list_posts.php',
                'tiki-view_blog.php',
                'tiki-view_blog_post.php'
            ],
            'calendar' => [
                'tiki-calendar.php',
                'tiki-admin_calendars.php',
                'tiki-calendar_import.php'
            ],
            'sheet' => [
                'tiki-sheets.php',
                'tiki-view_sheets.php',
                'tiki-graph_sheet.php',
                'tiki-history_sheets.php',
                'tiki-export_sheet.php',
                'tiki-import_sheet.php',
            ],
        ];

        foreach ($objectPaths as $object => $paths) {
            foreach ($paths as $path) {
                if (str_contains($url, $path)) {
                    return $object;
                }
            }
        }

        if (str_contains($url, 'tiki-ajax_services.php') && $_REQUEST['controller'] == 'calendar') {
            return 'calendar';
        }

        return null;
    }

    protected function findPermType($objectType)
    {
        switch ($objectType) {
            case 'wiki page':
                return 'wiki';
            case 'file gallery':
            case 'file':
                return 'file galleries';
            case 'tracker':
            case 'trackeritem':
                return 'trackers';
            case 'forum':
            case 'thread':
                return 'forums';
            case 'group':
                return 'group';
            case 'article':
            case 'topic':
                return 'articles';
            case 'blog':
            case 'blog post':
                return 'blogs';
            case 'calendar':
            case 'calendaritem':
                return 'calendar';
            case 'sheet':
                return 'sheet';
        }
    }
}
