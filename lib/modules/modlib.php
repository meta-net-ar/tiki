<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

use Tiki\Lib\Wiki\ConvertToTiki9;
use Tiki\Profiling\Timer;
use Tiki\Lib\CookieConsent\CookieConsentLib;
use Tiki\Sections;

/**
 *
 */
class ModLib extends TikiLib
{
    public $pref_errors = [];
    private bool $moduleAssignmentMode = false;

    // additional module zones added to this array will be exposed to tiki.tpl
    // TODO change modules user interface to enable additional zones
    public $module_zones = [
        'top' => 'top_modules',
        'topbar' => 'topbar_modules',
        'pagetop' => 'pagetop_modules',
        'left' => 'left_modules',
        'right' => 'right_modules',
        'pagebottom' => 'pagebottom_modules',
        'bottom' => 'bottom_modules',
        'admin' => 'admin_modules',
    ];

    public $cssfiles  = [
        'calendar_new'  => [
            'csspath'   => THEMES_BASE_FILES_FEATURE_CSS_PATH . '/calendar.css',
            'rank'      => 20,
        ],
        'action_calendar'   => [
            'csspath'   => THEMES_BASE_FILES_FEATURE_CSS_PATH . '/calendar.css',
            'rank'      => 20,
        ],
    ];

    public function __construct()
    {
        global $prefs;

        if (! empty($prefs['module_zone_available_extra'])) {
            foreach (array_filter((array) $prefs['module_zone_available_extra']) as $name) {
                $this->module_zones[$name] = $name . '_modules';
            }
        }
    }

    /**
     * @param      $name
     * @param      $title
     * @param      $data
     * @param null $parse
     *
     * @return Tiki\TikiDb\PdoResult
     * @throws Exception
     */
    public function replace_user_module($name, $title, $data, $parse = null, $tgt_mod = null)
    {
        global $prefs;

        if ((! empty($name)) && (! empty($data))) {
            $query = 'INSERT INTO `tiki_user_modules` (`name`, `title`, `data`, `parse`) VALUES (?, ?, ?, ?)';

            $params = [
                $name,
                $title,
                $data,
                $parse,
            ];

            if (! empty((string) $tgt_mod)) {
                $query = 'UPDATE `tiki_user_modules` SET `name` = ?, `title` = ?, `data` = ?, `parse` = ? WHERE `name` = ?';
                $params[] = $tgt_mod;
            }

            $result = $this->query($query, $params);

            $cachelib = TikiLib::lib('cache');
            $cachelib->invalidate("user_modules_$name");

            $wikilib = TikiLib::lib('wiki');    // used to require lib/wiki/wikilib.php where convertToTiki9 lives
            $converter = new ConvertToTiki9();
            $converter->saveObjectStatus($name, 'tiki_user_modules', 'new9.0+');

            return $result;
        }
    }

    /**
     * @param int  $moduleId
     * @param      $name
     * @param      $title
     * @param      $position
     * @param      $order
     * @param int  $cache_time
     * @param int  $rows
     * @param null $groups
     * @param null $params
     * @param null $type
     *
     * @return bool
     * @throws Exception
     */
    public function assign_module($moduleId, $name, $title, $position, $order, $cache_time, $rows, $groups = null, $params = null, $type = null)
    {
        //check for valid values
        $cache_time = is_numeric($cache_time) ? $cache_time : 0;
        $rows = is_numeric($rows) ? $rows : 10;

        if (is_array($params)) {
            $params = $this->serializeParameters($name, $params);
        }

        if ($moduleId) {
            $query = "update `tiki_modules` set `name`=?,`title`=?,`position`=?,`ord`=?,`cache_time`=?,`rows`=?,`groups`=?,`params`=?,`type`=? where `moduleId`=?";
            $result = $this->query($query, [$name,$title,$position,(int) $order,(int) $cache_time,(int) $rows,$groups,$params,$type, $moduleId]);
            if (! $result || ! $result->numRows()) {
                $moduleId = false;
            }
        } else {
            $query = "delete from `tiki_modules` where `name`=? and `position`=? and `ord`=? and `params`=?";
            $this->query($query, [$name, $position, (int)$order, $params]);
            $query = "insert into `tiki_modules`(`name`,`title`,`position`,`ord`,`cache_time`,`rows`,`groups`,`params`,`type`) values(?,?,?,?,?,?,?,?,?)";
            $this->query($query, [$name,$title,$position,(int) $order,(int) $cache_time,(int) $rows,$groups,$params,$type]);
            $moduleId = $this->lastInsertId(); //to return the recently created module
            if ($type == "D" || $type == "P") {
                $query = 'select `moduleId` from `tiki_modules` where `name`=? and `title`=? and `position`=? and `ord`=? and `cache_time`=? and `rows`=? and `groups`=? and `params`=? and `type`=?';
                $moduleId = $this->getOne($query, [$name,$title,$position,(int) $order,(int) $cache_time,(int) $rows,$groups,$params,$type]);
            }
        }
        if ($type == "D" || $type == "P") {
            $usermoduleslib = TikiLib::lib('usermodules');
            $usermoduleslib->add_module_users($moduleId, $name, $position, $order, $type);
        }
        return $moduleId;
    }

    /* Returns the requested module assignation. A module assignation is represented by an array similar to a tiki_modules record. The groups field is unserialized in the module_groups key, a spaces-separated list of groups. */
    /**
     * @param $moduleId
     * @return mixed
     */
    public function get_assigned_module($moduleId)
    {
        $query = "select * from `tiki_modules` where `moduleId`=?";
        $result = $this->query($query, [$moduleId]);
        $res = $result->fetchRow();

        if ($res["groups"]) {
            $grps = unserialize($res["groups"]);
            if (! empty($grps)) {
                $res["module_groups"] = implode(' ', $grps);
            }
        }

        return $res;
    }

    /**
     * @param $moduleId
     * @return bool
     */
    public function unassign_module($moduleId)
    {
        $query = "delete from `tiki_modules` where `moduleId`=?";
        $result = $this->query($query, [$moduleId]);
        $query = "delete from `tiki_user_assigned_modules` where `moduleId`=?";
        $this->query($query, [$moduleId]);
        return $result && $result->numRows();
    }

    /**
     * @param $name
     * @return int
     */
    public function get_rows($name)
    {
        $query = "select `rows` from `tiki_modules` where `name`=?";

        $rows = $this->getOne($query, [$name]);

        if ($rows == 0) {
            $rows = 10;
        }

        return $rows;
    }

    /**
     * @param $moduleId
     *
     * @return Tiki\TikiDb\PdoResult
     */
    public function module_up($moduleId)
    {
        $query = "update `tiki_modules` set `ord`=`ord`-1 where `moduleId`=?";
        return $this->query($query, [$moduleId]);
    }

    /**
     * @param $moduleId
     *
     * @return Tiki\TikiDb\PdoResult
     */
    public function module_down($moduleId)
    {
        $query = "update `tiki_modules` set `ord`=`ord`+1 where `moduleId`=?";
        return $this->query($query, [$moduleId]);
    }

