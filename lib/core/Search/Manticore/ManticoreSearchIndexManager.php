<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Search\Manticore;

use Search\Manticore\PdoClient as ManticorePdoClient;
use TikiLib;

class ManticoreSearchIndexManager
{
    private $sqlClient;
    private $currentIndex;
    private $indexPrefix;
    private $preferenceIndex;

    public function __construct($currentIndex, $indexPrefix, $dsn, $pdoPort = 9306)
    {
        $this->currentIndex = $currentIndex;
        $this->indexPrefix = $indexPrefix . 'main_';
        $this->preferenceIndex = $indexPrefix . 'pref_';
        $this->sqlClient = new ManticorePdoClient($dsn, $pdoPort);
    }

    public function getIndexes($onlyUnused = false)
    {
        try {
            $langLib = TikiLib::lib('language');
            $tikiLanguages = $langLib->getLanguages();
            $indexes = $this->sqlClient->getIndicesByPrefix($this->indexPrefix);

            // Return all indexes
            if (! $onlyUnused) {
                return array_values($indexes);
            }

            // Otherwise, return only unused indexes
            return array_filter($indexes, function ($indexName) use ($tikiLanguages) {
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
            throw new \Exception(tr('An error occurred while getting indices for Manticore: %0', $e->getMessage()));
        }
    }

    public function indexExists($index)
    {
        try {
            $status = $this->sqlClient->getIndexStatus($index);
            if (empty($status)) {
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

            if (strncmp($indexName, $this->preferenceIndex, strlen($this->indexPrefix)) === 0) {
                $pattern = '/^' . preg_quote($this->preferenceIndex, '/') . '[a-zA-Z_-]+$/';
            } elseif (strncmp($indexName, $this->indexPrefix, strlen($this->indexPrefix)) === 0) {
                $pattern = '/^' . preg_quote($this->indexPrefix, '/') . '[a-zA-Z0-9_-]+$/';
            } else {
                throw new \Exception(tr('Invalid Manticore Index Name: %0', $indexName));
            }

            // Validate index name against pattern
            if (empty($pattern) || ! preg_match($pattern, $indexName)) {
                throw new \Exception(tr('Invalid Manticore Index Name format: %0', $indexName));
            }

            $this->sqlClient->deleteIndex($indexName);
        } catch (\Exception $e) {
            throw new \Exception(tr('An error occurred while removing Manticore index: %0 - %1', $indexName, $e->getMessage()));
        }
    }
}
