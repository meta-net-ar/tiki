<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Search\Query\Aggregation\Tier;

use Search\Query\Aggregation\AggregationInterface;
use Search\Query\Aggregation\Formula;
use Search\Query\Aggregation\Metric;
use Search\Query\Aggregation\Terms;
use Search_Index_Interface;
use Search_MySql_Index;

/**
 * Decides which execution tier a report's aggregation tree should run on.
 *
 * Two tiers are recognised today:
 *
 *  - {@see self::TIER_ENGINE}      pushes GROUP BY / metrics straight to the
 *                                  search backend.
 *
 *  - {@see self::TIER_TEMP_TABLE}  scrolls hits into a per-request MySQL
 *                                  working table and runs all aggregation
 *                                  as SQL there.
 */
class TierSelector
{
    public const TIER_ENGINE = 'engine';
    public const TIER_TEMP_TABLE = 'temp_table';
    public const TIER_AUTO = 'auto';

    public const VALID_TIERS = [
        self::TIER_ENGINE,
        self::TIER_TEMP_TABLE,
        self::TIER_AUTO,
    ];

    /**
     * @param array<string, AggregationInterface> $aggregations
     */
    public function select(array $aggregations, ?string $userOverride = null, $index = null): string
    {
        $override = $this->normalizeTier($userOverride);
        if ($override !== self::TIER_AUTO) {
            return $override;
        }
        if ($this->engineLacksAggregationSupport($index)) {
            return self::TIER_TEMP_TABLE;
        }
        return $this->detectFeatures($aggregations, $index) === [] ? self::TIER_ENGINE : self::TIER_TEMP_TABLE;
    }

    /**
     * @param array<string, AggregationInterface> $aggregations
     * @return array{tier: string, override: bool, reasons: string[]}
     */
    public function explain(array $aggregations, ?string $userOverride = null, $index = null): array
    {
        $override = $this->normalizeTier($userOverride);
        if ($override !== self::TIER_AUTO) {
            return [
                'tier' => $override,
                'override' => true,
                'reasons' => [],
            ];
        }
        if ($this->engineLacksAggregationSupport($index)) {
            return [
                'tier' => self::TIER_TEMP_TABLE,
                'override' => false,
                'reasons' => ['engine:no_aggregation_support'],
            ];
        }
        $reasons = $this->detectFeatures($aggregations, $index);
        return [
            'tier' => $reasons === [] ? self::TIER_ENGINE : self::TIER_TEMP_TABLE,
            'override' => false,
            'reasons' => $reasons,
        ];
    }

    private function normalizeTier(?string $tier): string
    {
        if ($tier === null) {
            return self::TIER_AUTO;
        }
        $tier = strtolower(trim($tier));
        if ($tier === '') {
            return self::TIER_AUTO;
        }
        if (! in_array($tier, self::VALID_TIERS, true)) {
            return self::TIER_AUTO;
        }
        return $tier;
    }

    /**
     * @param array<string, AggregationInterface> $aggregations
     * @return string[]
     */
    private function detectFeatures(array $aggregations, $index = null): array
    {
        $reasons = [];
        $engineCaps = $this->engineCapabilities($index);
        $this->walk($aggregations, $reasons, 0, $engineCaps);
        return array_values(array_unique($reasons));
    }

    /**
     * @param iterable<AggregationInterface> $aggregations
     * @param string[] $reasons
     * @param array<string, bool> $engineCaps
     */
    private function walk(iterable $aggregations, array &$reasons, int $depth, array $engineCaps): void
    {
        foreach ($aggregations as $agg) {
            if ($agg instanceof Formula) {
                $reasons[] = 'formula';
            }
            if ($agg instanceof Metric) {
                $kind = $agg->getKind();
                if ($kind === Metric::KIND_STATS && empty($engineCaps['metric:stats'])) {
                    $reasons[] = 'metric:stats';
                }
                if ($kind === Metric::KIND_CARDINALITY && empty($engineCaps['metric:cardinality'])) {
                    $reasons[] = 'metric:cardinality';
                }
            }
            if ($agg instanceof Terms) {
                if ($agg->withOthers()) {
                    $reasons[] = 'group:others';
                }
                if ($agg->withTotal()) {
                    $reasons[] = 'group:total';
                }
                if ($agg->getHaving() !== null) {
                    $reasons[] = 'group:having';
                }
                if ($depth > 0) {
                    $reasons[] = 'group:nested';
                }
                if (! $this->termsHasMetricChildren($agg) && empty($engineCaps['group:no_metrics'])) {
                    $reasons[] = 'group:no_metrics';
                }
                if ($agg->hasChildren()) {
                    $this->walk($agg->getChildren(), $reasons, $depth + 1, $engineCaps);
                }
            }
        }
    }

    private function termsHasMetricChildren(Terms $terms): bool
    {
        if (! $terms->hasChildren()) {
            return false;
        }
        foreach ($terms->getChildren() as $child) {
            if ($child instanceof Metric || $child instanceof Formula) {
                return true;
            }
        }
        return false;
    }

    private function engineLacksAggregationSupport($index): bool
    {
        return $index instanceof Search_MySql_Index;
    }

    /**
     * @return array<string, bool>
     */
    private function engineCapabilities($index): array
    {
        if ($index instanceof \Search\Manticore\Index) {
            return [
                'metric:stats' => false,
                'metric:cardinality' => false,
                'group:no_metrics' => false,
            ];
        }

        return [
            'metric:stats' => true,
            'metric:cardinality' => true,
            'group:no_metrics' => true,
        ];
    }
}
