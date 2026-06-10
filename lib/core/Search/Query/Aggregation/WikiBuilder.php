<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Search\Query\Aggregation;

use Feedback;
use Search\Query\Aggregation\Join\Join;
use Search_Query;
use WikiParser_PluginArgumentParser;
use WikiParser_PluginMatcher;

/**
 * Parses {group ...}, {metric ...}, {having ...} and {join ...} blocks
 * from a PluginList body into a Terms / Metric / Formula tree (attached
 * to the Search_Query) and a list of {@see Join} specs (resolved after the
 * search returns).
 */
class WikiBuilder
{
    /**
     * @var AggregationInterface[]
     */
    private array $aggregations = [];

    /**
     * @var Join[]
     */
    private array $joins = [];

    /**
     * @return AggregationInterface[]
     */
    public function getAggregations(): array
    {
        return $this->aggregations;
    }

    public function hasAggregations(): bool
    {
        return ! empty($this->aggregations);
    }

    /**
     * @return Join[]
     */
    public function getJoins(): array
    {
        return $this->joins;
    }

    public function hasJoins(): bool
    {
        return ! empty($this->joins);
    }

    public function apply(WikiParser_PluginMatcher $matches): void
    {
        $argParser = new WikiParser_PluginArgumentParser();

        /** @var Terms[] $stack */
        $stack = [];
        /** @var Terms[] $rootGroups */
        $rootGroups = [];
        /** @var Metric[] $rootMetrics */
        $rootMetrics = [];

        foreach ($matches as $match) {
            $name = $match->getName();
            if (! in_array($name, ['group', 'metric', 'having', 'join'], true)) {
                continue;
            }
            $args = $argParser->parse($match->getArguments());

            if ($name === 'group') {
                $group = $this->buildGroup($args);
                if ($group === null) {
                    continue;
                }
                if (empty($stack)) {
                    $rootGroups[] = $group;
                } else {
                    end($stack)->addChild($group);
                }
                $stack[] = $group;
                continue;
            }

            if ($name === 'metric') {
                $metric = $this->buildMetric($args);
                if ($metric === null) {
                    continue;
                }
                if (empty($stack)) {
                    $rootMetrics[] = $metric;
                } else {
                    end($stack)->addChild($metric);
                }
                continue;
            }

            if ($name === 'having') {
                if (empty($stack)) {
                    Feedback::error(tr('{having} block must appear inside a {group} block.'));
                    continue;
                }
                $this->applyHaving(end($stack), $args);
                continue;
            }

            if ($name === 'join') {
                $join = $this->buildJoin($args);
                if ($join !== null) {
                    $this->joins[] = $join;
                }
            }
        }

        foreach ($rootGroups as $g) {
            $this->aggregations[$g->getName()] = $g;
        }
        foreach ($rootMetrics as $m) {
            $this->aggregations[$m->getName()] = $m;
        }
    }

    public function build(Search_Query $query): void
    {
        foreach ($this->aggregations as $agg) {
            $query->addAggregation($agg);
        }
    }

    private function buildGroup(array $args): ?Terms
    {
        if (empty($args['field'])) {
            Feedback::error(tr('Missing field= for {group} block in PluginList aggregation.'));
            return null;
        }
        $field = (string) $args['field'];
        $name = $args['name'] ?? $field;
        $group = new Terms((string) $name, $field);
        if (isset($args['size'])) {
            $group->setSize((int) $args['size']);
        }
        if (isset($args['min']) && $args['min'] !== '') {
            $group->setMinDocCount((int) $args['min']);
        }
        if (isset($args['missing']) && $args['missing'] !== '') {
            $group->setMissing($args['missing']);
        }
        if (! empty($args['order'])) {
            $group->setOrderBy($args['order']);
        }
        if (! empty($args['label'])) {
            $group->setLabel((string) $args['label']);
        }
        if ($this->isYes($args['others'] ?? null)) {
            $group->setWithOthers(true, isset($args['others_label']) ? (string) $args['others_label'] : null);
        }
        if ($this->isYes($args['total'] ?? null)) {
            $group->setWithTotal(true, isset($args['total_label']) ? (string) $args['total_label'] : null);
        }
        if (! empty($args['palette'])) {
            $group->setPalette((string) $args['palette']);
        }
        if (isset($args['others_color']) && $args['others_color'] !== '') {
            $group->setOthersColor((string) $args['others_color']);
        }
        if (isset($args['total_color']) && $args['total_color'] !== '') {
            $group->setTotalColor((string) $args['total_color']);
        }
        return $group;
    }

