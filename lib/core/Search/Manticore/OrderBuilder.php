<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Search\Manticore;

use Search\Query\Order;
use Search\Query\OrderClause;

class OrderBuilder
{
    private $index;

    public function __construct(Index|null $index = null)
    {
        $this->index = $index;
    }

    public function build(OrderClause $clause)
    {
        $parts = [];
        foreach ($clause->getParts() as $order) {
            $parts[] = $this->buildOne($order);
        }
        return implode(', ', $parts);
    }

    protected function buildOne(Order $order)
    {
        $field = $order->getField();
        $isJsonField = ($this->index && $this->index->isFieldInJson($field));
        if (! $isJsonField) {
            $field = strtolower($field);
        }
        if ($order->getMode() == Order::MODE_SCRIPT) {
            $arguments = $order->getArguments();
            return $arguments['source'] . ' ' . $order->getOrder();
        } elseif ($field !== Order::FIELD_SCORE) {
            if ($isJsonField) {
                // TODO: distance?
                $jsonPath = $this->index->getJsonPathForField($field);
                if ($order->getMode() == Order::MODE_NUMERIC) {
                    $jsonPath = "INTEGER($jsonPath)";
                }
                return $jsonPath . ' ' . $order->getOrder();
            }
            $mapping = $this->index ? $this->index->getFieldMapping($field) : [];
            if ($order->getMode() == Order::MODE_NUMERIC && $mapping && ! in_array('float', $mapping['types']) && substr($field, -6) != '_nsort') {
                $this->index->ensureHasField($field . '_nsort');
                return $field . '_nsort' . ' ' . $order->getOrder();
            } elseif ($order->getMode() == Order::MODE_DISTANCE) {
                $this->index->ensureHasField($field);
                $arguments = $order->getArguments();
                $fields = preg_split('/\s*,\s*/', $field);
                return "GEODIST(" . $arguments['lat'] . ", " . $arguments['lon'] . ", " . $fields[0] . ", " . $fields[1] . ")" . ' ' . $order->getOrder();
            } else {
                $this->index->ensureHasField($field);
                return $field . ' ' . $order->getOrder();
            }
        }
    }
}
