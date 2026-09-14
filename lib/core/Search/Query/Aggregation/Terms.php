<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Search\Query\Aggregation;

/**
 * Bucket aggregation that groups documents by the distinct values of a field
 * and returns the top N buckets, optionally ordered by a sub-metric.
 *
 * Sub-aggregations (further metrics or other buckets) can be attached and will
 * be evaluated within each bucket's scope, enabling reports such as
 * "top 10 vendors by total amount, with average deal size and count".
 */
class Terms extends AbstractAggregation
{
    public const ORDER_KEY = '_key';
    public const ORDER_COUNT = '_count';

    private int $size = 10;
    private array $orderBy = [];
    private ?int $minDocCount = null;
    private $missing = null;

    private bool $withOthers = false;
    private ?string $othersLabel = null;
    private bool $withTotal = false;
    private ?string $totalLabel = null;
    /** @var string[] */
    private array $palette = [];
    private ?string $othersColor = null;
    private ?string $totalColor = null;

    private ?string $having = null;
    private array $havingIdentifiers = [];

    /**
     * @var AggregationInterface[]
     */
    private array $children = [];

    public function __construct(string $name, string $field)
    {
        parent::__construct($name, $field);
    }

    public function getKind(): string
    {
        return 'terms';
    }

    public function isBucket(): bool
    {
        return true;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function setSize(int $size): self
    {
        $this->size = max(0, $size);
        return $this;
    }

    /**
     * Order buckets by a sub-metric name, by `_key` or `_count`.
     *
     * @param string|array $orderBy 'metric_name desc' / '_count asc' / ['metric_name' => 'desc']
     */
    public function setOrderBy($orderBy): self
    {
        if (is_array($orderBy)) {
            $this->orderBy = $orderBy;
            return $this;
        }
        $orderBy = trim((string) $orderBy);
        if ($orderBy === '') {
            $this->orderBy = [];
            return $this;
        }
        $parts = preg_split('/\s+/', $orderBy);
        $field = $parts[0];
        $dir = strtolower($parts[1] ?? 'desc');
        $dir = ($dir === 'asc') ? 'asc' : 'desc';
        $field = self::normalizeOrderField($field);
        $this->orderBy = [$field => $dir];
        return $this;
    }

    /**
     * @return array<string,string> empty array means "use backend default"
     */
    public function getOrderBy(): array
    {
        return $this->orderBy;
    }

    /**
     * Map common user-facing order aliases to the canonical _count / _key
     * tokens that all backends (Elastic, Manticore, SQL compiler) recognise.
     */
    private static function normalizeOrderField(string $field): string
    {
        static $countAliases = ['doc_count', 'count', '_doc_count'];
        static $keyAliases   = ['key', '_term'];

        $lower = strtolower($field);
        if (in_array($lower, $countAliases, true)) {
            return self::ORDER_COUNT;
        }
        if (in_array($lower, $keyAliases, true)) {
            return self::ORDER_KEY;
        }
        return $field;
    }

    public function setMinDocCount(?int $min): self
    {
        $this->minDocCount = $min;
        return $this;
    }

    public function getMinDocCount(): ?int
    {
        return $this->minDocCount;
    }

    /**
     * Default value when the underlying field is null/missing.
     * Implemented backend-side as appropriate (ES `missing`, SQL COALESCE).
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

    /**
     * Append a synthetic "Others" bucket aggregating the tail beyond
     * `size`. Routes the report to the temp-table tier, where the SQL
     * compiler emits a UNION ALL branch summing the per-key rows whose
     * row number is past the Top-N cut-off.
     */
    public function setWithOthers(bool $with, ?string $label = null): self
    {
        $this->withOthers = $with;
        if ($label !== null) {
            $this->othersLabel = $label;
        }
        return $this;
    }

    public function withOthers(): bool
    {
        return $this->withOthers;
    }

    public function getOthersLabel(): string
    {
        return $this->othersLabel ?? 'Others';
    }

    /**
     * Append a synthetic grand-total bucket that holds the report-wide
     * value for each metric. Routes the report to the temp-table tier,
     * where the SQL compiler emits a UNION ALL branch aggregating the
     * per_key CTE.
     */
    public function setWithTotal(bool $with, ?string $label = null): self
    {
        $this->withTotal = $with;
        if ($label !== null) {
            $this->totalLabel = $label;
        }
        return $this;
    }

    public function withTotal(): bool
    {
        return $this->withTotal;
    }

    public function getTotalLabel(): string
    {
        return $this->totalLabel ?? 'Total';
    }

    /**
     * Color palette assigned to buckets via the virtual __bucket_color__
     * column. Accepts either a known palette name ("Tableau10", "Set3",
     * "Pastel1") or an explicit list of colors.
     *
     * @param string|string[] $palette
     */
    public function setPalette($palette): self
    {
        if (is_string($palette)) {
            if (str_contains($palette, ',')) {
                $palette = array_map('trim', explode(',', $palette));
            } elseif (str_contains($palette, ':')) {
                $palette = array_map('trim', explode(':', $palette));
            } else {
                $palette = self::resolveNamedPalette($palette);
            }
        }
        $this->palette = array_values(array_filter((array) $palette, fn ($c) => $c !== ''));
        return $this;
    }

    /**
     * @return string[]
     */
    public function getPalette(): array
    {
        return $this->palette;
    }

    public function setOthersColor(?string $color): self
    {
        $this->othersColor = $color;
        return $this;
    }

    public function getOthersColor(): ?string
    {
        return $this->othersColor;
    }

    public function setTotalColor(?string $color): self
    {
        $this->totalColor = $color;
        return $this;
    }

    public function getTotalColor(): ?string
    {
        return $this->totalColor;
    }

    /**
     * Resolve a well-known palette name to a list of CSS color strings.
     * Falls back to an empty array (no coloring) when unknown so reports
     * don't break on a typo.
     *
     * @return string[]
     */
    public static function resolveNamedPalette(string $name): array
    {
        $palettes = [
            'tableau10' => ['#4E79A7','#F28E2B','#E15759','#76B7B2','#59A14F','#EDC948','#B07AA1','#FF9DA7','#9C755F','#BAB0AC'],
            'set3'      => ['#8DD3C7','#FFFFB3','#BEBADA','#FB8072','#80B1D3','#FDB462','#B3DE69','#FCCDE5','#D9D9D9','#BC80BD','#CCEBC5','#FFED6F'],
            'pastel1'   => ['#FBB4AE','#B3CDE3','#CCEBC5','#DECBE4','#FED9A6','#FFFFCC','#E5D8BD','#FDDAEC','#F2F2F2'],
            'category10' => ['#1F77B4','#FF7F0E','#2CA02C','#D62728','#9467BD','#8C564B','#E377C2','#7F7F7F','#BCBD22','#17BECF'],
        ];
        return $palettes[strtolower($name)] ?? [];
    }

    public function setHaving(?string $expression, array $identifiers = []): self
    {
        $this->having = $expression;
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
        $this->havingIdentifiers = $unique;
        return $this;
    }

    public function getHaving(): ?string
    {
        return $this->having;
    }

    public function getHavingIdentifiers(): array
    {
        return $this->havingIdentifiers;
    }

    public function addChild(AggregationInterface $child): self
    {
        $this->children[$child->getName()] = $child;
        return $this;
    }

    /**
     * @return AggregationInterface[]
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    public function hasChildren(): bool
    {
        return ! empty($this->children);
    }
}