    /**
     * Sets position of module to left - this method does not appear to be used
     *
     * @param $moduleId
     *
     * @return Tiki\TikiDb\PdoResult
     */
    public function module_left($moduleId)
    {
        $query = "update `tiki_modules` set `position`='left' where `moduleId`=?";
        return $this->query($query, [$moduleId]);
    }

    /**
     * Sets position of module as right - this method does not appear to be used
     *
     * @param $moduleId
     *
     * @return Tiki\TikiDb\PdoResult
     */
    public function module_right($moduleId)
    {
        $query = "update `tiki_modules` set `position`='right' where `moduleId`=?";
        return $this->query($query, [$moduleId]);
    }

    /**
     * Reset all module ord's according to supplied array or by displayed order
     *
     * @param array $module_order [zone][moduleId] (optional)
     *
     * @return int
     */
    public function reorder_modules($module_order = [])
    {
        global $user;
        $all_modules = $this->get_modules_for_user($user, $this->module_zones);
        if (empty($module_order)) { // rewrite module order as displayed
            foreach ($all_modules as $zone => $contents) {
                $module_order[$zone] = [];
                foreach ($contents as $index => $module) {
                    $module_order[$zone][$index] = (int) $module['moduleId'];
                }
            }
        }
        $section_map = array_flip($this->module_zones);
        $bindvars = [];
        $query = 'UPDATE `tiki_modules` SET `ord`=?, `position`=? WHERE `moduleId`=?;';
        $i = 0;
        foreach ($module_order as $zone => $contents) {
            $section_initial = $section_map[$zone];
            foreach ($contents as $index => $moduleId) {
                if ($moduleId) {
                    $module = $all_modules[$zone][$index] ?? null;
                    if (
                        $module && (
                            $module['moduleId'] != $moduleId ||
                            $module['ord'] != $index + 1 ||
                            $module['position'] != $section_initial
                        )
                    ) {
                        $bindvars = [
                            $index + 1,
                            $section_initial,
                            $moduleId,
                        ];
                        $result = $this->query($query, $bindvars);
                        if ($result && $result->numRows()) {
                            $i = $i + $result->numRows();
                        }
                    }
                }
            }
        }
        return $i;
    }

    /**
     * @return array
     */
    public function get_all_modules()
    {
        $user_modules = $this->list_user_modules();

        $all_modules = [];

        foreach ($user_modules["data"] as $um) {
            $all_modules[] = $um["name"];
        }

        // Now add all the system modules
        $iterator = new FilesystemIterator(TEMPLATES_MODULES_PATH, FilesystemIterator::SKIP_DOTS);
        foreach ($iterator as $fileInfo) {
            $file = $fileInfo->getFilename();
            if (str_starts_with($file, 'mod-') && str_ends_with($file, '.tpl')) {
                if (! str_contains($file, "nocache")) {
                    // Remove 'mod-' (4 chars) from start and '.tpl' (4 chars) from end
                    $all_modules[] = substr($file, 4, -4);
                }
            }
        }

        return $all_modules;
    }

    /**
     * @param $name
     *
     * @return Tiki\TikiDb\PdoResult
     * @throws Exception
     */
    public function remove_user_module($name)
    {

        $query = "delete from `tiki_modules` where `name`=?";
        $this->query($query, [$name]);

        $query = " delete from `tiki_user_modules` where `name`=?";
        $result = $this->query($query, [$name]);

        $cachelib = TikiLib::lib('cache');
        $cachelib->invalidate('user_modules');

        return $result;
    }

    /**
     * @param string $sort_mode
     * @return array
     */
    public function list_user_modules($sort_mode = 'name_asc')
    {
        $query = "select * from `tiki_user_modules` order by " . $this->convertSortMode($sort_mode);

        $result = $this->query($query, []);
        $query_count = "select count(*) from `tiki_user_modules`";
        $count = $this->getOne($query_count, []);
        $ret = [];

        while ($res = $result->fetchRow()) {
            $ret[] = $res;
        }

        $retval = [];
        $retval["data"] = $ret;
        $retval["count"] = $count;
        return $retval;
    }

    /**
     * @return int
     */
    public function clear_cache()
    {
        global $tikidomain;
        $dircache = TEMP_CACHE_PATH;
        if ($tikidomain) {
            $dircache .= "/$tikidomain";
        }

        if (! is_dir($dircache)) {
            return 0;
        }

        $i = 0;
        $iterator = new FilesystemIterator($dircache, FilesystemIterator::SKIP_DOTS);

        foreach ($iterator as $fileInfo) {
            if (str_starts_with($fileInfo->getFilename(), 'mod-')) {
                // unlink() returns true on success
                if (unlink($fileInfo->getPathname())) {
                    $i++;
                }
            }
        }

        return $i;
    }

    /* @param module_info = info of a module
     * @param user_groups = list of groups of a user
     * @param user = the user
     * @return string 'y' = ok, 'n' = not ok
     */
    public function check_groups($module_info, $user, $user_groups)
    {
        global $prefs, $tiki_p_admin;
        if (empty($user)) {
            $user_groups = [ 'Anonymous' ];
        }
        $pass = 'y';
        if (
            $tiki_p_admin == 'y' && $prefs['modhideanonadmin'] == 'y' && $module_info['groups'] == serialize(['Anonymous']) &&
                strpos($_SERVER["SCRIPT_NAME"], 'tiki-admin_modules.php') === false
        ) {
            $pass = 'n';
        } elseif ($tiki_p_admin != 'y' && $prefs['modallgroups'] != 'y') {
            if ($module_info['groups']) {
                if (! is_array($module_info['groups'])) {
                    $module_groups = unserialize($module_info['groups']);
                } else {
                    $module_groups = $module_info['groups'];
                }
            } else {
                $module_groups = [];
            }
            if (! empty($module_groups)) {  // if no groups are set show to all users (modules revamp [MOD] in Tiki 7)
                $pass = 'n';
                if ($prefs['modseparateanon'] !== 'y') {
                    foreach ($module_groups as $mod_group) {
                        if (in_array($mod_group, $user_groups)) {
                            $pass = 'y';
                            break;
                        }
                    }
                } else {
                    if (! $user) {
                        if (in_array('Anonymous', $module_groups)) {
                            $pass = 'y';
                        }
                    } else {
                        foreach ($module_groups as $mod_group) {
                            if ($mod_group === 'Anonymous') {
                                continue;
                            }
                            if (in_array($mod_group, $user_groups)) {
                                $pass = 'y';
                                break;
                            }
                        }
                    }
                }
            }
        }
        return $pass;
    }

    /**
     * @param $user
     * @param array $module_zones
     * @return array
     */
    public function get_modules_for_user($user, array $module_zones = [])
    {
        if (empty($module_zones)) {
            $module_zones = $this->module_zones;
        }
        $list = $this->get_raw_module_list_for_user($user, $module_zones);

        foreach ($list as & $partial) {
            $partial = array_map([ $this, 'augment_module_parameters' ], $partial);
            if (! $this->is_admin_mode(true)) {
                $partial = array_values(array_filter($partial, [ $this, 'filter_active_module' ]));
            }
        }

        return $list;
    }

