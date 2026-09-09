<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * @return array
 */
function module_action_similarcontent_info()
{
    return [
        'name' => tra('Similar Content'),
        'description' => tra('Find similar content based on tags.'),
        'prefs' => ["feature_freetags"],
        'params' => [
            'contentType' => [
                'name' => tra('Similar Content Filter'),
                'description' => tra('Display only similar content of type specified'),
                'options' => [
                    ['text' => tra('Default: All content types'), 'value' => ''],
                    ['text' => tra('Article'), 'value' => 'article'],
                    ['text' => tra('Wiki page'), 'value' => 'wiki page'],
                    ['text' => tra('Blog post'), 'value' => 'blog post'],
                ],
            ],
            'broaden' => [
                'name' => tra('Broaden FreeTag Search'),
                'description' => tra('Find similar content that contains one of the Tags or All of the Tags'),
                'options' => [
                    ['text' => tra('Default: Needs to contain all tags'), 'value' => ''],
                    ['text' => tra('Needs to contain all tags'), 'value' => 'n'],
                    ['text' => tra('Needs to contain one of the tags'), 'value' => 'y'],
                ],
            ],
        ],
        'common_params' => ['nonums', 'rows']
    ];
}

/**
 * @param $mod_reference
 * @param $module_params
 */
function module_action_similarcontent($mod_reference, $module_params)
{
    $smarty = TikiLib::lib('smarty');
    $freetaglib = TikiLib::lib('freetag');

    $filterType = '';
    if (isset($module_params['contentType'])) {
        $filterType = $module_params['contentType'];
    }

    $broaden = 'n';
    if (isset($module_params['broaden'])) {
        $broaden = $module_params['broaden'];
    }

    $currentContentType = "article";
    if (isset($_REQUEST['articleId'])) {
        $currentContentType = "article";
        $contentId = $_REQUEST['articleId'];
    } else {
        if (isset($_REQUEST['postId'])) {
            $currentContentType = "blog post";
            $contentId = $_REQUEST['postId'];
        } else {
            if (isset($_REQUEST['page'])) {
                $currentContentType = "wiki page";
                $contentId = $_REQUEST['page'];
            }
        }
    }

    if (isset($contentId)) {
        $tags = $freetaglib->get_tags_on_object($contentId, $currentContentType);
        $allTags = [];
        foreach ($tags['data'] as $tag) {
            $allTags[] = $tag['tag'];
        }

        $similarContent = $freetaglib->get_objects_with_tag_combo($allTags, $filterType, '', 0, $mod_reference['rows'], 'name_asc', '', $broaden);
        $relatedExclusiveContent = [];

        foreach ($similarContent['data'] as $item) {
            if ($item['type'] != $currentContentType) {
                $relatedExclusiveContent[] = $item;
            } else {
                if ($item['itemId'] != $contentId) {
                    $relatedExclusiveContent[] = $item;
                }
            }
        }
        $smarty->assign('similarContent', $relatedExclusiveContent);
    }

    //$smarty->assign('modLastBlogPosts', $ranking["data"]);
}
