<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\FunctionHandler;

use Smarty\FunctionHandler\Base;
use Smarty\Template;
use TikiLib;

/** params
 * - link_on_section
 * - css = use suckerfish menu
 * - type = vert|horiz
 * - id = menu ID (mandatory)
 * - translate = y|n , n means no option translation (default y)
 * - menu_cookie=y|n (default y) n, it will automatically open the submenu the url is in
 * - bs_menu_class='' custom class for the top level bootstrap menu element
 * - sectionLevel: displays from this level only
 * - toLevel : displays to this level only
 * - drilldown ??
 * - bootstrap : navbar|basic (equates to horiz or vert in old menus)
 * - setSelected=y|n (default=y) processes all menu items to show currently selected item, also sets open states, sectionLevel, toLevel etc
 *                                 so menu_cookie, sectionLevel and toLevel will be ignored if this is set to n
 */
class Menu extends Base
{
    public function handle($params, Template $template)
    {
        global $prefs;
        global $tikilib;

        $smarty = TikiLib::lib('smarty');
        $default = ['css' => 'y'];
        if (isset($params['params'])) {
            $params = array_merge($params, $params['params']);
            unset($params['params']);
        }
        $params = array_merge($default, $params);
        extract($params, EXTR_SKIP);

        if (empty($link_on_section) || $link_on_section == 'y') {
            $smarty->assign('link_on_section', 'y');
        } else {
            $smarty->assign('link_on_section', 'n');
        }

        if (empty($translate)) {
            $translate = 'y';
        }
        $smarty->assign_by_ref('translate', $translate);

        if (empty($menu_cookie)) {
            $menu_cookie = 'y';
        }
        $smarty->assign_by_ref('menu_cookie', $menu_cookie);

        if (empty($bs_menu_class)) {
            $bs_menu_class = '';
        }
        $smarty->assign_by_ref('bs_menu_class', $bs_menu_class);

        list($menu_info, $channels) = $this->getMenuWithSelections($params);
        $smarty->assign('menu_channels', $channels['data'] ?? []);
        $smarty->assign('menu_info', $menu_info);

        $objectCategories = \TikiLib::lib('categ')->get_current_object_categories();

        if ($objectCategories) {
            $categGroups = array_values(
                array_filter(
                    array_map(
                        function ($categId) {
                            $categ = \TikiLib::lib('categ')->get_category($categId);
                            $parent = \TikiLib::lib('categ')->get_category($categ["parentId"]);
                            if (! $parent || $parent["parentId"] != 0 || ! $parent["tplGroupContainerId"]) {
                                return null;
                            }
                            $templatedgroupid = \TikiLib::lib('attribute')->get_attribute("category", $categId, "tiki.category.templatedgroupid");
                            $tplGroup = \TikiLib::lib('user')->get_groupId_info($templatedgroupid);
                            if (empty($tplGroup['groupName'])) {
                                return null;
                            }
                            return [$parent["tplGroupContainerId"] => $tplGroup['groupName']];
                        },
                        $objectCategories
                    ),
                    function ($group) {
                        return $group != null;
                    }
                )
            );
        } else {
            $categGroups = [];
        }

        if (isset($params['bootstrap']) && $params['bootstrap'] !== 'n' && $prefs['javascript_enabled'] === 'y') {
            $structured = [];

            // Unification with structure menus - adds sectionLevel
            if (empty($menu_info['structure']) and ! empty($channels['data'])) {
                $channels['data'] = $this->addSectionLevelsToMenuData($channels['data']);
            }

            if (! empty($channels['data'])) {
                // Builds Menus nested tree of options
                foreach ($channels['data'] as $element) {
                    $attribute = \TikiLib::lib('attribute')->get_attribute('menu', $element["optionId"], 'tiki.menu.templatedgroupid');
                    if ($attribute && $catName = $categGroups[$attribute]) {
                        $element["name"] = str_replace("--groupname--", $catName, $element["name"]);
                        $element["url"] = str_replace("--groupname--", $catName, $element["name"]);
                        $element["sefurl"] = str_replace("--groupname--", $catName, $element["sefurl"]);
                        $element["canonic"] = str_replace("--groupname--", $catName, $element["canonic"]);
                    } elseif ($attribute && ! $categGroups[$attribute]) {
                        continue;
                    }

                    if ($element['type'] !== '-') {
                        $level = $element['sectionLevel'];
                        // Creates new branch at level 0
                        if ($level === 0) {
                            array_push($structured, $element);
                            continue;
                        }

                        // Always selects last branch at level 0
                        $branch = &$structured[count($structured) - 1];

                        // Selects nested part of the branch at element level
                        for ($i = 0; $i < $level - 1; $i++) {
                            if ($branch['children']) {
                                $branch = &$branch['children'][count($branch['children']) - 1];
                            }
                        }

                        // Pushes the element at the end of selected element children.
                        if (! empty($branch['children'])) {
                            array_push($branch['children'], $element);
                        } else {
                            $branch['children'] = [$element];
                        }
                    }
                }
            }

            $smarty->assign('list', $structured);
            $menu_icons = $tikilib->fetchAll('SELECT optionId, url, icon FROM tiki_menu_options');
            $iconset_pref = $prefs['theme_iconset'];
            $menu_icons_searchable = [];
            foreach ($menu_icons as $key => $value) {
                $default_icon = "";
                switch ($iconset_pref) {
                    case "bootstrap_icon_font":
                        $default_icon = "<i class='menu-icon bi bi-pencil'></i>";
                        if ($value['icon']) {
                            $icoArray = iconset_bootstrap_icon_font();
                            if (array_key_exists($value['icon'], $icoArray['icons'])) {
                                $specific_one = $icoArray['icons'][$value['icon']]['id']; #value['icon] is the iconname and id is the icon id to append to bi bi-
                                $iconForThisMenu = "<i class='menu-icon bi bi-$specific_one' ></i>";
                            }
                        }
                        $menu_icons_searchable[$value['optionId']] = $iconForThisMenu ?? $default_icon;
                        unset($iconForThisMenu); #unset to detach reflexion on next iteration
                        break;
                    case "legacy":
                        $default_icon = "<img class='menu-icon' src='img/icons/pencil.png'/>"; #get prepend from any icon within the set
                        $icon = \TikiLib::lib('iconset')->getIconsetForTheme($prefs['theme_iconset'], "")->getIcon($value['icon'], true);
                        if ($icon) {
                            if (count($icon) == 4) { //legacy icons has 4 meta datas
                                $icon_html = "<img class='menu-icon' src='" . $icon["prepend"] . $icon["id"] . $icon["append"] . "'/>";
                            }
                        }
                        $menu_icons_searchable[$value['optionId']] = $icon_html ?? $default_icon;
                        unset($icon_html);
                        break;
                    case "theme_specific_iconset":
                        $default_icon = "<i class='menu-icon fas fa-pencil'></i>";
                        $icon = \TikiLib::lib('iconset')->getIconsetForTheme($prefs['theme_iconset'], "")->getIcon($value['icon'], true);
                        if ($icon) {
                            if (count($icon) == 2) {
                                $icon_html = "<i class='menu-icon " . $icon["prepend"] . $icon["id"] . "'></i>";
                            }
                        }
                        $menu_icons_searchable[$value['optionId']] = $icon_html ?? $default_icon;
                        unset($icon_html);
                        break;
                    case "default": //default is fontwasemome
                        $default_icon = "<i class='menu-icon fas fa-pencil'></i>";
                        $icon = \TikiLib::lib('iconset')->getIconsetForTheme($prefs['theme_iconset'], "")->getIcon($value['icon'], true);
                        if ($icon) {
                            if (count($icon) == 2) {
                                $icon_html = "<i class='menu-icon " . $icon["prepend"] . $icon["id"] . "'></i>";
                            }
                        }
                        $menu_icons_searchable[$value['optionId']] = $icon_html ?? $default_icon;
                        unset($icon_html);
                        break;
                }
            }
            switch ($iconset_pref) {
                case "bootstrap_icon_font":
                    $icons = iconset_bootstrap_icon_font();
                    $icons_html = [];
                    foreach ($icons['icons'] as $iconName => $meta) {
                        foreach ($meta as $key => $iconID) {
                            if ($icons['prepend']) {
                                array_push($icons_html, [$iconName, "<i class='" . $icons['prepend'] . $iconID . "'></i>"]);
                            }
                        }
                    }
                    $icons_html_json = json_encode($icons_html);
                    break;
                case "legacy":
                    $icons = \TikiLib::lib('iconset')->getIconsetForTheme($prefs['theme_iconset'], "")->icons();
                    $icons_html = [];
                    foreach ($icons as $iconName => $meta) {
                        if ($meta['prepend'] and  $meta['append']) {
                            array_push($icons_html, [$iconName, "<img src='" . $meta['prepend'] . $meta['id'] . $meta['append'] . "'/>"]);
                        }
                    }
                    $icons_html_json = json_encode($icons_html);
                    break;
                case "theme_specific_iconset":
                    $icons = \TikiLib::lib('iconset')->getIconsetForTheme($prefs['theme_iconset'], "")->icons();
                    $icons_html = [];
                    foreach ($icons as $iconName => $meta) {
                        $prepend = $meta['prepend'] ?? "";
                        if ($prepend) {
                            array_push($icons_html, [$iconName, "<i class='" . $prepend . $meta['id'] . "'></i>"]);
                        }
                    }
                    $icons_html_json = json_encode($icons_html);
                    break;
                case "default": //default is fontwasemome
                    $icons = \TikiLib::lib('iconset')->getIconsetForTheme($prefs['theme_iconset'], "")->icons();
                    $icons_html = [];
                    foreach ($icons as $iconName => $meta) {
                        $prepend = $meta['prepend'] ?? "";
                        if ($prepend) {
                            array_push($icons_html, [$iconName, "<i class='" . $prepend . $meta['id'] . "'></i>"]);
                        }
                    }
                    $icons_html_json = json_encode($icons_html);
                    break;

                default: //when pref is not within the mentionned 4 cases
                    $icons_html_json = json_encode([]);
                    break;
            }
            $smarty->assign('menu_icons_html', $menu_icons_searchable);
            $smarty->assign('icon_picker_set', $icons_html_json);
            return $smarty->fetch('bootstrap_menu.tpl');
        }
        if ($params['css'] !== 'n' && $prefs['feature_cssmenus'] == 'y') {
            static $idCssmenu = 0;
            if (! isset($css_id)) { //adding $css_id parameter to customize menu id and prevent automatic id renaming when a menu is removed
                $smarty->assign('idCssmenu', $idCssmenu++);
            } else {
                $smarty->assign('idCssmenu', $css_id);
            }

            if (empty($params['type'])) {
                $params['type'] = 'vert';
            }
            $smarty->assign('menu_type', $params['type']);

            if (! empty($drilldown) && $drilldown == 'y') {
                $smarty->assign('drilldownmenu', 'y');
            }

            $tpl = 'tiki-user_cssmenu.tpl';
        } else {
            $tpl = 'tiki-user_menu.tpl';
        }

        $tpl = 'tiki-user_menu.tpl';
        $data = $smarty->fetch($tpl);
        return \MenuLib::clean_menu_html($data);
    }

