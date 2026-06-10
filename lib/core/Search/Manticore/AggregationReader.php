<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Search\Manticore;

use Search\Query\Aggregation\Metric;
use Search\Query\Aggregation\Terms;
use Search\ResultSet\AggregationResult;

/**
 * Inverse of {@see AggregationBuilder}: given the rowsets returned by
 * Manticore — either from FACET clauses or standalone SELECT … GROUP BY
 * queries — rebuild structured {@see AggregationResult}
 * objects (the same shape the Elastic backend produces, so downstream
 * templates and the join resolver stay backend-agnostic).
 *
 * Multi-column FACETs (nested terms) are folded back into the same nested
 * bucket tree the user defined, by pivoting on the leading group columns.
 */
class AggregationReader
{
    /**
     * @param array<int, array<int, array<string, mixed>>> $rowsets
     *        Rowsets in declaration order (each is the array returned by one
     *        FACET line).
     * @param array<string, array> $specs  Per-aggregation metadata produced
     *        by {@see AggregationBuilder::build()} or
     *        {@see AggregationBuilder::buildGroupByQueries()}, keyed by the
     *        aggregation alias used in the SQL.
     * @return array<string, AggregationResult>  keyed by
     *         the user-facing aggregation name
     */
    public function read(array $rowsets, array $specs): array
    {
        $byAlias = [];
        foreach ($rowsets as $rows) {
            if (empty($rows)) {
                continue;
            }
            $alias = array_key_first($rows[0]);
            $byAlias[strtolower($alias)] = $rows;
        }

        $out = [];
        foreach ($specs as $facetAlias => $spec) {
            $rows = $byAlias[strtolower($facetAlias)] ?? null;
            if ($rows === null) {
                continue;
            }
            if ($spec['kind'] === 'terms') {
                $out[$spec['agg']->getName()] = $this->readTerms($spec, $rows);
            } elseif ($spec['kind'] === 'metrics') {
                foreach ($this->readRootMetrics($spec, $rows) as $name => $result) {
                    $out[$name] = $result;
                }
            }
        }
        return $out;
    }

    /**
     * Pivot a flat list of rows produced by a (potentially multi-column)
     * GROUP BY back into a nested bucket tree mirroring the source
     * aggregation structure.
     */
    private function readTerms(array $spec, array $rows): AggregationResult
    {
        /** @var Terms $rootAgg */
        $rootAgg = $spec['agg'];
        /** @var Metric[] $metrics */
        $metrics = $spec['metrics'];
        /** @var array<int, array{expr: string, alias: string, agg: Terms}> $groupFields */
        $groupFields = $spec['group_fields'];

        $aggsByDepth = [];
        foreach ($groupFields as $depth => $gf) {
            $aggsByDepth[$depth] = $gf['agg'];
        }

        $rootResult = (new AggregationResult($rootAgg->getName(), 'terms', true))
            ->setField($rootAgg->getField())
            ->setLabel($rootAgg->getLabel());

        $labelMap = [];
        foreach ($metrics as $metric) {
            if ($metric->getLabel() !== null) {
                $labelMap[$metric->getName()] = $metric->getLabel();
            }
        }
        if ($labelMap !== []) {
            $rootResult->setMetricLabels($labelMap);
        }

        $tree = [];
        foreach ($rows as $row) {
            $expanded = $this->expandJsonArrayKeys($row, $groupFields);
            foreach ($expanded as $expandedRow) {
                $this->mergeRowIntoTree($expandedRow, $groupFields, $metrics, $rootResult, $aggsByDepth, $tree);
            }
        }

        return $rootResult;
    }

    /**
     * If any group-key column holds a JSON-encoded array string, expand
     * the row into multiple rows (one per array element). This handles
     * Manticore returning multivalue JSON fields as a single bucket key.
     *
     * @param array<int, array{expr: string, alias: string, agg: Terms}> $groupFields
     * @return array<int, array> list of rows (usually just [$row] if no expansion needed)
     */
    private function expandJsonArrayKeys(array $row, array $groupFields): array
    {
        $lowerRow = array_change_key_case($row, CASE_LOWER);
        $expandableKeys = [];

        foreach ($groupFields as $gf) {
            $alias = strtolower($gf['alias']);
            $key = $lowerRow[$alias] ?? null;
            if ($key !== null && is_string($key) && str_starts_with($key, '[')) {
                $decoded = json_decode($key, true);
                if (is_array($decoded) && $decoded !== []) {
                    $expandableKeys[$alias] = $decoded;
                }
            }
        }

        if ($expandableKeys === []) {
            return [$row];
        }

        $originalKeyMap = [];
        foreach (array_keys($row) as $k) {
            $originalKeyMap[strtolower($k)] = $k;
        }

        $results = [$row];
        foreach ($expandableKeys as $alias => $values) {
            $origKey = $originalKeyMap[$alias] ?? $alias;
            $newResults = [];
            foreach ($results as $partial) {
                foreach ($values as $val) {
                    $copy = $partial;
                    $copy[$origKey] = $val;
                    $newResults[] = $copy;
                }
            }
            $results = $newResults;
        }

        return $results;
    }

