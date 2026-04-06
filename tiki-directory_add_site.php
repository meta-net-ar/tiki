<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
$inputConfiguration = [
    [
        'staticKeyFilters'            => [
            'parent'                  => 'int',       // post
            'addtocat'                => 'int',       // get
            'siteId'                  => 'int',       // post
            'save'                    => 'bool',      // post
            'name'                    => 'striptags', // post
            'url'                     => 'url',       // post
            'isValid'                 => 'word',      // post
            'description'             => 'striptags', // post
            'country'                 => 'string',    // post
            'sort_mode'               => 'word',      // get
            'offset'                  => 'int',       // get
            'find'                    => 'string',    // get
        ],
        'staticKeyFiltersForArrays'   => [
            'siteCats'                => 'int',       // post
        ],
    ],
];
require_once('tiki-setup.php');
include_once('lib/directory/dirlib.php');
use Tiki\Sections;
$section = Sections::SECTION_DIRECTORY;
Sections::setCurrentSection($section);
$access->check_feature('feature_directory');
$access->check_permission('tiki_p_submit_link');
//get_strings tra('Submit a new link')
// If no parent category then the parent category is 0
if (! isset($_REQUEST["parent"])) {
    $_REQUEST["parent"] = 0;
}
// If no site category then the site category is -1
if (! isset($_REQUEST["addtocat"])) {
    $_REQUEST["addtocat"] = - 1;
}
$smarty->assign('parent', $_REQUEST["parent"]);
$smarty->assign('addtocat', $_REQUEST["addtocat"]); // tells directory_add_site which category to select in menu list
$all = 0;
if ($_REQUEST["parent"] == 0) {
    $parent_name = 'Top';
    $all = 1;
} else {
    $parent_info = $dirlib->dir_get_category($_REQUEST['parent']);
    $parent_name = $parent_info['name'];
}
$smarty->assign('parent_name', $parent_name);
if (isset($parent_info) && $user) {
    if (in_array($parent_info['editorGroup'], $userlib->get_user_groups($user))) {
        $tiki_p_autosubmit_link = 'y';
        $smarty->assign('tiki_p_autosubmit_link', 'y');
    }
}
// Now get the path to the parent category
$path = $dirlib->dir_get_category_path_admin($_REQUEST["parent"]);
$smarty->assign_by_ref('path', $path);

$_REQUEST["siteId"] = $_REQUEST["siteId"] ?? 0;
$info = [];
if ($_REQUEST["siteId"]) {
    $info = $dirlib->dir_get_site($_REQUEST["siteId"]);
}

if (empty($info)) {
    $info["name"] = '';
    $info["description"] = '';
    $info["url"] = '';
    $info["country"] = 'None';
    $info["isValid"] = 'y';
}

