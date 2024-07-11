<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

/**
 * Embeddable Tiki pages with access control via AuthTokens
 */

$inputConfiguration = [
    [
        'staticKeyFilters' => [
            'page' => 'pagename',
            'TOKEN' => 'string',
        ],
        'catchAllUnset' => null
    ],
];

require_once("tiki-setup.php");

$wikilib = TikiLib::lib('wiki');
$statslib = TikiLib::lib('stats');
require_once('lib/wiki/renderlib.php');

$access->check_feature('feature_wiki');

$page = $wikilib->get_page_by_slug($jitRequest->page->pagename());
$info = $tikilib->get_page_info($page);

$smarty->assign_by_ref('page', $page);

// Process page display options
$wikilib->processPageDisplayOptions(TikiLib::lib('header'));

$smarty->assign('fullscreen', 'n');

$page = $info['pageName'];
if ($prefs['wiki_customize_title_tag'] === 'y') {
    $attributelib = TikiLib::lib('attribute');
    $attributes = $attributelib->get_attribute('wiki page', $page, "tiki.wiki.page_title");
    if ($attributes !== false) {
        $smarty->assign('tagTitle', $attributes);
    }
}

$pageRenderer = new WikiRenderer($info, $user);
$objectperms = $pageRenderer->applyPermissions();

$pageCache = Tiki_PageCache::create()
    ->disableForRegistered()
    ->onlyForGet()
    ->requiresPreference('memcache_wiki_output')
    ->addValue('role', 'wiki-page-output')
    ->addValue('page', $page)
    ->addValue('locale', $prefs['language'])
    ->addKeys($_GET, array_keys($_GET))
    ->checkMeta('wiki-page-output-meta-timestamp', ['page' => $page,])
    ->dieAndOutputOrStore();

// Now check permissions to access this page
if (! $pageRenderer->canView) {
    $access->display_error($page, tra('You do not have permission to view this page.'), '401');
}

// Last modified header
if ($prefs['wiki_last_modified_header'] == 'y') {
    $wikilib->addToPageHttpHeaders($info);
}

// Now increment page hits since we are visiting this page
$tikilib->add_hit($page);

//add a hit
$statslib->stats_hit($page, 'wiki');
if ($prefs['feature_actionlog'] == 'y') {
    $logslib->add_action('Viewed', $page);
}

// Display the Index Template
$pageRenderer->runSetups();

header("Access-Control-Allow-Origin: *");
echo (string) $smarty->getTemplateVars('parsed');

TikiLib::events()->trigger(
    'tiki.wiki.view',
    array_merge(
        (is_array($info) ? $info : []),
        [
            'type' => 'wiki page',
            'object' => $page,
            'user' => $GLOBALS['user'],
        ]
    )
);
