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

        $parentId = $parentType = $permType = $additional = null;

        switch ($objectType) {
            case 'wiki page':
                $objectId = ! empty($_REQUEST['page']) ? $_REQUEST['page'] : null;
                $permType = 'wiki';
                break;
            case 'file gallery':
                $filegallib = TikiLib::lib('filegal');
                $objectId = ! empty($_REQUEST['galleryId']) ? $_REQUEST['galleryId'] : null;
                if (is_array($objectId)) {
                    $objectId = reset($objectId);
                }
                $fileId = ! empty($_REQUEST['fileId']) ? $_REQUEST['fileId'] : null;
                if ($fileId) {
                    $parentId = $objectId;
                    $parentType = $objectType;
                    $objectId = $fileId;
                    $objectType = 'file';
                }
                $permType = 'file galleries';
                break;
            case 'tracker':
                $objectId = ! empty($_REQUEST['trackerId']) ? $_REQUEST['trackerId'] : null;
                $itemId = ! empty($_REQUEST['itemId']) ? $_REQUEST['itemId'] : null;
                if ($itemId) {
                    $item = Tracker_Item::fromId($itemId);
                    if ($item && ($info = $item->getInfo())) {
                        $parentId = $info['trackerId'];
                        $parentType = $objectType;
                        $objectId = $itemId;
                        $objectType = 'trackeritem';
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
                }
                $permType = 'trackers';
                break;
            case 'forum':
                $objectId = ! empty($_REQUEST['forumId']) ? $_REQUEST['forumId'] : null;
                $threadId = ! empty($_REQUEST['comments_parentId']) ? $_REQUEST['comments_parentId'] : null;
                if (! $threadId) {
                    $threadId = ! empty($_REQUEST['threadId']) ? $_REQUEST['threadId'] : null;
                }
                if ($threadId) {
                    $thread_info = TikiLib::lib('comments')->get_comment($threadId);
                    $parentId = $thread_info['object'];
                    $parentType = $objectType;
                    $objectId = $threadId;
                    $objectType = 'thread';
                }
                $permType = 'forums';
                break;
            case 'group':
                $objectId = ! empty($_REQUEST['group']) ? $_REQUEST['group'] : null;
                break;
            case 'article':
                $objectId = ! empty($_REQUEST['articleId']) ? $_REQUEST['articleId'] : null;
                $topicId = ! empty($_REQUEST['topic']) ? $_REQUEST['topic'] : null;
                if ($objectId) {
                    $article_data = TikiLib::lib('art')->get_article($objectId);
                    $parentId = $article_data['topicId'];
                    $parentType = 'topic';
                } elseif ($topicId) {
                    $objectId = $topicId;
                    $objectType = 'topic';
                }
                $permType = 'articles';
                break;
            case 'blog':
                $objectId = ! empty($_REQUEST['blogId']) ? $_REQUEST['blogId'] : null;
                $postId = ! empty($_REQUEST['postId']) ? $_REQUEST['postId'] : null;
                if ($postId) {
                    $post_info = TikiLib::lib('blog')->get_post($postId);
                    $parentId = $post_info['blogId'];
                    $parentType = $objectType;
                    $objectId = $postId;
                    $objectType = 'blog post';
                }
                $permType = 'blogs';
                break;
            case 'calendar':
                $objectId = ! empty($_REQUEST['calendarId']) ? $_REQUEST['calendarId'] : null;
                if (! $objectId && isset($_REQUEST['calIds']) && is_array($_REQUEST['calIds']) && count($_REQUEST['calIds']) == 1) {
                    $objectId = reset($_REQUEST['calIds']);
                }
                $calitemId = ! empty($_REQUEST['calitemId']) ? $_REQUEST['calitemId'] : $jitRequest->calitemId->int();
                if ($calitemId) {
                    $calitem = TikiLib::lib('calendar')->get_item($calitemId);
                    $parentId = $calitem['calendarId'];
                    $parentType = $objectType;
                    $objectId = $calitemId;
                    $objectType = 'calendaritem';
                }
                $permType = 'calendar';
                break;
            case 'sheet':
                $objectId = ! empty($_REQUEST['sheetId']) ? $_REQUEST['sheetId'] : null;
                break;
        }

        $all = TikiLib::lib('user')->get_permissions(0, -1, 'permName_asc', '', $this->findPermType($objectType), '', true);

        $accessor = \Perms::get(['type' => $objectType, 'object' => $objectId]);
        $loaded = $accessor->getResolver()->dump();

        $results = [];

        foreach ($all['data'] as $permDef) {
            foreach ($loaded['perms'] as $perm => $groups) {
                if ($perm != str_replace('tiki_p_', '', $permDef['name'])) {
                    continue;
                }
                $results[$permDef['type']][$perm] = $groups;
            }
        }

        $loaded['perms'] = $results;

        $smarty = TikiLib::lib('smarty');
        $smarty->loadPlugin('smarty_function_permission_link');
        if ($loaded['from'] == 'parent object' && $parentId && $parentType) {
            $loaded['edit_link'] = smarty_function_permission_link([
                'mode' => 'icon',
                'type' => $parentType,
                'permType' => $permType,
                'id' => $parentId,
            ], $smarty->getEmptyInternalTemplate());
        } elseif ($loaded['from'] == 'object' && $permType) {
            $loaded['edit_link'] = smarty_function_permission_link([
                'mode' => 'icon',
                'type' => $objectType,
                'permType' => $permType,
                'id' => $objectId,
            ], $smarty->getEmptyInternalTemplate());
        } elseif ($loaded['from'] == 'category') {
            $affecting_categories = TikiLib::lib('categ')->get_object_categories($objectType, $objectId);
            $links = [];
            foreach ($affecting_categories as $categId) {
                $links[] = smarty_function_permission_link([
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
                $links[] = smarty_function_permission_link([
                    'mode' => 'icon',
                    'type' => 'category',
                    'permType' => $permType,
                    'id' => $categId,
                ], $smarty->getEmptyInternalTemplate());
            }
            $loaded['edit_link'] = implode(' ', $links);
        } elseif ($loaded['from'] == 'global') {
            $loaded['edit_link'] = smarty_function_permission_link([
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
                if (strpos($url, $path) !== false) {
                    return $object;
                }
            }
        }

        if (strpos($url, 'tiki-ajax_services.php') !== false && $_REQUEST['controller'] == 'calendar') {
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
                return 'file galleries';
            case 'tracker':
            case 'trackeritem':
                return 'trackers';
            case 'forum':
                return 'forums';
            case 'group':
                return 'group';
            case 'articles':
                return 'articles';
            case 'blog':
                return 'blogs';
            case 'calendar':
                return 'calendar';
            case 'sheet':
                return 'sheet';
        }
    }
}
