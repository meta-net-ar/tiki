<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Search\Elastic;

use Search\Query\Aggregation\AggregationInterface;
use Search\Query\Aggregation\Metric;
use Search\Query\Aggregation\Terms;
use Search\ResultSet\AggregationResult;

/**
 * Reads the `aggregations` section of an Elasticsearch search response and
 * normalises it into AggregationResult instances matching the original
 * AggregationInterface request tree.
 */
class AggregationReader
{
    private $data;

    /**
     * @param object|array $data the raw decoded ES response (`stdClass` from
     *                           `Search_Elastic_Connection::search`)
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * @return AggregationResult|null
     */
    public function read(AggregationInterface $agg): ?AggregationResult
    {
        $aggs = $this->getAggsContainer($this->data);
        if ($aggs === null) {
            return null;
        }
        return $this->readOne($agg, $aggs);
    }

    private function getAggsContainer($container)
    {
        if (is_object($container) && isset($container->aggregations)) {
            return $container->aggregations;
        }
        if (is_object($container) && isset($container->aggs)) {
            return $container->aggs;
        }
        return null;
    }

    private function readOne(AggregationInterface $agg, $container): ?AggregationResult
    {
        $name = $agg->getName();
        if (! is_object($container) || ! isset($container->$name)) {
            return null;
        }
        $entry = $container->$name;

        if ($agg instanceof Terms) {
            return $this->readTerms($agg, $entry);
        }
        if ($agg instanceof Metric) {
            return $this->readMetric($agg, $entry);
        }
        return null;
    }

    private function readMetric(Metric $agg, $entry): AggregationResult
    {
        $result = new AggregationResult($agg->getName(), $agg->getKind(), false);
        $result->setField($agg->getField());
        $result->setLabel($agg->getLabel());
        if (! is_object($entry)) {
            return $result;
        }
        if ($agg->getKind() === Metric::KIND_STATS) {
            $stats = [];
            foreach (['count', 'min', 'max', 'avg', 'sum'] as $k) {
                if (isset($entry->$k)) {
                    $stats[$k] = $entry->$k;
                }
            }
            $result->setValue($stats);
            return $result;
        }
        $result->setValue($entry->value ?? null);
        return $result;
    }

    private function readTerms(Terms $agg, $entry): AggregationResult
    {
        $result = new AggregationResult($agg->getName(), 'terms', true);
        $result->setField($agg->getField());
        $result->setLabel($agg->getLabel());

        $labelMap = [];
        foreach ($agg->getChildren() as $child) {
            if ($child->getLabel() !== null) {
                $labelMap[$child->getName()] = $child->getLabel();
            }
        }
        if ($labelMap !== []) {
            $result->setMetricLabels($labelMap);
        }

        if (is_object($entry)) {
            if (isset($entry->sum_other_doc_count)) {
                $result->setSumOtherDocCount((int) $entry->sum_other_doc_count);
            }
            if (isset($entry->doc_count_error_upper_bound)) {
                $result->setDocCountErrorUpperBound((int) $entry->doc_count_error_upper_bound);
            }
        }
        $buckets = (is_object($entry) && isset($entry->buckets)) ? $entry->buckets : [];
        $children = $agg->getChildren();
        foreach ($buckets as $bucket) {
            $row = [
                'key' => $bucket->key ?? null,
                'doc_count' => (int) ($bucket->doc_count ?? 0),
                'metrics' => [],
                'children' => [],
            ];
            foreach ($children as $child) {
                $sub = $this->readOne($child, $bucket);
                if ($sub === null) {
                    continue;
                }
                if ($sub->isBucket()) {
                    $row['children'][$child->getName()] = $sub;
                } else {
                    $row['metrics'][$child->getName()] = $sub->getValue();
                }
            }
            $result->addBucket($row);
        }
        return $result;
    }
}
