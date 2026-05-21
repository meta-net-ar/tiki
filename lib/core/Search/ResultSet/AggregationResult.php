<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Search\ResultSet;

/**
 * Result of a {@see \Search\Query\Aggregation\AggregationInterface} request,
 * normalized across index backends (Elasticsearch / Manticore / MySQL).
 *
 * For metric aggregations: $value holds the scalar (or stats map).
 * For bucket aggregations: $buckets holds an ordered list of buckets, each
 *   shaped as:
 *     [
 *       'key'        => mixed,                      // raw bucket key
 *       'doc_count'  => int,
 *       'metrics'    => array<string,mixed>,        // child metric results by name
 *       'children'   => array<string, AggregationResult>,
 *                                                  // nested bucket results by child name
 *     ]
 */
class AggregationResult
{
    private string $name;
    private string $kind;
    private bool $isBucket;
    private ?string $field = null;
    /** @var array<int, array> */
    private array $buckets = [];
    private $value = null;
    private ?int $sumOtherDocCount = null;
    private ?int $docCountErrorUpperBound = null;
    private ?string $label = null;
    /** @var array<string, string> metric name => display label */
    private array $metricLabels = [];

    public function __construct(string $name, string $kind, bool $isBucket)
    {
        $this->name = $name;
        $this->kind = $kind;
        $this->isBucket = $isBucket;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setLabel(?string $label): self
    {
        $this->label = $label;
        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function getKind(): string
    {
        return $this->kind;
    }

    public function isBucket(): bool
    {
        return $this->isBucket;
    }

    /**
     * Source field of the underlying aggregation. For bucket aggregations
     * this is the field used for grouping; for metric aggregations the field
     * being reduced. Used by post-processing steps (join resolver) to match
     * results back to their source.
     */
    public function setField(?string $field): self
    {
        $this->field = $field;
        return $this;
    }

    public function getField(): ?string
    {
        return $this->field;
    }

    public function setValue($value): self
    {
        $this->value = $value;
        return $this;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function addBucket(array $bucket): self
    {
        $this->buckets[] = $bucket + [
            'key' => null,
            'doc_count' => 0,
            'metrics' => [],
            'children' => [],
        ];
        return $this;
    }

    /**
     * Replace the bucket list. Used by post-processors (e.g. join resolver)
     * that need to rewrite buckets after the backend response has been
     * read in.
     *
     * @param array<int, array{key?: mixed, doc_count?: int, metrics?: array<string, mixed>, children?: array<string, AggregationResult>}> $buckets
     */
    public function setBuckets(array $buckets): self
    {
        $this->buckets = [];
        foreach ($buckets as $bucket) {
            $this->addBucket($bucket);
        }
        return $this;
    }

    /**
     * @return array<int, array{key: mixed, doc_count: int, metrics: array<string, mixed>, children: array<string, AggregationResult>}>
     */
    public function getBuckets(): array
    {
        return $this->buckets;
    }

    public function getBucketCount(): int
    {
        return count($this->buckets);
    }

    public function setSumOtherDocCount(?int $count): self
    {
        $this->sumOtherDocCount = $count;
        return $this;
    }

    public function getSumOtherDocCount(): ?int
    {
        return $this->sumOtherDocCount;
    }

    public function setDocCountErrorUpperBound(?int $err): self
    {
        $this->docCountErrorUpperBound = $err;
        return $this;
    }

    public function getDocCountErrorUpperBound(): ?int
    {
        return $this->docCountErrorUpperBound;
    }

    public function setMetricLabels(array $labels): self
    {
        $this->metricLabels = $labels;
        return $this;
    }

    public function getMetricLabels(): array
    {
        return $this->metricLabels;
    }

    /**
     * Convenience: flatten this bucket aggregation into a list of associative
     * row arrays suitable for table/chart rendering. Each output row contains:
     *   - <name>            => bucket key
     *   - doc_count         => int
     *   - <child metric>    => scalar (or [...]/null for stats)
     *   - <child bucket>    => AggregationResult (kept as object)
     *
     * For metric aggregations, returns a single-row list with the scalar at
     * key <name>.
     */
    public function toRows(): array
    {
        if (! $this->isBucket) {
            return [[
                $this->name => $this->value,
            ]];
        }
        $rows = [];
        foreach ($this->buckets as $bucket) {
            $row = [
                $this->name => $bucket['key'],
                'doc_count' => $bucket['doc_count'],
            ];
            foreach ($bucket['metrics'] as $metricName => $metricValue) {
                $row[$metricName] = $metricValue;
            }
            foreach ($bucket['children'] as $childName => $childResult) {
                $row[$childName] = $childResult;
            }
            $rows[] = $row;
        }
        return $rows;
    }
}
