<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

use Tiki\Lib\Diff\DiffUtils;
use Tiki\Lib\Wiki\ConvertToTiki9;

class HistLib extends TikiLib
{
    // Whitelisted aliases for tiki_lastchanges.php
    // These are not real SQL columns but appear in get_last_changes() result
    public $pure_aliases = [
        'action',     // actionlog.action — Type of action performed
        'lastModif',  // actionlog.lastModif — Timestamp of the action
        'user',       // actionlog.user — User who performed the action
        'ip',         // actionlog.ip — IP address used
        'object',     // actionlog.object — Affected page name or object
        'comment',    // history_fields.comment — Edit summary
        'version',    // history_fields.version — Wiki page version
        'page_id',    // history_fields.page_id — Numeric page ID
    ];
    /*
        *   Removes a specific version of a page
        *
        */
    public function remove_version($page, $version, $historyId = '')
    {
        global $prefs;
        if ($prefs['feature_contribution'] == 'y') {
            $contributionlib = TikiLib::lib('contribution');
            if ($historyId == '') {
                $query = 'select `historyId` from `tiki_history` where `pageName`=? and `version`=?';
                $historyId = $this->getOne($query, [$page, $version]);
            }
            $contributionlib->remove_history($historyId);
        }
        $query = "delete from `tiki_history` where `pageName`=? and `version`=?";
        $result = $this->query($query, [$page,$version]);
        if ($result && $result->numRows() > 0) {
            $logslib = TikiLib::lib('logs');
            $logslib->add_action("Removed version", $page, 'wiki page', "version=$version");
            //get_strings tra("Removed version $version")
            return true;
        }
        return false;
    }

    public function use_version($page, $version, $comment = '')
    {
        $this->invalidate_cache($page);

        $query = "select * from `tiki_history` where `pageName`=? and `version`=?";
        $result = $this->query($query, [$page,$version]);

        if (! $result->numRows()) {
            return false;
        }

        $res = $result->fetchRow();

        global $prefs;
        // add both an optional, manual comment, and an automatic comment to existing one (after truncating if needed)
        if (trim($comment) <> '') {
            $comment = ". " . trim($comment);
        }
        $ver_comment = " [" . tr('Rollback by %0 to version %1', $GLOBALS['user'], $version) . $comment . "]";
        $too_long = 200 - strlen($res["comment"] . $ver_comment);
        if ($too_long < 0) {
            $too_long -= 4;
            $res["comment"] = substr($res["comment"], 0, $too_long) . '...';
        }
        $res["comment"] = $res["comment"] . $ver_comment;

        $query = "update `tiki_pages` set `data`=?,`lastModif`=?,`user`=?,`comment`=?,`version`=`version`+1,`ip`=?, `description`=?, `is_html`=?";
        $bindvars = [$res['data'], $res['lastModif'], $res['user'], $res['comment'], $res['ip'], $res['description'], $res['is_html']];

        // handle rolling back once page has been edited in a different editor (wiki or wysiwyg) based on is_html in history
        if ($prefs['feature_wysiwyg'] == 'y' && $prefs['wysiwyg_optional'] == 'y' && $prefs['wysiwyg_memo'] == 'y') {
            if ($res['is_html'] == 1) {
                // big hack: when you move to wysiwyg you do not come back usually -> wysiwyg should be a column in tiki_history
                $info = $this->get_hist_page_info($page);
                $bindvars[] = $info['wysiwyg'];
            } else {
                $bindvars[] = 'n';
            }
            $query .= ', `wysiwyg`=?';
        }
        $query .= ' where `pageName`=?';
        $bindvars[] = $page;
        $result = $this->query($query, $bindvars);
        $query = "delete from `tiki_links` where `fromPage` = ?";
        $result = $this->query($query, [$page]);
        $this->clear_links($page);
        $pages = TikiLib::lib('parser')->get_pages($res["data"], true);

        foreach ($pages as $a_page => $types) {
            $this->replace_link($page, $a_page, $types);
        }

        $this->replicate_page_to_history($page);

        global $prefs;
        if ($prefs['feature_actionlog'] == 'y') {
            $logslib = TikiLib::lib('logs');
            $logslib->add_action("Rollback", $page, 'wiki page', "version=$version");
        }
        //get_strings tra("Changed actual version to $version");
        return true;
    }

