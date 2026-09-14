<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Search\Query\Aggregation\Sql;

use InvalidArgumentException;
use RuntimeException;
use Search\Query\Aggregation\Sql\Schema\Inferrer;
use Search_Query;
use Throwable;
use TikiDb;

/**
 * Materialises the search-engine result set for a single report into a
 * MySQL working table that the SQL executor will then aggregate against.
 *
 * Lifecycle (driven by the executor):
 *
 *     $pop = new TempTablePopulator($db, $schema);
 *     try {
 *         $name = $pop->create();
 *         $rows = $pop->populate($name, $query, $index);
 *         // ... compiler + fetchAll ...
 *     } finally {
 *         $pop->drop($name);
 *     }
 */
class TempTablePopulator
{
    public const DEFAULT_BATCH_SIZE = 1000;

    public const DEFAULT_MAX_ROWS = 200000;

    private TikiDb $db;

    /** @var array{columns: array, indexes?: array, warnings?: array} */
    private array $schema;

    /** @var array<string, array{name: string, type: string, sql_type: string, source_field: string}> indexed by source_field */
    private array $columnsBySource;

    /** @var array<int, bool> positional index of columns that are group keys */
    private array $groupKeyPositions = [];

    private int $maxRows = self::DEFAULT_MAX_ROWS;

    /** @var string[] */
    private array $warnings = [];

    public function __construct(TikiDb $db, array $schema)
    {
        $this->db = $db;
        $this->schema = $schema;
        $this->columnsBySource = [];
        $pos = 0;
        foreach ($schema['columns'] ?? [] as $col) {
            $this->validateIdentifier($col['name'], 'column name');
            if (! empty($col['source_field'])) {
                $this->columnsBySource[$col['source_field']] = $col;
                if (! empty($col['is_group_key'])) {
                    $this->groupKeyPositions[$pos] = true;
                }
                $pos++;
            }
        }
    }

    public function setMaxRows(int $max): self
    {
        if ($max < 1) {
            throw new InvalidArgumentException('max_rows must be a positive integer');
        }
        $this->maxRows = $max;
        return $this;
    }

    /**
     * @return string[]
     */
    public function getWarnings(): array
    {
        return $this->warnings;
    }

    public function create(?string $forcedName = null): string
    {
        $name = $forcedName ?? $this->generateTableName();
        $this->validateIdentifier($name, 'table name');

        $columnsDdl = [];
        foreach ($this->schema['columns'] ?? [] as $col) {
            $columnsDdl[] = sprintf('`%s` %s NULL', $col['name'], $col['sql_type']);
        }
        if ($columnsDdl === []) {
            throw new RuntimeException('AggregationTempTablePopulator: schema has no columns; refusing to CREATE empty table');
        }

        foreach ($this->schema['indexes'] ?? [] as $idx) {
            $cols = array_map(fn ($c) => '`' . $c . '`', $idx['columns']);
            $columnsDdl[] = sprintf('KEY `%s` (%s)', $idx['name'], implode(', ', $cols));
        }

        $ddl = sprintf(
            "CREATE TABLE `%s` (\n  %s\n) ENGINE=MEMORY",
            $name,
            implode(",\n  ", $columnsDdl)
        );

        try {
            $this->db->queryException($ddl);
        } catch (Throwable $memErr) {
            $this->warnings[] = sprintf(
                'Falling back to ENGINE=InnoDB for aggregation table `%s`: %s',
                $name,
                $memErr->getMessage()
            );
            $ddl = preg_replace('/ENGINE=MEMORY$/', 'ENGINE=InnoDB', $ddl);
            $this->db->queryException($ddl);
        }

        return $name;
    }

    /**
     * @return int number of rows inserted
     */
    public function populate(string $tableName, Search_Query $query, $index): int
    {
        $this->validateIdentifier($tableName, 'table name');
        $sourceFields = array_keys($this->columnsBySource);
        if ($sourceFields !== []) {
            $query->setSelectionFields($sourceFields);
        }
        $batchSize = $query->getScrollBatchSize() ?? self::DEFAULT_BATCH_SIZE;

        $insertCols = [];
        foreach ($this->columnsBySource as $col) {
            $insertCols[] = '`' . $col['name'] . '`';
        }
        $colCount = count($insertCols);
        $insertPrefix = sprintf(
            'INSERT INTO `%s` (%s) VALUES ',
            $tableName,
            implode(', ', $insertCols)
        );

        $batch = [];
        $rowCount = 0;
        foreach ($query->scroll($index) as $row) {
            $expanded = $this->expandRow($row);
            foreach ($expanded as $expandedRow) {
                $batch[] = $expandedRow;
                $rowCount++;
                if ($rowCount > $this->maxRows) {
                    throw new RuntimeException(sprintf(
                        'Report exceeds aggregation row limit (%d). Tighten the filters or override the limit.',
                        $this->maxRows
                    ));
                }
            }
            if (count($batch) >= $batchSize) {
                $this->flushBatch($insertPrefix, $colCount, $batch);
                $batch = [];
            }
        }
        if ($batch !== []) {
            $this->flushBatch($insertPrefix, $colCount, $batch);
        }
        return $rowCount;
    }

