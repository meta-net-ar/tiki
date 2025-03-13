<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Search\Manticore;

use Search\Manticore\PdoClient as ManticorePdoClient;

class ManticoreSearchIndexManager
{
    private $sqlClient;
    private $currentIndex;
    private $indexPrefix;

    public function __construct($currentIndex, $indexPrefix, $dsn, $pdoPort = 9306)
    {
        $this->currentIndex = $currentIndex;
        $this->indexPrefix = $indexPrefix;
        $this->sqlClient = new ManticorePdoClient($dsn, $pdoPort);
    }

    public function getUnusedIndexes()
    {
        try {
            $indexes = $this->sqlClient->getIndicesByPrefix($this->indexPrefix);
            return array_values(array_filter($indexes, function ($indexName) {
                return $indexName !== $this->currentIndex;
            }));
        } catch (\Exception $e) {
            throw new \Exception("An error occurred while getting indices for Manticore: " . $e->getMessage());
        }
    }

    public function removeIndex($indexName)
    {
        try {
            $this->sqlClient->deleteIndex($indexName);
        } catch (\Exception $e) {
            throw new \Exception("An error occurred while removing Manticore index: $indexName - " . $e->getMessage());
        }
    }
}
