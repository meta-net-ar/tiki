<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Search\Query\Aggregation\Sql\Schema;

use Search\Query\Aggregation\AggregationInterface;
use Search\Query\Aggregation\Formula;
use Search\Query\Aggregation\Terms;

/**
 * Walks an aggregation tree and returns the minimal list of search-index
 * fields that need to be projected by {@see \Search_Query::setSelectionFields()}
 * for the temp-table tier to populate its working set.
 */
class ProjectionInferrer
{
    /**
     * @param array<string, AggregationInterface> $aggregations
     * @return string[]
     */
    public function infer(array $aggregations): array
    {
        $fields = [];
        $this->walk($aggregations, $fields);
        return array_values($fields);
    }

    private function walk(iterable $aggregations, array &$fields): void
    {
        foreach ($aggregations as $agg) {
            $this->collectField($agg->getField(), $fields);

            if ($agg instanceof Formula && $agg->isAggregate()) {
                foreach ($agg->getIdentifiers() as $ident) {
                    $this->collectField($ident, $fields);
                }
            }

            if ($agg instanceof Terms && $agg->hasChildren()) {
                $this->walk($agg->getChildren(), $fields);
            }
        }
    }

    private function collectField(?string $field, array &$fields): void
    {
        if ($field === null || $field === '') {
            return;
        }
        if (! isset($fields[$field])) {
            $fields[$field] = $field;
        }
    }
}
