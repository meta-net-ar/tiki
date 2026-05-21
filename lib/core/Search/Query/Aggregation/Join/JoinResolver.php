<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Search\Query\Aggregation\Join;

use Search_Expr_Or;
use Search_Expr_Token;
use Search_Index_Interface;
use Search_Query;
use Search_Query_Relation;
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

        if ($join->getRelation()) {
            $this->buildRelationFilter($query, $join, $keys);
        } elseif ($join->getField()) {
            $this->buildFieldFilter($query, $join, $keys);
        } else {
            foreach ($keys as $key) {
                $query->addObject($join->getObjectType() ?: 'trackeritem', (string) $key);
            }
        }

        if ($join->getTrackerId() !== null) {
            $query->filterIdentifier((string) $join->getTrackerId(), 'tracker_id');
        }
        if ($join->getField() || $join->getRelation()) {
            $objectType = $join->getObjectType();
            if ($objectType) {
                $query->filterType($objectType);
            }
        }

        $select = ['object_id', 'object_type'];
        if ($join->getField()) {
            $select[] = $join->getField();
        }
        if ($join->getRelation()) {
            $select[] = 'relations';
        }
        foreach ($join->getSelect() as $field) {
            if (! in_array($field, $select, true)) {
                $select[] = $field;
            }
        }
        $query->setSelectionFields($select);
        $query->setRange(0, count($keys));

        $sub = $query->search($index);

        if ($join->getRelation()) {
            return $this->buildRelationLookup($sub, $join, $keys);
        } elseif ($join->getField()) {
            return $this->buildFieldLookup($sub, $join);
        }
        return $this->buildObjectIdLookup($sub);
    }

    private function buildFieldFilter(Search_Query $query, Join $join, array $keys): void
    {
        $or = new Search_Expr_Or([]);
        $type = $join->isMultivalue() ? 'multivalue' : 'identifier';
        foreach ($keys as $key) {
            $or->addPart(new Search_Expr_Token((string) $key, $type, $join->getField()));
        }
        $query->getExpr()->addPart($or);
    }

    private function buildRelationFilter(Search_Query $query, Join $join, array $keys): void
    {
        $or = new Search_Expr_Or([]);
        $objectType = $join->getObjectType() ?: 'trackeritem';
        foreach ($keys as $key) {
            $token = Search_Query_Relation::token($join->getRelation(), $objectType, (string) $key);
            $or->addPart(new Search_Expr_Token($token, 'multivalue', 'relations'));
        }
        $query->getExpr()->addPart($or);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function buildObjectIdLookup(Search_ResultSet $sub): array
    {
        $lookup = [];
        foreach ($sub as $row) {
            if (! isset($row['object_id'])) {
                continue;
            }
            $lookup[(string) $row['object_id']] = $row;
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

    /**
     * @param string[] $keys bucket keys to match against
     * @return array<string, array<string, mixed>>
     */
    private function buildRelationLookup(Search_ResultSet $sub, Join $join, array $keys): array
    {
        $qualifier = $join->getRelation();
        $objectType = $join->getObjectType() ?: 'trackeritem';

        $tokenToKey = [];
        foreach ($keys as $key) {
            $rawToken = $qualifier . ':' . $objectType . ':' . (string) $key;
            $tokenToKey[$rawToken] = (string) $key;
            $tokenToKey[$this->hashMultivalueTokenMysql($rawToken)] = (string) $key;
            $tokenToKey[(string) crc32($rawToken)] = (string) $key;
        }

        $lookup = [];
        foreach ($sub as $row) {
            $relations = $row['relations'] ?? '';
            if (is_array($relations)) {
                $tokens = $relations;
            } else {
                $tokens = preg_split('/[\s,]+/', (string) $relations, -1, PREG_SPLIT_NO_EMPTY);
            }
            foreach ($tokens as $token) {
                $token = (string) $token;
                if (isset($tokenToKey[$token])) {
                    $bucketKey = $tokenToKey[$token];
                    if (! isset($lookup[$bucketKey])) {
                        $lookup[$bucketKey] = $row;
                    }
                }
            }
        }
        return $lookup;
    }

    private function hashMultivalueTokenMysql(string $value): string
    {
        $hash = md5($value);
        return strtr('token' . $hash, '1234567890', 'pqrtuvwxyz');
    }
}