    /**
     * @param $module
     * @return mixed
     */
    public function augment_module_parameters($module)
    {
        global $prefs;

        parse_str($module['params'], $module_params);
        $default_params = [
            'decorations' => 'y',
            'overflow' => 'n',
            'nobox' => 'n',
            'notitle' => 'n',
            'error' => '',
            'flip' => ( $prefs['user_flip_modules'] == 'module' ) ? 'n' : $prefs['user_flip_modules'],
        ];

        if (! is_array($module_params)) {
            $module_params = [];
        }

        $module_params = array_merge($default_params, $module_params);

        $module_params['module_position'] = $module['position'];
        $module_params['module_ord'] = $module['ord'];

        if ($module['name'] == 'package' && ! empty($module_params['otherparams'])) {
            parse_str($module_params['otherparams'], $other_params);
            if (is_array($other_params)) {
                $module_params = $module_params + $other_params;
            }
        }

        if ($prefs['user_flip_modules'] === 'n') {
            $module_params['flip'] = 'n';
        }

        if (isset($module_params['section']) && $module_params['section'] == 'wiki') {
            $module_params['section'] = 'wiki page';
        }

        $module['params'] = $module_params;

        return $module;
    }

    /**
     * Decide if a module should be shown in the current request context.
     *
     * Applies module-level preferences, unified admin backend rules and
     * various visibility constraints (language, section, page, groups, etc.).
     * When the unified admin backend is enabled, non-admin modules are
     * normally hidden on control panel pages. Module assignment mode can
     * override this so modules can be displayed in the site layout context.
     *
     * @param array $module tiki_modules row with parsed params
     * @return bool true if the module should be displayed
     */
    public function filter_active_module($module)
    {
        global $page, $prefs, $user;
        $section = Sections::getCurrentSection();
        $tikilib = TikiLib::lib('tiki');
        $pref_module_name = 'module_' . preg_replace('/[^a-zA-Z0-9]/', '_', $module["name"]);

        if (! isset($prefs[$pref_module_name])) {
            return false;
        }

        if ($prefs[$pref_module_name] != 'y') {
            return false;
        }
        if (TikiLib::lib('login')->isSwitched() && isset($_SESSION["keep_login_box_visible"]) && $_SESSION["keep_login_box_visible"] == 'y') {
            if ($module["name"] === "login_box" && (! isset($module["mode"]) || isset($module["mode"]) === "module")) {
                return true;
            }
        }
        // Load only Unified Admin Interface specific modules for admins on admin panels.
        $topLogin = $module['name'] === 'login_box' && $module['position'] === 'top';
        $topLogo = $module['name'] === 'logo' && $module['position'] === 'top';
        $topQA = $module['name'] === 'quickadmin' && $module['position'] === 'top';
        $footer = $module['position'] === 'bottom';
        $isControlPanel = Sections::isCurrentSection(Sections::SECTION_ADMIN_LAYOUT);

        if (
            $prefs['theme_unified_admin_backend'] === 'y'
            && $isControlPanel
            && ! $this->isModuleAssignmentMode()
            && $module['position'] !== 'admin'
            && ! $topLogin
            && ! $topLogo
            && ! $footer
            && ! $topQA
        ) {
            return false;
        }

        // Validate preferences
        $module_info = $this->get_module_info($module['name']);
        $params = $module['params'];

        if ($prefs['feature_perspective'] == 'y') {
            $perspectivelib = TikiLib::lib('perspective');
            $persp = $perspectivelib->get_current_perspective($prefs);
            if (empty($persp)) {
                $persp = 0;
            }
            if (isset($params['perspective']) && ! in_array($persp, (array) $params['perspective'])) {
                return false;
            }
        }

        if (isset($params["lang"]) && ! in_array($prefs['language'], (array) $params["lang"])) {
            return false;
        }

        if (isset($params['section']) && ( ! isset($section)  || ! in_array($section, (array) $params['section']))) {
            return false;
        }

        if (isset($params['nopage']) && isset($page) && isset($section) && Sections::isCurrentSection(Sections::SECTION_WIKI_PAGE)) {
            if (in_array($page, (array) $params['nopage'])) {
                return false;
            }
        }

        if (isset($params['page'])) {
            if (! isset($section) || ! Sections::isCurrentSection(Sections::SECTION_WIKI_PAGE) || ! isset($page)) { // must be in a page
                return false;
            } elseif (! in_array($page, (array) $params['page'])) {
                return false;
            }
        }

        if (isset($params['theme'])) {
            global $tc_theme;

            $ok = false;
            foreach ((array) $params['theme'] as $t) {
                // remove any css extension
                $t = preg_replace('/\.css$/i', '', $t);
                if ($t[0] != '!') { // usual behavior
                    if (! empty($tc_theme) && $t === $tc_theme) {
                        $ok = true;
                    } elseif ($t === $prefs['theme'] && empty($tc_theme)) {
                        $ok = true;
                    }
                } else { // negation behavior
                    $excluded_theme = substr($t, 1);
                    $ok = true;
                    if (! empty($tc_theme) && $excluded_theme === $tc_theme) {
                        return false;
                    } elseif ($excluded_theme === $prefs['theme'] && empty($tc_theme)) {
                        return false;
                    }
                }
            }
            if (! $ok) {
                return false;
            }
        }

        if (! Perms::get()->admin) {
            $user_groups = Perms::get()->getGroups();
        } else {
            $user_groups = [];
        }

        if ('y' != $this->check_groups($module, $user, $user_groups)) {
            return false;
        }

        if (isset($params['creator']) && Sections::isCurrentSection(Sections::SECTION_WIKI_PAGE) && isset($page)) {
            if (! $page_info = $tikilib->get_page_info($page)) {
                return false;
            } elseif ($params['creator'] == 'y' && $page_info['creator'] != $user) {
                return false;
            } elseif ($params['creator'] == 'n' && $page_info['creator'] == $user) {
                return false;
            }
        }

        if (isset($params['contributor']) && Sections::isCurrentSection(Sections::SECTION_WIKI_PAGE) && isset($page)) {
            if (! $page_info = $tikilib->get_page_info($page)) {
                return false;
            } else {
                $wikilib = TikiLib::lib('wiki');
                $contributors = $wikilib->get_contributors($page);
                $contributors[] = $page_info['creator'];
                $in = in_array($user, $contributors);

                if ($params['contributor'] == 'y' && ! $in) {
                    return false;
                } elseif ($params['contributor'] == 'n' && $in) {
                    return false;
                }
            }
        }

        if ($module['name'] == 'login_box' && basename($_SERVER['SCRIPT_NAME']) == 'tiki-login_scr.php') {
            return false;
        }

        if ($prefs['feature_categories'] == 'y') {
            if ($this->is_hidden_by_category($params)) {
                return false;
            }

            if ($this->is_hidden_by_no_category($params)) {
                return false;
            }
        }

        if ($prefs['cookie_consent_feature'] == 'y' && $prefs['cookie_consent_disable'] !== 'y') {      // check if consent required to show
            if (! empty($params['cookie_consent']) && $params['cookie_consent'] === 'y') {
                if (! CookieConsentLib::isCategoryAllowed(CookieConsentLib::BUILTIN_COOKIE_CATEGORY_ESSENTIAL)) {
                    return false;
                }
            }
        }

        foreach ($module_info['prefs'] as $p) {
            if ($prefs[$p] != 'y') {
                $this->add_pref_error($module['name'], $p);
                return false;
            }
        }

        return true;
    }

