<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
//this script may only be included - so its better to die if called directly.
if (str_contains($_SERVER["SCRIPT_NAME"], basename(__FILE__))) {
    header("location: index.php");
    exit;
}

/**
 *
 */
class HotwordsLib extends TikiLib
{
    /**
     * @param int $offset
     * @param $maxRecords
     * @param string $sort_mode
     * @param string $find
     * @return array
     */
    public function list_hotwords($offset = 0, $maxRecords = -1, $sort_mode = 'word_desc', $find = '')
    {

        if ($find) {
            $findesc = '%' . $find . '%';
            $mid = " where `word` like ?";
            $bindvars = [$findesc];
        } else {
            $mid = '';
            $bindvars = [];
        }

        $query = "select * from `tiki_hotwords` $mid order by " . $this->convertSortMode($sort_mode);
        $query_count = "select count(*) from `tiki_hotwords` $mid";
        $result = $this->query($query, $bindvars, $maxRecords, $offset);
        $count = $this->getOne($query_count, $bindvars);
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
     * @param $word
     * @param $url
     *
     * @return Tiki\TikiDb\PdoResult
     */
    public function add_hotword($word, $url)
    {
//      $word = addslashes($word);

        $url = addslashes($url);
        $query = "delete from `tiki_hotwords` where `word`=?";
        $this->query($query, [$word]);
        $query = "insert into `tiki_hotwords`(`word`,`url`) values(?,?)";
        return $this->query($query, [$word,$url]);
    }

    /**
     * @param $word
     *
     * @return Tiki\TikiDb\PdoResult
     */
    public function remove_hotword($word)
    {
        $query = "delete from `tiki_hotwords` where `word`=?";
        return $this->query($query, [$word]);
    }
}
$hotwordlib = new HotwordsLib();
