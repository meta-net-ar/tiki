<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tracker\Filter;

class Filter implements \JsonSerializable
{
    private $permName;
    private $mode;
    private $position = 'default';
    private $label;
    private $help;
    private $type;
    private $control;
    private $applyCondition;

    public function __construct($permName, $mode)
    {
        $this->permName = $permName;
        $this->mode = $mode;
    }

    public function getField()
    {
        return $this->permName;
    }

    public function getMode()
    {
        return $this->mode;
    }

    public function setLabel($label)
    {
        $this->label = $label;
        return $this;
    }

    public function getLabel()
    {
        return $this->label;
    }

    public function getPosition()
    {
        return $this->position;
    }

    public function setPosition($position)
    {
        $this->position = $position;
        return $this;
    }

    public function setHelp($help)
    {
        $this->help = $help;
        return $this;
    }

    public function getHelp()
    {
        return $this->help;
    }

    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setControl(Control\Control $control)
    {
        $this->control = $control;
        return $this;
    }

    public function getControl()
    {
        return $this->control;
    }

    public function setApplyCondition(callable $apply)
    {
        $this->applyCondition = $apply;
        return $this;
    }

    public function applyCondition(\Search_Query $query)
    {
        $cb = $this->applyCondition;
        $cb($this->control, $query);
    }

    public function applyInput(\JitFilter $input)
    {
        $this->control->applyInput($input);
    }

    public function copyProperties(self $other)
    {
        $this->help = $other->help;
        $this->label = $other->label;
        $this->position = $other->position;
        $this->control = clone $other->control;
        $this->applyCondition = $other->applyCondition;
    }

    public function jsonSerialize(): mixed
    {
        return [
            'label' => $this->label,
            'field' => $this->permName,
            'mode' => $this->mode,
            'position' => $this->position,
            'applied_value' => $this->control->getQueryArguments(),
        ];
    }
}