    /**
     * @param $params
     * @return bool
     */
    private function is_hidden_by_category($params)
    {
        global $cat_type, $cat_objid;
        if (empty($params['category'])) {
            return false;
        }

        if (empty($cat_type) || empty($cat_objid)) {
            return true;
        }

        $catIds = TikiLib::lib('categ')->get_object_categories($cat_type, $cat_objid);

        if (empty($catIds)) {
            return true;
        }

        // Multi-value params of custom modules need transformation into an array
        if (is_array($params['category'])) {
            $categories = (array) $params['category'];
        } else {
            $categories = explode(';', $params['category']);
        }

        return ! $this->matches_any_in_category_list($categories, $catIds, ! empty($params['subtree']));
    }

    /**
     * @param $params
     * @return bool
     */
    private function is_hidden_by_no_category($params)
    {
        global $cat_type, $cat_objid;
        if (empty($params['nocategory'])) {
            return false;
        }

        if (empty($cat_type) || empty($cat_objid)) {
            return false;
        }

        $catIds = TikiLib::lib('categ')->get_object_categories($cat_type, $cat_objid);

        if (empty($catIds)) {
            return false;
        }

        // Multi-value params of custom modules need transformation into an array
        if (is_array($params['nocategory'])) {
            $categories = (array) $params['nocategory'];
        } else {
            $categories = explode(';', $params['nocategory']);
        }

        return $this->matches_any_in_category_list($categories, $catIds, ! empty($params['subtree']));
    }

    /**
     * @param $desiredList
     * @param $categoryList
     * @param bool $deep
     * @return bool
     */
    private function matches_any_in_category_list($desiredList, $categoryList, $deep = false)
    {
        if (empty($categoryList)) {
            return false;
        }

        $allcats = null;    // only needed if category names are used or $deep is set

        foreach ($desiredList as $category) {
            if (is_numeric($category)) {
                if (in_array($category, $categoryList)) {
                    return true;
                }
            } else {
                if (! $allcats) {
                    // gets all categories (cached but perms are checked so only load all if necessary)
                    $allcats = TikiLib::lib('categ')->getCategories();
                }
                foreach ($categoryList as $id) {
                    if (isset($allcats[$id]) && $allcats[$id]['name'] == $category) {
                        return true;
                    }
                }
            }
        }

        if ($deep) {
            $nextList = [];
            if (! $allcats) {
                // gets all categories (cached but perms are checked so only load all if necessary)
                $allcats = TikiLib::lib('categ')->getCategories();
            }
            foreach ($categoryList as $id) {
                if (isset($allcats[$id]) && $allcats[$id]['parentId']) {
                    $nextList[] = $allcats[$id]['parentId'];
                }
            }

            return $this->matches_any_in_category_list($desiredList, $nextList, $deep);
        }

        return false;
    }

    /**
     * @param       $user
     * @param array $module_zones
     *
     * @return array
     * @throws Exception
     */
    private function get_raw_module_list_for_user($user, array $module_zones)
    {
        global $prefs;
        $usermoduleslib = TikiLib::lib('usermodules');

        $out = array_fill_keys(array_values($module_zones), []);

        if (! empty($prefs['module_file'])) {
            $out = array_merge($out, $this->read_module_file($prefs['module_file']));
        } elseif (
            $prefs['user_assigned_modules'] == 'y'
            //need to use Perms class instead of $tiki_p_configure_modules global as the global is null
            //for some reason when feature_modulecontrols is not set
            && Perms::get()->configure_modules
            && $user
            && $usermoduleslib->user_has_assigned_modules($user)
        ) {
            foreach ($module_zones as $zone => $zone_name) {
                $out[$zone_name] = $usermoduleslib->get_assigned_modules_user($user, $zone);
            }
        } else {
            $modules_by_position = $this->get_assigned_modules(null, 'y');
            foreach ($module_zones as $zone => $zone_name) {
                if (isset($modules_by_position[$zone])) {
                    $out[$zone_name] = $modules_by_position[$zone];
                }
            }
        }

        return $out;
    }

    /**
     * @return array
     */
    public function list_module_files()
    {
        if (! is_dir(MODULES_PATH)) {
            return [];
        }

        $files = [];
        $iterator = new FilesystemIterator(MODULES_PATH, FilesystemIterator::SKIP_DOTS);
        foreach ($iterator as $fileInfo) {
            $filename = $fileInfo->getFilename();
            // Match mod-func-*.php
            if (str_starts_with($filename, 'mod-func-') && str_ends_with($filename, '.php')) {
                $files[] = $filename;
            }
        }

        sort($files);
        return $files;
    }


