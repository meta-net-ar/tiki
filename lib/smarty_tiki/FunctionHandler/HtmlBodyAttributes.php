<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/* return the attributes for a standard tiki page body tag
 * jonnyb refactoring for tiki5
 * eromneg adding additional File Gallery popup body class
 */

 namespace SmartyTiki\FunctionHandler;

use Smarty\FunctionHandler\Base;
use Smarty\Template;

class HtmlBodyAttributes extends Base
{
    public function handle($params, Template $template)
    {
        global $section, $prefs, $page, $section_class, $user;
        $smarty = \TikiLib::lib('smarty');
        $back = '';
        $onload = '';
        $class = 'tiki' . (isset($params['class']) ? ' ' . $params['class'] : '');

        //filename of script called (i.e. tiki-index, tiki-user_information, tiki-view_forum, etc), then sanitize chars
        $script_filename = pathinfo($_SERVER['SCRIPT_NAME'], PATHINFO_FILENAME);
        $class .= ' ' . filter_var($script_filename, FILTER_SANITIZE_SPECIAL_CHARS, [FILTER_FLAG_STRIP_LOW, FILTER_FLAG_STRIP_HIGH]);

        if (isset($section_class)) {
            $class .= ' ' . $section_class;
        }

        // To distinguish legacy admin and UAB for style purposes; can remove when legacy admin is removed. - g_c-l
        if ($prefs['theme_unified_admin_backend'] === 'y') {
            $class .= ' uab';
        }

        if ($prefs['feature_fixed_width'] == 'y') {
            $class .= ' fixed_width';
        }

        if (! empty($_REQUEST["page"]) && strtolower($_REQUEST["page"]) === 'sandbox') {
            $class .= ' sandbox ';
        }

        if ($prefs['site_layout']) {
            $class .= ' layout_' . $prefs['site_layout'];
        }

        if (! empty($_REQUEST['filegals_manager'])) {
            $class .= ' filegal_popup';
        }

        if (isset($_SESSION['fullscreen']) && $_SESSION['fullscreen'] == 'y') {
            $class .= empty($class) ? ' ' : '';
            $class .= ' fullscreen';
        }

        if (! empty($_COOKIE['sidebar_collapsed'])) {
            $class .= empty($class) ? ' ' : '';
            $class .= ' sidebar_collapsed';
        }

        if (isset($prefs['layout_add_body_group_class']) && $prefs['layout_add_body_group_class'] === 'y') {
            if (empty($user)) {
                $class .= ' grp_Anonymous';
            } elseif (\TikiLib::lib('user')->user_is_in_group($user, 'Registered')) {
                $class .= ' grp_Registered';
                if (\TikiLib::lib('user')->user_is_in_group($user, 'Admins')) {
                    $class .= ' grp_Admins';
                }
            }
        }

        if ($prefs['feature_perspective'] == 'y') {
            $perspectivelib = \TikiLib::lib('perspective');
            $perspectiveId = 0;
            $perspectiveName = '';
            if (! empty($_SESSION['current_perspective'])) {
                $perspectiveId = $_SESSION['current_perspective'];
                $perspectiveName = $_SESSION['current_perspective_name'];
            } elseif (empty($_SESSION['current_perspective']) && $perspectivelib->get_current_perspective($prefs) && $perspectivelib->get_current_perspective($prefs) > 0) {
                $perspectiveId = $perspectivelib->get_current_perspective($prefs);
                $perspectiveName = $perspectivelib->get_perspective_name($perspectivelib->get_current_perspective($prefs));
                $perspectivelib->set_perspective($perspectiveId);
            }
            if ($perspectiveId > 0) {
                $class .= ' perspective' . $perspectiveId;
                $class .= ' perspective_' . preg_replace("/[^a-z0-9]/", "_", strtolower($perspectiveName));
            }
        }

        if ($categories = $smarty->getTemplateVars('objectCategoryIds')) {
            foreach ($categories as $cat) {
                if (in_array($cat, $prefs['categories_add_class_to_body_tag'])) {
                    $class .= ' cat_' . str_replace(' ', '-', \TikiLib::lib('categ')->get_category_name($cat));
                }
            }
        }

        if (! empty($page) && $page == $prefs['tikiIndex']) {
            $class .= ' homepage';
        }

        if (! empty($pageLang)) {
            $class .= ' lang_' . $pageLang;
        } else {
            $class .= ' lang_' . $prefs['language'];
        }

        if (getCookie('hide_zone_left')) {
            $class .= ' hide_zone_left';
        }

        if (getCookie('hide_zone_right')) {
            $class .= ' hide_zone_right';
        }

        if ($prefs['wiki_make_ordered_list_items_display_unique_numbers'] === 'y') {
            $class .= ' uol'; // add class to display all the ordered lists sub-items indented with unique numbering like "1.2", "1.2.1", etc.
        }

        if (! empty($onload)) {
            $back .= ' onload="' . $onload . '"';
        }

        if (! empty($class)) {
            $back .= ' class="' . $class . '"';
        }

        return $back;
    }
}
