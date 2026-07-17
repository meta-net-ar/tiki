<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

/**
 * This class handles interaction with MySQL raw database table for the MySQL search index.
 *
 * Since Jul, 2024, underlaying table MyISAM was switched to InnoDB storage engine which resulted in unavoidable index partitioning due to hard-limit of InnoDB row size (https://dev.mysql.com/doc/refman/8.0/en/innodb-limits.html)
 * Partitioning works by intelligently splitting tables by max supported field count per table. The benefit over splitting by tracker is that this will cover the case when tracker fields are more than allowed for one table and also we won't have to deal with dereferencing field names to tracker IDs when inserting the records. Furthermore, we have global sources which add fields to any tracker item, so it is not enough to put the tracker fields in one table.
 *
 * It splits fields into separate tables when it reaches the maximum number of supported fields per table. This number depends on server configuration, so we try to calculate the maximum dynamically. Then, when this maximum is reached, a new table is created. This results in the following structure of tables:
 * - index_abcd - main one containing object_type, object_id and first X columns
 * - index_abcd_1 - next X columns
 * - index_abcd_2 - next X columns
 * ...
 * - index_abcd_N - the last Y columns (Y<=X)
 *
 * 1 indexed document can be spread across multiple of these tables or all of them. This depends on the search indexer which adds/augments documents with fields. The side-effect here is that now we need to insert rows in multiple tables sharing the same primary key ID. This means that we cannot (easily) buffer insert statements, so we end up executing the insert statements immediately to get the inserted id and execute the next ones immediately. This has a side effect of slowing down the indexing time - my sample database slowed down by around 6 times (from 1 minute to 6 minutes). If this turns out to be a problem, we can optimize by supporting per-table buffer and executing buffers sequentially, adding the IDs from the first buffer (after successful insertion) to the insert statements of next ones. Not trivial to do, so I will postpone and see how this goes. Another idea proposed by @benoitg is to use UUID primary keys but we have to check for side-effects elsewhere in the unified index.
 *
 * Searching has been changed to support joining tables, so the query syntax and everything remains the same, unit tests continue to pass for all engine types, etc. The only change is that now, instead of executing `select * from index_table where ...`, we execute `select * from index_table left join index_table_1 using(id) left join index_table_2 using(id) ... where ...`. Since field names are unique, we don't need to do aliases when joining and since ID is a primary index, these joins are fast. There shouldn't be a performance penalty when searching partitioned index like that.
 *
 * A good side effect is that we now support potentially unlimited number of fields in MySQL index - we had a unit test to proof that 1500 fields by 3 different variations turn into an exception that is not supported. I converted this test to a normal test that such an index with 4500 fields is now supported and documents can be retrieved from it.
 */
class Search_MySql_Table extends TikiDb_Table
{
    public const MAX_MYSQL_INDEXES_PER_TABLE = 64;
    public const UNIFIED_MYSQL_READ_LOG_GROUP = "Unified index MySql: Read";
    public const UNIFIED_MYSQL_WRITE_LOG_GROUP = "Unified index MySql: Write";
    private $definition = false;
    private $indexes = [];
    private $tableFields = [];
    private $exists = null;

    private $schemaBuffer;
    private $dataBuffer;
    private $tfTranslator;
    private $fullTextTablesToAnalyze = [];

    private $max_columns_per_table = -1;

    public function __construct($db, $table)
    {
        parent::__construct($db, $table);

        $table = $this->escapeIdentifier($this->tableName);
        $this->schemaBuffer = new Search_MySql_QueryBuffer($db, 2000, "ALTER TABLE $table ");
        $this->dataBuffer = new Search_MySql_QueryBuffer($db, 100, '-- '); // Null Object, replaced later
        $this->tfTranslator = new Search_MySql_TrackerFieldTranslator();
        $this->calculateMaxColumnsPerTable();
    }

    public function __destruct()
    {
        try {
            $this->flush();
        } catch (Search_MySql_Exception $e) {
            # ignore this to cleanly destruct the object
        }
    }

    public function drop()
    {
        $stopwordTableName = $this->tableName . '_stopwords';
        $escapedStopwordTable = $this->escapeIdentifier($stopwordTableName);
        $this->db->query("DROP TABLE IF EXISTS $escapedStopwordTable", options: [TikiDb::QUERY_OPTION_LOG_GROUP => self::UNIFIED_MYSQL_WRITE_LOG_GROUP]);

        $tables = $this->indexTables();
        foreach ($tables as $table) {
            $table = $this->escapeIdentifier($table);
            $this->db->query("DROP TABLE IF EXISTS $table", options: [TikiDb::QUERY_OPTION_LOG_GROUP => self::UNIFIED_MYSQL_WRITE_LOG_GROUP]);
        }

        $this->definition = false;
        $this->exists = false;

        $this->emptyBuffer();
    }