$smarty->assign('siteId', $_REQUEST["siteId"]);
$smarty->assign_by_ref('info', $info);
$smarty->assign('save', 'n');
// Replace (add or edit) a site
if (isset($_REQUEST["save"])) {
    $access->checkCsrf();
    $msg = "";
    if (empty($user) && $prefs['feature_antibot'] == 'y' && ! $captchalib->validate()) {
        $msg .= $captchalib->getErrors();
    }
    if (empty($_REQUEST["name"])) {
        $msg .= tra("Must enter a name to add a site. ");
    }
    if (empty($_REQUEST["url"])) {
        $msg .= tra("Must enter a url to add a site. ");
    } else {
        if (! str_starts_with($_REQUEST["url"], 'http://') && ! str_starts_with($_REQUEST["url"], 'https://')) {
            $_REQUEST["url"] = 'http://' . $_REQUEST["url"];
        }
        if ($dirlib->dir_url_exists($_REQUEST['url'])) {
            $msg .= tra("URL already added to the directory. Duplicate site? ");
        }
        if ($prefs['directory_validate_urls'] == 'y') {
            @$fsh = fopen($_REQUEST['url'], 'r');
            if (! $fsh) {
                $msg .= tra("URL cannot be accessed wrong URL or site is offline and cannot be added to the directory. ");
            }
        }
    }
    if (! isset($_REQUEST["siteCats"]) || count($_REQUEST["siteCats"]) == 0) {
        $msg .= tra("Must select a category. ");
    }
    if (isset($_REQUEST["isValid"]) && $_REQUEST["isValid"] == 'on') {
        $_REQUEST["isValid"] = 'y';
    } else {
        $_REQUEST["isValid"] = 'n';
    }
    if ($tiki_p_autosubmit_link == 'y') {
        $_REQUEST["isValid"] = 'y';
    }
    if ($msg == "") { // no error
        $siteId = $dirlib->dir_replace_site($_REQUEST["siteId"], $_REQUEST["name"], $_REQUEST["description"], $_REQUEST["url"], $_REQUEST["country"], $_REQUEST["isValid"]);
        $dirlib->remove_site_from_categories($siteId);
        foreach ($_REQUEST["siteCats"] as $acat) {
            $dirlib->dir_add_site_to_category($siteId, $acat);
        }
        $info["isValid"] = 'y';
        $smarty->assign('save', 'y');
    } else {
        $info["isValid"] = 'n';
        Feedback::warning($msg);
    }
    $info = [];
    $info["name"] = $_REQUEST['name'];
    $info["description"] = $_REQUEST['description'];
    $info["url"] = $_REQUEST['url'];
    $info["country"] = $_REQUEST['country'];
    $smarty->assign('siteId', 0);
}
// Listing: categories in the parent category
// Pagination resolution
if (! isset($_REQUEST["sort_mode"])) {
    $sort_mode = 'created_desc';
} else {
    $sort_mode = $_REQUEST["sort_mode"];
}
$offset = $_REQUEST["offset"] ?? 0;
$find = $_REQUEST["find"] ?? '';
$smarty->assign_by_ref('offset', $offset);
$smarty->assign_by_ref('sort_mode', $sort_mode);
$smarty->assign('find', $find);
// What are we paginating: items
if ($all) {
    $items = $dirlib->dir_list_all_sites($offset, $maxRecords, $sort_mode, $find);
} else {
    $items = $dirlib->dir_list_sites($_REQUEST["parent"], $offset, $maxRecords, $sort_mode, $find, $isValid = '');
}
$pages_count = ceil($items["count"] / $maxRecords);
$smarty->assign_by_ref('pages_count', $pages_count);
$smarty->assign('actual_page', 1 + ($offset / $maxRecords));
if ($items["count"] > ($offset + $maxRecords)) {
    $smarty->assign('next_offset', $offset + $maxRecords);
} else {
    $smarty->assign('next_offset', -1);
}
if ($offset > 0) {
    $smarty->assign('prev_offset', $offset - $maxRecords);
} else {
    $smarty->assign('prev_offset', -1);
}
$smarty->assign_by_ref('items', $items["data"]);
$categs = $dirlib->dir_get_all_categories_accept_sites(0, -1, 'name asc', $find, $_REQUEST["siteId"]);
if (isset($_REQUEST["save"]) && $msg != "" && isset($_REQUEST["siteCats"])) { // an error occurred, the chosen categs have to be set again
    $temp_max = count($categs);
    foreach ($_REQUEST["siteCats"] as $acat) {
        for ($ix = 0; $ix < $temp_max; ++$ix) {
            if ($categs[$ix]["categId"] == $acat) {
                $categs[$ix]["belongs"] = 'y';
            }
        }
    }
}
$smarty->assign('categs', $categs);
$countries = $tikilib->get_flags();
usort($countries, 'country_sort');
$smarty->assign_by_ref('countries', $countries);
// This page should be displayed with Directory section options
include_once('tiki-section_options.php');
// Display the template
$smarty->assign('mid', 'tiki-directory_add_site.tpl');
$smarty->display("tiki.tpl");
/**
 * @param $a
 * @param $b
 * @return int
 */
function country_sort($a, $b)
{
    if ($a == 'None' || $b == 'Other') {
        return -1;
    } elseif ($b == 'None' || $a == 'Other') {
        return 1;
    } else {
        return strcmp($a, $b);
    }
}
