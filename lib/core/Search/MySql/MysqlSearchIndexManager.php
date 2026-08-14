<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Search\MySql;

use TikiLib;

class MysqlSearchIndexManager
{
    private $currentIndex;
    private $preferenceIndex;
    private $indexPrefix;

    public function __construct($currentIndex)
    {
        $this->currentIndex = $currentIndex;
        $this->indexPrefix = 'index_';
        $this->preferenceIndex = 'index_pref_';
    }

    public function getIndexes($onlyUnused = false)
    {
        global $tikilib;

        try {
            $langLib = TikiLib::lib('language');
            $tikiLanguages = $langLib->getLanguages();
            $query = "SHOW TABLES LIKE ?";
            $list = $tikilib->fetchAll($query, [$this->indexPrefix . '%']);
            $indexes = array_map(function ($item) {
                $tmp = array_values($item);
                return reset($tmp);
            }, $list);

            // Return all indexes
            if (! $onlyUnused) {
                return array_values($indexes);
            }

            // Otherwise, return only unused indexes
            return array_values(array_filter($indexes, function ($indexName) use ($tikiLanguages) {
                // Ensure it matches the expected prefix (index_*)
                if (strncmp($indexName, $this->indexPrefix, strlen($this->indexPrefix)) !== 0) {
                    return false;
                }

                // Skip the current active index
                if (preg_match("/^{$this->currentIndex}(_\d*)?$/", $indexName)) {
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
            }));
        } catch (\Exception $e) {
            throw new \Exception(tr('An error occurred while getting indices for MySQL: %0', $e->getMessage()));
        }
    }

    public function indexExists($index)
    {
        global $tikilib;

        try {
            $query = "SHOW TABLES LIKE ?";
            $indexes = $tikilib->fetchAll($query, [$index]);
            if (empty($indexes)) {
                return false;
            }
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    public function removeIndex($indexName)
    {
        global $tikilib;

        try {
            $pattern = null;

            if (strncmp($indexName, $this->preferenceIndex, strlen($this->preferenceIndex)) === 0) {
                $pattern = '/^' . preg_quote($this->preferenceIndex, '/') . '[a-zA-Z_-]+$/';
            } elseif (strncmp($indexName, $this->indexPrefix, strlen($this->indexPrefix)) === 0) {
                $pattern = '/^' . preg_quote($this->indexPrefix, '/') . '[a-zA-Z0-9_-]+$/';
            } else {
                throw new \Exception(tr('Invalid MYSQL Index Name: %0', $indexName));
            }

            // Validate index name against pattern
            if (empty($pattern) || ! preg_match($pattern, $indexName)) {
                throw new \Exception(tr('Invalid MYSQL Index Name format: %0', $indexName));
            }

            $escapedTable = "`" . $indexName . "`";
            $query = "DROP TABLE IF EXISTS $escapedTable";
            $tikilib->query($query);
        } catch (\Exception $e) {
            throw new \Exception(tr('An error occurred while removing MySQL index: %0 - %1', $indexName, $e->getMessage()));
        }
    }
}