    /**
    * Helper method to get proper link of a given menu item
    *
    * @param String $url_from_db The current url saved within the database
    *
    * @return String The new parsed link
    */

    public static function helperFunctionContructMenuUrlFromDb($url_from_db)
    {
        if (preg_match('/^\(\(([^()]+)\)\)$/', $url_from_db)) { //as page url are save as ((V-Log)) this should match the regex
            $match = [];
            preg_match("/[^()]+/", $url_from_db, $match);
            $page_name = $match[0];
            $page_name = str_replace(" ", "-", $page_name);
            return "tiki-index.php?page=" . $page_name;
        }
        return $url_from_db;
    }

    public static function compareMenuOptions($a, $b)
    {
        return strcmp(tra($a['name']), tra($b['name']));
    }

    public static function getMenuWithSelections($params)
    {
        global $user, $prefs;
        $tikilib = TikiLib::lib('tiki');
        $menulib = TikiLib::lib('menu');
        $cachelib = TikiLib::lib('cache');
        $cacheName = isset($prefs['mylevel']) ? $prefs['mylevel'] : 0;
        $cacheName .= '_' . $prefs['language'] . '_' . md5(implode("\n", $tikilib->get_user_groups($user)));

        extract($params, EXTR_SKIP);

        if (isset($structureId)) {
            $cacheType = 'structure_' . $structureId . '_';
        } else {
            if (array_key_exists('id', $params)) {
                $cacheType = 'menu_' . $id . '_';
            } else {
                \Feedback::error('The menu ID is not provided');
                $id = 0;
                $cacheType = 'menu_' . $id . '_';
            }
        }

        if ($cdata = $cachelib->getSerialized($cacheName, $cacheType)) {
            list($menu_info, $channels) = $cdata;
        } elseif (! empty($structureId)) {
            $structlib = TikiLib::lib('struct');

            if (! is_numeric($structureId)) {
                $structureId = $structlib->get_struct_ref_id($structureId);
            }

            $channels = $structlib->build_subtree_toc($structureId);
            $structure_info = $structlib->s_get_page_info($structureId);
            $channels = $structlib->to_menu($channels, $structure_info['pageName'], 0, 0, $params);
            $menu_info = ['type' => 'd', 'menuId' => $structureId, 'structure' => 'y'];
        } elseif (! empty($id)) {
            $menu_info = $menulib->get_menu($id);
            $channels = $menulib->list_menu_options($id, 0, -1, 'position_asc', '', '', isset($prefs['mylevel']) ? $prefs['mylevel'] : 0);
            $channels = $menulib->sort_menu_options($channels);
        } else {
            return '<span class="alert-warning">menu function: Menu or Structure ID not set</span>';
        }
        if (strpos($_SERVER['SCRIPT_NAME'], 'tiki-register') === false) {
            $cachelib->cacheItem($cacheName, serialize([$menu_info, $channels]), $cacheType);
        }
        if (! isset($setSelected) || $setSelected !== 'n') {
            $channels = $menulib->setSelected($channels, isset($sectionLevel) ? $sectionLevel : '', isset($toLevel) ? $toLevel : '', $params);
        }

        foreach ($channels['data'] as &$item) {
            if (! empty($menu_info['parse']) && $menu_info['parse'] === 'y') {
                //    $item['block'] = TikiLib::lib('parser')->contains_html_block($item['name']); // Only used for CSS menus
                $item['name'] = preg_replace('/(.*)\n$/', '$1', $item['name']); // parser adds a newline to everything
            }
        }

        return [$menu_info, $channels];
    }

    public static function addSectionLevelsToMenuData($data)
    {
        $sectionLevel = 0;
        $prev_type = null;
        $new_data = array_map(function ($menu_item) use (&$sectionLevel, &$prev_type) {
            if ($menu_item['type'] === 's') {
                $sectionLevel = 0;
            } elseif (($prev_type === 's' || is_numeric($prev_type)) && $menu_item['type'] === 'o') {
                $sectionLevel++;
            } elseif ($menu_item['type'] === '-') {
                if ($sectionLevel - 1 >= 0) {
                    $sectionLevel--;
                }
            } elseif (is_numeric($menu_item['type'])) {
                $sectionLevel = (int)$menu_item['type'];
            }
            $prev_type = $menu_item['type'];
            $menu_item['sectionLevel'] = $sectionLevel;

            return $menu_item;
        }, $data);
        return $new_data;
    }
}