    /**
     * @param $module
     * @return array|mixed
     */
    public function get_module_info($module)
    {
        if (is_array($module)) {
            $moduleName = $module['name'];
        } else {
            $moduleName = $module;
        }

        global $prefs;

        $cachelib = TikiLib::lib('cache');
        $cacheKey = 'module.' . $moduleName . Language::getCurrentLanguage();
        $info = $cachelib->getSerialized($cacheKey, 'module');

        if ($info) {
            if (! isset($info['cachekeygen'])) {
                $info['cachekeygen'] = [ $this, 'createDefaultCacheKey' ];
            }
            return $info;
        }

        $phpfuncfile = MODULES_PATH . '/mod-func-' . $moduleName . '.php';
        $info_func = "module_{$moduleName}_info";
        $info = [];

        if ($phpfuncfile = $this->safeFileExistsInPath($phpfuncfile, MODULES_PATH)) {
            include_once $phpfuncfile;

            if (function_exists($info_func)) {
                $info = $info_func();
                if (! empty($info['params'])) {
                    foreach ($info['params'] as &$p) {
                        $p['section'] = 'module';
                    }
                }
            }

            $info['type'] = 'function';
        }

        $defaults = [
            'name' => $moduleName,
            'description' => tra('Description not available'),
            'type' => 'include',
            'prefs' => [],
            'params' => [],
        ];

        $info = array_merge($defaults, $info);

        $info['params'] = array_merge(
            $info['params'],
            [
                'title' => [
                    'name' => tra('Module Title'),
                    'description' => tra('Title to display at the top of the box.'),
                    'filter' => 'striptags',
                    'section' => 'appearance',
                ],
                'nobox' => [
                    'name' => tra('No Box'),
                    'description' => tra('Show only the content'),
                    'section' => 'appearance',
                    'options' => [
                        ['text' => tra('Default: No'), 'value' => ''],
                        ['text' => tra('Yes'), 'value' => 'y'],
                        ['text' => tra('No'), 'value' => 'n'],
                    ],
                ],
                'decorations' => [
                    'name' => tra('Backgrounds and Borders (if specified by the theme)'),
                    'description' => tra('"n": show only title and content.'),
                    'section' => 'appearance',
                    'options' => [
                        ['text' => tra('Default: Yes'), 'value' => ''],
                        ['text' => tra('Yes'), 'value' => 'y'],
                        ['text' => tra('No'), 'value' => 'n'],
                    ],
                ],
                'notitle' => [
                    'name' => tra('No Title'),
                    'description' => tra('Hide module title'),
                    'filter' => 'alpha',
                    'section' => 'appearance',
                    'options' => [
                        ['text' => tra('Default: No'), 'value' => ''],
                        ['text' => tra('Yes'), 'value' => 'y'],
                        ['text' => tra('No'), 'value' => 'n'],
                    ],
                ],
                'device' => [
                    'name' => tra('Display on device'),
                    'description' => tra('On which device the module must be displayed, by default it is displayed on all devices.'),
                    'since' => '24',
                    'section' => 'visibility',
                    'separator' => ';',
                    'advanced' => true,
                    'set_value_as_array' => true,
                    'options' => [
                        ['text' => tra('Hidden on all'), 'value' => 'HIDE_ALL'],
                        ['text' => tra('Hidden only on xs (<576px)'), 'value' => 'HIDE_XS'],
                        ['text' => tra('Hidden only on sm (576px–767px)'), 'value' => 'HIDE_SM'],
                        ['text' => tra('Hidden only on md (768px–991px)'), 'value' => 'HIDE_MD'],
                        ['text' => tra('Hidden only on lg (992px–1199px)'), 'value' => 'HIDE_LG'],
                        ['text' => tra('Hidden only on xl (1200px–1399px)'), 'value' => 'HIDE_XL'],
                        ['text' => tra('Hidden only on xxl (≥1400px)'), 'value' => 'HIDE_XXL'],
                        ['text' => tra('Visible on all'), 'value' => 'SHOW_ALL'],
                        ['text' => tra('Visible only on xs (<576px)'), 'value' => 'SHOW_XS'],
                        ['text' => tra('Visible only on sm (576px–767px)'), 'value' => 'SHOW_SM'],
                        ['text' => tra('Visible only on md (768px–991px)'), 'value' => 'SHOW_MD'],
                        ['text' => tra('Visible only on lg (992px–1199px)'), 'value' => 'SHOW_LG'],
                        ['text' => tra('Visible only on xl (1200px–1399px)'), 'value' => 'SHOW_XL'],
                        ['text' => tra('Visible only on xxl (≥1400px)'), 'value' => 'SHOW_XXL'],
                        ['text' => tra('Print only (Hide on screen)'), 'value' => 'SHOW_PRINT'],
                        ['text' => tra('Screen only (Hide on print)'), 'value' => 'SHOW_SCREEN'],
                    ],
                ],
                'category' => [
                    'name' => tra('Category'),
                    'description' => tra('Module displayed depending on category. Separate multiple category IDs or names by semi-colons.'),
                    'section' => 'visibility',
                    'separator' => ';',
                    'filter' => 'alnum',
                    'profile_reference' => 'category',
                ],
                'nocategory' => [
                    'name' => tra('No Category'),
                    'description' => tra('Module is hidden depending on category. Separate multiple category IDs or names by semi-colons. This takes precedence over the category parameter above.'),
                    'section' => 'visibility',
                    'separator' => ';',
                    'filter' => 'alnum',
                    'profile_reference' => 'category',
                ],
                'subtree' => [
                    'name' => tra('Category subtrees'),
                    'description' => tra('Consider child categories of the categories listed in "category" and "no category" to be part of those categories.'),
                    'section' => 'visibility',
                    'filter' => 'int',
                    'options' => [
                        ['text' => tra('Default: No'), 'value' => ''],
                        ['text' => tra('Yes'), 'value' => '1'],
                        ['text' => tra('No'), 'value' => '0'],
                    ],
                ],
                'perspective' => [
                    'name' => tra('Perspective'),
                    'description' => tra('Module is displayed only in the listed perspective ID(s). Separate multiple perspective IDs by semi-colons.'),
                    'separator' => ';',
                    'filter' => 'digits',
                    'section' => 'visibility',
                    'profile_reference' => 'perspective',
                ],
                'lang' => [
                    'name' => tra('Language'),
                    'description' => tra('Module is displayed only when the specified language(s) in use. Designate languages by two-character language codes. Separate multiple languages by semi-colons.'),
                    'separator' => ';',
                    'filter' => 'lang',
                    'section' => 'visibility',
                ],
                'section' => [
                    'name' => tra('Section'),
                    'description' => tra('Module is displayed only in the specified sections.'),
                    'separator' => ';',
                    'filter' => 'striptags',
                    'section' => 'visibility',
                    'options' => [
                        ['text' => tra('Blogs'), 'value' => 'blogs'],
                        ['text' => tra('Calendar'), 'value' => 'calendar'],
                        ['text' => tra('Categories'), 'value' => 'categories'],
                        ['text' => tra('Articles'), 'value' => 'cms'],
                        ['text' => tra('Contacts'), 'value' => 'contacts'],
                        ['text' => tra('Directory'), 'value' => 'directory'],
                        ['text' => tra('FAQs'), 'value' => 'faqs'],
                        ['text' => tra('Featured links'), 'value' => 'featured_links'],
                        ['text' => tra('File galleries'), 'value' => 'file_galleries'],
                        ['text' => tra('Forums'), 'value' => 'forums'],
                        ['text' => tra('Google Maps'), 'value' => 'gmaps'],
                        ['text' => tra('HTML pages'), 'value' => 'html_pages'],
                        ['text' => tra('Maps'), 'value' => 'maps'],
                        ['text' => tra('My Tiki'), 'value' => 'mytiki'],
                        ['text' => tra('Newsletters'), 'value' => 'newsletters'],
                        ['text' => tra('Polls'), 'value' => 'poll'],
                        ['text' => tra('Quizzes'), 'value' => 'quizzes'],
                        ['text' => tra('Surveys'), 'value' => 'surveys'],
                        ['text' => tra('Trackers'), 'value' => 'trackers'],
                        ['text' => tra('User messages'), 'value' => 'user_messages'],
                        ['text' => tra('Webmail'), 'value' => 'webmail'],
                        ['text' => tra('Wiki pages'), 'value' => 'wiki page'],
                    ],
                ],
                'page' => [
                    'name' => tra('Page Filter'),
                    'description' => tra('Module is displayed only on the specified page(s). Separate multiple page names by semi-colons.'),
                    'separator' => ';',
                    'filter' => 'pagename',
                    'section' => 'visibility',
                    'profile_reference' => 'wiki_page',
                ],
                'nopage' => [
                    'name' => tra('No Page'),
                    'description' => tra('Module is not displayed on the specified page(s). Separate multiple page names by semi-colons.'),
                    'separator' => ';',
                    'filter' => 'pagename',
                    'section' => 'visibility',
                    'profile_reference' => 'wiki_page',
                ],
                'theme' => [
                    'name' => tra('Theme'),
                    'description' => tra('Module is displayed or not displayed depending on the theme. (Enter the theme\'s file name, for example, "thenews.css".) Prefix the theme name with "!" for the module to not display. Separate multiple theme names by semi-colons.'),
                    'separator' => ';',
                    'filter' => 'themename',
                    'section' => 'visibility',
                ],
                'creator' => [
                    'name' => tra('Creator'),
                    'description' => tra('Module only available based on the relationship of the user with the wiki page.'),
                    'filter' => 'alpha',
                    'section' => 'visibility',
                    'options' => [
                        ['text' => tra('Default: Everyone'), 'value' => ''],
                        ['text' => tra('Only creators'), 'value' => 'y'],
                        ['text' => tra('Only non-creators'), 'value' => 'n'],
                    ],
                ],
                'contributor' => [
                    'name' => tra('Contributor'),
                    'description' => tra('Module only available based on the relationship of the user with the wiki page.'),
                    'filter' => 'alpha',
                    'section' => 'visibility',
                    'options' => [
                        ['text' => tra('Default: Everyone'), 'value' => ''],
                        ['text' => tra('Only contributors'), 'value' => 'y'],
                        ['text' => tra('Only non-contributors'), 'value' => 'n'],
                    ],
                ],
                'flip' => [
                    'name' => tra('Flip'),
                    'description' => tra('Users can open and close the module.'),
                    'filter' => 'alpha',
                    'section' => 'appearance',
                    'options' => [
                        ['text' => tra('Default: Module always open'), 'value' => ''],
                        ['text' => tra('Module initially open'), 'value' => 'y'],
                        ['text' => tra('Module initially closed'), 'value' => 'yc'],
                        ['text' => tra('Module always open'), 'value' => 'n'],
                    ],
                ],
                'style' => [
                    'name' => tra('Style'),
                    'description' => tra('CSS style attribute (for example, to position the module)'),
                    'section' => 'appearance',
                ],
                'class' => [
                    'name' => tra('Class'),
                    'description' => tra('Extra class (for CSS or JavaScript)'),
                    'section' => 'appearance',
                ],
                'topclass' => [
                    'name' => tra('Containing Class'),
                    'description' => tra('Custom CSS class of div around the module.'),
                    'section' => 'appearance',
                ],
            ]
        );

        if (($prefs['cookie_consent_feature'] ?? null) === 'y' && $prefs['cookie_consent_disable'] !== 'y') {
            $info['params']['cookie_consent'] = [
                'name' => tra('Cookie Consent'),
                'description' => tra('Show only if consent to accept cookies has been granted.'),
                'filter' => 'alpha',
                'section' => 'visibility',
                'options' => [
                    ['text' => tra('Default: Always shown'), 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n'],
                ],
            ];
        }

        // Parameters common to several modules, but not all
        $common_params = [
            'nonums' => [
                'name' => tra('No Numbers'),
                'description' => tra('Do not number list items.'),
                'section' => 'appearance',
                'options' => [
                    ['text' => tra('Default: No'), 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n'],
                ],
            ],
            'rows' => [
                'name' => tra('Rows'),
                'description' => tra('Number of rows, or items, to display.') . ' ' . tra('Default: 10.'),
                'section' => 'appearance',
            ]
        ];

        if ($info['type'] == 'function') {
            foreach ($common_params as $key => $common_param) {
                $info['params'][$key] = $common_param;
            }
        }

        // Parameters are not required, unless specified.
        if (! empty($info['params'])) {
            foreach ($info['params'] as &$param) {
                if (! isset($param['required'])) {
                    $param['required'] = false;
                }
            }
        }

        $cachelib->cacheItem($cacheKey, serialize($info), 'module');

        if (! isset($info['cachekeygen'])) {
            $info['cachekeygen'] = [ $this, 'createDefaultCacheKey' ];
        }

        return $info;
    }

    /**
     * @param $mod_reference
     * @return string
     */
    public function createDefaultCacheKey($mod_reference)
    {
        global $prefs;
        return $mod_reference['moduleId'] . '-' . $mod_reference['name'] . '-' . $prefs['language'] . '-' .
               serialize($mod_reference['params']) . (isset($_SESSION['current_perspective']) ? '-p' . $_SESSION['current_perspective'] : '');
    }

    /**
     * @param $mod_reference
     *
     * @return bool|mixed|null|string|string[]
     * @throws SmartyException
     */

    public function execute_module($mod_reference)
    {
        global $prefs, $tiki_p_admin;
        $smarty = TikiLib::lib('smarty');

        try {
            $defaults = [
                'style' => '',
                'nonums' => 'n',
                'decorations' => 'y',
                'overflow' => 'n',
                'nobox' => 'n',
                'notitle' => 'n',
                'error' => '',
                'flip' => ( $prefs['user_flip_modules'] == 'module' ) ? 'n' : $prefs['user_flip_modules'],
            ];
            $module_params = (array) ($mod_reference['params'] ?? []);
            $module_params = array_merge($defaults, $module_params); // not sure why style doesn't get set sometime but is used in the tpl

            $mod_reference = array_merge(['moduleId' => null, 'ord' => 0, 'position' => 0, 'rows' => 10, 'cache_time' => 0], $mod_reference);

            $info = $this->get_module_info($mod_reference);
            $cachefile = $this->get_cache_file($mod_reference, $info);

            $prefslib = TikiLib::lib('prefs');

            foreach ((array) $info['prefs'] as $preference) {
                if ($prefs[$preference] != 'y') {
                    $this->add_pref_error($mod_reference['name'], $preference);
                    return \SmartyTiki\BlockHandler\Remarksbox::render(
                        [
                            'type' => 'warning',
                            'title' => tr('Failed to execute "%0" module', $mod_reference['name']),
                        ],
                        tr('Required features: ' . $preference . '. If you do not have permission to activate these features, ask the site administrator.'),
                        $smarty->getEmptyInternalTemplate(),
                        $repeat
                    );
                }
            }

            if (! $cachefile || $this->require_cache_build($mod_reference, $cachefile) || $this->is_admin_mode()) {
                if ($this->is_admin_mode()) {
                    $timer = new Timer();
                    $timer->start('module');
                }
                if ($info['type'] == "function") { // Use the module name as default module title. This can be overriden later. A module can opt-out of this in favor of a dynamic default title set in the TPL using clear_assign in the main module function. It can also be overwritten in the main module function.
                    $smarty->assign('tpl_module_title', tra($info['name']));
                }

                $smarty->assign('nonums', $module_params['nonums']);

                if ($info['type'] == 'include') {
                    $phpfile = MODULES_PATH . '/mod-' . $mod_reference['name'] . '.php';

                    if (file_exists($phpfile)) {
                        include $phpfile;
                    }
                } elseif ($info['type'] == 'function') {
                    $function = 'module_' . $mod_reference['name'];
                    $phpfuncfile = MODULES_PATH . '/mod-func-' . $mod_reference['name'] . '.php';

                    if (file_exists($phpfuncfile)) {
                        include_once $phpfuncfile;
                    }

                    if (function_exists($function)) {
                        $function($mod_reference, $module_params);
                    }
                }

                $ck = getCookie('mod-' . $mod_reference['name'] . $mod_reference['position'] . $mod_reference['ord'], 'menu', 'o');
                $smarty->assign('module_display', ($ck == 'o'));

                $smarty->assign_by_ref('module_rows', $mod_reference['rows']);
                $smarty->assign_by_ref('module_params', $module_params); // module code can unassign this if it wants to hide params
                $smarty->assign('module_ord', $mod_reference['ord']);
                $smarty->assign('module_position', $mod_reference['position']);
                $smarty->assign('moduleId', $mod_reference['moduleId']);
                if (isset($module_params['title'])) {
                    $smarty->assign('tpl_module_title', tra($module_params['title']));
                }
                $smarty->assign('tpl_module_name', $mod_reference['name']);

                $tpl_module_style = empty($mod_reference['module_style']) ? '' : $mod_reference['module_style'];

                if (
                    $tiki_p_admin == 'y' && $this->is_admin_mode() && (! $this->filter_active_module($mod_reference) ||
                            $prefs['modhideanonadmin'] == 'y' && (empty($mod_reference['groups']) || $mod_reference['groups'] == serialize(['Anonymous'])))
                ) {
                    $tpl_module_style .= 'opacity: 0.5;';
                }
                if (isset($module_params['overflow']) && $module_params['overflow'] === 'y') {
                    $tpl_module_style .= 'overflow:visible !important;';
                }
                $smarty->assign('tpl_module_style', $tpl_module_style);

                $template = MODULES_PATH . '/mod-' . $mod_reference['name'] . '.tpl';

                if (file_exists(SMARTY_TEMPLATES_PATH . '/' . $template)) {
                    $data = $smarty->fetch($template);
                } else {
                    $data = $this->get_user_module_content($mod_reference['name'], $module_params);
                }
                $smarty->clear_assign('module_params'); // ensure params not available outside current module
                $smarty->clear_assign('tpl_module_title');
                $smarty->clear_assign('tpl_module_name');
                $smarty->clear_assign('tpl_module_style');

                if ($this->is_admin_mode() && $timer) {
                    $elapsed = round($timer->stop('module'), 3);
                    $data = $data ?? '';
                    $data = preg_replace('/<div /', '<div title="Module Execution Time ' . $elapsed . 's" ', $data, 1);
                }

                if (! empty($cachefile) && ! $this->is_admin_mode()) {
                    file_put_contents($cachefile, $data);
                }
            } else {
                $data = file_get_contents($cachefile);
            }

            return $data;
        } catch (Exception $e) {
            if ($tiki_p_admin == 'y') {
                $message = $e->getMessage();
            } else {
                $message = tr('Contact the system administrator');
            }
            $repeat = false;
            return \SmartyTiki\BlockHandler\Remarksbox::render(
                [
                    'type' => 'warning',
                    'title' => tr('Failed to execute "%0" module', $mod_reference['name']),
                ],
                html_entity_decode($message),
                $smarty->getEmptyInternalTemplate(),
                $repeat
            );
        }
    }

    /**
     * Returns true when we are on the Admin Modules screen with permission.
     *
     * Used to detect the modules management context (tiki-admin_modules.php).
     * When $ifShowingHiddenModules is true, this only returns true if the
     * request also has show_hidden_modules set, which some modules use to
     * decide whether to render additional admin-only or placeholder output.
     *
     * @param bool $ifShowingHiddenModules also require show_hidden_modules in the request
     * @return bool
     */
    public function is_admin_mode($ifShowingHiddenModules = false)
    {
        global $tiki_p_admin_modules;

        $ok = true;
        if ($ifShowingHiddenModules && empty($_REQUEST['show_hidden_modules'])) {
            $ok = false;
        }
        return $ok && $tiki_p_admin_modules === 'y' &&
                strpos($_SERVER["SCRIPT_NAME"], 'tiki-admin_modules.php') !== false;
    }

    /**
     * Returns true when the current request is managing module assignments.
     *
     * This is used to override normal layout filtering so modules can be
     * displayed in context on tiki-admin_modules.php.
     *
     * @return bool
     */
    public function isModuleAssignmentMode()
    {
        return $this->moduleAssignmentMode;
    }

    /**
     * Enable or disable module assignment mode for the current request.
     *
     * @param bool $enabled
     * @return void
     */
    public function setModuleAssignmentMode($enabled)
    {
        $this->moduleAssignmentMode = (bool) $enabled;
    }

    /**
     * @param $name
     * @param $module_params
     * @return mixed
     */
    public function get_user_module_content($name, $module_params)
    {
        $smarty = TikiLib::lib('smarty');
        $tikilib = TikiLib::lib('tiki');
        $smarty->assign('module_type', 'module');
        $info = $this->get_user_module($name);
        if (! empty($info)) {
            $info = $this->parse($info);

            // re-assign module_params for the custom module in case a module plugin is used inside it
            // by value, not by ref, so a nested recursive call can't repoint it
            $smarty->assign('user_module_params', (array) $module_params);
            $smarty->assign('user_title', tra($info['title']));
            $smarty->assign_by_ref('user_data', $info['data']);
            $smarty->assign_by_ref('user_module_name', $info['name']);

            return $smarty->fetch('modules/user_module.tpl');
        }
    }

    /**
     * Parses custom module content if the module requires
     *
     * @param $info
     * @return mixed
     */
    public function parse($info)
    {
        if (isset($info['parse']) && $info['parse'] == 'y') {
            $parserlib = TikiLib::lib('parser');
            $info['data'] = $parserlib->parse_data($info['data'], [
                'is_html' => true,
                'suppress_icons' => true,
                'typography' => false,  // typography feature breaks quotes and causes smarty compiler errors, so disable it for custom modules
            ]);
            $info['title'] = $parserlib->parse_data($info['title'], [
                'noparseplugins' => true,
                'is_html' => true,
            ]);
        }

        return $info;
    }

    /**
     * @param $mod_reference
     * @param $info
     * @return null|string
     */
    public function get_cache_file($mod_reference, $info)
    {
        global $tikidomain, $user;
        $nocache = TEMPLATES_MODULES_PATH . '/mod-' . $mod_reference["name"] . '.tpl.nocache';

        // Uncacheable
        if (! empty($user) || (isset($mod_reference['cache_time']) && $mod_reference['cache_time'] <= 0) || file_exists($nocache)) {
            return null;
        }

        $cb = $info['cachekeygen'];

        $cachefile = TEMP_CACHE_PATH;
        if ($tikidomain) {
            $cachefile .= "$tikidomain/";
        }

        $cachefile .= 'mod-' . md5(call_user_func($cb, $mod_reference));

        return $cachefile;
    }

    // Returns whether $cachefile needs to be [re]built
    /**
     * @param $mod_reference
     * @param $cachefile
     * @return bool
     */
    public function require_cache_build($mod_reference, $cachefile)
    {
        $tikilib = TikiLib::lib('tiki');
        return ! file_exists($cachefile)
            || ( $tikilib->now - filemtime($cachefile) ) >= $mod_reference['cache_time'];
    }

    /**
     * @param $input
     * @param $params
     */
    public function dispatchValues($input, &$params)
    {
        if (is_string($input)) {
            parse_str($input, $module_params);
        } else {
            $module_params = $input;
        }

        foreach ($params as $name => & $inner) {
            if (isset($module_params[$name])) {
                if (isset($inner['separator'])) {
                    if (isset($inner['set_value_as_array'])) {
                        $inner['value'] = (array) $module_params[$name];
                    } else {
                        $inner['value'] = implode($inner['separator'], (array) $module_params[$name]);
                    }

                    // Init dynamic parameter options with current values
                    if (isset($inner['options']) && empty($inner['options'])) {
                        $inner['options'] = array_map(function ($value) {
                            return ['text' => $value, 'value' => $value];
                        }, (array) $inner['value']);
                    }
                } else {
                    $inner['value'] = $module_params[$name];
                }
            } elseif (isset($inner['default'])) {
                $inner['value'] = $inner['default'];
            } else {
                $inner['value'] = null;
            }
        }
        // resort params into sections
        $reorderedparams = [];
        foreach ($params as $k => $p) {
            if (! isset($reorderedparams[$p['section']])) {
                $reorderedparams[$p['section']] = [];
            }
            $reorderedparams[$p['section']][$k] = $p;
        }
        $params = $reorderedparams;
    }

    /**
     * @param $name
     * @param $params
     * @return string
     */
    public function serializeParameters($name, $params)
    {
        $info = $this->get_module_info($name);
        $expanded = [];

        foreach ($info['params'] as $name => $def) {
            if (isset($def['filter'])) {
                $filter = TikiFilter::get($def['filter']);
            } else {
                $filter = null;
            }

            if (isset($params[$name]) && $params[$name] !== '') {
                if (isset($def['separator']) && ! is_array($params[$name]) && strpos($params[$name], $def['separator']) !== false) {
                    $parts = explode($def['separator'], $params[$name]);

                    if ($filter) {
                        foreach ($parts as & $single) {
                            $single = $filter->filter($single);
                            $single = trim($single);
                        }
                    }
                } else {
                    $parts = $params[$name];
                    if ($filter) {
                        $parts = $filter->filter($parts);
                    }
                }

                $expanded[$name] = $parts;
            }
        }
        if (empty($expanded)) {
            return '';// http_build_query return NULL or '' depending on system
        }

        return http_build_query($expanded, '', '&');
    }

    /**
     * @param $module_name
     * @param $preference_name
     */
    public function add_pref_error($module_name, $preference_name)
    {
        $this->pref_errors[] = ['mod_name' => $module_name, 'pref_name' => $preference_name];
    }


    /* Returns all module assignations for a certain position, or all positions (by default). A module assignation
    is represented by an array similar to a tiki_modules record. The groups field is unserialized in the module_groups key, a spaces-separated list of groups.
    If asking for a specific position, returns an array of module assignations. If not, returns an array of arrays of modules assignations indexed by positions. For example: array("l" -> array("module assignation"))
    TODO: Document $displayed's effect */
    /**
     * @param null $position
     * @param string $displayed
     * @return array
     */
    public function get_assigned_modules($position = null, $displayed = "n")
    {

        $filter = '';
        $bindvars = [];

        if ($position !== null) {
            $filter .= 'where `position`=?';
            $bindvars[] = $position;
        }

        if ($displayed != 'n') {
            $filter .= ( $filter == '' ? 'where' : 'and' ) . " (`type` is null or `type` != ?)";
            $bindvars[] = 'y';
        }

        $query = "select * from `tiki_modules` $filter order by " . $this->convertSortMode("ord_asc");

        $result = $this->fetchAll($query, $bindvars);

        $ret = [];
        foreach ($result as $res) {
            if ($res["groups"] && strlen($res["groups"]) > 1) {
                $grps = @unserialize($res["groups"]);

                $res["module_groups"] = '';
                if (is_array($grps)) {
                    foreach ($grps as $grp) {
                        $res["module_groups"] .= " $grp ";
                    }
                }
            } else {
                $res["module_groups"] = '&nbsp;';
            }
            if ($position === null) {
                if (! isset($ret[$res['position']])) {
                    $ret[$res['position']] = [];
                }
                $ret[$res['position']][] = $res;
            } else {
                $ret[] = $res;
            }
        }
        return $ret;
    }

    /**
     * @param $name
     * @return mixed
     */
    public function is_user_module($name)
    {
        return $this->table('tiki_user_modules')->fetchCount(['name' => $name]);
    }

    /**
     * @param $name
     * @return mixed
     */
    public function get_user_module($name)
    {
        return $this->table('tiki_user_modules')->fetchFullRow(['name' => $name]);
    }

    /**
     * @global TikiLib $tikilib
     * @param bool $added shows current prefs not in defaults
     * @return array (prefname => array( 'current' => current value, 'default' => default value ))
     */
    public function getModulesForExport()
    {
        $export = [];
        $assigned_modules = $this->get_assigned_modules();

        foreach ($assigned_modules as $zone => $modules) {
            foreach ($modules as $pos => $module) {
                $modtogo['type'] = 'module';
                $modtogo['data'] = [];

                $modtogo['data']['name'] = $module['name'];
                parse_str($module['params'], $modtogo['data']['params']);
                $modtogo['data']['groups'] = unserialize($module['groups']);
                $modtogo['data']['order'] = $module['ord'];

                $modtogo['data']['position'] = str_replace('_modules', '', $this->module_zones[$module['position']] ?? '');

                if ($this->is_user_module($module['name'])) {
                    $um = $this->get_user_module($module['name']);
                    if (preg_match("/^\!*\{.*\}$/", trim($um['data']), $matches)) { // start and end with { and } makes yaml parser think it's a serialized value
                        $um['data'] = $um['data'] . "\n";                           // so force it to be a literal block
                    }
                    $modtogo['data']['custom'] = $um['data'];       // the yaml dumper copes with linefeeds etc as a literal block
                    $modtogo['data']['parse'] = empty($um['parse']) ? 'n' : $um['parse'];
                }

                $export[] = $modtogo;
            }
        }
        return $export;
    }

    /**
     * @param $filename
     * @return array|mixed
     */
    private function read_module_file($filename)
    {
        $cachelib = TikiLib::lib('cache');

        $expiry = filemtime($filename);
        if ($modules = $cachelib->getSerialized($filename, 'modules', $expiry)) {
            return $modules;
        }

        $content = file_get_contents($filename);
        if (! $content) {
            Feedback::error(tr('Module file "%0" not found.', $filename));
            return '';
        }

        $profile = Tiki_Profile::fromString("{CODE(caption=>YAML)}$content{CODE}");

        $out = array_fill_keys(array_values($this->module_zones), []);
        foreach ($profile->getObjects() as $object) {
            if ($object->getType() == 'module') {
                $handler = new Tiki_Profile_InstallHandler_Module($object, []);

                $data = $handler->getData();
                $object->replaceReferences($data);
                $data = $handler->formatData($data);

                $data['groups'] = unserialize($data['groups']);
                $position = $data['position'];
                $zone = $this->module_zones[$position];
                $out[$zone][] = $data;
            }
        }

        $cachelib->cacheItem($filename, serialize($out), 'modules');
        return $out;
    }
}

/**
 * Function made available in the template files to behave differently depending on if a zone is empty or not.
 */
function zone_is_empty($zoneName)
{
    $smarty = TikiLib::lib('smarty');
    $moduleZones = $smarty->getTemplateVars('module_zones');

    $key = $zoneName . '_modules';
    if (empty($moduleZones[$key])) {
        return true;
    }

    foreach ($moduleZones[$key] as $module) {
        $data = (string) ($module['data'] ?? '');
        if (! empty($data)) {
            return false;
        }
    }

    return true;
}
