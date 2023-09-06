<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Search\Manticore;

use TikiLib;
use TikiFilter;

class Index implements \Search_Index_Interface, \Search_Index_QueryRepository
{
    private $client;
    private $pdo_client;
    private $index;
    private $indexer;
    private $providedMappings = [];

    private $facetCount = 10;

    private $multisearchIndices;
    private $multisearchStack;

    public static $searchedFields = ['fulltext' => [], 'others' => []];

    public function __construct(Client $client, PdoClient $pdo_client, $index)
    {
        $this->client = $client;
        $this->pdo_client = $pdo_client;
        $this->index = $index;
        $this->indexer = null;
        $this->providedMappings = $this->pdo_client->describe($index);
    }

    public function getClient()
    {
        return $this->client;
    }

    public function getPdoClient()
    {
        return $this->pdo_client;
    }

    public function setIndexer($indexer)
    {
        $this->indexer = $indexer;
    }

    public function destroy()
    {
        if ($this->pdo_client->deleteIndex($this->index)) {
            $this->pdo_client->deleteIndex($this->index . 'pq');
            $this->providedMappings = [];
            $stopwords_file = $this->getStopwordsFilePath();
            if (file_exists($stopwords_file)) {
                unlink($stopwords_file);
            }
            return true;
        }
        return false;
    }

    public function exists()
    {
        $indexStatus = $this->pdo_client->getIndexStatus($this->index);

        if (empty($indexStatus) || ! empty($indexStatus['error'])) {
            return false;
        }

        return true;
    }

    public function addDocument(array $data)
    {
        $this->pdo_client->index($this->index, $this->generateDocument($data));
    }

    private function generateDocument(array $data)
    {
        $objectType = $data['object_type']->getValue();

        $this->generateMapping($objectType, $data);

        $data = array_map(
            function ($entry) {
                return $entry->getValue() ?? "";
            },
            $data
        );

        foreach ($data as $field => $value) {
            if (isset($this->providedMappings[$field . '_nsort'])) {
                $data[$field . '_nsort'] = @floatval($value);
            }
        }

        return $data;
    }

    private function generateMapping($type, $data)
    {
        global $prefs;

        // stored conversion of manticore field names to tiki field names
        $fieldMapping = $this->getUnifiedFieldMapping();

        // stored list of date-only fields as Manticore stores all datetime types as timestamp
        $dateFields = $this->getUnifiedDateFields();

        // correct mapping back to tiki field names (possibly come from manticore server desc statement)
        $providedMappingsCorrectName = array_flip(array_map(function ($field) use ($fieldMapping) {
            return $fieldMapping[$field] ?? $field;
        }, array_keys($this->providedMappings)));

        // extract the difference of the new data only and convert to manticore types
        $mapping = array_map(
            [$this, 'convertToManticoreType'],
            array_diff_key($data, $providedMappingsCorrectName)
        );

        // observe 256 full-text fields index limit - convert the rest to string attributes
        if ($this->indexer) {
            $indexedFields = $this->getIndexedFields();
            foreach ($mapping as $field => $type) {
                if ($type['type'] == 'text' && ! in_array($field, $indexedFields)) {
                    $mapping[$field] = ['type' => 'string'];
                }
            }
        } else {
            // lack of indexer means we cannot build up the field list, so keep the mapping as it is
        }

        // cache date-only field list
        foreach ($mapping as $field => $type) {
            if (! empty($type['dateonly'])) {
                unset($mapping[$field]['dateonly']);
                $dateFields[] = $field;
            }
        }

        // add nsort numeric field counterparts to the string attribute fields
        foreach ($mapping as $field => $type) {
            if (($type['type'] == 'string' || $type['type'] == 'text') && ! isset($mapping[$field . '_nsort'])) {
                $mapping[$field . '_nsort'] = ['type' => 'float'];
            }
            if (($type['type'] == 'timestamp') && ! isset($mapping[$field . '_nsort'])) {
                $mapping[$field . '_nsort'] = ['type' => 'timestamp'];
            }
        }

        // create or update the index
        if (empty($this->providedMappings)) {
            $this->pdo_client->createIndex($this->index, $mapping, $this->getIndexSettings());
        } else {
            foreach ($mapping as $field => $type) {
                $this->pdo_client->alter($this->index, 'add', $field, $type);
            }
        }

        // save provided mappings and the field names conversion
        foreach ($mapping as $field => $type) {
            $this->providedMappings[$field] = [
                'types' => $type['type'] == 'text' ? ['text', 'string'] : [$type['type']],
                'options' => $type['options'] ?? [],
            ];
            $fieldMapping[strtolower($field)] = $field;
        }

        if ($prefs['storedsearch_enabled'] == 'y' && $mapping) {
            // update percolate index
            $pq_mapping = [];
            foreach ($this->providedMappings as $field => $opts) {
                $pq_mapping[strtolower($field)] = [
                    'type' => $opts['types'][0],
                    //'options' => $opts['options'],
                ];
            }
            $this->updatePercolateIndex($this->index, $pq_mapping);
        }

        TikiLib::lib('tiki')->set_preference('unified_field_mapping', json_encode($fieldMapping));
        TikiLib::lib('tiki')->set_preference('unified_date_fields', json_encode($dateFields));
    }

