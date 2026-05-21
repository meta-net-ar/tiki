<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Search\Query\Aggregation;

/**
 * An aggregation requested on a Search_Query. Mirrors Search_Query_Facet_Interface
 * but supports both bucket aggregations (Terms, ...) and metric aggregations
 * (Sum, Avg, Min, Max, Count, Cardinality, Stats), as well as nesting
 * (a Terms bucket can contain further metric or bucket children).
 *
 * Used by index backends to push GROUP BY / metric computations to the engine
 * (Elasticsearch aggs, Manticore SphinxQL GROUP BY, MySQL GROUP BY) so the
 * server returns small bucket sets instead of full document hits.
 */
interface AggregationInterface
{
    /**
     * Unique name within the query / parent aggregation.
     */
    public function getName(): string;

    public function setName(string $name): self;

    /**
     * Optional human-readable label.
     */
    public function getLabel(): ?string;

    public function setLabel(?string $label): self;

    /**
     * Backend-neutral kind of aggregation. Currently:
     *  - 'sum', 'avg', 'min', 'max', 'value_count', 'cardinality', 'stats'
     *  - 'terms'
     */
    public function getKind(): string;

    /**
     * Field this aggregation operates on. Bucket aggs group by it; metric
     * aggs compute over it. Some kinds (e.g. value_count) accept the implicit
     * document count and may return null.
     */
    public function getField(): ?string;

    /**
     * True if the aggregation produces buckets (each potentially containing
     * sub-aggregations). False if it produces a single metric value.
     */
    public function isBucket(): bool;
}