    // Used to see a specific version of the page
    public function get_view_date($date_str)
    {
        global $tikilib;

        if (! $date_str) {
            // Date is undefined
            throw new Exception();
        }

        $view_date = $date_str;
        $tsp = explode('-', $date_str);

        if (count($tsp) == 3) {
            // Date in YYYY-MM-DD format
            $view_date = $tikilib->make_time(23, 59, 59, $tsp[1] + 1, $tsp[2], $tsp[0] + 1900);
        }

        return $view_date;
    }

    public function get_user_versions($user)
    {
        $query
            = "select `pageName`,`version`, `lastModif`, `user`, `ip`, `comment` from `tiki_history` where `user`=? order by `lastModif` desc";

        $result = $this->query($query, [$user]);
        $ret = [];

        while ($res = $result->fetchRow()) {
            $aux = [];

            $aux["pageName"] = $res["pageName"];
            $aux["version"] = $res["version"];
            $aux["lastModif"] = $res["lastModif"];
            $aux["ip"] = $res["ip"];
            $aux["comment"] = $res["comment"];
            $ret[] = $aux;
        }

        return $ret;
    }

    // Returns information about a specific version of a page
    public function get_version($page, $version)
    {
        //fix for encoded slowly without doing it all at once in the installer upgrade script
        $wikilib = TikiLib::lib('wiki');
        $converter = new ConvertToTiki9();
        $converter->convertPageHistoryFromPageAndVersion($page, $version);

        $query = "select * from `tiki_history` where `pageName`=? and `version`=?";
        $result = $this->query($query, [$page,$version]);
        $res = $result->fetchRow();
        return $res;
    }

    // Get page info for a specified version
    public function get_hist_page_info($pageName, $version = null)
    {
        $info = parent::get_page_info($pageName);

        if (empty($version)) {
            // No version = last version
            return $info;
        }

        if (! $info) {
            // Page does not exist
            return false;
        }

        $old_info = $this->get_version($pageName, $version);

        if ($old_info == null) {
            // History does not exist
            if ($version == $this->get_page_latest_version($pageName) + 1) {
                // Last version
                return $info;
            }

            throw new Exception();
        }

        // Override parameters with versioned data
        $info['data'] = $old_info['data'];
        $info['version'] = $old_info['version'];
        $info['last_version'] = $info['version'];
        $info["user"] = $old_info["user"];
        $info["ip"] = $old_info["ip"];
        $info["description"] = $old_info["description"];
        $info["comment"] = $old_info["comment"];
        $info["is_html"] = $old_info["is_html"];
        $info['lastModif'] = $old_info["lastModif"];
        $info['page_size'] = strlen($old_info['data']);

        return $info;
    }

    // Returns all the versions for this page
    // without the data itself
    public function get_page_history($page, $fetchdata = true, $offset = 0, $limit = -1)
    {
        global $prefs;

        $query = "select * from `tiki_history` where `pageName`=? order by `version` desc";
        $result = $this->query($query, [$page], $limit, $offset);
        $ret = [];

        while ($res = $result->fetchRow()) {
            $aux = [];

            $aux["version"] = $res["version"];
            $aux["lastModif"] = $res["lastModif"];
            $aux["user"] = $res["user"];
            $aux["ip"] = $res["ip"];
            if ($fetchdata == true) {
                $aux["data"] = $res["data"];
            }
            $aux["pageName"] = $res["pageName"];
            $aux["description"] = $res["description"];
            $aux["comment"] = $res["comment"];
            $aux["is_html"] = $res["is_html"];
            //$aux["percent"] = levenshtein($res["data"],$actual);
            if ($prefs['feature_contribution'] == 'y') {
                $contributionlib = TikiLib::lib('contribution');
                $aux['contributions'] = $contributionlib->get_assigned_contributions($res['historyId'], 'history');
                $logslib = TikiLib::lib('logs');
                $aux['contributors'] = $logslib->get_wiki_contributors($aux);
            }
            if ($prefs['markdown_enabled'] === 'y') {
                $wikiParserParsable = new WikiParser_Parsable($res['data']);
                $syntaxPluginResult = $wikiParserParsable->guess_syntax($res['data']);
                $aux['is_markdown'] = $syntaxPluginResult['syntax'] === 'markdown';
                $aux['wysiwyg'] = array_key_exists('editor', $syntaxPluginResult) && $syntaxPluginResult['editor'] === 'wysiwyg' ? 'y' : 'n';
            }
            $ret[] = $aux;
        }

        return $ret;
    }

