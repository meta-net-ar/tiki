<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

class RelationTest extends TikiTestCase
{
    private static $relationlib;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::$relationlib = TikiLib::lib('relation');
    }
    protected function setUp(): void
    {
        parent::setUp();
        TikiDb::get()->query('DELETE FROM `tiki_object_relations` WHERE `relation` LIKE ?', ['tiki.test%']);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        TikiDb::get()->query('DELETE FROM `tiki_object_relations` WHERE `relation` LIKE ?', ['tiki.test%']);
    }

    public function testNoRelations(): void
    {
        $lib = self::$relationlib;

        $this->assertEquals([], $lib->get_relations_from('test wiki page', 'HomePage'));
    }

    public function testAddRelation(): void
    {
        $lib = self::$relationlib;
        $lib->add_relation('tiki.test.link', 'test wiki page', 'HomePage', 'test wiki page', 'SomePage');

        $this->assertEquals(
            [['relation' => 'tiki.test.link', 'type' => 'test wiki page', 'itemId' => 'SomePage', 'fieldId' => null, 'metaItemId' => null]],
            $this->removeId($lib->get_relations_from('test wiki page', 'HomePage'))
        );
    }

    public function testDuplicateRelation(): void
    {
        $lib = self::$relationlib;
        $lib->add_relation('tiki.test.link', 'test wiki page', 'HomePage', 'test wiki page', 'SomePage');
        $lib->add_relation('tiki.test.link', 'test wiki page', 'HomePage', 'test wiki page', 'SomePage');

        $this->assertEquals(
            [['relation' => 'tiki.test.link', 'type' => 'test wiki page', 'itemId' => 'SomePage', 'fieldId' => null, 'metaItemId' => null]],
            $this->removeId($lib->get_relations_from('test wiki page', 'HomePage'))
        );
    }

    public function testMultipleResults(): void
    {
        $lib = self::$relationlib;
        $lib->add_relation('tiki.test.link', 'test wiki page', 'HomePage', 'test wiki page', 'SomePage');
        $lib->add_relation('tiki.test.link', 'test wiki page', 'HomePage', 'test tracker item', '23');
        $lib->add_relation('tiki.test.something', 'test wiki page', 'HomePage', 'test tracker item', '23');
        $lib->add_relation('tiki.test.link', 'test tracker item', '23', 'test wiki page', 'SomePage');

        $result = $this->removeId($lib->get_relations_from('test wiki page', 'HomePage'));

        $this->assertContains(['relation' => 'tiki.test.link', 'type' => 'test wiki page', 'itemId' => 'SomePage', 'fieldId' => null, 'metaItemId' => null], $result);
        $this->assertContains(['relation' => 'tiki.test.link', 'type' => 'test tracker item', 'itemId' => '23', 'fieldId' => null, 'metaItemId' => null], $result);
        $this->assertContains(['relation' => 'tiki.test.something', 'type' => 'test tracker item', 'itemId' => '23', 'fieldId' => null, 'metaItemId' => null], $result);
    }

    public function testFilterByType(): void
    {
        $lib = self::$relationlib;
        $lib->add_relation('tiki.test.link', 'test wiki page', 'HomePage', 'test wiki page', 'SomePage');
        $lib->add_relation('tiki.test.link', 'test wiki page', 'HomePage', 'test tracker item', '23');
        $lib->add_relation('tiki.test.something', 'test wiki page', 'HomePage', 'test tracker item', '23');
        $lib->add_relation('tiki.test.link', 'test tracker item', '23', 'test wiki page', 'SomePage');

        $this->assertEquals(
            [['relation' => 'tiki.test.something', 'type' => 'test tracker item', 'itemId' => '23', 'fieldId' => null, 'metaItemId' => null],],
            $this->removeId($lib->get_relations_from('test wiki page', 'HomePage', 'tiki.test.something'))
        );
    }

    public function testRelationNamesChecked(): void
    {
        $lib = self::$relationlib;
        $lib->add_relation('tiki.link', 'test wiki page', 'HomePage', 'test wiki page', 'SomePage');
        $lib->add_relation('TIKI . test  . link  ', 'test wiki page', 'HomePage', 'test tracker item', '23');

        $this->assertEquals(
            [['relation' => 'tiki.test.link', 'type' => 'test tracker item', 'itemId' => '23', 'fieldId' => null, 'metaItemId' => null],],
            $this->removeId($lib->get_relations_from('test wiki page', 'HomePage'))
        );
    }

    public function testLoadGroupOfRelations(): void
    {
        $lib = self::$relationlib;
        $lib->add_relation('tiki.test.sem.related', 'test wiki page', 'HomePage', 'test wiki page', 'SomePage');
        $lib->add_relation('tiki.test.sem.source', 'test wiki page', 'HomePage', 'test external', 'http://wikipedia.org');
        $lib->add_relation('tiki.test.link', 'test wiki page', 'HomePage', 'test external', 'http://wikipedia.org');

        $result = $this->removeId($lib->get_relations_from('test wiki page', 'HomePage', 'tiki.test.sem.'));

        $this->assertContains(['relation' => 'tiki.test.sem.related', 'type' => 'test wiki page', 'itemId' => 'SomePage', 'fieldId' => null, 'metaItemId' => null], $result);
        $this->assertContains(['relation' => 'tiki.test.sem.source', 'type' => 'test external', 'itemId' => 'http://wikipedia.org', 'fieldId' => null, 'metaItemId' => null], $result);
        $this->assertNotContains(['relation' => 'tiki.test.link', 'type' => 'test external', 'itemId' => 'http://wikipedia.org', 'fieldId' => null, 'metaItemId' => null], $result);
    }

    public function testRevert(): void
    {
        $lib = self::$relationlib;
        $lib->add_relation('tiki.test.sem.related', 'test wiki page', 'HomePage', 'test wiki page', 'SomePage');
        $lib->add_relation('tiki.test.sem.source', 'test wiki page', 'HomePage', 'test external', 'http://wikipedia.org');
        $lib->add_relation('tiki.test.link', 'test wiki page', 'HomePage', 'test external', 'http://wikipedia.org');

        $result = $this->removeId($lib->get_relations_to('test external', 'http://wikipedia.org', 'tiki.test.sem.'));

        $this->assertEquals(
            [['relation' => 'tiki.test.sem.source', 'type' => 'test wiki page', 'itemId' => 'HomePage', 'fieldId' => null, 'metaItemId' => null]],
            $result
        );
    }

    public function testGetSingle(): void
    {
        $lib = self::$relationlib;
        $id = $lib->add_relation('tiki.test.sem.related', 'test wiki page', 'HomePage', 'test wiki page', 'SomePage');

        $data = $lib->get_relation($id);

        $this->assertEquals('tiki.test.sem.related', $data['relation']);
    }

    public function testRemoveSingle(): void
    {
        $lib = self::$relationlib;
        $id = $lib->add_relation('tiki.test.sem.related', 'test wiki page', 'HomePage', 'test wiki page', 'SomePage');

        $lib->remove_relation($id);

        $this->assertFalse($lib->get_relation($id));
    }

    public function testObjectRetrieval(): void
    {
        $lib = self::$relationlib;
        $lib->add_relation('tiki.test.related', 'trackeritem', 123, 'trackeritem', 321);

        $relations = $lib->getObjectRelations('trackeritem', 123, 'tiki.test.related');

        $this->assertEquals(1, count($relations));

        $rel = $relations[0];
        $this->assertEquals('trackeritem', $rel->target->type);
        $this->assertEquals(321, $rel->target->itemId);
        $this->assertEquals(null, $rel->getMetadataItemId());
    }

    public function testInvertObjectRetrieval(): void
    {
        $lib = self::$relationlib;
        $lib->add_relation('tiki.test.related', 'trackeritem', 123, 'trackeritem', 321);

        $relations = $lib->getObjectRelations('trackeritem', 321, 'tiki.test.related.invert');

        $this->assertEquals(1, count($relations));
        $this->assertEquals(123, $relations[0]->target->itemId);
    }

    public function testDoubleInvertObjectRetrieval(): void
    {
        $lib = self::$relationlib;
        $lib->add_relation('tiki.test.related.invert', 'trackeritem', 123, 'trackeritem', 321);
        $lib->add_relation('tiki.test.related', 'trackeritem', 789, 'trackeritem', 987);

        $relations = $lib->getObjectRelations('trackeritem', 123, 'tiki.test.related', true);
        $this->assertEquals(1, count($relations));
        $this->assertEquals(321, $relations[0]->target->itemId);

        $relations = $lib->getObjectRelations('trackeritem', 789, 'tiki.test.related.invert', true);
        $this->assertEquals(1, count($relations));
        $this->assertEquals(987, $relations[0]->target->itemId);
    }

    private function removeId($data)
    {
        foreach ($data as & $row) {
            unset($row['relationId']);
        }

        return $data;
    }
}
