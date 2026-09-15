<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Search\Query\Aggregation;

abstract class AbstractAggregation implements AggregationInterface
{
    protected string $name;
    protected ?string $field;
    protected ?string $label = null;

    public function __construct(string $name, ?string $field = null)
    {
        $this->name = $name;
        $this->field = $field;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getField(): ?string
    {
        return $this->field;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(?string $label): self
    {
        $this->label = $label;
        return $this;
    }

    abstract public function getKind(): string;

    abstract public function isBucket(): bool;
}
