<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Search\Manticore;

class FacetReader
{
    private $results;

    public function __construct(array $results)
    {
        $this->results = $results;
    }

    public function getFacetFilter(\Search_Query_Facet_Interface $facet)
    {

        foreach ($facet->getDependencies() as $dependency) {
            $entry = $this->getFacetEntry($dependency->getName());
            if (empty($entry)) {
                return null;
            }
            $entries = $this->getFromBucket($entry, $dependency);
            if (empty($entries)) {
                return null;
            }
        }

        if (! $facet->isBuiltFromIndex()) {
            return new \Search_ResultSet_FacetFilter($facet, $facet->getData());
        }

        $entry = $this->getFacetEntry($facet->getName());

        $entries = $this->getFromBucket($entry, $facet);
        if ($entries) {
            return new \Search_ResultSet_FacetFilter($facet, $entries);
        } else {
            return null;
        }
    }

    private function getFacetEntry($name)
    {
        foreach ($this->results as $result) {
            if (empty($result)) {
                continue;
            }

            if (strtolower(array_key_first($result[0])) == strtolower($name)) {
                return $result;
            }
        }
        return [];
    }

    private function getFromBucket($entry, $facet)
    {
        $out = [];
        foreach ($entry as $row) {
            $value = array_shift($row);
            $count = array_pop($row);
            if ($facet->getType() === 'date_histogram') {
                // ES histogram is in milliseconds, Manticore is in seconds
                $value = intval($value) * 1000;
            }
            if ($value != '') {
                $out[] = ['value' => $value, 'count' => $count];
            }
        }
        return $out;
    }
}
