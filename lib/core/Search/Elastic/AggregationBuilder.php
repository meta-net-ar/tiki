<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Search\Elastic;

use Search\Query\Aggregation\AggregationInterface;
use Search\Query\Aggregation\Metric;
use Search\Query\Aggregation\Terms;

/**
 * Translates a list of AggregationInterface into an Elasticsearch `aggs`
 * request fragment.
 *
 * Supports metric aggregations (sum, avg, min, max, value_count, cardinality,
 * stats) and the bucket aggregation Terms with arbitrary nested children.
 * Bucket order can reference any sibling metric by name, enabling native
 * top-N-by-metric.
 */
class AggregationBuilder
{
    /** @var Search_Elastic_Index|null */
    private $index;

    public function __construct($index = null)
    {
        $this->index = $index;
    }

    /**
     * @param AggregationInterface[] $aggregations
     * @return array<string, mixed> ES request fragment, e.g. ['aggs' => [...]]
     */
    public function build(array $aggregations): array
    {
        if (empty($aggregations)) {
            return [];
        }
        $aggs = [];
        foreach ($aggregations as $agg) {
            $aggs[$agg->getName()] = $this->buildOne($agg);
        }
        return ['aggs' => $aggs];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildOne(AggregationInterface $agg): array
    {
        if ($agg instanceof Terms) {
            return $this->buildTerms($agg);
        }
        if ($agg instanceof Metric) {
            return $this->buildMetric($agg);
        }
        return [Metric::KIND_COUNT => ['field' => 'object_id']];
    }

    private function buildMetric(Metric $agg): array
    {
        $kind = $agg->getKind();
        $body = [];
        $field = $agg->getField();
        if ($field !== null) {
            $body['field'] = $this->resolveField($field);
        } else {
            // value_count without a field -> count all docs in scope using
            // a stable always-present field
            $body['field'] = 'object_id';
        }
        $missing = $agg->getMissing();
        if ($missing !== null) {
            $body['missing'] = $missing;
        }
        return [$kind => $body];
    }

    private function buildTerms(Terms $agg): array
    {
        $body = [
            'field' => $this->resolveField((string) $agg->getField()),
            'size' => $agg->getSize() ?: 10,
        ];
        $missing = $agg->getMissing();
        if ($missing !== null) {
            $body['missing'] = $missing;
        }
        $minDocCount = $agg->getMinDocCount();
        if ($minDocCount !== null) {
            $body['min_doc_count'] = $minDocCount;
        }
        $orderBy = $agg->getOrderBy();
        if (! empty($orderBy)) {
            $order = [];
            foreach ($orderBy as $name => $dir) {
                $order[$name] = $dir;
            }
            $body['order'] = $order;
        }

        $out = ['terms' => $body];

        if ($agg->hasChildren()) {
            $children = [];
            foreach ($agg->getChildren() as $child) {
                $children[$child->getName()] = $this->buildOne($child);
            }
            $out['aggs'] = $children;
        }
        return $out;
    }

    private function resolveField(string $field): string
    {
        // ES `terms` aggregations need a non-analyzed (keyword/.facet) field.
        // The existing Tiki convention is to expose a `.facet` keyword
        // sub-field for full-text fields; reuse the same probe used by
        // Search_Elastic_FacetBuilder.
        if ($this->index && method_exists($this->index, 'hasFacetField') && $this->index->hasFacetField($field)) {
            return $field . '.facet';
        }
        return $field;
    }
}