    private function updatePercolateIndex($index, $mapping)
    {
        $name = $index . 'pq';
        $status = $this->pdo_client->getIndexStatus($name);
        if (! empty($status)) {
            $this->pdo_client->deleteIndex($name);
        }
        $this->pdo_client->createIndex($name, $mapping, ['type' => 'pq']);
        return $name;
    }

    private function getIndexSettings()
    {
        global $prefs;

        $stopwords_file = $this->getStopwordsFilePath();
        file_put_contents($stopwords_file, implode("\n", $prefs['unified_stopwords']));

        $settings = [
            'min_infix_len' => 2,
            'stopwords' => $stopwords_file,
            'morphology' => $prefs['unified_manticore_morphology'] ?? '',
            // TODO: see what other options to support https://manual.manticoresearch.com/Creating_an_index/Local_indexes/Plain_and_real-time_index_settings#Natural-language-processing-specific-settings
        ];

        return $settings;
    }

    /**
     * Returns the actual path to the stopwords file for this index
     * Tiki temp is preferred but if that is not world-readable,
     * Manticore might not be able to read it, so fallback to system temp
     */
    private function getStopwordsFilePath()
    {
        $dir = realpath(TIKI_PATH . DIRECTORY_SEPARATOR . 'temp');
        $parent = $dir;
        while ($parent) {
            $stat = stat($parent);
            $others_bit = decoct($stat['mode'] & 000007);
            $executable = $others_bit & 001;
            if (! $executable) {
                if ($dir != sys_get_temp_dir()) {
                    $dir = sys_get_temp_dir();
                    $parent = $dir;
                    continue;
                } else {
                    $dir = '/tmp';
                    break;
                }
            }
            if (dirname($parent) != $parent) {
                $parent = dirname($parent);
            } else {
                $parent = false;
            }
        }
        if (! is_writable($dir)) {
            throw new FatalException(tr('Cannot write Manticore stopwords file in Tiki temp dir or system tmp dir. Check that temp dir permissions allow Tiki user to write to and Manticore user to read from that directory.'));
        }
        return $dir . DIRECTORY_SEPARATOR . 'manticore-stopwords-' . $this->index;
    }

    private function getIndexedFields()
    {
        global $prefs;

        static $data = null;

        if (! is_null($data)) {
            return $data;
        }

        if (! empty($prefs['unified_manticore_always_index'])) {
            $data = preg_split('/\s*,\s*/', $prefs['unified_manticore_always_index']);
        } else {
            $data = [];
        }

        if (! empty($prefs['unified_default_content'])) {
            $data = array_merge($data, $prefs['unified_default_content']);
            $data = array_unique($data);
        }

        if (count($data) > 256) {
            $data = null;
            throw new FatalException(tr('Fatal Manticore error: you have defined more than 256 fields to be indexed as full-text. Please edit search setting "unified_manticore_always_index" to decrease the number of fields.'));
        }

        if (! $this->indexer) {
            return $data;
        }

        $typeFactory = $this->getTypeFactory();

        $fields = $this->indexer->getAvailableFieldTypes();
        $converted = [];
        foreach ($fields as $name => $type) {
            if (empty($type)) {
                continue;
            }
            $searchType = $typeFactory->$type('');
            $field = $this->convertToManticoreType($searchType);
            if ($field['type'] == 'text' && count($data) < 256 && ! in_array($name, $data)) {
                $data[] = $name;
            }
            if ($field['type'] == 'text' && ! in_array($name, $data) && ! in_array($name, $converted)) {
                $converted[] = $name;
            }
        }

        $this->indexer->addStats('fulltext fields', [
            'indexed' => $data,
            'converted to string' => $converted,
        ]);

        return $data;
    }

