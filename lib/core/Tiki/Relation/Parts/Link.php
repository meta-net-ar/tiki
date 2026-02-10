<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Relation\Parts;

use TikiLib;

abstract class Link implements \ArrayAccess
{
    public string $type;
    public string $itemId;

    public function __construct(string $type, string $itemId)
    {
        $this->type = $type;
        $this->itemId = $itemId;
    }

    public function offsetExists($offset): bool
    {
        return in_array($offset, ['type', 'itemId']);
    }

    public function offsetGet($offset): mixed
    {
        switch ($offset) {
            case 'type':
                return $this->type;
            case 'itemId':
                return $this->itemId;
            case 'title':
                return $this->getTitle();
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

    public function __toString()
    {
        return $this->type . ':' . $this->itemId;
    }

    public function getTitle($format = null)
    {
        $title = TikiLib::lib('object')->get_title($this->type, $this->itemId, $format);
        if (empty($title)) {
            $title = strval($this);
        }
        return $title;
    }
}
