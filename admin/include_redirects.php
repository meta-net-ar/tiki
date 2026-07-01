<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

global $prefs, $smarty, $base_url;

$tikilib = TikiLib::lib('tiki');
$list_pages = $tikilib->list_pages();

$object_relations = $tikilib->fetchAll('SELECT * FROM `tiki_object_relations` WHERE relation = ?', ['tiki.link.alias']);
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