    public function exists()
    {
        if (is_null($this->exists)) {
            $tables = $this->db->listTables();
            $this->exists = in_array($this->tableName, $tables);
        }

        return $this->exists;
    }

    public function insert(array $values, $ignore = false)
    {
        $sharded = [];
        $fields = array_keys($values);
        foreach ($fields as $fieldName) {
            $table = $this->definition[$fieldName]['table'];
            $sharded[$table][$fieldName] = $values[$fieldName];
        }

        $id = 0;
        foreach ($sharded as $table => $values) {
            if ($id) {
                $values['id'] = $id;
            }
            $keySet = implode(', ', array_map([$this, 'escapeIdentifier'], array_map([$this->tfTranslator, 'shortenize'], array_keys($values))));
            $valueSet = '(' . implode(', ', array_map([$this->db, 'qstr'], $values)) . ')';
            $this->addToBuffer($table, $keySet, $valueSet);
            $result = $this->dataBuffer->flush();
            if ($id == 0 && $result && $result->numrows > 0) {
                $id = $this->db->lastInsertId();
            }
        }

        return 0;
    }

    public function ensureHasField($fieldName, $type)
    {
        $this->loadDefinition();

        if (! isset($this->definition[$fieldName])) {
            $table = $this->addField($fieldName, $type);
            $this->definition[$fieldName] = [
                'table' => $table,
                'type' => $type,
            ];
            $this->tableFields[$table][] = $fieldName;
        }
    }

    public function hasIndex($fieldName, $type)
    {
        $this->loadDefinition();

        $indexName = $fieldName . '_' . $type;
        return isset($this->indexes[$indexName]);
    }

    /**
     * Make sure the indexing table contains a certain index. Index will only be added if it is not present on the table.
     * @param $fieldName
     * @param $type
     * @throws Search_MySql_QueryException
     */
    public function ensureHasIndex($fieldName, $type)
    {
        global $prefs;

        $this->loadDefinition();
        $fieldName = $this->tfTranslator->normalize($fieldName);

        if (! isset($this->definition[$fieldName])) {
            if (preg_match('/^tracker_field_/', $fieldName)) {
                $msg = tr('Field %0 does not exist in the current index. Please check field permanent name and if you have any items in that tracker.', TikiFilter::get('xss')->filter($fieldName));
                if ($prefs['unified_exclude_nonsearchable_fields'] === 'y') {
                    $msg .= ' ' . tr('You have disabled indexing non-searchable tracker fields. Check if this field is marked as searchable.');
                }
            } else {
                $msg = tr('Field %0 does not exist in the current index. If this is a tracker field, the proper syntax is tracker_field_%0.', TikiFilter::get('xss')->filter($fieldName), TikiFilter::get('xss')->filter($fieldName));
            }
            $e = new Search_MySql_QueryException($msg);
            if ($fieldName == 'tracker_id' || $prefs['search_error_missing_field'] !== 'y') {
                $e->suppress_feedback = true;
            }
            throw $e;
        }

        $indexName = $fieldName . '_' . $type;
        $table = $this->definition[$fieldName]['table'];

        // Static MySQL limit on 64 indexes per table
        $indexesPerTable = count(array_filter($this->indexes, function ($r) use ($table) {
            return $r['table'] == $table;
        }));
        if (! isset($this->indexes[$indexName]) && $indexesPerTable < self::MAX_MYSQL_INDEXES_PER_TABLE) {
            if ($type == 'fulltext') {
                $this->addFullText($fieldName);
                $actualIndex = true;
            } elseif ($type == 'index') {
                $this->addIndex($fieldName);
                $actualIndex = true;
            } else {
                $actualIndex = false;
            }

            if ($actualIndex) {
                $this->indexes[$indexName] = [
                    'table' => $table,
                ];
            }
        } elseif ($indexesPerTable >= self::MAX_MYSQL_INDEXES_PER_TABLE) {
            $msg = tr('Maximum number of indexes per InnoDB table reached for MySQL index %0 when trying to add index %1.', $table, $indexName);
            throw new Search_MySql_QueryException($msg);
        }
    }

    public function getFieldType($fieldName)
    {
        $this->loadDefinition();
        if (isset($this->definition[$fieldName])) {
            return $this->definition[$fieldName]['type'];
        }
        return null;
    }