    // Returns one version of the page from the history
    // without the data itself (version = 0 now returns data from current version)
    public function get_page_from_history($page, $version, $fetchdata = false)
    {
        $wikilib = TikiLib::lib('wiki');
        $converter = new ConvertToTiki9();
        $converter->convertPageHistoryFromPageAndVersion($page, $version);

        if ($fetchdata == true) {
            if ($version > 0) {
                $query = "select `pageName`, `description`, `version`, `lastModif`, `user`, `ip`, `data`, `comment`, `is_html` from `tiki_history` where `pageName`=? and `version`=?";
            } else {
                $query = "select `pageName`, `description`, `version`, `lastModif`, `user`, `ip`, `data`, `comment`, `is_html` from `tiki_pages` where `pageName`=?";
            }
        } else {
            if ($version > 0) {
                $query = "select `pageName`, `description`, `version`, `lastModif`, `user`, `ip`, `comment`, `is_html` from `tiki_history` where `pageName`=? and `version`=?";
            } else {
                $query = "select `pageName`, `description`, `version`, `lastModif`, `user`, `ip`, `comment`, `is_html` from `tiki_pages` where `pageName`=?";
            }
        }
        if ($version > 0) {
            $result = $this->query($query, [$page,$version]);
        } else {
            $result = $this->query($query, [$page]);
        }

        $ret = [];

        while ($res = $result->fetchRow()) {
            $aux = [];

            $aux["version"] = $res["version"];
            $aux["lastModif"] = $res["lastModif"];
            $aux["user"] = $res["user"];
            $aux["ip"] = $res["ip"];
            if ($fetchdata == true) {
                $aux["data"] = $res["data"];
            }
            $aux["pageName"] = $res["pageName"];
            $aux["description"] = $res["description"];
            $aux["comment"] = $res["comment"];
            $aux["is_html"] = $res["is_html"];
            //$aux["percent"] = levenshtein($res["data"],$actual);
            $ret[] = $aux;
        }

        return empty($ret) ? $ret : $ret[0];
    }

    /**
     * note that this function returns the latest but one version in the
     * history db table, which is one less than the current version
     *
     * @param string $page          page name
     * @param string $sort_mode     default version_desc
     * @return bool int
     */

    public function get_page_latest_version($page, $sort_mode = 'version_desc')
    {

        $query = "select `version` from `tiki_history` where `pageName`=? order by " . $this->convertSortMode($sort_mode);
        $result = $this->query($query, [$page], 2);
        $ret = false;

        if ($res = $result->fetchRow()) {
            if ($res = $result->fetchRow()) {
                $ret = $res['version'];
            }
        }

        return $ret;
    }

    // Use largest version +1 in history table rather than tiki_page because versions used to be bugged
    // tiki_history is also bugged as not all changes get stored in the history, like minor changes
    // and changes that do not modify the body of the page. Both numbers are wrong, but the largest of
    // them both is right.
    // Also, it's possible that latest version in history has been rejected (with Flagged Revisions),
    // so the current version is not the biggest number.
    public function get_page_next_version($page, $willDoHistory = true)
    {
        $query = "select `version` from `tiki_history` where `pageName`=? order by `version` desc";
        $result = $this->query($query, [$page], 1);
        $version = 0;

        if ($res = $result->fetchRow()) {
            $version = $res['version'];
        }

        $query = "select `version` from `tiki_pages` where `pageName`=?";
        $result = $this->query($query, [$page], 1);

        if ($res = $result->fetchRow()) {
            $version = max($res['version'], $version);
        }

        return $version + ($willDoHistory ? 1 : 0);
    }

    public function version_exists($pageName, $version)
    {

        $query = "select `pageName` from `tiki_history` where `pageName` = ? and `version`=?";
        $result = $this->query($query, [$pageName,$version]);
        return $result->numRows();
    }