    /**
     * @param array<int, array{expr: string, alias: string, agg: Terms}> $groupFields
     * @param Metric[] $metrics
     * @param array<int, Terms> $aggsByDepth
     * @param array $tree  reference: composite-key index of created buckets
     */
    private function mergeRowIntoTree(
        array $row,
        array $groupFields,
        array $metrics,
        AggregationResult $rootResult,
        array $aggsByDepth,
        array &$tree
    ): void {
        $row = array_change_key_case($row, CASE_LOWER);
        $docCount = isset($row['count(*)']) ? (int) $row['count(*)'] : 0;
        $metricsRow = [];
        foreach ($metrics as $metric) {
            $alias = strtolower($this->aliasOf($metric->getName()));
            if (! array_key_exists($alias, $row)) {
                continue;
            }
            $metricsRow[$metric->getName()] = $this->castMetricValue($row[$alias]);
        }

        $parentResult = $rootResult;
        $pathKey = '';
        foreach ($groupFields as $depth => $gf) {
            $alias = strtolower($gf['alias']);
            $key = $row[$alias] ?? null;
            $pathKey .= ($depth ? '|' : '') . (string) $key;

            if (! isset($tree[$pathKey])) {
                $bucket = [
                    'key' => $key,
                    'doc_count' => $docCount,
                    'metrics' => [],
                    'children' => [],
                ];
                $isLeaf = ($depth === count($groupFields) - 1);
                if ($isLeaf) {
                    $bucket['metrics'] = $metricsRow;
                }
                $existingBuckets = $parentResult->getBuckets();
                $existingBuckets[] = $bucket;
                $parentResult->setBuckets($existingBuckets);
                $newIndex = count($existingBuckets) - 1;
                $tree[$pathKey] = ['result' => $parentResult, 'index' => $newIndex];

                if (! $isLeaf) {
                    $childAgg = $aggsByDepth[$depth + 1];
                    $childResult = (new AggregationResult($childAgg->getName(), 'terms', true))
                        ->setField($childAgg->getField());

                    $allBuckets = $parentResult->getBuckets();
                    $allBuckets[$newIndex]['children'][$childAgg->getName()] = $childResult;
                    $parentResult->setBuckets($allBuckets);

                    $parentResult = $childResult;
                }
            } else {
                $entry = $tree[$pathKey];
                $existingBuckets = $entry['result']->getBuckets();
                if ($depth === count($groupFields) - 1) {
                    $existingBuckets[$entry['index']]['doc_count'] += $docCount;
                    foreach ($metricsRow as $mn => $mv) {
                        $existing = $existingBuckets[$entry['index']]['metrics'][$mn] ?? null;
                        if ($existing !== null && is_numeric($existing) && is_numeric($mv)) {
                            $existingBuckets[$entry['index']]['metrics'][$mn] = $existing + $mv;
                        } else {
                            $existingBuckets[$entry['index']]['metrics'][$mn] = $mv;
                        }
                    }
                    $entry['result']->setBuckets($existingBuckets);
                } else {
                    $children = $existingBuckets[$entry['index']]['children'];
                    $childAgg = $aggsByDepth[$depth + 1];
                    if (! isset($children[$childAgg->getName()])) {
                        $children[$childAgg->getName()] = (new AggregationResult($childAgg->getName(), 'terms', true))
                            ->setField($childAgg->getField());
                        $existingBuckets[$entry['index']]['children'] = $children;
                        $entry['result']->setBuckets($existingBuckets);
                    }
                    $parentResult = $children[$childAgg->getName()];
                }
            }
        }
    }

    /**
     * @return array<string, AggregationResult>
     */
    private function readRootMetrics(array $spec, array $rows): array
    {
        $row = array_change_key_case($rows[0] ?? [], CASE_LOWER);
        $out = [];
        /** @var Metric[] $metrics */
        $metrics = $spec['metrics'];
        foreach ($metrics as $metric) {
            $alias = strtolower($this->aliasOf($metric->getName()));
            if (! array_key_exists($alias, $row)) {
                continue;
            }
            $result = new AggregationResult($metric->getName(), $metric->getKind(), false);
            $result->setField($metric->getField());
            $result->setLabel($metric->getLabel());
            $result->setValue($this->castMetricValue($row[$alias]));
            $out[$metric->getName()] = $result;
        }
        return $out;
    }

    /**
     * Aliases sent to Manticore are sanitised to [A-Za-z0-9_], matching
     * {@see AggregationBuilder::quoteIdent()}. Some Manticore versions
     * lower-case alias names in the result rows; {@see read()} handles
     * this by normalising rowset keys via `strtolower()`.
     */
    private function aliasOf(string $name): string
    {
        $sanitised = preg_replace('/[^A-Za-z0-9_]/', '_', $name);
        if ($sanitised === '' || preg_match('/^[0-9]/', $sanitised)) {
            $sanitised = '_' . $sanitised;
        }
        return $sanitised;
    }

    private function castMetricValue($raw)
    {
        if ($raw === null) {
            return null;
        }
        if (is_numeric($raw)) {
            return $raw + 0;
        }
        return $raw;
    }
}