    public function getFieldsCount()
    {
        $this->loadDefinition();
        return array_sum(array_map(function ($fields) {
            return count($fields) - 1;
        }, $this->tableFields));
    }

    public function fetchCountIndex($conditions = [])
    {
        $tables = $this->indexTables();
        $join = $this->getIndexTablesSqlJoins();
        if ($result = $this->fetchAll([$this->count()], $conditions, 1, 0, null, $join, options: [TikiDb::QUERY_OPTION_LOG_GROUP => self::UNIFIED_MYSQL_READ_LOG_GROUP])) {
            $result = reset($result);
            if ($result) {
                return reset($result);
            }
        }
        return false;
    }

    public function getIndexTablesSqlJoins()
    {
        $tables = $this->indexTables();
        array_shift($tables);
        $join = '';
        foreach ($tables as $table) {
            $join .= ' LEFT JOIN ' . $this->escapeIdentifier($table) . ' USING(id)';
        }
        return  $join;
    }

    /**
     * Fetch results from all the index tables but leave the fields relevant for each document type
     * to reduce memory footprint for big indices.
     * @param array $selectFields
     * @param array $conditions
     * @param int $numrows
     * @param int $offset
     * @param string $orderClause
     * @return array of associative arrays
     */
    public function fetchAllIndex(array $selectFields = [], array $conditions = [], $numrows = -1, $offset = -1, $orderClause = null)
    {
        $available_fields = TikiLib::lib('unifiedsearch')->getAvailableFields();
        $join = $this->getIndexTablesSqlJoins();
        $resultset = $this->query($selectFields, $conditions, $numrows, $offset, $orderClause, $join, options: [TikiDb::QUERY_OPTION_LOG_GROUP => self::UNIFIED_MYSQL_READ_LOG_GROUP]);
        $hasCustomSelect = ! (isset($selectFields[0]) && ($selectFields[0] instanceof TikiDb_Expr) && $selectFields[0]->getQueryPart(null) === '*');
        $result = [];
        while ($row = $resultset->fetchRow()) {
            if ($hasCustomSelect) {
                $fields = [];
            } elseif ($row['object_type'] == 'trackeritem') {
                $fields = $available_fields['object_types']['trackeritem' . $row['tracker_id']] ?? [];
            } else {
                $fields = $available_fields['object_types'][$row['object_type']] ?? [];
            }
            if ($fields) {
                $real_row = [];
                foreach ($fields as $field) {
                    $field = $this->tfTranslator->shortenize($field);
                    foreach ($row as $key => $value) {
                        if (str_starts_with($key, $field)) {
                            $real_row[$key] = $row[$key];
                        }
                    }
                }
                $result[] = $real_row;
            } else {
                $result[] = $row;
            }
        }
        return $result;
    }

    public function deleteMultipleIndex(array $conditions)
    {
        if (! $this->exists()) {
            return;
        }

        $tables = $this->indexTables();
        $matches = $this->fetchAll(['id'], $conditions, options: [TikiDb::QUERY_OPTION_LOG_GROUP => self::UNIFIED_MYSQL_WRITE_LOG_GROUP]);
        foreach ($matches as $row) {
            $conditions = ['id' => $row['id']];
            foreach ($tables as $table) {
                $bindvars = [];
                $query = "DELETE FROM {$this->escapeIdentifier($table)}";
                $query .= $this->buildConditions($conditions, $bindvars);
                $this->db->queryException($query, $bindvars, options: [TikiDb::QUERY_OPTION_LOG_GROUP => self::UNIFIED_MYSQL_WRITE_LOG_GROUP]);
            }
        }
    }

    public function indexTables($tableName = null)
    {
        if (! empty($this->definition)) {
            $tables = array_keys($this->tableFields);
        } else {
            if (! $tableName) {
                $tableName = $this->tableName;
            }
            $tables = [$tableName];
            $result = $this->db->fetchAll("SHOW TABLES LIKE '" . $tableName . "_%'", options: [TikiDb::QUERY_OPTION_LOG_GROUP => self::UNIFIED_MYSQL_READ_LOG_GROUP]);

            $partitions = array_filter(
                array_map(fn($row) => array_shift($row), $result),
                fn($table) => preg_match('/_[0-9]+$/', $table)
            );

            $tables = array_merge($tables, $partitions);
        }
        return $tables;
    }

