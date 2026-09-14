<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Search\Query\Aggregation;

use Feedback;
use Search\Query\Aggregation\Join\Join;
use Search\Query\Aggregation\Join\JoinResolver;
use Search\Query\Aggregation\Sql\Executor as SqlExecutor;
use Search\Query\Aggregation\Tier\TierSelector;
use Search_Index_Interface;
use Search_Query;
use Search_ResultSet;
use TikiDb;
use Throwable;

/**
 * Thin facade that encapsulates the full aggregation lifecycle:
 *
 *   1. Parse wiki markup via {@see WikiBuilder} (optional — callers may
 *      supply a pre-built aggregation tree instead).
 *   2. Select the execution tier ({@see TierSelector}).
 *   3. For the engine tier, push aggregations onto the query so the
 *      backend handles them during {@see Search_Query::search()}.
 *   4. For the temp-table tier, run the SQL executor after the search
 *      and attach the results to the result set.
 *   5. Resolve any {@see Join} specs against the aggregation buckets.
 *
 * Plugins call either {@see searchWithAggregations()} (wraps the search
 * call) or {@see enrichResult()} (when the search was already performed
 * elsewhere, e.g. multisearch).
 */
class QueryFacade
{
    /** @var AggregationInterface[] */
    private array $aggregations = [];

    /** @var Join[] */
    private array $joins = [];

    private string $resolvedTier = TierSelector::TIER_ENGINE;

    private ?string $tierOverride = null;

    /**
     * Populate from wiki markup ({group}, {metric}, {having}, {join}).
     */
    public function applyWikiMarkup(\WikiParser_PluginMatcher $matches): self
    {
        $builder = new WikiBuilder();
        $builder->apply($matches);
        $this->aggregations = $builder->getAggregations();
        $this->joins = $builder->getJoins();
        return $this;
    }

    /**
     * Populate from a pre-built aggregation tree (programmatic callers).
     *
     * @param AggregationInterface[] $aggregations
     * @param Join[] $joins
     */
    public function setAggregations(array $aggregations, array $joins = []): self
    {
        $this->aggregations = $aggregations;
        $this->joins = $joins;
        return $this;
    }

    public function setTierOverride(?string $tier): self
    {
        $this->tierOverride = $tier;
        return $this;
    }

    public function hasAggregations(): bool
    {
        return ! empty($this->aggregations);
    }

    public function getResolvedTier(): string
    {
        return $this->resolvedTier;
    }

    /**
     * Prepare the query for engine-tier aggregations (must be called
     * before the search), then run the search, then handle temp-table
     * tier and joins on the result.
     *
     * For multisearch queries the caller should use {@see prepareQuery()}
     * + {@see enrichResult()} instead.
     */
    public function searchWithAggregations(
        Search_Query $query,
        Search_Index_Interface $index
    ): Search_ResultSet {
        $this->prepareQuery($query, $index);
        $result = $query->search($index);
        $this->enrichResult($result, $query, $index);
        return $result;
    }

    /**
     * Pre-search step: select the tier and, when the engine tier is
     * chosen, push aggregations onto the query so the backend includes
     * them in the request. Call this before {@see Search_Query::search()}.
     */
    public function prepareQuery(Search_Query $query, $index = null): void
    {
        if ($this->aggregations === []) {
            $this->resolvedTier = TierSelector::TIER_ENGINE;
            return;
        }

        $this->resolvedTier = (new TierSelector())->select(
            $this->aggregations,
            $this->tierOverride,
            $index
        );

        if ($this->resolvedTier === TierSelector::TIER_ENGINE) {
            foreach ($this->aggregations as $agg) {
                $query->addAggregation($agg);
            }
            $query->setRange(0, 0);
        }
    }

    /**
     * Post-search step: if the temp-table tier was selected, execute the
     * SQL aggregation pipeline and attach results. Then resolve joins.
     *
     * Safe to call even when no aggregations are configured (no-op).
     */
    public function enrichResult(
        Search_ResultSet $result,
        Search_Query $query,
        $index = null
    ): void {
        if ($this->aggregations !== [] && $this->resolvedTier === TierSelector::TIER_TEMP_TABLE) {
            $this->executeTempTable($result, $query, $index);
        }

        if ($this->joins !== []) {
            (new JoinResolver($this->joins))->resolve($result, $index);
        }
    }

    private function executeTempTable(
        Search_ResultSet $result,
        Search_Query $query,
        $index
    ): void {
        $aggQuery = clone $query;
        $aggQuery->clearAggregations();
        try {
            $executor = new SqlExecutor(TikiDb::get());
            foreach ($executor->execute($this->aggregations, $aggQuery, $index) as $aggResult) {
                $result->setAggregationResult($aggResult);
            }
            foreach ($executor->getLastWarnings() as $w) {
                Feedback::warning($w);
            }
        } catch (Throwable $e) {
            Feedback::error(tr('Aggregation failed: %0', $e->getMessage()));
        }
    }
}
