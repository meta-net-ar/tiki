<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Relation\Parts;

class Source extends Link
{
    public ?int $fieldId;

    public function __construct(string $type, string $itemId, ?int $fieldId = null)
    {
        parent::__construct($type, $itemId);
        $this->fieldId = $fieldId;
    }

    public function offsetExists($offset): bool
    {
        return $offset == 'fieldId' || parent::offsetExists($offset);
    }

    public function offsetGet($offset): mixed
    {
        if ($offset == 'fieldId') {
            return $this->fieldId;
        }
        return parent::offsetGet($offset);
    }
}
