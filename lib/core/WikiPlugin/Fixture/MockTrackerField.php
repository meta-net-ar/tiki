<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\WikiPlugin\Fixture;

use Tiki_Formula_Function_TrackerField;

class MockTrackerField extends Tiki_Formula_Function_TrackerField
{
    private $data = [];

    public function fetchValue($object, $field, $default)
    {
        if (isset($this->data[$object][$field])) {
            return $this->data[$object][$field];
        } else {
            return $default;
        }
    }

    public function addValues($id, array $data)
    {
        if (! isset($this->data[$id])) {
            $this->data[$id] = $data;
        } else {
            $this->data[$id] = array_merge($this->data[$id], $data);
        }
    }
}
