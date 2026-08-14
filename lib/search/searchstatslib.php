<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 *
 */
class SearchStatsLib extends TikiLib
{
    private const MAX_SEARCH_TERM_LENGTH = 50;

    public function clear_search_stats()
    {
        $query = "delete from tiki_search_stats";
        $result = $this->query($query, []);
    }

    public function register_term_hit($term)
    {
        $term = trim($term);

        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            if (mb_strlen($term, 'UTF-8') > self::MAX_SEARCH_TERM_LENGTH) {
                $term = mb_substr($term, 0, self::MAX_SEARCH_TERM_LENGTH, 'UTF-8');
            }
        } elseif (strlen($term) > self::MAX_SEARCH_TERM_LENGTH) {
            $term = substr($term, 0, self::MAX_SEARCH_TERM_LENGTH);
        }

        $table = $this->table('tiki_search_stats');
        $table->insertOrUpdate(
            [
                'hits' => $table->increment(1),
            ],
            [
                'term' => $term,
                'hits' => 1,
            ]
        );
    }

    /**
     * @param $offset
     * @param $maxRecords
     * @param $sort_mode
     * @param $find
     * @return array
     */
    public function list_search_stats($offset, $maxRecords, $sort_mode, $find)
    {
        if ($find) {
            $mid = " where (`term` like ?)";
            $bindvars = ["%$find%"];
        } else {
            $mid = "";
            $bindvars = [];
        }

        $query = "select * from `tiki_search_stats` $mid order by " . $this->convertSortMode($sort_mode);
        $query_count = "select count(*) from `tiki_search_stats` $mid";
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
}
