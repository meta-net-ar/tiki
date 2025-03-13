<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

class Search_Action_TrackerItemInsert implements Search_Action_Action
{
    private $insert_object_id = null;

    public function getValues()
    {
        return [
            'to_tracker' => true,
            'object_type' => true,
            'object_id' => true,
            'field' => false,
            'value' => false,
            'ignore_errors' => false,   // ignore replaceItem errors such as isMandatory and validation
        ];
    }

    public function validate(JitFilter $data)
    {
        $object_type = $data->object_type->text();
        $object_id = $data->object_id->int();
        $to_tracker = $data->to_tracker->int();

        $newItem = Tracker_Item::newItem($to_tracker);
        if (! $newItem->canModify()) {
            throw new Search_Action_Exception(tr("You don't have permission to create new items"));
        }

        return true;
    }

    public function execute(JitFilter $data)
    {
        $object_id = $data->object_id->int();
        $to_tracker = $data->to_tracker->int();
        $field = $data->field->word();
        $value = $data->value->text();
        $ignore_errors = $data->ignore_errors->text() === 'y';  // y/n

        $definition = Tracker_Definition::get($to_tracker);
        $utilities = new Services_Tracker_Utilities();

        $value = $this->stripNp($value);
        $inserted = [];
        if (! empty($field) and ! empty($value)) {
            $inserted[$field] = $value;
        }

        $itemId = $utilities->insertItem(
            $definition,
            [
                'status' => 'o',
                'fields' => $inserted,
                'validate' => ! $ignore_errors,
            ]
        );

        if (! empty($itemId)) {
            $this->insert_object_id = $itemId;
            return true;
        } else {
            return false;
        }
    }

    public function inputType(): string
    {
        return "text";
    }

    public function requiresInput(JitFilter $data)
    {
        return false;
    }

    public function changeObject($data)
    {
        if (empty($this->insert_object_id)) {
            return $data;
        }
        if ($data instanceof JitFilter) {
            $dataArr = $data->asArray();
            $dataArr['object_id'] = $this->insert_object_id;
            $data = new JitFilter($dataArr);
        } else {
            $data['object_id'] = $this->insert_object_id;
        }
        return $data;
    }

    private function stripNp($value)
    {
        return str_replace(['~np~', '~/np~'], '', $value);
    }
}