    // This function get the last changes from pages from the last $days days
    // if days is 0 this gets all the registers
    public function get_last_changes($days, $offset = 0, $limit = -1, $sort_mode = 'lastModif_desc', $findwhat = '', $repeat = false)
    {
            global $user;

        $bindvars = [];
        $categories = $this->get_jail();
        if (! isset($categjoin)) {
            $categjoin = '';
        }
        if ($categories) {
            $categjoin .= "inner join `tiki_objects` as tob on (tob.`itemId`= ta.`object` and tob.`type`= ?) inner join `tiki_category_objects` as tc on (tc.`catObjectId`=tob.`objectId` and tc.`categId` IN(" . implode(', ', array_fill(0, count($categories), '?')) . ")) ";
            $bindvars = array_merge(['wiki page'], $categories);
        }

        $where = "where true ";
        if ($findwhat) {
            $findstr = '%' . $findwhat . '%';
            $where .= " and ta.`object` like ? or ta.`user` like ? or ta.`comment` like ?";
            $bindvars = array_merge($bindvars, [$findstr,$findstr,$findstr]);
        }

        if ($days) {
            $toTime = $this->make_time(23, 59, 59, $this->date_format("%m"), $this->date_format("%d"), $this->date_format("%Y"));
            $fromTime = $toTime - (24 * 60 * 60 * $days);
            $where .= " and ta.`lastModif`>=? and ta.`lastModif`<=? ";
            $bindvars[] = $fromTime;
            $bindvars[] = $toTime;
        }

        $group_by = "";
        if ($repeat) {
            $group_by = "group by thf.`page_id`";
        }

        // WARNING: This assumes the current version of each page will be found in tiki_history
        $query = "select distinct ta.`action`, ta.`lastModif`, ta.`user`, ta.`ip`, ta.`object`, thf.`comment`, thf.`version`, thf.`page_id` from `tiki_actionlog` ta
            inner join (select th.`version`, th.`comment`, th.`pageName`, th.`lastModif`, tp.`page_id` from `tiki_history` as th LEFT OUTER JOIN `tiki_pages` tp ON tp.`pageName` = th.`pageName` AND tp.`version` = th.`version`) as thf on ta.`object`=thf.`pageName` and ta.`lastModif`=thf.`lastModif` and ta.`objectType`='wiki page' and ta.`action` <> 'Removed version' " . $categjoin . $where . $group_by . " order by ta." . $this->convertSortMode($sort_mode);

        // TODO: Optimize. This fetches all records just to be able to give a count.
        $result = Perms::filter([ 'type' => 'wiki page' ], 'object', $this->fetchAll($query, $bindvars), [ 'object' => 'object' ], 'view');
        $count = count($result);
        $ret = [];

        if ($limit == -1) {
            $result = array_slice($result, $offset);
        } else {
            $result = array_slice($result, $offset, $limit);
        }
        foreach ($result as $res) {
            $res['current'] = isset($res['page_id']);
            $res['pageName'] = $res['object'];
            $ret[] = $res;
        }

        return ['data' => $ret, 'count' => $count];
    }
    public function get_nb_history($page)
    {
        $query_count = "select count(*) from `tiki_history` where `pageName` = ?";
        $count = $this->getOne($query_count, [$page]);
        return $count;
    }

    // This function gets the version number of the version before or after the time specified
    // (note that current version is not included in search)
    public function get_version_by_time($page, $unixtimestamp, $before_or_after = 'before', $include_minor = true)
    {
        $query = "select `version`, `version_minor`, `lastModif` from `tiki_history` where `pageName`=? order by `version` desc";
        $result = $this->query($query, [$page]);
        $ret = [];
        $version = 0;
        while ($res = $result->fetchRow()) {
            $aux = [];
            $aux["version"] = $res["version"];
            $aux["version_minor"] = $res["version_minor"];
            $aux["lastModif"] = $res["lastModif"];
            $ret[] = $aux;
        }
        foreach ($ret as $ver) {
            if ($ver["lastModif"] <= $unixtimestamp && ($include_minor || $ver["version_minor"] == 0)) {
                if ($before_or_after == 'before') {
                    $version = (int) $ver["version"];
                    break;
                } elseif ($before_or_after == 'after') {
                    break;
                }
            }
            if ($before_or_after == 'after' && ($include_minor || $ver["version_minor"] == 0)) {
                $version = (int) $ver["version"];
            }
        }
        return max(0, $version);
    }
}


