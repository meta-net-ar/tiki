<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
require_once('tiki-setup.php');

//this script may only be included - so its better to die if called directly.
if (str_contains($_SERVER['SCRIPT_NAME'], basename(__FILE__))) {
    header('location: index.php');
    exit;
}

global $base_url, $prefs, $tikipath;
global $tikilib;

$access->check_permission('tiki_p_admin');
$access->check_feature('sitemap_enable');

$sitemap = new Tiki\Sitemap\Generator();

$listPages = $tikilib->list_pages();
if ($prefs['wiki_customize_title_tag'] === 'y') {
    $attributelib = TikiLib::lib('attribute');
    $attributes = $attributelib->getAllAttributes('tiki.wiki.page_title');
    foreach ($listPages['data'] as &$page) {
        if (isset($attributes[$page['pageName']])) {
            $page['attribute_title'] = $attributes[$page['pageName']];
        } else {
            $page['attribute_title'] = "No content";
        }
    }
    $smarty->assign('attributes', $attributes);
}

if (isset($_REQUEST['rebuild'])) {
    $sitemap->generate($base_url);
    Feedback::success(tr('New sitemap created!'));
    $access->redirect('tiki-admin.php?page=seoprefs');
}

$smarty->assign('title', tr('Sitemap'));
$smarty->assign('listPages', $listPages['data']);
$smarty->assign('Url', $base_url . 'tiki-sitemap.php?file=' . $sitemap->getSitemapFilename());
$smarty->assign('sitemapAvailable', file_exists($sitemap->getSitemapPath(false)));
