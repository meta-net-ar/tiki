<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

use Search\Manticore\PdoClient as ManticoreClient;

class FederatedSearchLib
{
    private $unified;
    private $indices = [];
    private $loaded = false;

    public function __construct($unifiedsearch)
    {
        $this->unified = $unifiedsearch;
    }

    public function addIndex($indexName, Search\Federated\IndexInterface $index)
    {
        $this->indices[$indexName] = $index;
    }

    public function getIndices()
    {
        $this->load();

        return $this->indices;
    }

    public function augmentSimpleQuery(Search_Query $query, $content)
    {
        $indices = $this->getIndices();

        foreach ($indices as $indexName => $index) {
            $sub = $this->addForIndex($query, $indexName, $index);
            $index->applyContentConditions($sub, $content);
        }
    }

    public function augmentSimilarQuery(Search_Query $query, $type, $object)
    {
        $indices = $this->getIndices();

        foreach ($indices as $indexName => $index) {
            $sub = $this->addForIndex($query, $indexName, $index);
            $index->applySimilarConditions($sub, $type, $object);
        }
    }

    private function load()
    {
        if (! $this->loaded) {
            $this->loaded = true;

            $table = TikiDb::get()->table('tiki_extwiki');
            $tikis = $table->fetchAll($table->all(), ['indexname' => $table->not('')]);

            foreach ($tikis as $tiki) {
                $this->addIndex($tiki['indexname'], new Search\Federated\TikiIndex($this->extractBaseUrl($tiki['extwiki']), json_decode($tiki['groups']) ?: []));
            }
        }
    }

    private function addForIndex($query, $indexName, $index)
    {
        $sub = new Search_Query();
        foreach ($index->getTransformations() as $trans) {
            $sub->applyTransform($trans);
        }

        if (strstr($indexName, ':')) {
            $parts = explode(':', $indexName);
            $prefix = array_pop($parts);
        } else {
            $prefix = $indexName;
        }

        $query->includeForeign($prefix, $sub);

        return $sub;
    }

    private function extractBaseUrl($url)
    {
        $slash = strrpos($url, '/');
        return substr($url, 0, $slash + 1);
    }

    public function createIndex($location, $index, $type, array $mapping)
    {
        $connection = Search_Elastic_Connection::buildFromPrefs($location);
        $connection->mapping(
            $index,
            [$type],
            function () use ($mapping) {
                return $mapping;
            }
        );
    }

    /**
     * Manticore distributed table/index creation out of the defined extwiki indexes and the local one
     */
    public function recreateDistributedIndex(ManticoreClient $client)
    {
        global $prefs;

        $list = [$prefs['unified_manticore_index_current']];
        $indices = $this->getIndices();
        foreach ($indices as $indexName => $index) {
            // extwiki specifies an alias while actual index/table name is a unique string using the alias as a prefix
            // get the latest index defined using that prefix
            $def = $client->parseDistributedIndexDefinition($indexName);
            if ($def['type'] == 'agent') {
                // remote agent definition
                $indexClient = new ManticoreClient($def['host'], $def['port_sql']);
            } else {
                // local index
                $indexClient = $client;
            }
            $available = $indexClient->getIndicesByPrefix($def['index']);
            $latest = '';
            foreach ($available as $candidate) {
                if (empty($latest)) {
                    $latest = $candidate;
                    continue;
                }
                if (strcmp($latest, $candidate) < 0) {
                    $latest = $candidate;
                }
            }
            if (empty($latest)) {
                $latest = $def['index'];
            }
            if ($def['type'] == 'agent') {
                $parts = explode(':', $indexName);
                array_pop($parts);
                $parts[] = $latest;
                $list[] = implode(':', $parts);
            } else {
                $list[] = $latest;
            }
        }

        $client->recreateDistributedIndex($list);
    }
}
