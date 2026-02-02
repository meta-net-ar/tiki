<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tracker\Tabular\Source;

class ODBCSourceEntry implements SourceEntryInterface
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function render(\Tracker\Tabular\Schema\Column $column, $allow_multiple)
    {
        $values = [];
        $fields = $column->getRemoteFields();
        foreach ($fields as $field) {
            $values[] = $this->getFieldDataFromRemoteField($field);
        }
        return $column->render($allow_multiple ? $values : $values[0], ['allow_multiple' => $allow_multiple]);
    }

    public function raw(\Tracker\Tabular\Schema\Column $column)
    {
        $fields = $column->getRemoteFields();
        foreach ($fields as $field) {
            $value = $this->getFieldDataFromRemoteField($field);
            if ($value !== null) {
                return $value;
            }
        }
        return null;
    }

    public function parseInto(&$info, $column)
    {
        $remoteFields = $column->getRemoteFields();
        if (count($remoteFields) > 1) {
            $entry = [];
            foreach ($remoteFields as $remoteField) {
                $value = $this->getFieldDataFromRemoteField($remoteField);
                if ($value !== null) {
                    $entry[] = $value;
                }
            }
        } else {
            $entry = $this->getFieldDataFromRemoteField($column->getRemoteField());
        }
        $column->parseInto($info, $entry, $this->data);
    }

    protected function getFieldDataFromRemoteField($remoteField)
    {
        if (isset($this->data[$remoteField])) {
            $value = $this->data[$remoteField];
        } elseif (str_contains($remoteField, '.')) {
            $parts = explode('.', $remoteField);
            $value = $this->data[array_pop($parts)] ?? null;
        } else {
            $value = null;
        }
        return $value;
    }
}