    private function isYes($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        $v = strtolower(trim((string) $value));
        return $v === 'y' || $v === 'yes' || $v === '1' || $v === 'true' || $v === 'on';
    }

    private function buildJoin(array $args): ?Join
    {
        if (empty($args['name'])) {
            Feedback::error(tr('Missing name= for {join} block in PluginList aggregation.'));
            return null;
        }
        if (empty($args['on'])) {
            Feedback::error(tr('Missing on= for {join} block in PluginList aggregation.'));
            return null;
        }
        $select = [];
        if (! empty($args['select'])) {
            $select = array_filter(array_map('trim', explode(',', (string) $args['select'])));
        }
        $objectType = isset($args['type']) ? (string) $args['type'] : 'trackeritem';
        $trackerId = null;
        if (isset($args['tracker']) && $args['tracker'] !== '') {
            $trackerId = (int) $args['tracker'];
            if ($trackerId <= 0) {
                $trackerId = null;
            } else {
                $objectType = 'trackeritem';
            }
        }

        $field = ! empty($args['field']) ? (string) $args['field'] : null;
        $multivalue = isset($args['multivalue']) && $this->isYes($args['multivalue']);

        return new Join(
            (string) $args['name'],
            (string) $args['on'],
            $select,
            $objectType,
            $trackerId,
            $field,
            $multivalue
        );
    }

    private function applyHaving(Terms $group, array $args): void
    {
        if (empty($args['expr'])) {
            Feedback::error(tr('Missing expr= for {having} block.'));
            return;
        }
        $sanitizer = new ExpressionSanitizer();
        try {
            $expr = $sanitizer->sanitize((string) $args['expr']);
        } catch (\InvalidArgumentException $e) {
            Feedback::error(tr('Invalid {having} expression: %0', $e->getMessage()));
            return;
        }
        $group->setHaving($expr, $sanitizer->extractIdentifiers($expr));
    }

    private function buildMetric(array $args): ?AggregationInterface
    {
        if (empty($args['name'])) {
            Feedback::error(tr('Missing name= for {metric} block in PluginList aggregation.'));
            return null;
        }
        $op = strtolower((string) ($args['op'] ?? 'count'));

        if ($op === Formula::KIND) {
            return $this->buildFormula($args);
        }

        if (! Metric::isValidOp($op)) {
            Feedback::error(tr(
                'Unknown {metric} op="%0". Valid: %1',
                $op,
                implode(', ', array_merge(
                    Metric::acceptedOps(),
                    [Formula::KIND]
                ))
            ));
            return null;
        }
        $field = isset($args['field']) ? (string) $args['field'] : null;
        try {
            $metric = new Metric((string) $args['name'], $op, $field);
        } catch (\InvalidArgumentException $e) {
            Feedback::error($e->getMessage());
            return null;
        }
        if (isset($args['missing']) && $args['missing'] !== '') {
            $metric->setMissing($args['missing']);
        }
        if (! empty($args['label'])) {
            $metric->setLabel((string) $args['label']);
        }
        return $metric;
    }

    private function buildFormula(array $args): ?Formula
    {
        if (empty($args['expr'])) {
            Feedback::error(tr(
                'Missing expr= for {metric op="formula"} "%0".',
                (string) $args['name']
            ));
            return null;
        }
        $sanitizer = new ExpressionSanitizer();
        try {
            $expr = $sanitizer->sanitize((string) $args['expr']);
        } catch (\InvalidArgumentException $e) {
            Feedback::error(tr(
                'Invalid {metric op="formula"} "%0": %1',
                (string) $args['name'],
                $e->getMessage()
            ));
            return null;
        }
        $formula = new Formula(
            (string) $args['name'],
            $expr,
            $sanitizer->isAggregate($expr),
            $sanitizer->extractIdentifiers($expr)
        );
        if (! empty($args['label'])) {
            $formula->setLabel((string) $args['label']);
        }
        return $formula;
    }
}