    public function drop(string $tableName): void
    {
        if (! $this->isValidIdentifier($tableName)) {
            return;
        }
        try {
            $this->db->queryException(sprintf('DROP TABLE IF EXISTS `%s`', $tableName));
        } catch (Throwable $e) {
            $this->warnings[] = sprintf('DROP TABLE `%s` failed: %s', $tableName, $e->getMessage());
        }
    }

    /**
     * Expand a single scrolled row into one or more temp-table rows.
     *
     * When a group-key column contains an array, each element produces a
     * separate row (cartesian product if multiple group keys have arrays).
     * Non-group-key arrays are still coerced to a string as before.
     *
     * @return array<int, array> list of coerced row tuples
     */
    private function expandRow($row): array
    {
        if (! is_array($row) && ! ($row instanceof \ArrayAccess)) {
            throw new InvalidArgumentException(sprintf(
                'AggregationTempTablePopulator::expandRow() expects array or ArrayAccess, got %s',
                is_object($row) ? get_class($row) : gettype($row)
            ));
        }

        $coerced = [];
        $expandPositions = [];
        $pos = 0;
        foreach ($this->columnsBySource as $sourceField => $col) {
            $value = is_array($row)
                ? ($row[$sourceField] ?? null)
                : $row[$sourceField];

            if (is_array($value) && isset($this->groupKeyPositions[$pos])) {
                if (empty($value)) {
                    return [];
                }
                $coerced[] = array_map(
                    fn($v) => $this->coerceValue($v, $col['type']),
                    array_values($value)
                );
                $expandPositions[] = $pos;
            } else {
                $coerced[] = $this->coerceValue($value, $col['type']);
            }
            $pos++;
        }

        if ($expandPositions === []) {
            return [$coerced];
        }

        return $this->cartesianExpand($coerced, $expandPositions);
    }

    /**
     * Given a partially-built row where some positions hold arrays of
     * alternatives, compute the cartesian product and return flat rows.
     */
    private function cartesianExpand(array $template, array $expandPositions): array
    {
        $results = [$template];
        foreach ($expandPositions as $pos) {
            $values = $template[$pos];
            $newResults = [];
            foreach ($results as $partial) {
                foreach ($values as $val) {
                    $copy = $partial;
                    $copy[$pos] = $val;
                    $newResults[] = $copy;
                }
            }
            $results = $newResults;
        }
        return $results;
    }

    private function coerceValue($value, string $type)
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_array($value)) {
            $value = is_string(reset($value)) ? implode(', ', $value) : json_encode($value);
        }
        switch ($type) {
            case Inferrer::TYPE_NUMERIC:
                if (is_numeric($value)) {
                    return $value + 0;
                }
                $ts = strtotime((string) $value);
                if ($ts !== false) {
                    return $ts;
                }
                $clean = preg_replace('/[^\d\.\-]/', '', (string) $value);
                if ($clean === '' || ! is_numeric($clean)) {
                    return null;
                }
                return $clean + 0;
            case Inferrer::TYPE_TIMESTAMP:
                if (is_numeric($value)) {
                    return (int) $value;
                }
                $ts = strtotime((string) $value);
                return $ts === false ? null : $ts;
            default:
                return (string) $value;
        }
    }

    private function flushBatch(string $insertPrefix, int $colCount, array $batch): void
    {
        if ($batch === []) {
            return;
        }
        $rowPlaceholder = '(' . implode(', ', array_fill(0, $colCount, '?')) . ')';
        $sql = $insertPrefix . implode(', ', array_fill(0, count($batch), $rowPlaceholder));
        $values = [];
        foreach ($batch as $row) {
            foreach ($row as $v) {
                $values[] = $v;
            }
        }
        $this->db->queryException($sql, $values);
    }

    private function generateTableName(): string
    {
        return 'tiki_agg_' . substr(bin2hex(random_bytes(6)), 0, 12);
    }

    private function isValidIdentifier(string $ident): bool
    {
        return (bool) preg_match('/^[a-zA-Z_][a-zA-Z0-9_]{0,63}$/', $ident);
    }

    private function validateIdentifier(string $ident, string $context): void
    {
        if (! $this->isValidIdentifier($ident)) {
            throw new RuntimeException(sprintf(
                'AggregationTempTablePopulator: refusing unsafe %s "%s"',
                $context,
                $ident
            ));
        }
    }
}