    private function convertToManticoreType($entry)
    {
        if ($entry instanceof \Search_Type_Numeric) {
            return [
                "type" => "float",
            ];
        } elseif ($entry instanceof \Search_Type_Whole) {
            return [
                "type" => "string",
            ];
        } elseif ($entry instanceof \Search_Type_MultivalueJson) {
            return [
                "type" => "json",
            ];
        } elseif ($entry instanceof \Search_Type_MultivalueInt) {
            return [
                "type" => "multi",
            ];
        } elseif ($entry instanceof \Search_Type_JsonEncoded) {
            return [
                "type" => "json",
            ];
        } elseif ($entry instanceof \Search_Type_DateTime) {
            return [
                "type" => "timestamp",
            ];
        } elseif ($entry instanceof \Search_Type_Timestamp) {
            return [
                "type" => "timestamp",
                'dateonly' => $entry->isDateOnly(),
            ];
        } else {
            return [
                "type" => "text",
                "options" => ["indexed", "attribute"]
            ];
        }
    }

    public function optimize()
    {
        $this->pdo_client->optimize($this->index);
    }

    public function endUpdate()
    {
        $this->optimize();
    }

    public function invalidateMultiple(array $objectList)
    {
        foreach ($objectList as $object) {
            $this->pdo_client->unindex($this->index, $object['object_type'], $object['object_id']);
        }
    }

    public function initSearch()
    {
        $search = new \Manticoresearch\Search($this->client->getClient());
        $search->setIndex($this->index);
        return $search;
    }