function histlib_helper_setup_diff($page, $oldver, $newver, $diff_style = '', $current_ver = 0)
{
    global $prefs;
    $smarty = TikiLib::lib('smarty');
    $histlib = TikiLib::lib('hist');
    $tikilib = TikiLib::lib('tiki');
    $prefs['wiki_edit_section'] = 'n';

    $info = $tikilib->get_page_info($page);
    $exists = 0;
    $old = $new = [];
    $new['data'] = '';
    $old['data'] = '';
    if ($oldver == 0 || $oldver == $info["version"]) {
        $old = & $info;
        $smarty->assign_by_ref('old', $info);
    } else {
        // fetch the required page from history, including its content
        while ($oldver > 0 && ! ($exists = $histlib->version_exists($page, $oldver) )) {
            --$oldver;
        }

        if ($exists) {
            $old = $histlib->get_page_from_history($page, $oldver, true);
            $smarty->assign_by_ref('old', $old);
        }
    }
    if ($newver == 0 || $newver >= $info["version"]) {
        $new =& $info;
        $smarty->assign_by_ref('new', $info);
    } else {
        // fetch the required page from history, including its content
        while ($newver > 0 && ! ($exists = $histlib->version_exists($page, $newver) )) {
            --$newver;
        }

        if ($exists) {
            $new = $histlib->get_page_from_history($page, $newver, true);
            $smarty->assign_by_ref('new', $new);
        }
    }
    //
    if ($current_ver == 0) {
        $curver = null;
        $response = 'n';
        $smarty->assign_by_ref('object_curver', $response);
        $smarty->assign_by_ref('curver', $curver);
    } else {
        if ($exists) {
            $curver = $histlib->get_page_from_history($page, $current_ver, true);
            $response = 'y';
            $smarty->assign_by_ref('object_curver', $response);
            $smarty->assign_by_ref('curver', $curver);
        }
    }

    $oldver_mod = $oldver;
    if ($oldver == 0) {
        $oldver_mod = 1;
    }

    $query = "SELECT `comment`, `version` from `tiki_history` WHERE `pageName`=? and `version` BETWEEN ? AND ? ORDER BY `version` DESC";
    $result = $histlib->query($query, [$page,$oldver_mod,$newver]);
    $diff_summaries = [];

    if ($oldver == 0) {
        $diff_summaries[] = $old['comment'];
    }

    while ($res = $result->fetchRow()) {
        $aux = [];

        $aux["comment"] = $res["comment"];
        $aux["version"] = $res["version"];
        $diff_summaries[] = $aux;
    }
    $smarty->assign('diff_summaries', $diff_summaries);

    if (empty($diff_style) || $diff_style == "old") {
        $diff_style = $prefs['default_wiki_diff_style'];
    }
    $smarty->assign('diff_style', $diff_style);
    $parserlib = TikiLib::lib('parser');
    if ($diff_style == "sideview") {
        $old["data"] = $parserlib->parse_data($old["data"], ['preview_mode' => true]);
        $new["data"] = $parserlib->parse_data($new["data"], ['preview_mode' => true]);
    } else {
        if ($info['is_html'] == 1 and $diff_style != "htmldiff") {
            $search[] = "~</(table|td|th|div|p)>~";
            $replace[] = "\n";
            $search[] = "~<(hr|br) />~";
            $replace[] = "\n";
            $old['data'] = strip_tags(preg_replace($search, $replace, $old['data']), '<h1><h2><h3><h4><b><i><u><span>');
            $new['data'] = strip_tags(preg_replace($search, $replace, $new['data']), '<h1><h2><h3><h4><b><i><u><span>');
        }
        if ($diff_style == "htmldiff" && $old['is_html'] != 1) {
            $parse_options = ['is_html' => ($old['is_html'] == 1), 'noheadinc' => true, 'suppress_icons' => true, 'noparseplugins' => true];
            $old["data"] = $parserlib->parse_data($old["data"], $parse_options);
            $new["data"] = $parserlib->parse_data($new["data"], $parse_options);

            $old['data'] = histlib_strip_irrelevant($old['data']);
            $new['data'] = histlib_strip_irrelevant($new['data']);
        }
        # If the user doesn't have permission to view
        # source, strip out all tiki-source-based comments
        global $tiki_p_wiki_view_source;
        if ($tiki_p_wiki_view_source != 'y') {
            $old["data"] = preg_replace(';~tc~(.*?)~/tc~;s', '', $old["data"]);
            $new["data"] = preg_replace(';~tc~(.*?)~/tc~;s', '', $new["data"]);
        }

        $html = DiffUtils::diff2($old["data"], $new["data"], $diff_style);
        $smarty->assign_by_ref('diffdata', $html);
    }
}

function histlib_strip_irrelevant($data)
{
    $data = preg_replace("/<(h1|h2|h3|h4|h5|h6|h7)\s+([^\\\\>]+)>/i", '<$1>', $data);
    return $data;
}
