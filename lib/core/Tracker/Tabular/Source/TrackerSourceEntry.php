<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tracker\Tabular\Source;

class TrackerSourceEntry implements SourceEntryInterface
{
    private $item;
    private $data;
    private $extra;

    public function __construct($itemIdOrItem)
    {
        if (is_numeric($itemIdOrItem)) {
            $this->item = \Tracker_Item::fromId($itemIdOrItem);
        } else {
            $this->item = $itemIdOrItem;
        }
        $this->data = $this->item->getData();
        $this->extra = [
            'itemId' => $this->data['itemId'],
            'status' => $this->data['status'],
        ];
    }

    public function render(\Tracker\Tabular\Schema\Column $column, $allow_multiple)
    {
        $value = $this->raw($column);
        return $column->render($value, array_merge($this->extra, ['allow_multiple' => $allow_multiple]));
    }

    public function raw(\Tracker\Tabular\Schema\Column $column)
    {
        $field = $column->getField();
        if (isset($this->data['fields'][$field])) {
            $value = $this->data['fields'][$field];
        } else {
            $value = null;
        }
        return $value;
    }

    public function backfillPK($pk, $value)
    {
        \TikiLib::lib('trk')->modify_field($this->data['itemId'], $pk, $value);
    }
}
