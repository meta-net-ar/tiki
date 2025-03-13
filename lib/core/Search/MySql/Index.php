<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

use Tiki\Utilities\Identifiers;

class Search_MySql_Index implements Search_Index_Interface
{
    private $db;
    private $table;
    private $builder;
    private $tfTranslator;
    private $index_name;

    public function __construct(TikiDb $db, $index)
    {
        $this->db = $db;
        $this->index_name = $index;
        $this->table = new Search_MySql_Table($db, $index);
        $this->builder = new Search_MySql_QueryBuilder($db, $this->table);
        $this->tfTranslator = new Search_MySql_TrackerFieldTranslator();
    }

    public function destroy()
    {
        $this->table->drop();
        return true;
    }

    public function exists()
    {
        return $this->table->exists();
    }

    public function addDocument(array $data)
    {
        foreach ($data as $key => $value) {
            $this->handleField($key, $value);
        }

        $data = array_map(
            function ($entry) {
                return $this->getValue($entry);
            },
            $data
        );

        $this->table->insert($data);
    }

    private function getValue(Search_Type_Interface $data)
    {
        $value = $data->getValue();

        if (isset($value)) {
            if (
                ($data instanceof Search_Type_Whole
                    || $data instanceof Search_Type_PlainShortText
                    || $data instanceof Search_Type_PlainText
                    || $data instanceof Search_Type_MultivalueText)
                && strlen($value) >= 65535
            ) {
                $value = function_exists('mb_strcut') ?
                    mb_strcut($value, 0, 65535) : substr($value, 0, 65535);
            }
        }

        return $value;
    }

    private function handleField($name, $value)
    {
        if ($value instanceof Search_Type_Whole) {
            $this->table->ensureHasField($name, 'TEXT');
        } elseif ($value instanceof Search_Type_Numeric) {
            $this->table->ensureHasField($name, 'FLOAT');
        } elseif ($value instanceof Search_Type_PlainShortText) {
            $this->table->ensureHasField($name, 'TEXT');
        } elseif ($value instanceof Search_Type_PlainText) {
            $this->table->ensureHasField($name, 'TEXT');
        } elseif ($value instanceof Search_Type_PlainMediumText) {
            $this->table->ensureHasField($name, 'MEDIUMTEXT');
        } elseif ($value instanceof Search_Type_WikiText) {
            $this->table->ensureHasField($name, 'MEDIUMTEXT');
        } elseif ($value instanceof Search_Type_MultivalueText) {
            $this->table->ensureHasField($name, 'TEXT');
        } elseif ($value instanceof Search_Type_Timestamp) {
            $this->table->ensureHasField($name, $value->isDateOnly() ? 'DATE' : 'DATETIME');
        } else {
            throw new Exception('Unsupported type: ' . get_class($value));
        }
    }

    public function endUpdate()
    {
        $this->table->flush();
    }

    public function optimize()
    {
        $this->table->flush();
    }

    public function invalidateMultiple(array $objectList)
    {
        foreach ($objectList as $object) {
            $this->table->deleteMultipleIndex($object);
        }
    }

    public function findClosestWord($word, $funcName)
    {
        $tikilib = TikiLib::lib('tiki');

        $sql = "
            SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(contents, ' ', numbers.n), ' ', -1) AS word,
            $funcName(SUBSTRING_INDEX(SUBSTRING_INDEX(contents, ' ', numbers.n), ' ', -1), ?) AS distance
            FROM {$this->table->getTableName()}
            {$this->table->getIndexTablesSqlJoins()}
            JOIN (SELECT 1 n UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7) numbers
            ON CHAR_LENGTH(contents) - CHAR_LENGTH(REPLACE(contents, ' ', '')) >= numbers.n - 1
            WHERE LENGTH(SUBSTRING_INDEX(SUBSTRING_INDEX(contents, ' ', numbers.n), ' ', -1)) >= 3
            HAVING distance <= 2
            ORDER BY distance ASC
            LIMIT 1;
        ";

        $result = $tikilib->query($sql, [$word])->fetchRow();

