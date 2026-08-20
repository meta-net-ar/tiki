<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

global $prefs, $smarty, $base_url, $tikiroot;


$tikilib = TikiLib::lib('tiki');

function get_item_id_from_custom_url(string $url) {
    // parse url, if has page=? then use it as itemID
    // if no page then we take everythin after $tikiroot or $base_url as itemID (user can have included scheme then base_url become usefull or just path then tikiroot is enough)
    global $base_url, $tikiroot;
    $parsedUrl = parse_url($url);
    if (isset($parsedUrl['query'])){
        parse_str($parsedUrl['query'], $queryParams);
        if (isset($queryParams['page'])) {
            return $queryParams['page'];
        }
    }
    if(str_starts_with($url, $base_url)) {
        return substr($url, strlen($base_url));
    }
    if(str_starts_with($url, $tikiroot)) {
        return substr($url, strlen($tikiroot));
    }
    return $url;
}

if (! empty($_POST['redirects'])) {
    $redirects = json_decode($_POST['redirects'], true);
    if (is_array($redirects)) {
        $tikilib->query('DELETE FROM `tiki_object_relations` WHERE relation = ?', ['tiki.link.alias']);
        foreach ($redirects as $redirect) {
            $sourceMode = $redirect['source_mode'] ?? 'existing';
            $targetMode = $redirect['target_mode'] ?? 'existing';

            if ($sourceMode === 'custom') {
                $sourceType = 'custom';
                $sourceItemId = $redirect['custom_source'] ? get_item_id_from_custom_url($redirect['custom_source']) : '';
            } else {
                $sourceType = $redirect['source_type'] ?? 'wiki page';
                $sourceItemId = $redirect['source_itemId'] ?? '';
            }

            if ($targetMode === 'custom') {
                $targetType = 'custom';
                $targetItemId = $redirect['custom_target'] ? get_item_id_from_custom_url($redirect['custom_target']) : '';
            } else {
                $targetType = $redirect['target_type'] ?? 'wiki page';
                $targetItemId = $redirect['target_itemId'] ?? '';
            }

            if (empty($sourceItemId) || empty($targetItemId)) {
                continue;
            }

            $redirectCode = (int) ($redirect['redirect_code'] ?? 301);
            if (! in_array($redirectCode, [301, 302, 307, 308], true)) {
                $redirectCode = 301;
            }

            $tikilib->query(
                'INSERT INTO `tiki_object_relations`
                    (`relation`, `source_type`, `source_itemId`, `source_fieldId`, `target_type`, `target_itemId`, `metadata_itemId`)
                    VALUES (?, ?, ?, ?, ?, ?, ?)',
                ['tiki.link.alias', $sourceType, $sourceItemId, $redirectCode, $targetType, $targetItemId, null]
            );
        }
    }
}

$list_pages = $tikilib->list_pages();

$object_relations = $tikilib->fetchAll('SELECT * FROM `tiki_object_relations` WHERE relation = ?', ['tiki.link.alias']);
foreach ($object_relations as &$relation) {
    $relation['redirect_code'] = (int) ($relation['source_fieldId'] ?? 301);
    if ($relation['source_type'] === 'custom') {
        $relation['source_mode'] = 'custom';
        $relation['custom_source'] = $relation['source_itemId'];
    } else {
        $relation['source_mode'] = 'existing';
        $relation['custom_source'] = '';
    }
    if ($relation['target_type'] === 'custom') {
        $relation['target_mode'] = 'custom';
        $relation['custom_target'] = $relation['target_itemId'];
    } else {
        $relation['target_mode'] = 'existing';
        $relation['custom_target'] = '';
    }
}
unset($relation);
$tiki_links = $tikilib->fetchAll('SELECT * FROM `tiki_links`');
$objects = $tikilib->fetchAll('SELECT * FROM `tiki_objects` WHERE `type` IN (?, ?, ?)', ['wiki page', 'article', 'blog post']);

$wiki_pages = array_filter($objects, fn($object) => $object['type'] === 'wiki page');
$articles = array_filter($objects, fn($object) => $object['type'] === 'article');
$blog_posts = array_filter($objects, fn($object) => $object['type'] === 'blog post');

TikiLib::lib('header')->add_js_module('
import "@vue-mf/root-config"; 
import \'@vue-mf/redirect-builder\'');

TikiLib::lib('header')->add_jq_onready('
window.registerApplication({
    name: "@vue-mf/redirect-builder",
    app: () => importShim("@vue-mf/redirect-builder"),
    activeWhen: (location) => {
        let condition = true;
        return condition;
    },
    customProps: {
        wikiPages: \'' . ($wiki_pages ? json_encode($wiki_pages) : '') . '\',
        articles: \'' . ($articles ? json_encode($articles) : '') . '\',
        blogPosts: \'' . ($blog_posts ? json_encode($blog_posts) : '') . '\',
        baseUrl: \'' . $base_url . '\',
        redirects: \'' . ($object_relations ? json_encode($object_relations) : '') . '\',
    },
})



onDOMElementRemoved("single-spa-application:@vue-mf/redirect-builder", function () {
    window.unregisterApplication("@vue-mf/redirect-builder");
});');


$smarty->assign('object_relations', $object_relations);
$smarty->assign('tiki_links', $tiki_links);
$smarty->assign('wiki_pages', $wiki_pages);
$smarty->assign('articles', $articles);
$smarty->assign('blog_posts', $blog_posts);
$smarty->assign('page_list', $list_pages);
$smarty->assign('base_url', $base_url);