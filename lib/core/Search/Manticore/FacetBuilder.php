<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Search\Manticore;

class FacetBuilder
{
    private $index;
    private $count;
    private array $possibleFields;

    public function __construct(Index $index, $count = 10)
    {
        $this->index = $index;
        $this->count = $count;
        $this->possibleFields = [];
    }

    public function setPossibleFields(array $fields)
    {
        $this->possibleFields = $fields;
    }

    public function build(array $facets)
    {
        if (empty($facets)) {
            return;
        }

        $out = [];
        foreach ($facets as $facet) {
            $out[] = $this->buildFacet($facet);
        }
        return implode(' ', $out);
    }

    private function buildFacet($facet)
    {
        $out = '';

        $field = $facet->getField();
        $isJsonField = ($this->index && $this->index->isFieldInJson($field));
        if ($isJsonField) {
            $facetField = $this->index->getJsonPathForField($field);
            $facetDateField = $facetField . '_ts';
        } else {
            $field = strtolower($field);
            if (! in_array($field, $this->possibleFields)) {
                return $out;
            }
            $facetField = $facetDateField = $field;
            try {
                $this->index->ensureHasField($facetField);
            } catch (Exception $e) {
                // ignore facet requests for missing fields
                return '';
            }
        }

        $type = $facet->getType();
        if ($type === 'date_histogram') {
            $out = 'FACET DATE_HISTOGRAM(BIGINT(' . $facetDateField . '), {calendar_interval=\'' . $facet->getInterval() . '\'}) AS ' . $facet->getName() . ' ORDER BY FACET() ASC';
        } elseif ($type === 'date_range') {
            $ranges = array_map(function ($range) {
                return '{range_from=\'' . $range['from'] . '\',range_to=\'' . $range['to'] . '\'}';
            }, $facet->getRanges());
            $out = 'FACET DATE_RANGE(BIGINT(' . $facetDateField . '), ' . implode(',', $ranges) . ') AS ' . $facet->getName() . ' ORDER BY FACET() ASC';
        } else {
            $count = $facet->getCount() ?: $this->count;
            $order = $facet->getOrder();

            $out = 'FACET ' . $facetField . ' AS ' . $facet->getName();
            if ($order) {
                foreach ($order as $field => $direction) {
                    $out .= ' ORDER BY ' . $field . ' ' . $direction;
                    break;
                }
            } else {
                $out .= ' ORDER BY COUNT(*) DESC';
            }
            $out .= ' LIMIT ' . $count;
        }

        return $out;
    }
}