    public function find(\Search_Query_Interface $query, $resultStart, $resultCount)
    {
        $builder = new QueryBuilder($this);
        $built = $builder->build($query->getExpr());

        $condition = $built['query'];
        $select = $built['select'];

        if (empty($condition)) {
            // empty queries return no results
            return new ResultSet([], 0, $resultStart, $resultCount);
        }

        if ($query->getForeignQueries()) {
            $table = PdoClient::distributedIndexName();
        } else {
            $table = $this->index;
        }

        $builder = new OrderBuilder($this);
        $order = $builder->build($query->getSortOrder());

        $builder = new FacetBuilder($this);
        $builder->setPossibleFields($this->pdo_client->possibleFacetFields($table));
        $facets = $builder->build($query->getFacets());

        $sql = "SELECT *";

        foreach ($select as $key => $expr) {
            $sql .= ", $expr as $key";
        }

        $sql .= " FROM $table WHERE $condition";

        if ($order) {
            $sql .= " ORDER BY $order";
        }

        $sql .= " LIMIT $resultStart, $resultCount option not_terms_only_allowed=1,cutoff=0";

        if ($resultStart + $resultCount > 1000) {
            $sql .= ',max_matches=' . ($resultStart + $resultCount);
        }

        if ($facets) {
            $sql .= ' ' . $facets;
        }

        $results = $this->pdo_client->fetchAllRowsets($sql);
        $result = $results[0];

        $meta = $this->pdo_client->fetchAll('SHOW META');
        foreach ($meta as $row) {
            if ($row['Variable_name'] == 'total_found') {
                $totalCount = intval($row['Value']);
            }
        }

        $fieldMapping = $this->getUnifiedFieldMapping();
        $dateFields = $this->getUnifiedDateFields();

        $timestampFields = [];
        $multiFields = [];
        foreach ($this->providedMappings as $field => $mapping) {
            if (in_array('timestamp', $mapping['types'])) {
                $timestampFields[] = $field;
            }
            if (in_array('multi', $mapping['types']) || in_array('mva', $mapping['types'])) {
                $multiFields[] = $field;
            }
        }

        $entries = [];
        foreach ($result as $data) {
            foreach ($data as $key => $_) {
                if (substr($key, -6) == '_nsort') {
                    unset($data[$key]);
                }
                if (isset($select[$key])) {
                    unset($data[$key]);
                }
            }

            if (isset($data['_score'])) {
                $data['score'] = round($data['_score'], 2);
            }

            if (isset($data['index_name'])) {
                $data['_index'] = preg_replace('/_[^_]+$/', '', $data['index_name']);
            }

            // Manticore stores datetimes as timestamp values while MySQL/ES store as datetime strings
            // Tiki interface expects datetime strings in GMT, so we need a conversion before using the result
            foreach ($timestampFields as $tsField) {
                if (! empty($data[$tsField])) {
                    $isDateOnly = in_array($fieldMapping[$tsField] ?? $tsField, $dateFields);
                    $dt = new \Search_Type_DateTime($data[$tsField], $isDateOnly);
                    $data[$tsField] = $dt->getValue();
                    $data['ignored_fields'][] = $fieldMapping[$tsField] ?? $tsField;
                } else {
                    $data[$tsField] = '';
                }
            }

            // convert MVA fields to array of ints (they get returned as comma-separated string)
            foreach ($multiFields as $mField) {
                if (! empty($data[$mField])) {
                    $data[$mField] = explode(',', $data[$mField]);
                } else {
                    $data[$mField] = [];
                }
            }

            // convert lowercase stored field names back to Tiki equivalents
            if ($fieldMapping) {
                $mapped = [];
                foreach ($data as $key => $value) {
                    $key = $fieldMapping[$key] ?? $key;
                    $mapped[$key] = $value;
                }
                $data = $mapped;
                unset($mapped);
            }

            $entries[] = $data;
        }

        $resultSet = new ResultSet($entries, $totalCount, $resultStart, $resultCount);

        $words = $this->getWords($query->getExpr());
        $resultSet->setHighlightHelper(new \Search_MySql_HighlightHelper($words));

        $reader = new FacetReader($results);
        foreach ($query->getFacets() as $facet) {
            if ($filter = $reader->getFacetFilter($facet)) {
                $resultSet->addFacetFilter($filter);
            }
        }

        return $resultSet;
    }

    public function scroll(\Search_Query_Interface $query)
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

    public function getTypeFactory()
    {
        return new TypeFactory();
    }

    private function createDocumentReader()
    {
        $pdo_client = $this->pdo_client;
        $index = $this->index;
        return function ($type, $object) use ($pdo_client, $index) {
            static $previous, $content;

            $now = "$index~$type~$object";
            if ($previous === $now) {
                return $content;
            }

            $previous = $now;
            $content = (array) $pdo_client->document($index, $type, $object);
            return $content;
        };
    }

    public function getMatchingQueries(array $document)
    {
        $document = $this->generateDocument($document);
        return $this->pdo_client->percolate($this->index, $document);
    }

    public function store($name, \Search_Expr_Interface $expr)
    {
        $search = $this->initSearch();
        $decorator = new QueryDecorator($search, $this);
        $decorator->setDocumentReader($this->createDocumentReader());
        $decorator->decorate($expr);

        $doc = $search->compile();
        $this->client->storeQuery($this->index, $doc, $name);
    }

    public function unstore($name)
    {
        $this->pdo_client->unstoreQuery($this->index, $name);
    }

    public function setFacetCount($count)
    {
        $this->facetCount = (int) $count;
    }

    public function getFieldMapping($field)
    {
        if (array_key_exists($field, $this->providedMappings)) {
            return $this->providedMappings[$field];
        }
        if (array_key_exists(strtolower($field), $this->providedMappings)) {
            return $this->providedMappings[strtolower($field)];
        }
        $lowcase = array_map(function ($el) {
            return strtolower($el);
        }, array_keys($this->providedMappings));
        $key = array_search(strtolower($field), $lowcase);
        if ($key !== false) {
            $i = 0;
            foreach ($this->providedMappings as $field => $mapping) {
                if ($i == $key) {
                    return $mapping;
                }
                $i++;
            }
        }
        return [];
    }

    public function getFieldMappings()
    {
        return $this->providedMappings;
    }

