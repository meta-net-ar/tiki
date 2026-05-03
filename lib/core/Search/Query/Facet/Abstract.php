<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
abstract class Search_Query_Facet_Abstract implements Search_Query_Facet_Interface
{
    protected $name;
    protected $field;
    protected $renderCallback;
    protected $label;
    protected $dependencies = [];
    protected $dependencisNames = [];

    public function __construct($field)
    {
        $this->field = $field;
        $this->name = $field;
        $this->label = ucfirst($field);
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    public function getField()
    {
        return $this->field;
    }

    public function getLabel()
    {
        return $this->label;
    }

    public function setLabel($label)
    {
        $this->label = $label;
        return $this;
    }

    public function setRenderCallback($callback)
    {
        $this->renderCallback = $callback;
        return $this;
    }

    public function render($value)
    {
        if ($cb = $this->renderCallback) {
            return call_user_func($cb, $value);
        }

        return $value;
    }

    public function getData()
    {
        return null;
    }

    public function isBuiltFromIndex(): bool
    {
        return true;
    }

    public function getOperator()
    {
        return null;
    }

    /**
     * @return Search_Query_Facet_Interface[]
     */
    public function getDependencies(): array
    {
        return $this->dependencies;
    }

    public function addDependencyName(string $facetName)
    {
        $this->dependencisNames[] = $facetName;
        return $this;
    }

    public function addDependency(Search_Query_Facet_Interface $facet)
    {
        $this->dependencies[] = $facet;
        return $this;
    }


    public function getDependencyNames(): array
    {
        return $this->dependencisNames;
    }

    abstract public function getType();
}
