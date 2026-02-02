<?php

/**
 * @package tikiwiki
 */

use Tiki\ObjectRenderer\ObjectList;

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
require_once 'tiki-setup.php';
$categlib = TikiLib::lib('categ');

$access->check_feature('feature_print_indexed');

$inputConfiguration = [
    ['staticKeyFilters' => [
        'list' => 'alpha',
        'comments' => 'alpha',
    ] ],
    ['staticKeyFiltersForArrays' => [
        'languages' => 'alpha',
        'categId' => 'digits',
    ] ],
    [ 'catchAllUnset' => null ],
];

if (! isset($_GET['list']) || ! in_array($_GET['list'], ['categorylist', 'glossary'])) {
    $access->display_error('tiki-print_indexed.php', tra('Missing object list type argument'));
}

$objectList = new ObjectList();
$objectList->addCustomIndex('title');
$indexPages = [];

switch ($_GET['list']) {
    case 'categorylist':
        $access->check_feature('feature_categories');

        if (isset($_GET['categId'])) {
            $categId = (int) $_GET['categId'];
            $objects = $categlib->list_category_objects($categId, 0, -1, 'name_asc', '', '', true, false);

            $indexPages[] = [
                    'key' => 'title',
                    'indextitle' => tra('Index'),
                    'options' => [
                            'decorator' => 'indexrow',
                            'display' => 'title',
                    ],
            ];

            foreach ($objects['data'] as $index => $values) {
                $type = $values['type'];
                $item = $values['itemId'];
                $objectList->add($type, $item, []);
            }
        }
        break;

    case 'glossary':
        if (isset($_REQUEST['languages'])) {
            $languages = (array)$_REQUEST['languages'];
        } else {
            $languages = [Language::getCurrentLanguage()];
        }

        $filterLang = reset($languages);
        foreach ($languages as $num => $code) {
            $key = 'lang_title_' . $code;

            if ($num > 0) {
                $objectList->addCustomIndex($key);
            } else {
                $key = 'title';
            }

            $indexPages[] = [
                'key' => $key,
                'indextitle' => tr('Index (%0)', $code),
                'options' => [
                    'decorator' => 'indexrow',
                    'display' => 'title',
                    'languages' => [$code],
                ],
            ];
        }

        $filter = [ 'lang' => $filterLang ];

        if (isset($_GET['categId'])) {
            $access->check_feature('feature_categories');
            $filter['categId'] = $_GET['categId'];
        }

        $pages = $tikilib->list_pages(0, -1, 'pageName_asc', '', '', true, true, false, false, $filter);

        foreach ($pages['data'] as $info) {
            $objectList->add('wiki page', $info['pageName'], ['languages' => $languages]);
        }

        break;
}

$objectList->finalize();

$smarty->display('header.tpl');
$smarty->display('print/print-page_header.tpl');

foreach ($indexPages as $page) {
    $smarty->assign('indextitle', $page['indextitle']);
    $smarty->display('print/print-index_header.tpl');
    $objectList->render($smarty, $page['key'], $page['options']);
    $smarty->display('print/print-index_footer.tpl');
}

// Display all data
$objectList->render(
    $smarty,
    null,
    [
        'decorator' => 'indexed',
        'display' => 'object',
        'comments' => $_REQUEST['comments'] == 'y',
    ]
);

$smarty->display('print/print-page_footer.tpl');
$smarty->display('footer.tpl');