    private function loadDefinition()
    {
        if (! empty($this->definition)) {
            return;
        }

        if (! $this->exists()) {
            $this->createTable();
            $this->loadDefinition();
        }

        $this->definition = [];
        $this->indexes = [];
        $this->tableFields = [];

        $tables = $this->indexTables();
        foreach ($tables as $table) {
            $result = $this->db->fetchAll("DESC {$this->escapeIdentifier($table)}", options: [TikiDb::QUERY_OPTION_LOG_GROUP => self::UNIFIED_MYSQL_READ_LOG_GROUP]);
            foreach ($result as $row) {
                $this->definition[$this->tfTranslator->normalize($row['Field'])] = [
                    'table' => $table,
                    'type' => $row['Type']
                ];
                $this->tableFields[$table][] = $this->tfTranslator->normalize($row['Field']);
            }

            $result = $this->db->fetchAll("SHOW INDEXES FROM {$this->escapeIdentifier($table)}", options: [TikiDb::QUERY_OPTION_LOG_GROUP => self::UNIFIED_MYSQL_READ_LOG_GROUP]);
            foreach ($result as $row) {
                $this->indexes[$this->tfTranslator->normalize($row['Key_name'])] = [
                    'table' => $table,
                ];
            }
        }
    }

    private function createTable()
    {
        $table = $this->escapeIdentifier($this->tableName);
        $this->db->query(
            "CREATE TABLE IF NOT EXISTS $table (
                `id` INT NOT NULL AUTO_INCREMENT,
                `object_type` VARCHAR(15) NOT NULL,
                `object_id` VARCHAR(235) NOT NULL,
                PRIMARY KEY(`id`),
                INDEX (`object_type`, `object_id`(160))
            ) ENGINE=InnoDB ROW_FORMAT=DYNAMIC",
            options: [TikiDb::QUERY_OPTION_LOG_GROUP => self::UNIFIED_MYSQL_WRITE_LOG_GROUP]
        );
        $this->exists = true;

