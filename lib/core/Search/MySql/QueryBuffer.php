<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Search_MySql_QueryBuffer
{
    private $db;
    private $count;
    private $prefix;
    private $buffer = [];

    public function __construct(TikiDb $db, $count, $prefix)
    {
        $this->db = $db;
        $this->count = max(10, (int) $count);
        $this->prefix = $prefix;
    }

    public function push($block)
    {
        $this->buffer[] = $block;

        if (count($this->buffer) == $this->count) {
            $this->flush();
        }
    }

    public function flush()
    {
        if (count($this->buffer) == 0) {
            return;
        }

        return $this->realFlush();
    }

    public function setPrefix($prefix)
    {
        if ($prefix !== $this->prefix) {
            $this->flush();
        }

        $this->prefix = $prefix;
    }

    private function realFlush()
    {
        $query = $this->prefix . implode(', ', $this->buffer);
        $result = $this->db->queryError(
            $query,
            $error,
            options: [TikiDb::QUERY_OPTION_LOG_GROUP => Search_MySql_Table::UNIFIED_MYSQL_WRITE_LOG_GROUP]
        );

        $this->clear();

        if ($error) {
            throw new Search_MySql_LimitReachedException(tr("Could not perform index modification: %0", TikiFilter::get('xss')->filter($error)));
        }

        return $result;
    }

    public function clear()
    {
        $this->buffer = [];
    }
}
