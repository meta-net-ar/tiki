<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Search\Query\Aggregation\Join;

/**
 * Specification of a {join} block in PluginList aggregation reports.
 *
 * After the main aggregation query returns its bucket tree, the resolver
 * uses the spec to issue ONE batched lookup per join (filtered by the small
 * set of bucket keys plus the optional tracker / object_type constraint)
 * and attaches the projected fields back to each bucket as namespaced
 * metrics ("<join name>.<field>").
 */
class Join
{
    private string $name;
    private string $on;
    /** @var string[] */
    private array $select;
    private ?string $objectType;
    private ?int $trackerId;
    private ?string $field;
    private bool $multivalue;

    /**
     * @param string $name      lookup namespace, e.g. "vendor"
     * @param string $on        name of the bucket aggregation to attach to
     * @param string[] $select  list of fields to project from joined documents
     * @param string|null $objectType  e.g. 'trackeritem' (default), or null
     * @param int|null $trackerId      restrict to a single tracker
     * @param string|null $field       lookup by this indexed field instead of object_id
     * @param bool $multivalue         if true, use multivalue matching for field lookup
     */
    public function __construct(
        string $name,
        string $on,
        array $select,
        ?string $objectType = 'trackeritem',
        ?int $trackerId = null,
        ?string $field = null,
        bool $multivalue = false
    ) {
        $this->name = $name;
        $this->on = $on;
        $this->select = $select;
        $this->objectType = $objectType;
        $this->trackerId = $trackerId;
        $this->field = $field;
        $this->multivalue = $multivalue;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getOn(): string
    {
        return $this->on;
    }

    /**
     * @return string[]
     */
    public function getSelect(): array
    {
        return $this->select;
    }

    public function getObjectType(): ?string
    {
        return $this->objectType;
    }

    public function getTrackerId(): ?int
    {
        return $this->trackerId;
    }

    public function getField(): ?string
    {
        return $this->field;
    }

    public function isMultivalue(): bool
    {
        return $this->multivalue;
    }
}