    public function isTextField($field)
    {
        $mapping = $this->getFieldMapping($field);
        if (! empty($mapping['types']) && in_array('text', $mapping['types'])) {
            return true;
        }
        if (! empty($mapping['options']) && in_array('indexed', $mapping['options'])) {
            return true;
        }
        return false;
    }

    /**
     * Manticore/sphinx stores attribute names in lower case, so we need a mapping when building the result set from searches
     * @return array of key/value pairs with mapping between Manticore and Tiki fields
     */
    public function getFieldNameMapping()
    {
        $fields = array_keys($this->providedMappings);
        return array_combine(array_map(function ($field) {
            return strtolower($field);
        }, $fields), $fields);
    }

    public function ensureHasField($field)
    {
        global $prefs;

        $fields = preg_split('/\s*,\s*/', $field);
        foreach ($fields as $field) {
            $mapping = $this->getFieldMapping($field);
            if (empty($mapping) && $prefs['search_error_missing_field'] === 'y') {
                if (preg_match('/^tracker_field_/', $field)) {
                    $msg = tr('Field %0 does not exist in the current index. Please check field permanent name and if you have any items in that tracker.', TikiFilter::get('xss')->filter($field));
                    if ($prefs['unified_exclude_nonsearchable_fields'] === 'y') {
                        $msg .= ' ' . tr('You have disabled indexing non-searchable tracker fields. Check if this field is marked as searchable.');
                    }
                } else {
                    $msg = tr('Field %0 does not exist in the current index. If this is a tracker field, the proper syntax is tracker_field_%0.', TikiFilter::get('xss')->filter($field), TikiFilter::get('xss')->filter($field));
                }
                $e = new Exception($msg);
                if ($field == 'tracker_id') {
                    $e->suppress_feedback = true;
                }
                throw $e;
            }
        }
    }

    /**
     * Store fields used in search queries and if they are fulltext searches or not
     */
    public static function addSearchedField(string $field, string $type)
    {
        if ($type !== 'fulltext') {
            $type = 'others';
        }
        if (! in_array($field, self::$searchedFields[$type])) {
            self::$searchedFields[$type][] = $field;
        }
    }

    /**
     * Warn user if there is a mismatch between indexed fulltext fields and actual fields
     * used in the search queries.
     */
    public function generateSearchedFieldIndexStats()
    {
        $stats = $this->indexer->getStats('fulltext fields');
        $stats['indexed but not used in fulltext queries'] = [];
        $stats['indexed but used in non-fulltext queries'] = [];
        foreach ($stats['indexed'] as $field) {
            if (! in_array($field, Index::$searchedFields['fulltext'])) {
                $stats['indexed but not used in fulltext queries'][] = $field;
            }
            if (in_array($field, Index::$searchedFields['others'])) {
                $stats['indexed but used in non-fulltext queries'][] = $field;
            }
        }
        $stats['not indexed but used in queries'] = [];
        foreach ($stats['converted to string'] as $field) {
            if (in_array($field, Index::$searchedFields['others'])) {
                $stats['not indexed but used in queries'][] = $field;
            }
        }
        $this->indexer->addStats('fulltext fields', $stats);
    }

    protected function getUnifiedFieldMapping()
    {
        global $prefs;

        $fieldMapping = $prefs['unified_field_mapping'] ?? [];
        if ($fieldMapping) {
            $fieldMapping = json_decode($fieldMapping, true);
        }

        return $fieldMapping;
    }

    protected function getUnifiedDateFields()
    {
        global $prefs;

        $dateFields = $prefs['unified_date_fields'] ?? [];
        if ($dateFields) {
            $dateFields = json_decode($dateFields, true);
        }

        return $dateFields;
    }

    protected function getWords($expr)
    {
        $words = [];
        $factory = new \Search_Type_Factory_Direct();
        $expr->walk(
            function ($node) use (&$words, $factory) {
                if ($node instanceof \Search_Expr_Token && $node->getField() !== 'searchable') {
                    $word = $node->getValue($factory)->getValue();
                    if (is_string($word) && ! in_array($word, $words)) {
                        $words[] = $word;
                    }
                }
            }
        );

        return $words;
    }
}
