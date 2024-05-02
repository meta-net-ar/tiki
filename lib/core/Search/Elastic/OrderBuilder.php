<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

use Search\Query\Order;
use Search\Query\OrderClause;

class Search_Elastic_OrderBuilder
{
    private $index;

    public function __construct(Search_Elastic_Index|null $index = null)
    {
        $this->index = $index;
    }

    public function build(OrderClause $order)
    {
        $components = [];
        foreach ($order->getParts() as $part) {
            $components[] = $this->buildOne($part);
        }
        return [
            "sort" => $components,
        ];
    }

    protected function buildOne(Order $order)
    {
        $component = '_score';
        $field = $order->getField();

        if ($order->getMode() == Order::MODE_SCRIPT) {
            $arguments = $order->getArguments();

            $component = [
                "_script" => [
                    'type'   => $arguments['type'],
                    'script' => [
                        'lang'   => $arguments['lang'],
                        'source' => $arguments['source'],
                    ],
                    'order'  => $arguments['order'],
                ],
            ];
        } elseif ($field !== Order::FIELD_SCORE) {
            $this->ensureHasField($field);
            if ($order->getMode() == Order::MODE_NUMERIC) {
                $component = [
                    "$field.nsort" => $order->getOrder(),
                ];
            } elseif ($order->getMode() == Order::MODE_DISTANCE) {
                $arguments = $order->getArguments();

                $component = [
                    "_geo_distance" => [
                        'geo_point' => [
                            'lat' => $arguments['lat'],
                            'lon' => $arguments['lon'],
                        ],
                        'order' => $order->getOrder(),
                        'unit' => $arguments['unit'],
                        'distance_type' => $arguments['distance_type'],
                    ],
                ];
            } else {
                $component = [
                    "$field.sort" => $order->getOrder(),
                ];
            }
        }
        return $component;
    }

    public function ensureHasField($field)
    {
        global $prefs;

        $mapping = $this->index ? $this->index->getFieldMapping($field) : new stdClass();
        if ((empty($mapping) || empty((array)$mapping)) && $prefs['search_error_missing_field'] === 'y') {
            if (preg_match('/^tracker_field_/', $field)) {
                $msg = tr('Field %0 does not exist in the current index. Please check field permanent name and if you have any items in that tracker.', TikiFilter::get('xss')->filter($field));
                if ($prefs['unified_exclude_nonsearchable_fields'] === 'y') {
                    $msg .= ' ' . tr('You have disabled indexing non-searchable tracker fields. Check if this field is marked as searchable.');
                }
            } else {
                $msg = tr('Field %0 does not exist in the current index. If this is a tracker field, the proper syntax is tracker_field_%0.', TikiFilter::get('xss')->filter($field), TikiFilter::get('xss')->filter($field));
            }
            $e = new Search_Elastic_QueryParsingException($msg);
            if ($field == 'tracker_id') {
                $e->suppress_feedback = true;
            }
            throw $e;
        }
    }
}
