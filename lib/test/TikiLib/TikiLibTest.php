<?php

/**
 * Created by JetBrains PhpStorm.
 * User: alaindesilets
 * Date: 2013-10-08
 * Time: 1:33 PM
 * To change this template use File | Settings | File Templates.
 */

class TikiLibTest extends TikiTestCase
{
    private $some_page_name1 = 'SomePage1';
    private $some_page_name2 = 'SomePage2';
    private $some_page_name3 = 'SomePage3';

    protected function setUp(): void
    {
        $testhelpers = new TestHelpers();

        $testhelpers->simulateTikiScriptContext();
    }

    protected function tearDown(): void
    {
        $testhelpers = new TestHelpers();

        $testhelpers->removeAllVersions($this->some_page_name1);
        $testhelpers->removeAllVersions($this->some_page_name2);
        $testhelpers->removeAllVersions($this->some_page_name3);

        $testhelpers->removeAllVersions('PageThatDoesntExist');


        $testhelpers->stopSimulatingTikiScriptContext();
    }

    public function testRemoveAllVersionsRemovesAllRelationsAlso(): void
    {
        $testhelpers = new TestHelpers();
        $relationlib = TikiLib::lib('relation');
        $tikilib = TikiLib::lib('tiki');

        $testhelpers->createPage($this->some_page_name1, 0, "Hello from " . $this->some_page_name1);
        $testhelpers->createPage($this->some_page_name2, 0, "Hello from " . $this->some_page_name2);
        $testhelpers->createPage($this->some_page_name3, 0, "Hello from " . $this->some_page_name3);

        $relation_name = 'tiki.wiki.somerelation';
        $relationlib->add_relation($relation_name, 'wiki page', $this->some_page_name1, 'wiki page', $this->some_page_name2);
        $relationlib->add_relation($relation_name, 'wiki page', $this->some_page_name3, 'wiki page', $this->some_page_name1);

        $got_relations = $relationlib->get_relations_from('wiki page', $this->some_page_name1, $relation_name);
        $this->assertCount(
            1,
            $got_relations,
            "Initially, there should have been 1 relation from " . $this->some_page_name1
        );
        $got_relations = $relationlib->get_relations_to('wiki page', $this->some_page_name1, $relation_name);
        $this->assertCount(
            1,
            $got_relations,
            "Initially, there should have been 1 relation to " . $this->some_page_name1
        );

        $tikilib->remove_all_versions($this->some_page_name1);
        $got_relations = $relationlib->get_relations_from('wiki page', $this->some_page_name1, $relation_name);
        $this->assertCount(
            0,
            $got_relations,
            "After deleting the page, there shouldn't be any relations left from " . $this->some_page_name1
        );
        $got_relations = $relationlib->get_relations_to('wiki page', $this->some_page_name1, $relation_name);
        $this->assertCount(
            0,
            $got_relations,
            "After deleting the page, there shouldn't be any relations left to " . $this->some_page_name1
        );
    }
}
