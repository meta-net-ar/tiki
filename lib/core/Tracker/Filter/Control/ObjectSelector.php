<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tracker\Filter\Control;

class ObjectSelector implements Control
{
    private $fieldName;
    private $filters;
    private $value = '';
    private $multi = false;

    public function __construct($name, array $filters, $multi = false)
    {
        $this->fieldName = $name;
        $this->filters = $filters;
        $this->multi = $multi;
    }

    public function applyInput(\JitFilter $input)
    {
        if ($this->multi) {
            $value = $input->{$this->fieldName}->text();
            if (is_null($value)) {
                $value = [];
            } elseif (! is_array($value)) {
                $value = preg_split("/\r\n|\r|\n|,/", $value);    // any line ends or comma
            }
            $this->value = $value;
        } else {
            $this->value = $input->{$this->fieldName}->text();
        }
    }

    public function getQueryArguments()
    {
        if ($this->value) {
            return [$this->fieldName => $this->value];
        } else {
            return [];
        }
    }

    public function getDescription()
    {
        if ($this->value) {
            if ($this->multi && is_array($this->value)) {
                $desc = '';
                foreach ($this->value as $value) {
                    list ($type, $value) = explode(':', $value);
                    $desc .= ' ' . \TikiLib::lib('object')->get_title($type, $value);
                }
                return $desc;
            } else {
                list ($type, $value) = explode(':', $this->value);
                return \TikiLib::lib('object')->get_title($type, $value);
            }
        }
    }

    public function getId()
    {
        return $this->fieldName;
    }

    public function isUsable()
    {
        return true;
    }

    public function hasValue()
    {
        return ! empty($this->value);
    }

    public function getValue()
    {
        return $this->value;
    }

    public function __toString()
    {
        $params = $this->filters;
        $params['_id'] = $this->fieldName;
        $params['_name'] = $this->fieldName;

        $smarty = \TikiLib::lib('smarty');

        if ($this->multi) {
            if (is_array($this->value)) {
                $params['_value'] = implode("\n", $this->value);
            } else {
                $params['_value'] = $this->value;
            }

            $result = smarty_function_object_selector_multi($params, $smarty->getEmptyInternalTemplate());
        } else {
            $params['_value'] = $this->value;

            $result = smarty_function_object_selector($params, $smarty->getEmptyInternalTemplate());
        }

        return $result;
    }
}
