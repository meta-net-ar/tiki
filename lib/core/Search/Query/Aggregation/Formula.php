<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Search\Query\Aggregation;

/**
 * A *formula* metric: an arbitrary SQL expression authored by the report
 * writer. The expression has already been run through
 * {@see ExpressionSanitizer::sanitize()} by the wiki builder, so the
 * compiler can splice it into a generated SELECT without further escaping.
 *
 * Two flavours, distinguished by whether the expression contains an
 * aggregate function:
 *
 *  1. aggregate:
 *   SUM(amount * fx_rate)
 *   ROUND(AVG(price), 2)
 *   Emitted inside the per_key CTE alongside the regular metrics.
 *   It can reference any temp-table column declared by the schema
 *   inferrer (the sanitizer has extracted its identifiers for that
 *   purpose) but NOT other metric aliases.
 *
 *  2. post-aggregate
 *   revenue / NULLIF(orders, 0)
 *   cumulative_sales - target
 *   Emitted in the outer SELECT of the labels CTE, where it can
 *   reference any already-computed metric alias (including derived
 *   metrics) by name. It cannot reference raw temp-table columns
 *   because those are not in scope at that level.
 *
 * Formula metrics share the same name namespace as regular metrics, so
 * a report template renders them as ordinary metric columns. Their
 * presence in a report is one of the triggers that escalates the
 * {@see \Search\Query\Aggregation\Tier\TierSelector} to the temp-table
 * tier regardless of the rest of the aggregation tree.
 */
class Formula extends AbstractAggregation
{
    public const KIND = 'formula';

    private string $expression;
    private bool $isAggregate;

    /** @var string[] identifiers referenced by the expression (lowercased) */
    private array $identifiers;

    /**
     * @param string   $name         alias under which the metric is exposed
     * @param string   $expression   sanitized SQL fragment
     * @param bool     $isAggregate  true if the fragment contains an
     *                               aggregate function; routes the formula
     *                               to the per_key CTE vs the outer SELECT
     * @param string[] $identifiers  identifiers the expression refers to,
     *                               in their original case (used by the
     *                               schema inferrer to declare temp-table
     *                               columns whose source_field matches
     *                               the case-sensitive index field name);
     *                               de-duplicated case-insensitively,
     *                               first occurrence wins
     */
    public function __construct(
        string $name,
        string $expression,
        bool $isAggregate,
        array $identifiers = []
    ) {
        parent::__construct($name, null);
        $this->expression = $expression;
        $this->isAggregate = $isAggregate;
        $seen = [];
        $unique = [];
        foreach ($identifiers as $ident) {
            $low = strtolower($ident);
            if (isset($seen[$low])) {
                continue;
            }
            $seen[$low] = true;
            $unique[] = $ident;
        }
        $this->identifiers = $unique;
    }

    public function getKind(): string
    {
        return self::KIND;
    }

    /** Formula nodes never act as buckets. */
    public function isBucket(): bool
    {
        return false;
    }

    public function getExpression(): string
    {
        return $this->expression;
    }

    public function isAggregate(): bool
    {
        return $this->isAggregate;
    }

    /** @return string[] */
    public function getIdentifiers(): array
    {
        return $this->identifiers;
    }
}
