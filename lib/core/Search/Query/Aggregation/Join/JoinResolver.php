<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Search\Query\Aggregation\Join;

use Search_Index_Interface;
use Search_Query;
use Search_ResultSet;
use Search\ResultSet\AggregationResult;

/**
 * Post-processes a Search_ResultSet's aggregation tree by resolving each
 * {@see Join}: looks up the joined documents in one batched search per
 * join and attaches the projected fields onto each matching bucket as
 * namespaced metrics.
 */
class JoinResolver
{
    public const MAX_KEYS_PER_JOIN = 5000;

    /**
     * @param Join[] $joins
     */
    public function __construct(private readonly array $joins)
    {
    }

    public function resolve(Search_ResultSet $resultSet, Search_Index_Interface $index): void
    {
        if (empty($this->joins) || ! $resultSet->hasAggregations()) {
            return;
        }
        foreach ($this->joins as $join) {
            $this->resolveOne($resultSet, $index, $join);
        }
    }

    private function resolveOne(
        Search_ResultSet $resultSet,
        Search_Index_Interface $index,
        Join $join
    ): void {
        $matches = [];
        foreach ($resultSet->getAggregationResults() as $aggResult) {
            $this->collectMatchingBuckets($aggResult, $join, $matches);
        }
        if (empty($matches)) {
            return;
        }

        $keys = [];
        foreach ($matches as &$entry) {
            foreach ($entry['buckets'] as &$bucket) {
                $key = $bucket['key'];
                if ($key === null || $key === '') {
                    continue;
                }
                $keys[(string) $key] = $key;
            }
            unset($bucket);
        }
        unset($entry);
        if (empty($keys)) {
            return;
        }
        if (count($keys) > self::MAX_KEYS_PER_JOIN) {
            $keys = array_slice($keys, 0, self::MAX_KEYS_PER_JOIN, true);
        }

        $lookup = $this->fetchLookup($index, $join, array_values($keys));

        foreach ($matches as &$entry) {
            $aggResult = $entry['result'];
            $newBuckets = [];
            foreach ($aggResult->getBuckets() as $bucket) {
                $key = $bucket['key'];
                $row = $key !== null && isset($lookup[(string) $key])
                    ? $lookup[(string) $key]
                    : null;
                foreach ($join->getSelect() as $field) {
                    $bucket['metrics'][$join->getName() . '.' . $field] = $row[$field] ?? null;
                }
                $newBuckets[] = $bucket;
            }
            $aggResult->setBuckets($newBuckets);
        }
        unset($entry);
    }

    private function collectMatchingBuckets(
        AggregationResult $aggResult,
        Join $join,
        array &$matches
    ): void {
        if (! $aggResult->isBucket()) {
            return;
        }
        $matchesThis = $aggResult->getName() === $join->getOn()
            || $aggResult->getField() === $join->getOn();
        if ($matchesThis) {
            $matches[] = ['result' => $aggResult, 'buckets' => $aggResult->getBuckets()];
        }
        foreach ($aggResult->getBuckets() as $bucket) {
            foreach ($bucket['children'] ?? [] as $child) {
                if ($child instanceof AggregationResult) {
                    $this->collectMatchingBuckets($child, $join, $matches);
                }
            }
        }
    }

    private function fetchLookup(
        Search_Index_Interface $index,
        Join $join,
        array $keys
    ): array {
        $query = new Search_Query();

        if ($join->getField()) {
            $this->buildFieldFilter($query, $join, $keys);
        } else {
            foreach ($keys as $key) {
                $keyStr = (string) $key;
                if (str_contains($keyStr, ':')) {
                    [$type, $id] = explode(':', $keyStr, 2);
                    $query->addObject($type, $id);
                } else {
                    $query->addObject($join->getObjectType() ?: 'trackeritem', $keyStr);
                }
            }
        }

        if ($join->getTrackerId() !== null) {
            $query->filterIdentifier((string) $join->getTrackerId(), 'tracker_id');
        }
        if ($join->getField()) {
            $objectType = $join->getObjectType();
            if ($objectType) {
                $query->filterType($objectType);
            }
        }

        $select = ['object_id', 'object_type'];
        if ($join->getField()) {
            $select[] = $join->getField();
        }
        foreach ($join->getSelect() as $field) {
            if (! in_array($field, $select, true)) {
                $select[] = $field;
            }
        }
        $query->setSelectionFields($select);
        $query->setRange(0, count($keys));

        $sub = $query->search($index);

        if ($join->getField()) {
            return $this->buildFieldLookup($sub, $join);
        }
        return $this->buildObjectIdLookup($sub, $keys);
    }

    private function buildFieldFilter(Search_Query $query, Join $join, array $keys): void
    {
        $expr = implode(' OR ', array_map(fn($k) => '"' . (string) $k . '"', $keys));
        if ($join->isMultivalue()) {
            $query->filterMultivalue($expr, $join->getField());
        } else {
            $query->setIdentifierFields([$join->getField()]);
            $query->filterMultivalue($expr, $join->getField());
        }
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function buildObjectIdLookup(Search_ResultSet $sub, array $keys): array
    {
        $keySet = array_flip(array_map('strval', $keys));

        $lookup = [];
        foreach ($sub as $row) {
            $objectId = $row['object_id'] ?? null;
            $objectType = $row['object_type'] ?? null;
            if ($objectId === null) {
                continue;
            }

            $compositeKey = $objectType . ':' . $objectId;
            if (isset($keySet[$compositeKey])) {
                $lookup[$compositeKey] = $row;
            } elseif (isset($keySet[(string) $objectId])) {
                $lookup[(string) $objectId] = $row;
            }
        }
        return $lookup;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function buildFieldLookup(Search_ResultSet $sub, Join $join): array
    {
        $lookupField = $join->getField();
        $lookup = [];
        foreach ($sub as $row) {
            $fieldVal = $row[$lookupField] ?? null;
            if ($fieldVal === null || $fieldVal === '') {
                continue;
            }
            $lookup[(string) $fieldVal] = $row;
        }
        return $lookup;
    }
}