        $this->setupStopwordTable();
        $this->emptyBuffer();
    }

    private function createAuxTable($table)
    {
        $table = $this->escapeIdentifier($table);
        $this->db->query(
            "CREATE TABLE IF NOT EXISTS $table (
                `id` INT NOT NULL AUTO_INCREMENT,
                PRIMARY KEY(`id`)
            ) ENGINE=InnoDB ROW_FORMAT=DYNAMIC",
            options: [TikiDb::QUERY_OPTION_LOG_GROUP => self::UNIFIED_MYSQL_WRITE_LOG_GROUP]
        );
    }

    private function addField($fieldName, $type)
    {
        $targetTable = null;
        $lastNum = 1;
        foreach ($this->tableFields as $table => $fields) {
            if ($this->max_columns_per_table > 0 && count($fields) < $this->max_columns_per_table) {
                $targetTable = $table;
                break;
            }
            if (preg_match("/^{$this->tableName}_(\d+)$/", $table, $m)) {
                $lastNum = intval($m[1]);
            }
        }

        if (is_null($targetTable)) {
            $targetTable = $this->tableName . '_' . ($lastNum + 1);
            $this->createAuxTable($targetTable);
        }

        $table = $this->escapeIdentifier($targetTable);
        $this->schemaBuffer->setPrefix("ALTER TABLE $table ");

        $fieldName = $this->escapeIdentifier($this->tfTranslator->shortenize($fieldName));
        $this->schemaBuffer->push("ADD COLUMN $fieldName $type");

        return $targetTable;
    }

    private function addIndex($fieldName)
    {
        $table = $this->escapeIdentifier($this->definition[$fieldName]['table']);
        $this->schemaBuffer->setPrefix("ALTER TABLE $table ");

        $indexName = $fieldName . '_index';
        $escapedIndex = $this->escapeIdentifier($this->tfTranslator->shortenize($indexName));
        $escapedField = $this->escapeIdentifier($this->tfTranslator->shortenize($fieldName));

        $currentType = $this->definition[$fieldName]['type'];
        if ($currentType == 'TEXT' || $currentType == 'text') {
            $this->schemaBuffer->push("MODIFY COLUMN $escapedField VARCHAR(235)");
            $this->definition[$fieldName]['type'] = 'VARCHAR(235)';
        }

        $this->schemaBuffer->push("ADD INDEX $escapedIndex ($escapedField)");
    }

    /**
    * Creates and populates a custom stopword table for the current index.
    * This allows Tiki to control InnoDB's stopword list directly during index creation.
    * This method is called right before a FULLTEXT index is added.
    *
    * @return string The name of the created stopword table.
    */
    private function setupStopwordTable(): string
    {
        global $prefs;

        $stopwordTableName = $this->tableName . '_stopwords';

        $this->db->query("DROP TABLE IF EXISTS `{$stopwordTableName}`");
        $this->db->query("CREATE TABLE `{$stopwordTableName}` (value VARCHAR(30)) ENGINE=INNODB");
        $stopwords = $prefs['unified_stopwords'] ?? [];
        if (! empty($stopwords)) {
            $stopwordTable = $this->db->table($stopwordTableName, false);
            foreach ($stopwords as $word) {
                $stopwordTable->insert(['value' => $word]);
            }
        }

        return $stopwordTableName;
    }

    /**
     * Encode Tiki-supported identifiers for InnoDB's filename character set.
     *
     * Tiki-created database names are limited to ASCII letters, digits, dollar
     * signs, underscores, and hyphens. MySQL preserves letters, digits, and
     * underscores in filenames, so only the remaining two characters require
     * encoding. Search index names use a subset of the same character set.
     */
    private static function encodeInnoDBIdentifier(string $identifier): string
    {
        return strtr($identifier, [
            '$' => '@0024',
            '-' => '@002d',
        ]);
    }

    private function addFullText($fieldName)
    {
        $stopwordTableName = $this->setupStopwordTable();
        $dbName = $this->db->getOne("SELECT DATABASE()");
        $innodbStopwordTableName = sprintf(
            '%s/%s',
            self::encodeInnoDBIdentifier($dbName),
            self::encodeInnoDBIdentifier($stopwordTableName)
        );
        $this->db->query("SET SESSION innodb_ft_user_stopword_table = ?", [$innodbStopwordTableName]);

        $table = $this->escapeIdentifier($this->definition[$fieldName]['table']);
        $this->schemaBuffer->setPrefix("ALTER TABLE $table ");

        $indexName = $fieldName . '_fulltext';
        $escapedIndex = $this->escapeIdentifier($this->tfTranslator->shortenize($indexName));
        $escapedField = $this->escapeIdentifier($this->tfTranslator->shortenize($fieldName));

        $this->schemaBuffer->push("ADD FULLTEXT INDEX $escapedIndex ($escapedField)");
        // InnoDB presently supports one FULLTEXT index creation at a time
        $this->schemaBuffer->flush();
        $this->fullTextTablesToAnalyze[$this->definition[$fieldName]['table']] = true;
    }

    private function emptyBuffer()
    {
        $this->schemaBuffer->clear();
        $this->dataBuffer->clear();
        $this->fullTextTablesToAnalyze = [];
    }

    private function addToBuffer($table, $keySet, $valueSet)
    {
        $this->schemaBuffer->flush();

        $this->dataBuffer->setPrefix("INSERT IGNORE INTO {$this->escapeIdentifier($table)} ($keySet) VALUES ");
        $this->dataBuffer->push($valueSet);
    }

    public function flush()
    {
        $this->schemaBuffer->flush();
        $this->dataBuffer->flush();

        /*
         * Tiki creates FULLTEXT indexes lazily, after the search table has been populated.
         * On affected MySQL and MariaDB versions, adding another FULLTEXT index can make an
         * existing MATCH() return an invalid relevance value. Using that value in Tiki's
         * weighted score calculation then fails with "DOUBLE value is out of range"
         * (error 1690, SQLSTATE 22003).
         *
         * MySQL tracks this trigger and error in https://bugs.mysql.com/bug.php?id=118535.
         * ANALYZE TABLE is a tested workaround for Tiki's MySQL/MariaDB backend, not a
         * permanent upstream fix.
         */
        foreach (array_keys($this->fullTextTablesToAnalyze) as $table) {
            $table = $this->escapeIdentifier($table);
            $this->db->query(
                "ANALYZE TABLE $table",
                options: [TikiDb::QUERY_OPTION_LOG_GROUP => self::UNIFIED_MYSQL_WRITE_LOG_GROUP]
            );
        }
        $this->fullTextTablesToAnalyze = [];
    }

    private function calculateMaxColumnsPerTable()
    {
        // actual row size is approximately half the size of the innodb page size
        $actual_size = 8000;
        $innodb_page_size = $this->db->getOne('select @@innodb_page_size');
        if ($innodb_page_size) {
            $actual_size = intval($innodb_page_size) / 2;
        }
        if ($actual_size > 16000) {
            $actual_size = 16000;
        }
        // object_type/object_id use varchar total of 250 characters in utf8mb4 4 bytes per character
        $actual_size -= 250 * 4;
        // up to 40 byte pointers for TEXT columns, the other ones we use fit in 40 bytes, we don't use varchars in the index
        $this->max_columns_per_table = floor($actual_size / 40);
    }
}
