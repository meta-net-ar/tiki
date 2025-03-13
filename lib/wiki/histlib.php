<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class HistLib extends TikiLib
{
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
        $res = $this->version_exists($page, $version);
        if (! $res) {
            $logslib = TikiLib::lib('logs');
            $logslib->add_action("Removed version", $page, 'wiki page', "version=$version");
            //get_strings tra("Removed version $version")
            return true;
        } else {
            return false;
        }
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
        $converter = new convertToTiki9();
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
        $converter = new convertToTiki9();
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
        $cant = count($result);
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

        return ['data' => $ret, 'cant' => $cant];
    }
    public function get_nb_history($page)
    {
        $query_cant = "select count(*) from `tiki_history` where `pageName` = ?";
        $cant = $this->getOne($query_cant, [$page]);
        return $cant;
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

/**
 *
 * This class represents a structured view (per word) on a document. Feeding it with additional references, it can be used to generate a
 * complete view of the document including changes made over time (like the "Track changes" in some word processing programs). A statistics
 * of the different authors contributions can be generated as well
 *
 * @author cdrwhite
 * @since 6.0
 */
class Document
{
    /**
     * @var bool
     */
    private $history;
    private $showpopups;
    /**
     * @var array   a list of words and whitespaces represented by an array(word,author,deleted,diffid,[deleted_by])
     */
    private $document;

    /**
     * @var array   array of statistical data grouped by author each represented by an array(words,deleted_words,whitespaces,deleted_whitespaces,characters,deleted_characters,printables,deleted_printables)
     * @see getStatistics
     */
    private $statistics;

    /**
     * @var array   sum of all statistics for all authors, generated by getStatistics, retrieved by getTotal()
     * @see getTotal;
     */
    private $total;

    /**
     * @var string  filter used in getStatistics to distinguish between characters and printable characters
     * @see getStatistics
     */
    private $filter;

    /**
     * @var int processing settings
     */
    private $process = 1;

    /**
     * @var bool    should the page contents be parsed (HTML instead of WIKI text)
     */
    private $parsed;

    /**
     * @var bool    should the html tags be stripped from the parsed contents
     */
    private $nohtml;

    /**
     * @var string  start marker. If set, text before this marker (including the marker itself) will be removed
     */
    public $startmarker = '';

    /**
     * @var string  end marker. If set, text after this marker (including the marker itself) will be removed
     */
    public $endmarker = '';

    /**
     * @var string  regex for splitting page text into an array of words;
     */
    private $search = "#(\[[^\[].*?\]|\(\(.*?\)\)|(~np~\{.*?\}~/np~)|<[^>]+>|[,\"':\s]+|[^\s,\"':<]+|</[^>]+>)#";

    /**
     * @var array   Page info
     */
    private $info;

    /**
     * @var array   complete page history
     */
    private $data;

    /**
     *
     * Initializing Internal variables for getStatistics and getTotals and adding the first page to the document
     * @param string    $page       Name of the page to include
     * @param int       $lastversion    >0 uses the version specified (or last page, if this is greater than the version of the last page) =0 uses the latest(current) version, <0 means a timestamp (lastModif has to be before that)
     * @param int       $process    0 = don't parse (take original wiki text and count wiki tags/plugins), 1 = parse (take html as base), 2 = parse and strip html tags
     * @param string    $start      start marker (all text will be skipped, including this marker which must be at the beginning of a line)
     * @param string    $end        end marker (all text will be skipped from this marker on, including this marker which must be at the beginning of a line)
     */
    public function __construct($page, $lastversion = 0, $process = 1, $showpopups = true, $startmarker = '', $endmarker = '')
    {
        $histlib = TikiLib::lib('hist');

        $this->document = [];
        $this->history = false;
        $this->filter = '/([[:blank:]]|[[:cntrl:]]|[[:punct:]]|[[:space:]])/';
        $this->parsed = true;
        $this->nohtml = false;
        $this->showpopups = $showpopups;
        switch ($process) {
            case 0:
                $this->parsed = false;
                    $this->process = 0;
                break;
            case 2:
                $this->nohtml = true;
                    $this->process = 2;
                break;
        }
        $this->startmarker = $startmarker;
        $this->endmarker = $endmarker;

        $this->info = $histlib->get_hist_page_info($page, true);
        if ($lastversion == 0) {
            $lastversion = $this->info['version'];
        }
        $this->data = [];
        $this->data = [[
                'version'       => $this->info['version'],
                'lastModif'     => $this->info['lastModif'],
                'user'          => $this->info['user'],
                'ip'            => $this->info['ip'],
                'pageName'      => $page,
                'description'   => $this->info['description'],
                'comment'       => $this->info['comment'],
                'data'          => $this->info['data'],
            ]];
        $this->data = array_merge($this->data, $histlib->get_page_history($page, true, 0, -1));
        $next = count($this->data) - 1;
        $author = $this->data[$next]['user'];
        $next = $this->getLastAuthorText($author, $next, $lastversion);
        if ($next == -1) {  // all pages from the same author, no need to diff
            $index = $this->getIndex($lastversion);
        } else {
            $index = $next;
        }
        $source = $this->removeText($this->data[$index]['data']);
        $source = preg_replace(['/\{AUTHOR\(.+?\)\}/','/{AUTHOR\}/','/\{INCLUDE\(.+?\)\}\{INCLUDE\}/'], ' ~np~$0~/np~', $source);
        if ($this->parsed) {
            $source = TikiLib::lib('parser')->parse_data($source, ['suppress_icons' => true]);
        }
        if ($this->nohtml) {
            $source = strip_tags($source);
        }
        preg_match_all($this->search, $source, $out, PREG_PATTERN_ORDER);
        $words = $out[0];
        $this->document = $this->addWords($this->document, $words, $author);
        if ($next == -1) {
            return;
        }
        do {
            $author = $this->data[$next - 1]['user'];
            $next = $this->getLastAuthorText($author, $next - 1, $lastversion);
            if ($next == -1) {
                $index = $this->getIndex($lastversion);
            } else {
                $index = $next;
            }
            $newpage = $this->removeText($this->data[$index]['data']);
            $this->mergeDiff($newpage, $author);
        } while ($next > 0);
        $this->parseAuthorAndInclude();
    }

    /**
     *
     * Removes all text before the first occurrence of start marker and after the last occurrence of the end marker
     * This copies the original behaviour of the wikiplugin_include even though it could be done with a regex in fewer lines
     * @param   string $text    contains the whole text
     * @return  string          returns the text inside the markers
     */
    private function removeText($text)
    {
        $start = ($this->startmarker != '');
        $stop = ($this->endmarker != '');
        if ($start || $stop) {
            $explText = explode("\n", $text);
            if ($start && $stop) {
                $state = 0;
                foreach ($explText as $i => $line) {
                    if ($state == 0) {
                        // Searching for start marker, dropping lines until found
                        unset($explText[$i]);   // Drop the line
                        if (0 == strcmp($this->startmarker, trim($line))) {
                            $state = 1; // Start retaining lines and searching for stop marker
                        }
                    } else {
                        // Searching for stop marker, retaining lines until found
                        if (0 == strcmp($this->endmarker, trim($line))) {
                            unset($explText[$i]);   // Stop marker, drop the line
                            $state = 0;         // Go back to looking for start marker
                        }
                    }
                }
            } elseif ($start) {
                // Only start marker is set. Search for it, dropping all lines until it is found.
                foreach ($explText as $i => $line) {
                    unset($explText[$i]); // Drop the line
                    if (0 == strcmp($this->startmarker, trim($line))) {
                        break;
                    }
                }
            } else {
                // Only stop marker is set. Search for it, dropping all lines after it is found.
                $state = 1;
                foreach ($explText as $i => $line) {
                    if ($state == 0) {
                        // Dropping lines
                        unset($explText[$i]);
                    } else {
                        // Searching for stop marker, retaining lines until found
                        if (0 == strcmp($this->endmarker, trim($line))) {
                            unset($explText[$i]);   // Stop marker, drop the line
                            $state = 0;         // Start dropping lines
                        }
                    }
                }
            }
            $text = implode("\n", $explText);
        }
        return $text;
    }

    /**
     *
     * get the id of the last text of the given author
     * @param string    $author     name of the current author
     * @param int       $start      start index
     * @param int       $lastversion    last version to check, assuming all versions, if none is provided
     * @return  int                 id of the first text of a different author or -1 if there is none
     * @see get_page_history_all
     */
    private function getLastAuthorText($author, $start = -1, $lastversion = -1)
    {
        if ($start == -1) {
            return $start;
        }
        if ($start < 0) {
            $start = count($this->data) - 1;
        }
        if ($lastversion == -1) {
            $lastversion = $this->data[0]['version'];
        }
        $i = $start;
        while ($i >= 0 and $this->data[$i]['user'] == $author and $this->data[$i]['version'] <= $lastversion) {
            $i--;
        }
        $i++;
        if ($this->data[$i]['version'] >= $lastversion) {
            $i = -1;
        }
        return $i;
    }

    /**
     *
     * gets the index position of the requested version in the data array
     * @param int   $version
     */
    private function getIndex($version)
    {
        for ($i = count($this->data) - 1; $i >= 0; $i--) {
            if ($this->data[$i]['version'] == $version) {
                return $i;
            }
        }
        return -1;
    }

    /**
     *
     * returns the history (identical to $histlib->get_page_history, but saves another fetch from database as we already have the info
     */
    public function getHistory()
    {
        return array_slice($this->data, 1);
    }

    /**
     *
     * returns the page info history (identical to $tikilib->get_page_info, but saves another fetch from database as we already have the info
     */
    public function getInfo()
    {
        return $this->info;
    }


    /**
     *
     * Generates an array of words from the internal document structure, which can be used by the diff class.
     * The internal document structure will be modified to allow mergeDiff to integrate a new page with the current page without losing any information
     * @see mergeDiff
     * @return  array   list of words in the document (no author etc.)
     */
    public function getDiffArray()
    {
        $diffarray = [];
        foreach ($this->document as &$word) {
            if (! $word['deleted']) {
                $word['diffid'] = count($diffarray);
                $diffarray[] = $word['word'];
            } else {
                $word['diffid'] = -1;
            }
        }
        return $diffarray;
    }

    /**
     *
     * Generates a statistics per author, the totals can be retrieved via getTotal
     * @see     getTotal
     * @param   string  $filter     regex to filter out non printable characters (difference between characters and printables)
     * @return  array               array indexed by author containing arrays with statistics (words, deleted_words, whitespaces, deleted_whitespaces, characters, deleted_characters, printables, deleted_printables)
     */
    public function getStatistics($filter = '/([[:blank:]]|[[:cntrl:]]|[[:punct:]]|[[:space:]])/')
    {
        $style = 0;
        if ($this->filter != $filter) { //a new filter invalidates the statistics
            $this->statistics = false;
            $this->filter = $filter;
        }
        if ($this->statistics != false) {
            return $this->statistics; //there is already a history for the current state
        }
        $this->statistics = [];
        $this->total = [
                    'words' => 0,
                    'deleted_words' => 0,
                    'whitespaces' => 0,
                    'deleted_whitespaces' => 0,
                    'characters'    => 0,
                    'deleted_characters' => 0,
                    'printables' => 0,
                    'deleted_printables' => 0,
                ];

        foreach ($this->document as $word) {
            $author = $word['author'];
            if (! isset($this->statistics[$author])) {
                $this->statistics[$author] = [
                    'words' => 0,
                    'words_percent' => 0,
                    'deleted_words' => 0,
                    'deleted_words_percent' => 0,
                    'whitespaces' => 0,
                    'whitespaces_percent' => 0,
                    'deleted_whitespaces' => 0,
                    'deleted_whitespaces_percent' => 0,
                    'characters'    => 0,
                    'characters_percent' => 0,
                    'deleted_characters' => 0,
                    'deleted_characters_percent' => 0,
                    'printables' => 0,
                    'printables_percent' => 0,
                    'deleted_printables' => 0,
                    'deleted_printables_percent' => 0,
                    'style' => "author$style",
                ];
                $style++;
                if ($style > 15) {
                    $style = 0;
                }
            } //isset author
            if ($word['deleted']) {
                $prefix = 'deleted_';
            } else {
                $prefix = '';
            }
            $w = $word['word'];
            if ($this->nohtml) {
                $w = strip_tags($w);
            }
            if (trim($w) == '') {
                $this->statistics[$author][$prefix . 'whitespaces']++;
                $this->total[$prefix . 'whitespaces']++;
            } else {
                $this->statistics[$author][$prefix . 'words']++;
                $this->total[$prefix . 'words']++;
            }
            $l = mb_strlen($w);
            $this->statistics[$author][$prefix . 'characters'] += $l;
            $this->total[$prefix . 'characters'] += $l;
            $l = mb_strlen(preg_replace($this->filter, '', $w));
            $this->statistics[$author][$prefix . 'printables'] += $l;
            $this->total[$prefix . 'printables'] += $l;
        } //foreach
        //calculate percentages
        foreach ($this->statistics as &$author) {
            $author['words_percent'] = $author['words'] / $this->total['words'];
            $author['deleted_words_percent'] = ($this->total['deleted_words'] != 0 ? $author['deleted_words'] / $this->total['deleted_words'] : 0);
            $author['whitespaces_percent'] = $author['whitespaces'] / $this->total['whitespaces'];
            $author['deleted_whitespaces_percent'] = ($this->total['deleted_whitespaces'] != 0 ? $author['deleted_whitespaces'] / $this->total['deleted_whitespaces'] : 0);
            $author['characters_percent'] = $author['characters'] / $this->total['characters'];
            $author['deleted_characters_percent'] = ($this->total['deleted_characters'] != 0 ? $author['deleted_characters'] / $this->total['deleted_characters'] : 0);
            $author['printables_percent'] = $author['printables'] / $this->total['printables'];
            $author['deleted_printables_percent'] = ($this->total['deleted_printables'] != 0 ? $author['deleted_printables'] / $this->total['deleted_printables'] : 0);
        }
        return $this->statistics;
    }

    /**
     *
     * gets the totals from a previous getStatistics call
     * @see     getStatistics
     * @return  array with statistics (words, deleted_words, whitespaces, deleted_whitespaces, characters, deleted_characters, printables, deleted_printables)
     */
    public function getTotal()
    {
        return $this->total;
    }

    /**
     *
     * Retrieves the document data in different formats,
     * @param string $type      can be one of 'words' (array of words/whitespaces), 'text' (unformatted string), 'wiki' (string with wikiplugin AUTHOR tags to show the authors) or the default empty string '' (returns the internal document structure)
     * @param array  $options   array containing the filter specific options:
     * <table>
     * <tr><th>Type</th><th>Name</th><th>Applicable for</th><th>Purpose</th></tr>
     * <tr><td>bool</td><td>showpopups</td><td>wiki</td><td>renders popups, defaults to true</td></tr>
     * <tr><td>bool</td><td>escape</td><td>text/wiki</td><td>Escapes brackets and htmlspecialchars</td></tr>
     * </table>
     * @return  array|string    depending on the parameter $type, a string or array containing the documents words
     */
    public function get($type = '', $options = [])
    {
        switch ($type) {
            case 'words':
                $words = [];
                foreach ($this->document as $word) {
                    $words[] = $word['word'];
                }
                return $words;
                break;
            case 'text':
                $text = '';
                foreach ($this->document as $word) {
                    $text .= $word['word'];
                }
                return $text;
            if ($options['escape']) {
                if (! $this->parsed) {
                    $text = '~np~' .
                          preg_replace(['/\~np\~/', '//\~\/np\~/'], ['&#126;np&#126;','&#126;/np&#126;;'], $text) .
                          '~/np~';
                }
                $text = preg_replace(['/</','/>/'], ['&lt;','&gt;'], $text);
            }
                break;
            case 'wiki':
                $text = '';
                $author = '';
                $deleted = 0;
                $deleted_by = '';
                if (isset($options['showpopups'])) {
                    $showpopups = $options['showpopups'];
                } else {
                    $showpopups = true;
                }
                foreach ($this->document as $word) {
                    $skip = false;
                    $d = isset($word['deleted_by']) ? $word['deleted_by'] : '';
                    $w = $word['word'];
                    if ($author != $word['author'] or $deleted != $word['deleted'] or $deleted_by != $d) {
                        if ($text != '') {
                            if ($options['escape']) {
                                $text .= '~/np~';
                            }
                            $text .= '{AUTHOR}';
                        }
                        $author = $word['author'];
                        $deleted = $word['deleted'];
                        $deleted_by = $d;
                        $text .= "{AUTHOR(author=\"$author\"" .
                                ($deleted ? ",deleted_by=\"$deleted_by\"" : '') .
                                ',visible="1"' .
                                ($showpopups ? ', popup="1"' : '') .
                                ')}';
                        if ($options['escape']) {
                            $text .= "~np~";
                        }
                    }
                    if (! $options['escape']) {
                        if ($this->parsed and ! $this->nohtml) { // skipping popups for links
                            if (substr($w, 0, 3) == '<a ') {
                                $text .= '{AUTHOR}';
                            }
                            if (substr($w, -4) == '</a>') {
                                $text .= $w . "{AUTHOR(author=\"$author\"" .
                                       ($deleted ? ",deleted_by=\"$deleted_by\"" : '') .
                                       ',visible="1", ' .
                                       ($showpopups ? ', popup="1"' : '') .
                                       ')}';
                                $skip = true;
                            }
                        }
                    } else { //escape existing tags
                        if (! $this->parsed) {
                              $w = preg_replace(['/\~np\~/', '/\~\/np\~/'], ['&#126;np&#126;','&#126;/np&#126;'], $w);
                        }
                        $w = preg_replace(['/</','/>/'], ['&amp;lt;','&amp;gt;'], $w); //double encode!
                    }
                    if (strlen($w) == 0 and ! $this->parsed) {
                        $text .= "\n";
                    } else {
                        if (! $skip) {
                            $text .= $w;
                        }
                    }
                } // foreach
                if ($options['escape']) {
                    $text .= "~/np~";
                }
                $text .= "{AUTHOR}";
                return $text;
                break;
            default:
                return $this->document;
        }
    }

    /**
     *
     * Adds the supplied list of words to the provided document structure
     * @param array     $doc        a list of words (arrays containing word, author, deleted, diffid, optionally deleted_by and statistical data) where the new words will be added to
     * @param array     $list       array of words/whitespaces to add to the document
     * @param string    $author     name of the author to credit
     * @return                      provided document structure $doc with the words from $list appended
     */
    private function addWords($doc, $list, $author, $deleted = false, $deleted_by = '')
    {
        $newdoc = $doc;
        foreach ($list as $word) {
            $newword = [
                            'word'      => $word,
                            'author'    => $author,
                            'deleted'   => $deleted,
                            'diffid'    => -1,
                            ];
            if ($deleted) {
                $newword['deleted_by'] = $deleted_by;
            }
            $newdoc[] = $newword;
        }
        return $newdoc;
    }

    /**
     *
     * moves a nuber of words from the b eginning of this document to the provided document structure
     * @param array     $doc        a list of words (arrays containing word, author, deleted, diffid, optionally deleted_by and statistical data) where the new words will be appended to
     * @param int       $pos        number of characters to move from the current documents beginning to the new list, deleted words which have a negative diff id wille be moved but not counted
     * @param array     $list       list of words to move
     * @param bool      $setDeleted mark the moved words as deleted, if not already deleted
     * @param string    $deletedBy  name of the author who deleted the words
     */
    private function moveWords(&$doc, &$pos, $list, $deleted = false, $deleted_by = '')
    {
        $pos += count($list);
        // get the words from the old document
        $i = 0;
        while ($i < count($this->document) and $this->document[$i]['diffid'] < $pos) {
            $word = $this->document[$i];
            if ($deleted) {
                if (! $word['deleted']) {
                    $word['deleted'] = true;
                    $word['deleted_by'] = $deleted_by;
                }
            }
            $doc[] = $word;
            $i++;
        }
        //take care of deleted words
        while ($i < count($this->document) and $this->document[$i]['diffid'] < 0) {
            $word = $this->document[$i];
            $doc[] = $word;
            $i++;
        }
        $this->document = array_slice($this->document, $i);
    }

    /**
     *
     * Returns an indexed array containing the plugins parameters indexed by key name
     * @param string    $pluginstr      Complete Plugin tag including brackets () containing the parameters
     * @return  array|bool              Array containing the parameters or false if none are given
     */
    public function retrieveParams($pluginstr)
    {
        $params = [];
        $start = strpos($pluginstr, '(');
        if ($start === false) {
            return false;
        }
        $end = strrpos($pluginstr, ')');
        if ($end === false) {
            return false;
        }
        $pstr = substr($pluginstr, $start + 1, $end - $start - 1);
        $plist = explode(',', $pstr);
        foreach ($plist as $paramstr) {
            $p = explode('=', trim($paramstr));
            $params[strtolower(trim($p[0]))] = preg_replace('/^"|^\&quot;|"$|\&quot;$/', '', trim($p[1]));
        }
        return $params;
    }

    /**
     *
     * merges a newer version of a page into the current document
     * @param string    $newpage    a string with a later version of the page
     * @param string    $newauthor  name of the author of the new version
     */
    public function mergeDiff($newpage, $newauthor)
    {
        $this->history = false;
        $author = $newauthor;
        $deleted = false;
        $deleted_by = '';
        $newdoc = [];
        $page = preg_replace(['/\{AUTHOR\(.+?\)\}/','/{AUTHOR\}/','/\{INCLUDE\(.+?\)\}\{INCLUDE\}/'], ' ~np~$0~/np~', $newpage);
        if ($this->parsed) {
            $page = TikiLib::lib('parser')->parse_data($page, ['suppress_icons' => true]);
            $page = preg_replace(['/\{AUTHOR\(.+?\)\}/','/{AUTHOR\}/','/\{INCLUDE\(.+?\)\}\{INCLUDE\}/'], ' ~np~$0~/np~', $page);
        }
        if ($this->nohtml) {
            $page = strip_tags($page);
        }
        preg_match_all($this->search, $page, $out, PREG_PATTERN_ORDER);
        $new = $out[0];
        $z = new Text_Diff($this->getDiffArray(), $new);
        $pos = 0;
        foreach ($z->getDiff() as $element) {
            if (is_a($element, 'Text_Diff_Op_copy')) {
                $this->moveWords($newdoc, $pos, $element->orig, $deleted, $deleted_by);
            } else {
                if (is_a($element, 'Text_Diff_Op_add')) {
                    $newdoc = $this->addWords($newdoc, $element->final, $author, $deleted, $deleted_by);
                } else {
                    if (is_a($element, 'Text_Diff_Op_delete')) {
                        $this->moveWords($newdoc, $pos, $element->orig, $deleted, $author);
                    } else { //change
                        $newdoc = $this->addWords($newdoc, $element->final, $author, $deleted, $deleted_by);
                        $this->moveWords($newdoc, $pos, $element->orig, true, $author);
                    } //delete
                } // add
            } // copy
        } // foreach diff
        $this->document = $newdoc;
    }

    /**
     *
     * Kills double whitespaces in parseAuthor before/after {author} tags
     * @param array $newdoc array containing the new document
     * @param int   $index  position in the old document
     */
    private function killDoubleWhitespaces(&$newdoc, &$index)
    {
        if (count($newdoc) > 2) {
            $w1 = $newdoc[count($newdoc) - 1]['word'];
            $w2 = $newdoc[count($newdoc) - 2]['word'];
            if ($this->nohtml) {
                $w1 = strip_tags($w1);
                $w2 = strip_tags($w2);
            }
            if (trim($w1) == '' and trim($w2) == '') {
                array_pop($newdoc); // kill one of the whitespaces
            }
        }
        if ($index < count($this->document) - 2) {
            $w1 = $this->document[$index + 1]['word'];
            $w2 = $this->document[$index + 2]['word'];
            if ($this->nohtml) {
                $w1 = strip_tags($w1);
                $w2 = strip_tags($w2);
            }
            if (trim($w1) == '' and trim($w2) == '') {
                $index++; // jump over one of the whitespaces
            }
        }
    }

    /**
     *
     * parses the left over author/include tags and sets the author accordingly
     */
    public function parseAuthorAndInclude()
    {
        $newdoc = [];
        $author = '';
        $deleted_by = '';
        for ($index = 0, $cdoc = count($this->document); $index < $cdoc; $index++) {
            $word = $this->document[$index];
            if (preg_match('/\{AUTHOR\(.+?\)\}/', $word['word'])) {
                $params = $this->retrieveParams($word['word']);
                $author = $params['author'];
                if (isset($params['deleted_by'])) {
                    $deleted_by = $params['deleted_by'];
                }
                // manage double whitespace before and after
                $this->killDoubleWhitespaces($newdoc, $index);
            } elseif (preg_match('/\{AUTHOR\}/', $word['word'])) {
                $author = '';
                $deleted_by = '';
                $this->killDoubleWhitespaces($newdoc, $index);
            } elseif (preg_match('/\{INCLUDE\(.+?\)\}\{INCLUDE\}/', $word['word'])) {
                $params = $this->retrieveParams($word['word']);
                $start = '';
                $stop = '';
                if (isset($params['start'])) {
                    $start = $params['start'];
                }
                if (isset($params['stop'])) {
                    $stop = $params['stop'];
                }
                $subdoc = new Document($params['page'], 0, $this->process, $this->showpopups, $start, $stop);
                $newdoc = array_merge($newdoc, $subdoc->get());
            } else { //normal word
                if ($author != '') {
                    $word['author'] = $author;
                }
                if ($deleted_by != '') {
                    $word['deleted'] = true;
                    $word['deleted_by'] = $deleted_by;
                }
                $newdoc[] = $word;
            }
        } //foreach
        $this->document = $newdoc;
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
        require_once('lib/diff/difflib.php');
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

        $html = diff2($old["data"], $new["data"], $diff_style);
        $smarty->assign_by_ref('diffdata', $html);
    }
}

function histlib_strip_irrelevant($data)
{
    $data = preg_replace("/<(h1|h2|h3|h4|h5|h6|h7)\s+([^\\\\>]+)>/i", '<$1>', $data);
    return $data;
}
