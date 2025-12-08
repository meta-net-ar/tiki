<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
$inputConfiguration = [[
    'staticKeyFiltersForArrays' => [
        'filter' => 'text',
        'sort_mode' => 'text',
    ],
    'catchAllUnset' => null,
]];

require_once 'tiki-setup.php';
$categlib = TikiLib::lib('categ');
$smarty = TikiLib::lib('smarty');
require_once 'lib/tree/BrowseTreeMaker.php';

$access->check_feature('feature_categories');

$ctall = $categlib->getCategories();


foreach ($ctall as &$c) {
    $url = htmlentities(
        'tiki-edit_categories.php?' . http_build_query(
            [
                'filter~categories' => $c['categId'],
            ]
        ),
        ENT_QUOTES,
        'UTF-8'
    );
    $name = htmlentities($c['name'], ENT_QUOTES, 'UTF-8');
    $perms = Perms::get('category', $c['categId']);
    $c['can_add'] = $perms->add_object ?: '';
    $c['can_remove'] = $perms->remove_object ?: '';
    $c['url'] = $url;
}

$smarty->assign('categories', $ctall);

$filter = $_REQUEST['filter'] ?? [];
$smarty->assign('filter', $filter);

if (count($filter)) {
    $unifiedsearchlib = TikiLib::lib('unifiedsearch');
    $query = $unifiedsearchlib->buildQuery($filter);
    if (isset($_REQUEST['sort_mode']) && $order = Search\Query\Order::parse($_REQUEST['sort_mode'])) {
        $query->setOrder($order);
    }
    $result = $query->search($unifiedsearchlib->getIndex());
    //do not list category objects since they cannot be recategorized on this page
    $resultArray = $result->getArrayCopy();
    $objectlib = TikiLib::lib('object');
    foreach ($resultArray as $key => $objectInfo) {
        if (
            $objectInfo['object_type'] === 'category' || ! in_array(
                $objectInfo['object_type'],
                TikiLib::lib('object')::get_supported_types()
            )
        ) {
            unset($resultArray[$key]);
        }
    }
    if (count($resultArray) < $result->count()) {
        //need to recreate Search_ResultSet object so that count, etc. are accurate
        $result = $result::create($resultArray);
    }
    $smarty->assign('result', $result);
    //display what filters have been applied
    $filtersApplied = $filter;
    foreach ($filtersApplied as $type => $value) {
        if (empty($value)) {
            unset($filtersApplied[$type]);
        }
    }
    $filterString = '';
    $i = 1;
    $appliedCount = count($filtersApplied);
    if ($appliedCount) {
        foreach ($filtersApplied as $type => $value) {
            if ($i) {
                $filterString .= ' ';
            }
            $filterString .= htmlspecialchars($type) . ' = ' . htmlspecialchars($value);
            if ($i < $appliedCount) {
                $filterString .= ' AND';
            }
            $i++;
        }
    } else {
        $filterString .= tr('No filters applied');
    }
    $smarty->assign('filterString', $filterString);
    $smarty->assign('filterCount', $appliedCount);
}
// }}}

$smarty->assign('mid', 'tiki-edit_categories.tpl');
$smarty->display('tiki.tpl');
