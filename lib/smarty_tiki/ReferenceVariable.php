<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki;

#[\AllowDynamicProperties]
class ReferenceVariable
{
    /**
     * template variable
     *
     * @var mixed
     */
    public $value = null;

    /**
     * Other r/w properties for foreach, for, while, etc.
     */
    public $step;
    public $total;
    public $first;
    public $last;
    public $key;
    public $show;
    public $iteration;
    public $index = null;

    /**
     * @param mixed|null $value
     */
    public function setValue(&$value): void
    {
        $this->value = $value;
    }

    /**
     * if true any output of this variable will be not cached
     *
     * @var boolean
     */
    private $nocache = false;

    /**
     * @param bool $nocache
     */
    public function setNocache(bool $nocache): void
    {
        $this->nocache = $nocache;
    }

    /**
     * create Smarty variable object
     *
     * @param mixed   $value   the value to assign
     * @param boolean $nocache if true any output of this variable will be not cached
     */
    public function __construct(&$value = null, $nocache = false)
    {
        $this->value = &$value;
        $this->nocache = $nocache;
    }

    public function getValue()
    {
        return $this->value;
    }

    /**
     * <<magic>> String conversion
     *
     * @return string
     */
    public function __toString()
    {
        return (string)$this->value;
    }

    /**
     * Handles ++$a and --$a in templates.
     *
     * @param $operator '++' or '--', defaults to '++'
     *
     * @return int|mixed
     * @throws Exception
     */
    public function preIncDec($operator = '++')
    {
        if ($operator == '--') {
            return --$this->value;
        } elseif ($operator == '++') {
            return ++$this->value;
        } else {
            throw new Exception("Invalid incdec operator. Use '--' or '++'.");
        }
        return $this->value;
    }

    /**
     * Handles $a++ and $a-- in templates.
     *
     * @param $operator '++' or '--', defaults to '++'
     *
     * @return int|mixed
     * @throws Exception
     */
    public function postIncDec($operator = '++')
    {
        if ($operator == '--') {
            return $this->value--;
        } elseif ($operator == '++') {
            return $this->value++;
        } else {
            throw new Exception("Invalid incdec operator. Use '--' or '++'.");
        }
    }

    /**
     * @return bool
     */
    public function isNocache(): bool
    {
        return $this->nocache;
    }
}
