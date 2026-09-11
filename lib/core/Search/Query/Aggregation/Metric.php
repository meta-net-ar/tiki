<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Search\Query\Aggregation;

use InvalidArgumentException;

/**
 * A metric aggregation: produces a single scalar (or a small fixed map for stats)
 * either over the whole result set or, when nested inside a bucket aggregation,
 * over each bucket.
 *
 * Supported kinds: sum, avg, min, max, value_count, cardinality, stats.
 *  - sum / avg / min / max: numeric reduction on $field
 *  - value_count: COUNT($field) (NULL skipped); when $field is null, equivalent to doc_count
 *  - cardinality: approximate distinct count on $field
 *  - stats: returns a map [count, min, max, avg, sum]
 */
class Metric extends AbstractAggregation
{
    public const KIND_SUM = 'sum';
    public const KIND_AVG = 'avg';
    public const KIND_MIN = 'min';
    public const KIND_MAX = 'max';
    public const KIND_COUNT = 'value_count';
    public const KIND_CARDINALITY = 'cardinality';
    public const KIND_STATS = 'stats';

    public const VALID_KINDS = [
        self::KIND_SUM,
        self::KIND_AVG,
        self::KIND_MIN,
        self::KIND_MAX,
        self::KIND_COUNT,
        self::KIND_CARDINALITY,
        self::KIND_STATS,
    ];

    /**
     * User-facing aliases mapped to their canonical KIND_*. Exposed so that
     * wiki-syntax parsers and other callers don't have to maintain their own
     * accept-list - they can reuse {@see acceptedOps()} and {@see normalizeKind()}.
     */
    public const KIND_ALIASES = [
        'count' => self::KIND_COUNT,
        'doc_count' => self::KIND_COUNT,
        'distinct' => self::KIND_CARDINALITY,
        'unique' => self::KIND_CARDINALITY,
    ];

    private string $kind;
    private $missing = null;

    /**
     * @param string $name unique name within the parent
     * @param string $kind one of the KIND_* constants (or short alias 'count' for value_count)
     * @param string|null $field source field (null only valid for kind=value_count -> doc count)
     */
    public function __construct(string $name, string $kind, ?string $field = null)
    {
        $kind = self::normalizeKind($kind);
        if (! in_array($kind, self::VALID_KINDS, true)) {
            throw new InvalidArgumentException(sprintf(
                'Unknown aggregation metric kind "%s". Valid: %s',
                $kind,
                implode(', ', self::VALID_KINDS)
            ));
        }
        if ($field === null && $kind !== self::KIND_COUNT) {
            throw new InvalidArgumentException(sprintf(
                'Aggregation metric "%s" of kind "%s" requires a field',
                $name,
                $kind
            ));
        }

        parent::__construct($name, $field);
        $this->kind = $kind;
    }

    public static function normalizeKind(string $kind): string
    {
        $kind = strtolower(trim($kind));
        return self::KIND_ALIASES[$kind] ?? $kind;
    }

    /**
     * Whether the given user-facing op string is recognised, either as a
     * canonical KIND_* value or as one of the {@see KIND_ALIASES}.
     */
    public static function isValidOp(string $op): bool
    {
        return in_array(self::normalizeKind($op), self::VALID_KINDS, true);
    }

    /**
     * Full list of accepted op strings (canonical kinds + aliases) for use
     * in wiki-syntax parsers and error messages.
     *
     * @return string[]
     */
    public static function acceptedOps(): array
    {
        return array_values(array_unique(array_merge(
            self::VALID_KINDS,
            array_keys(self::KIND_ALIASES)
        )));
    }

    public function getKind(): string
    {
        return $this->kind;
    }

    public function isBucket(): bool
    {
        return false;
    }

    /**
     * Default value when the underlying field is null/missing in a document.
     * Backend-dependent; passed through to ES "missing" parameter when supported.
     */
    public function setMissing($missing): self
    {
        $this->missing = $missing;
        return $this;
    }

    public function getMissing()
    {
        return $this->missing;
    }
}
