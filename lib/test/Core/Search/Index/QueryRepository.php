<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Search\Index;

use Search_Query;

abstract class QueryRepository extends \PHPUnit\Framework\TestCase
{
    protected $index;

    protected function populate($index)
    {
        $factory = $index->getTypeFactory();
        $index->addDocument([
            'object_type' => $factory->identifier('wiki page'),
            'object_id' => $factory->identifier('HomePage'),
            'contents' => $factory->plaintext('Hello World'),
        ]);
    }

    public function testNothingToMatch()
    {
        $tf = $this->index->getTypeFactory();
        $names = $this->index->getMatchingQueries([
            'object_type' => $tf->identifier('wiki page'),
            'object_id' => $tf->identifier('HomePage'),
            'contents' => $tf->plaintext('Hello World!'),
        ]);

        $this->assertEquals([], $names);
    }

    public function testFilterBasicContent()
    {
        $query = new Search_Query('Hello World');
        $query->store('my_custom_name', $this->index);

        $tf = $this->index->getTypeFactory();
        $names = $this->index->getMatchingQueries([
            'object_type' => $tf->identifier('wiki page'),
            'object_id' => $tf->identifier('HomePage'),
            'contents' => $tf->plaintext('Hello World!'),
        ]);

        $this->assertEquals(['my_custom_name'], $names);
    }

    public function testFilterFailsToFindContent()
    {
        $query = new Search_Query('Foobar');
        $query->store('my_custom_name', $this->index);

        $tf = $this->index->getTypeFactory();
        $names = $this->index->getMatchingQueries([
            'object_type' => $tf->identifier('wiki page'),
            'object_id' => $tf->identifier('HomePage'),
            'contents' => $tf->plaintext('Hello World!'),
        ]);

        $this->assertEquals([], $names);
    }

    public function testRemoveQuery()
    {
        $query = new Search_Query('Hello World');
        $query->store('my_custom_name', $this->index);
        $this->index->unstore('my_custom_name');

        $tf = $this->index->getTypeFactory();
        $names = $this->index->getMatchingQueries([
            'object_type' => $tf->identifier('wiki page'),
            'object_id' => $tf->identifier('HomePage'),
            'contents' => $tf->plaintext('Hello World!'),
        ]);

        $this->assertEquals([], $names);
    }
}
