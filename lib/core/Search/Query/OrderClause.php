<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Search\Query;

class OrderClause
{
    private $parts;

    public function __construct(Order|null $order = null)
    {
        $this->parts = [];
        $this->add($order);
    }

    public function add(Order|null $order = null)
    {
        if ($order) {
            $this->parts[] = $order;
        }
    }

    public function any()
    {
        return count($this->parts) > 0;
    }

    public function getParts()
    {
        return $this->parts;
    }

    public function hasField(string $field)
    {
        foreach ($this->parts as $part) {
            if ($part->getField() === $field) {
                return true;
            }
        }
        return false;
    }
}
