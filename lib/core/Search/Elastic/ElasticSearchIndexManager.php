<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Search\Elastic;

use Search_Elastic_Connection;

class ElasticSearchIndexManager
{
    private $connection;
    private $aliasName;
    private $currentIndex;

    public function __construct($currentIndex, $aliasName, $connectionUrl)
    {
        $this->currentIndex = $currentIndex;
        $this->aliasName = $aliasName;
        $this->connection = Search_Elastic_Connection::buildFromPrefs($connectionUrl);
    }

    public function getUnusedIndexes()
    {
        try {
            $indexes = array_keys(get_object_vars($this->connection->rawApi('/_aliases')));
            return array_values(array_filter($indexes, function ($indexName) {
                return strpos($indexName, $this->aliasName) !== 0 && $indexName !== $this->currentIndex && strpos($indexName, '.') !== 0;
            }));
        } catch (\Exception $e) {
            throw new \Exception("An error occurred while getting indices for Elasticsearch: " . $e->getMessage());
        }
    }

    public function removeIndex($indexName)
    {
        try {
            $this->connection->deleteIndex($indexName);
        } catch (\Exception $e) {
            throw new \Exception("An error occurred while removing indices for Elasticsearch: " . $e->getMessage());
        }
    }
}
