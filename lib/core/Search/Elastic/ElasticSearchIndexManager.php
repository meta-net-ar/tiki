<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Search\Elastic;

use Search_Elastic_Connection;
use TikiLib;

class ElasticSearchIndexManager
{
    private $connection;
    private $indexPrefix;
    private $currentIndex;
    private $preferenceIndex;

    public function __construct($currentIndex, $indexPrefix, $connectionUrl)
    {
        $this->currentIndex = $currentIndex;
        $this->indexPrefix = $indexPrefix . 'main_';
        $this->preferenceIndex = $indexPrefix . 'pref_';
        $this->connection = Search_Elastic_Connection::buildFromPrefs($connectionUrl);
    }

    public function getIndexes($onlyUnused = false)
    {
        try {
            $langLib = TikiLib::lib('language');
            $tikiLanguages = $langLib->getLanguages();
            $indexes = array_keys(get_object_vars($this->connection->rawApi('/_aliases')));

            // Return all indexes
            if (! $onlyUnused) {
                return array_filter($indexes, fn($indexName) => ! str_starts_with($indexName, '.'));
            }

            // Otherwise, return only unused indexes
            return array_filter($indexes, function ($indexName) use ($tikiLanguages) {
                // Skip reserved indexes
                if (str_starts_with($indexName, '.')) {
                    return false;
                }

                // Ensure it matches the expected prefix (indexPrefix_main_*)
                if (strncmp($indexName, $this->indexPrefix, strlen($this->indexPrefix)) !== 0) {
                    return false;
                }

                // Skip the current active index
                if ($indexName === $this->currentIndex) {
                    return false;
                }

                // Skip preference indexes still in use
                if (preg_match('/^' . preg_quote($this->preferenceIndex, '/') . '([a-zA-Z-]+)$/', $indexName, $matches)) {
                    $languageCode = $matches[1];
                    if (in_array($languageCode, $tikiLanguages, true)) {
                        return false;
                    }
                }

                return true;
            });
        } catch (\Exception $e) {
            throw new \Exception(tr('An error occurred while retrieving indices for Elasticsearch: %0', $e->getMessage()));
        }
    }

    public function indexExists($index)
    {
        try {
            $response = $this->connection->rawApi("/$index?pretty");
            if (! empty($response->status) && $response->status == 404) {
                return false;
            }
        } catch (\Exception $e) {
            return false;
        }
        return true;
    }

    public function removeIndex($indexName)
    {
        try {
            $pattern = null;

            if (strncmp($indexName, $this->preferenceIndex, strlen($this->preferenceIndex)) === 0) {
                $pattern = '/^' . preg_quote($this->preferenceIndex, '/') . '[a-zA-Z0-9_-]+$/';
            } elseif (strncmp($indexName, $this->indexPrefix, strlen($this->indexPrefix)) === 0) {
                $pattern = '/^' . preg_quote($this->indexPrefix, '/') . '[a-zA-Z0-9_-]+$/';
            } else {
                throw new \Exception(tr('Invalid ElasticSearch Index Name: %0', $indexName));
            }

            if (! preg_match($pattern, $indexName)) {
                throw new \Exception(tr('Invalid ElasticSearch Index Name format: %0', $indexName));
            }

            $this->connection->deleteIndex($indexName);
        } catch (\Exception $e) {
            throw new \Exception(tr('An error occurred while removing indices for Elasticsearch: %0 - %1', $indexName, $e->getMessage()));
        }
    }
}
