<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Search_MySql_LargeDatasetTest extends PHPUnit\Framework\TestCase
{
    protected $index;

    protected function setUp(): void
    {
        $this->index = $this->getIndex();
        $this->index->destroy();
    }

    protected function getIndex()
    {
        return new Search_MySql_Index(TikiDb::get(), 'test_index');
    }

    protected function tearDown(): void
    {
        if ($this->index) {
            $this->index->destroy();
        }
    }

    public function testManyColumns()
    {
        $typeFactory = $this->index->getTypeFactory();
        $document = [
            'object_type' => $typeFactory->identifier('test'),
            'object_id' => $typeFactory->identifier('test'),
        ];

        for ($i = 0; 1500 > $i; ++$i) {
            $document['identifier_' . $i] = $typeFactory->identifier('test');
            $document['sortable_' . $i] = $typeFactory->sortable('test');
            $document['plaintext_' . $i] = $typeFactory->plaintext('test');
        }

        $this->index->addDocument($document);

        $query = new Search_Query();
        $query->filterType('test');

        $results = $query->search($this->index);

        $this->assertEquals('test', $results[0]['object_id']);
    }
}
