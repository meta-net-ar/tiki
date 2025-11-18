<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
$section = 'wiki page';
$inputConfiguration = [
    [
        'staticKeyFilters'         => [
        'page_ref_id'              => 'int',                 //get
        'page'                     => 'pagename',            //get
        'printpages'               => 'string',              //get
        'printstructures'          => 'string',              //get
        'find'                     => 'string',              //post
        'addpage'                  => 'bool',                //post
        'removepage'               => 'bool',                //post
        'clearpages'               => 'bool',                //post
        'clearstructures'          => 'bool',                //post
        'addstructurepages'        => 'bool',                //post
        'addstructure'             => 'bool',                //post
        ],
        'staticKeyFiltersForArrays' => [
            'structureId'           => 'digits',       //post
            'pageName'              => 'pagename',     //post
            'selectedpages'         => 'pagename',     //post
        ],
    ],
];
require_once('tiki-setup.php');
$structlib = TikiLib::lib('struct');
$auto_query_args = ['page_ref_id', 'page', 'find', 'pageName', 'structureId', 'offset', 'printpages', 'printstructures'];

$access->check_feature('feature_wiki_multiprint');
$access->check_permission('tiki_p_view');
//get_strings tra('Multiple Print');
if (! isset($cookietab)) {
    $cookietab = '1';
}
if (! isset($_REQUEST['printpages']) && ! isset($_REQUEST['printstructures'])) {
    $printpages = [];
    $printstructures = [];
    if (isset($_REQUEST["page_ref_id"])) {
        $info = $structlib->s_get_page_info($_REQUEST['page_ref_id']);
        if (! empty($info)) {
            $printstructures[] = $_REQUEST['page_ref_id'];
        }
    } elseif (isset($_REQUEST["page"]) && $tikilib->page_exists($_REQUEST["page"])) {
        $printpages[] = $_REQUEST["page"];
    }
} else {
    if (isset($_REQUEST['printpages'])) {
        $printpages = json_decode(urldecode($_REQUEST['printpages']));
    } else {
        $printpages = [];
    }
    if (isset($_REQUEST['printstructures'])) {
        $printstructures = json_decode(urldecode($_REQUEST['printstructures']));
    } else {
        $printstructures = [];
    }
}
if (isset($_REQUEST["find"])) {
    $find = $_REQUEST["find"];
} else {
    $find = '';
}
$smarty->assign('find', $find);
if (isset($_REQUEST["addpage"])) {
    if (! isset($_REQUEST["pageName"])) {
        Feedback::error(tra('Please select at least one page'));
    } else {
        if (! in_array($_REQUEST["pageName"], $printpages)) {
            foreach ($_REQUEST['pageName'] as $value) {
                $printpages[] = $value;
            }
        }
    }
    $printstructures = [];
    $cookietab = 2;
}
if (isset($_REQUEST["removepage"])) {
    if (! isset($_REQUEST["selectedpages"])) {
        Feedback::warning(tra('Please select page to remove'));
    } else {
        foreach ($_REQUEST['selectedpages'] as $value) {
            unset($printpages[$value]);
        }
    }
    $printpages = array_merge($printpages);
    $cookietab = 2;
}
if (isset($_REQUEST["clearpages"])) {
    $printpages = [];
    $cookietab = 2;
}
if (isset($_REQUEST["clearstructures"])) {
    $printstructures = [];
}
if (isset($_REQUEST['addstructurepages'])) {
    if (! isset($_REQUEST["structureId"])) {
        Feedback::error(tra('Please select a structure'));
    } else {
        $struct = $structlib->get_subtree($_REQUEST["structureId"]);
        foreach ($struct as $struct_page) {
            // Handle dummy last entry
            if ($struct_page["pos"] != '' && $struct_page["last"] == 1) {
                continue;
            }
            $printpages[] = $struct_page["pageName"];
        }
    }
    $cookietab = 2;
}
if (isset($_REQUEST['addstructure'])) {
    $info = $structlib->s_get_page_info($_REQUEST['structureId']);
    if (! empty($info)) {
        $printstructures[] = $_REQUEST['structureId'];
    }
}
$smarty->assign_by_ref('printpages', $printpages);
$smarty->assign_by_ref('printstructures', $printstructures);
$form_printpages = urlencode(json_encode($printpages));
$smarty->assign_by_ref('form_printpages', $form_printpages);
$form_printstructures = urlencode(json_encode($printstructures));
$smarty->assign_by_ref('form_printstructures', $form_printstructures);
$pages = $tikilib->list_pageNames(0, -1, 'pageName_asc', $find);
$smarty->assign_by_ref('pages', $pages["data"]);
$structures = $structlib->list_structures(0, -1, 'pageName_asc', $find);
$smarty->assign_by_ref('structures', $structures["data"]);
foreach ($printstructures as $page_ref_id) {
    foreach ($structures['data'] as $struct) {
        if ($struct['page_ref_id'] == $page_ref_id) {
            $printnamestructures[] = $struct['pageName'];
            break;
        }
    }
}
$smarty->assign_by_ref('printnamestructures', $printnamestructures);

include_once('tiki-section_options.php');
// disallow robots to index page:
$smarty->assign('metatag_robots', 'NOINDEX, NOFOLLOW');
$smarty->assign('pdf_export', ($prefs['print_pdf_from_url'] != 'none') ? 'y' : 'n');
$smarty->assign('mid', 'tiki-print_pages.tpl');
$smarty->display("tiki.tpl");
