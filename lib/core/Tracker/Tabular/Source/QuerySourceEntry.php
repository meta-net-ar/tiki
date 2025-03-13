<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tracker\Tabular\Source;

class QuerySourceEntry implements SourceEntryInterface
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function render(\Tracker\Tabular\Schema\Column $column, $allow_multiple)
    {
        $field = $column->getField();
        $value = $this->raw($column);

        $extra = [];
        foreach ($column->getQuerySources() as $target => $field) {
            if (isset($this->data[$field])) {
                $extra[$target] = $this->data[$field];
            }
        }

        return $column->render($value, array_merge($extra, ['allow_multiple' => $allow_multiple]));
    }

    public function raw(\Tracker\Tabular\Schema\Column $column)
    {
        $key = 'tracker_field_' . $column->getField();

        $value = $this->data[$key];

        return $value;
    }

    public function backfillPK($pk, $value)
    {
        if ($this->data['object_type'] == 'trackeritem' && ! empty($this->data['object_id'])) {
            \TikiLib::lib('trk')->modify_field($this->data['object_id'], $pk, $value);
        }
    }
}