        return $result ? $result['word'] : $word;
    }

    public function callSuggestions(array $options): array
    {
        $didYouMean = false;
        $correctedWords = [];
        $words = $options['words'];
        $condition = $options['condition'];
        $conditions = $options['conditions'];
        $levenshteinFunctionName = $options['levenshteinFunctionName'];

        foreach ($words as $word) {
            $correctWord = $this->findClosestWord($word, $levenshteinFunctionName);
            if ($correctWord != $word) {
                $didYouMean = true;
                $correctedWords[] = $correctWord;
                $condition = str_replace("$word", "$correctWord", $condition);
            } else {
                $correctedWords[] = $word;
            }
        }

        $count = 0;
        if ($didYouMean) {
            $conditions = [$this->table->expr($condition)];
            $count = $this->table->fetchCountIndex($conditions);
        }

        return [$didYouMean, $correctedWords, $count, $conditions];
    }

    public function find(Search_Query_Interface $query, $resultStart, $resultCount)
    {
        try {
            $words = $query->getWords();
            $condition = $this->builder->build($query->getExpr());
            $conditions = empty($condition) ? [] : [
                $this->table->expr($condition),
            ];

            $scoreFields = [];
            $indexes = $this->builder->getRequiredIndexes();
            foreach ($indexes as $index) {
                $this->table->ensureHasIndex($index['field'], $index['type']);

                if (! in_array($index, $scoreFields) && $index['type'] == 'fulltext') {
                    $scoreFields[] = $index;
                }
            }

            $this->table->flush();

            $order = $this->getOrderClause($query, (bool) $scoreFields);

            if ($selectFields = $query->getSelectionFields()) {
                $selectFields = array_map(function ($field) {
                    return $this->tfTranslator->shortenize($field);
                }, $selectFields);
            } else {
                $selectFields = $this->table->all();
            }

            if ($scoreFields) {
                $str = $this->db->qstr(implode(' ', $words));
                $scoreCalc = '';
                foreach ($scoreFields as $field) {
                    $scoreCalc .= $scoreCalc ? ' + ' : '';
                    $scoreCalc .= "ROUND(MATCH(`{$this->tfTranslator->shortenize($field['field'])}`) AGAINST ($str),2) * {$field['weight']}";
                }
                $selectFields['score'] = $this->table->expr($scoreCalc);
            }

            $count = $this->table->fetchCountIndex($conditions);
            if ($query->processDidYouMean() && $count === 0) {
                // Try to create levenshtein function
                $levenshteinFunctionName = $this->createLevenshteinFunction();
                if ($levenshteinFunctionName) {
                    try {
                        $params = compact('words', 'condition', 'conditions', 'levenshteinFunctionName');
                        list($didYouMean, $correctKeywords, $count, $conditions) = $this->callSuggestions($params);
                        $scoreCalc = str_replace($words, $correctKeywords, $scoreCalc);
                    } finally {
                        // Drop the function after use
                        $this->db->query("DROP FUNCTION $levenshteinFunctionName");
                    }
                }
            }
            $entries = $this->table->fetchAllIndex($selectFields, $conditions, $resultCount, $resultStart, $order);

            foreach ($entries as &$entry) {
                foreach ($entry as $key => $val) {
                    $normalized = $this->tfTranslator->normalize($key);
                    if ($normalized != $key) {
                        $entry[$normalized] = $val;
                        unset($entry[$key]);
                    }
                }
            }

            $resultSet = new Search_ResultSet($entries, $count, $resultStart, $resultCount);
            if (! empty($didYouMean)) {
                $resultSet->setDidYouMean(implode(' ', $correctKeywords));
                $words = $correctKeywords;
            }
            $resultSet->setHighlightHelper(new Search_MySql_HighlightHelper($words));

            return $resultSet;
        } catch (Search_MySql_QueryException $e) {
            if (empty($e->suppress_feedback)) {
                Feedback::error($e->getMessage());
            }
            $resultSet = new Search_ResultSet([], 0, $resultStart, $resultCount);
            $resultSet->errorInQuery = $e->getMessage();
            return $resultSet;
        }
    }

    private function createLevenshteinFunction()
    {
        $uniqueRequestId = Identifiers::getHttpRequestId();
        $levenshteinFunctionName = "tiki_levenshtein_$uniqueRequestId";

        // Copyright (c) 2015 Felix Zandanel <felix@zandanel.me>

        // Permission is hereby granted, free of charge, to any person
        // obtaining a copy of this software and associated documentation
        // files (the "Software"), to deal in the Software without
        // restriction, including without limitation the rights to use,
        // copy, modify, merge, publish, distribute, sublicense, and/or sell
        // Copies of the Software, and to permit persons to whom the
        // Software is furnished to do so, subject to the following
        // conditions:

        // The above copyright notice and this permission notice shall be
        // included in all copies or substantial portions of the Software.
        // THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
        // EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
        // OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
        // NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
        // HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
        // WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
        // FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
        // OTHER DEALINGS IN THE SOFTWARE.

        $functionQuery = <<<SQL
CREATE FUNCTION IF NOT EXISTS $levenshteinFunctionName( s1 VARCHAR(255), s2 VARCHAR(255) )
  RETURNS INT
  DETERMINISTIC
  BEGIN
    DECLARE s1_len, s2_len, i, j, c, c_temp, cost INT;
    DECLARE s1_char CHAR;
    -- max strlen=255
    DECLARE cv0, cv1 VARBINARY(256);

    SET s1_len = CHAR_LENGTH(s1), s2_len = CHAR_LENGTH(s2), cv1 = 0x00, j = 1, i = 1, c = 0;

    IF s1 = s2 THEN
        RETURN 0;
    ELSEIF s1_len = 0 THEN
        RETURN s2_len;
    ELSEIF s2_len = 0 THEN
        RETURN s1_len;
    ELSE
        WHILE j <= s2_len DO
            SET cv1 = CONCAT(cv1, UNHEX(HEX(j))), j = j + 1;
        END WHILE;
        WHILE i <= s1_len DO
            SET s1_char = SUBSTRING(s1, i, 1), c = i, cv0 = UNHEX(HEX(i)), j = 1;
            WHILE j <= s2_len DO
                SET c = c + 1;
                IF s1_char = SUBSTRING(s2, j, 1) THEN
                    SET cost = 0; ELSE SET cost = 1;
                END IF;
                SET c_temp = CONV(HEX(SUBSTRING(cv1, j, 1)), 16, 10) + cost;
                IF c > c_temp THEN SET c = c_temp; END IF;
                SET c_temp = CONV(HEX(SUBSTRING(cv1, j+1, 1)), 16, 10) + 1;
                IF c > c_temp THEN
                    SET c = c_temp;
                END IF;
                SET cv0 = CONCAT(cv0, UNHEX(HEX(c))), j = j + 1;
            END WHILE;
            SET cv1 = cv0, i = i + 1;
        END WHILE;
    END IF;
    RETURN c;
  END
SQL;
        if ($this->db->query($functionQuery)) {
            return $levenshteinFunctionName;
        }

        return null;
    }

    public function scroll(Search_Query_Interface $query)
    {
        $perPage = 100;
        $hasMore = true;

        for ($from = 0; $hasMore; $from += $perPage) {
            $result = $this->find($query, $from, $perPage);
            foreach ($result as $row) {
                yield $row;
            }

            $hasMore = $result->hasMore();
        }
    }

    private function getOrderClause($query, $useScore)
    {
        $order = $query->getSortOrder();

        $parts = [];
        foreach ($order->getParts() as $part) {
            if ($part->getField() == Search\Query\Order::FIELD_SCORE) {
                if ($useScore) {
                    $parts[] = '`score` DESC';
                } else {
                    // No specific order
                }
                continue;
            }

            $this->table->ensureHasIndex($part->getField(), 'sort');

            if ($part->getMode() == Search\Query\Order::MODE_NUMERIC) {
                $parts[] = "CAST(`{$this->tfTranslator->shortenize($part->getField())}` as SIGNED) {$part->getOrder()}";
            } else {
                $parts[] = "`{$this->tfTranslator->shortenize($part->getField())}` {$part->getOrder()}";
            }
        }

        if ($parts) {
            return $this->table->expr(implode(', ', $parts));
        } else {
            return;
        }
    }

    public function getTypeFactory()
    {
        return new Search_MySql_TypeFactory();
    }

    public function getFieldsCount()
    {
        return $this->table->getFieldsCount();
    }

    /**
     * Function responsible for restoring old indexes
     * @param $indexesToRestore
     * @param $currentIndexTableName
     * @throws Exception
     */
    public function restoreOldIndexes($indexesToRestore, $currentIndexTableName)
    {
        $columns = [];
        foreach ($this->table->indexTables($currentIndexTableName) as $table) {
            $columns = array_merge($columns, array_column(TikiDb::get()->fetchAll("SHOW COLUMNS FROM $table"), 'Field'));
        }

        foreach ($indexesToRestore as $indexToRestore) {
            if (! in_array($indexToRestore['Column_name'], $columns)) {
                continue;
            }

            $indexType = strtolower($indexToRestore['Index_type']) == 'fulltext' ? 'fulltext' : 'index';

            try {
                $this->table->ensureHasIndex($indexToRestore['Column_name'], $indexType);
            } catch (Search_MySql_QueryException $exception) {
                // Left blank on purpose
            }
        }
    }

    public function isTextField($field)
    {
        $type = $this->table->getFieldType($field);
        if ($type == 'date' || $type == 'datetime') {
            return false;
        }
        return true;
    }
}
