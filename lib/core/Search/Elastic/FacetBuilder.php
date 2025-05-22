<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Search_Elastic_FacetBuilder
{
    private $count;
    private $mainKey;
    private $histogramInterval;
    private $index;

    public function __construct($count = 10, $useAggregations = false, $useSpecificInterval = false, $index = null)
    {
        $this->count = $count;
        $this->mainKey = $useAggregations ? 'aggregations' : 'facets';
        $this->histogramInterval = $useSpecificInterval ? 'calendar_interval' : 'interval';
        $this->index = $index;
    }

    public function build(array $facets)
    {
        if (empty($facets)) {
            return [];
        }

        $out = [];
        foreach ($facets as $facet) {
            $out[$facet->getName()] = $this->buildFacet($facet);
        }

        return [
            $this->mainKey => $out,
        ];
    }

    private function buildFacet(Search_Query_Facet_Interface $facet)
    {
        $type = $facet->getType();
        $field = $facet->getField();

        if ($this->index && $this->index->hasFacetField($field)) {
            $field .= '.facet';
        }

        $out = [
            'field' => $field,
        ];

        if ($type === 'date_histogram') {
            $out[$this->histogramInterval] = $facet->getInterval();
        } elseif ($type === 'date_range') {
            $out['ranges'] = $facet->getRanges();
        } else {
            $out['size'] = $facet->getCount() ?: $this->count;
            $order = $facet->getOrder();
            if ($order) {
                $out['order'] = $order;
            }
            $minDocCount = $facet->getMinDocCount();
            if ($minDocCount !== null) {
                $out['min_doc_count'] = $minDocCount;
            }
        }

        return [$type => $out];
    }
}
