<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Search\Query\Aggregation\Sql\Schema;

use Search\Query\Aggregation\AggregationInterface;
use Search\Query\Aggregation\Formula;
use Search\Query\Aggregation\Metric;
use Search\Query\Aggregation\Terms;

/**
 * Walks an aggregation tree and produces a CREATE TABLE schema for the
 * per-request working table that the temp-table tier populates.
 *
 * The output is intentionally minimal: only columns referenced by group
 * keys or metric fields are produced, and each column gets the narrowest
 * type that still supports the operations the tree asks for.
 *
 * Type inference policy:
 *
 *   field appears as ...                          -> SQL column type
 *   ----------------------------------------------------------------
 *   group key only                                 VARCHAR(255)
 *   metric source: sum / avg / min / max / stats   DECIMAL(20,4)
 *   metric source: cardinality / count             VARCHAR(255)
 *   field-type-resolver returns 'numeric'          DECIMAL(20,4)
 *   field-type-resolver returns 'timestamp'        BIGINT
 */
class Inferrer
{
    public const TYPE_TEXT = 'text';
    public const TYPE_NUMERIC = 'numeric';
    public const TYPE_TIMESTAMP = 'timestamp';

    public const TYPE_HINTS = [
        self::TYPE_TEXT,
        self::TYPE_NUMERIC,
        self::TYPE_TIMESTAMP,
    ];

    private const NUMERIC_OP_KINDS = [
        Metric::KIND_SUM,
        Metric::KIND_AVG,
        Metric::KIND_MIN,
        Metric::KIND_MAX,
        Metric::KIND_STATS,
    ];

    private $fieldTypeResolver;

    public function __construct(?callable $fieldTypeResolver = null)
    {
        $this->fieldTypeResolver = $fieldTypeResolver;
    }

    /**
     * @param array<string, AggregationInterface> $aggregations
     * @return array{
     *     columns: array<int, array{name: string, type: string, sql_type: string, source_field: string}>,
     *     indexes: array<int, array{name: string, columns: string[]}>,
     *     warnings: string[]
     * }
     */
    public function infer(array $aggregations): array
    {
        $declaredTypes = [];
        $sourceFields = [];
        $groupKeyFields = [];
        $warnings = [];
        $indexes = [];

        $this->walk($aggregations, $declaredTypes, $sourceFields, $indexes, $warnings, [], $groupKeyFields);

        $columns = [];
        foreach ($declaredTypes as $field => $type) {
            $columns[] = [
                'name' => $this->columnName($field),
                'type' => $type,
                'sql_type' => $this->sqlTypeFor($type),
                'source_field' => $sourceFields[$field],
                'is_group_key' => isset($groupKeyFields[$field]),
            ];
        }

        return [
            'columns' => $columns,
            'indexes' => $indexes,
            'warnings' => $warnings,
        ];
    }

    public function columnName(string $field): string
    {
        $col = strtolower($field);
        $col = preg_replace('/[^a-z0-9_]/', '_', $col);
        $col = preg_replace('/_+/', '_', $col);
        $col = trim($col, '_');
        if ($col === '' || ! preg_match('/^[a-z_]/', $col)) {
            $col = 'f_' . $col;
        }
        return $col;
    }

    private function walk(
        iterable $aggregations,
        array &$declaredTypes,
        array &$sourceFields,
        array &$indexes,
        array &$warnings,
        array $groupChain = [],
        array &$groupKeyFields = []
    ): void {
        foreach ($aggregations as $agg) {
            if ($agg instanceof Terms) {
                $field = $agg->getField();
                if ($field !== null && $field !== '') {
                    $this->declare($field, $this->resolveType($field, self::TYPE_TEXT), $declaredTypes, $sourceFields, $warnings);
                    $groupKeyFields[$field] = true;
                }
                $childChain = $field !== null && $field !== ''
                    ? array_merge($groupChain, [$this->columnName($field)])
                    : $groupChain;

                if ($agg->hasChildren()) {
                    $this->walk($agg->getChildren(), $declaredTypes, $sourceFields, $indexes, $warnings, $childChain, $groupKeyFields);
                }

                if ($groupChain === [] && $childChain !== []) {
                    $indexes[] = [
                        'name' => 'g_' . count($indexes),
                        'columns' => $childChain,
                    ];
                }
                continue;
            }

            if ($agg instanceof Metric) {
                $field = $agg->getField();
                if ($field === null || $field === '') {
                    continue;
                }
                $needsNumeric = in_array($agg->getKind(), self::NUMERIC_OP_KINDS, true);
                $required = $needsNumeric ? self::TYPE_NUMERIC : self::TYPE_TEXT;
                $resolved = $this->resolveType($field, $required);
                if ($needsNumeric && $resolved !== self::TYPE_NUMERIC) {
                    $warnings[] = sprintf(
                        'Field "%s" is used by metric "%s" with a numeric operation but is not declared numeric in the search index; values will be cast at populate time.',
                        $field,
                        $agg->getName()
                    );
                    $resolved = self::TYPE_NUMERIC;
                }
                $this->declare($field, $resolved, $declaredTypes, $sourceFields, $warnings);
                continue;
            }

            if ($agg instanceof Formula) {
                if ($agg->isAggregate()) {
                    foreach ($agg->getIdentifiers() as $ident) {
                        $this->declare($ident, $this->resolveType($ident, self::TYPE_NUMERIC), $declaredTypes, $sourceFields, $warnings);
                    }
                }
                continue;
            }
        }
    }

    private function declare(
        string $field,
        string $type,
        array &$declaredTypes,
        array &$sourceFields,
        array &$warnings
    ): void {
        $sourceFields[$field] = $field;
        if (! isset($declaredTypes[$field])) {
            $declaredTypes[$field] = $type;
            return;
        }
        $existing = $declaredTypes[$field];
        if ($existing === $type) {
            return;
        }
        $rank = [
            self::TYPE_TEXT => 0,
            self::TYPE_TIMESTAMP => 1,
            self::TYPE_NUMERIC => 2,
        ];
        if (($rank[$type] ?? 0) > ($rank[$existing] ?? 0)) {
            $declaredTypes[$field] = $type;
            $warnings[] = sprintf(
                'Field "%s" used as both %s and %s; column promoted to %s.',
                $field,
                $existing,
                $type,
                $type
            );
        }
    }

    private function resolveType(string $field, string $required): string
    {
        if ($this->fieldTypeResolver === null) {
            return $required;
        }
        $hint = ($this->fieldTypeResolver)($field);
        if (! is_string($hint) || ! in_array($hint, self::TYPE_HINTS, true)) {
            return $required;
        }
        return $hint;
    }

    private function sqlTypeFor(string $type): string
    {
        return match ($type) {
            self::TYPE_NUMERIC => 'DECIMAL(20,4)',
            self::TYPE_TIMESTAMP => 'BIGINT',
            default => 'VARCHAR(255)',
        };
    }
}
