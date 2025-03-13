<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Search\MySql;

class MysqlSearchIndexManager
{
    private $currentIndex;

    public function __construct($currentIndex)
    {
        $this->currentIndex = $currentIndex;
    }

    public function getUnusedIndexes()
    {
        global $tikilib;

        try {
            $indexes = $tikilib->fetchAll("SHOW TABLES LIKE 'index_%'");
            $indexes = array_map(function ($item) {
                return array_values($item)[0];
            }, $indexes);
            return array_values(array_filter($indexes, function ($indexName) {
                return $indexName !== $this->currentIndex;
            }));
        } catch (\Exception $e) {
            throw new \Exception("An error occurred while getting indices for MySQL: " . $e->getMessage());
        }
    }

    public function removeIndex($indexName)
    {
        global $tikilib;

        try {
            $tikilib->query("DROP TABLE IF EXISTS `$indexName`");
        } catch (\Exception $e) {
            throw new \Exception("An error occurred while removing MySQL index: $indexName - " . $e->getMessage());
        }
    }
}
