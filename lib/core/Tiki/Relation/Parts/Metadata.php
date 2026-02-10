<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Relation\Parts;

use TikiLib;

class Metadata implements \ArrayAccess
{
    public int $itemId;

    public function __construct(int $itemId)
    {
        $this->itemId = $itemId;
    }

    public function offsetExists($offset): bool
    {
        return in_array($offset, ['itemId', 'trackerId']);
    }

    public function offsetGet($offset): mixed
    {
        switch ($offset) {
            case 'itemId':
                return $this->itemId;
            case 'trackerId':
                $item = TikiLib::lib('trk')->get_tracker_item($this->itemId);
                return $item['trackerId'] ?? null;
            default:
                return null;
        }
    }

    public function offsetSet($offset, $value): void
    {
    }

    public function offsetUnset($offset): void
    {
    }

    public function getItem()
    {
        return \Tracker_Item::fromId($this->itemId);
    }
}
