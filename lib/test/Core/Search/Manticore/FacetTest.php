<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Search\Manticore;

use Search_Query;
use Search_Query_Facet_Term;
use Search_ResultSet_FacetFilter;

class FacetTest extends \PHPUnit\Framework\TestCase
{
    use IndexBuilder;

    protected $index;

    protected function setUp(): void
    {
        $this->index = $this->createIndex('_facet');
        $this->index->destroy();

        $this->populate($this->index);
    }

    protected function tearDown(): void
    {
        if ($this->index) {
            $this->index->destroy();
        }
    }

    public function testRequireFacet()
    {
        $facet = new Search_Query_Facet_Term('categories');

        $query = new Search_Query();
        $query->filterType('wiki page');
        $query->requestFacet($facet);

        $result = $query->search($this->index);
        $values = $result->getFacet($facet);

        $this->assertEquals(
            new Search_ResultSet_FacetFilter(
                $facet,
                [
                    ['value' => crc32(1), 'count' => 3],
                    ['value' => crc32(2), 'count' => 2],
                    ['value' => crc32('orphan'), 'count' => 1],
                    ['value' => crc32(3), 'count' => 1],
                ]
            ),
            $values
        );
    }

    protected function populate($index)
    {
        $this->add($index, 'ABC', [1, 2, 3]);
        $this->add($index, 'AB', [1, 2]);
        $this->add($index, 'A', [1]);
        $this->add($index, 'empty', ['orphan']);
    }

    private function add($index, $page, array $categories)
    {
        $typeFactory = $index->getTypeFactory();

        $index->addDocument(
            [
                'object_type' => $typeFactory->identifier('wiki page'),
                'object_id' => $typeFactory->identifier($page),
                'categories' => $typeFactory->multivalue($categories),
            ]
        );
    }
}
