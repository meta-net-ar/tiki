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
        $entry = [];

        foreach ($this->results as $result) {
            if (empty($result)) {
                continue;
            }
            if (strtolower(array_key_first($result[0])) == strtolower($facet->getName())) {
                $entry = $result;
                break;
            }
        }

        $entries = $this->getFromBucket($entry);
        if ($entries) {
            return new \Search_ResultSet_FacetFilter($facet, $entries);
        } else {
            return null;
        }
    }

    private function getFromBucket($entry)
    {
        $out = [];
        foreach ($entry as $row) {
            $value = array_shift($row);
            $count = array_pop($row);
            if ($value != '') {
                $out[] = ['value' => $value, 'count' => $count];
            }
        }
        return $out;
    }
}
