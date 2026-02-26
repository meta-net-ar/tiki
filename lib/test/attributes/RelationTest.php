<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
$relationlib = TikiLib::lib('relation');

class RelationTest extends TikiTestCase
{
    protected static $trackerId;
    protected static $old_prefs;
    protected static $testItems = [];

    public static function setUpBeforeClass(): void
    {
        global $prefs;
        self::$old_prefs = $prefs;
        $prefs['feature_trackers'] = 'y';

        parent::setUpBeforeClass();

        $trklib = TikiLib::lib('trk');

        self::$trackerId = $trklib->replace_tracker(null, 'Test Tracker', '', [], 'n');
        $trklib->replace_tracker_field(
            self::$trackerId,
            0,
            'summary',
            't',
            'y',
            'y',
            'y',
            'y',
            'n',
            'y',
            10,
            '',
            permName: 'summary'
        );

        $definition = Tracker_Definition::get(self::$trackerId);
        $fields = $definition->getFields();
        for ($i = 1; $i <= 4; $i++) {
            $input = ['ins_' . $fields[0]['fieldId'] => 'Test item ' . $i];
            $itemObject = Tracker_Item::newItem(self::$trackerId);
            $processedFields = $itemObject->prepareInput(new JitFilter($input));
            $itemId = $trklib->replace_item(
                self::$trackerId,
                null,
                ['data' => $processedFields]
            );
            self::$testItems[] = $itemId;
        }
    }

    public static function tearDownAfterClass(): void
    {
        global $prefs;

        parent::tearDownAfterClass();

        TikiLib::lib('trk')->remove_tracker(self::$trackerId);

        $prefs = self::$old_prefs;
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
        $lib = new RelationLib();

        $this->assertEquals([], $lib->get_relations_from('test wiki page', 'HomePage'));
    }

    public function testAddRelation(): void
    {
        $lib = new RelationLib();
        $lib->add_relation('tiki.test.link', 'test wiki page', 'HomePage', 'test wiki page', 'SomePage');

        $this->assertEquals(
            [['relation' => 'tiki.test.link', 'type' => 'test wiki page', 'itemId' => 'SomePage', 'fieldId' => null, 'metaItemId' => null]],
            $this->removeId($lib->get_relations_from('test wiki page', 'HomePage'))
        );
    }

    public function testDuplicateRelation(): void
    {
        $lib = new RelationLib();
        $lib->add_relation('tiki.test.link', 'test wiki page', 'HomePage', 'test wiki page', 'SomePage');
        $lib->add_relation('tiki.test.link', 'test wiki page', 'HomePage', 'test wiki page', 'SomePage');

        $this->assertEquals(
            [['relation' => 'tiki.test.link', 'type' => 'test wiki page', 'itemId' => 'SomePage', 'fieldId' => null, 'metaItemId' => null]],
            $this->removeId($lib->get_relations_from('test wiki page', 'HomePage'))
        );
    }

    public function testMultipleResults(): void
    {
        $lib = new RelationLib();
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
        $lib = new RelationLib();
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
        $lib = new RelationLib();
        $lib->add_relation('tiki.link', 'test wiki page', 'HomePage', 'test wiki page', 'SomePage');
        $lib->add_relation('TIKI . test  . link  ', 'test wiki page', 'HomePage', 'test tracker item', '23');

        $this->assertEquals(
            [['relation' => 'tiki.test.link', 'type' => 'test tracker item', 'itemId' => '23', 'fieldId' => null, 'metaItemId' => null],],
            $this->removeId($lib->get_relations_from('test wiki page', 'HomePage'))
        );
    }

    public function testLoadGroupOfRelations(): void
    {
        $lib = new RelationLib();
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
        $lib = new RelationLib();
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
        $lib = new RelationLib();
        $id = $lib->add_relation('tiki.test.sem.related', 'test wiki page', 'HomePage', 'test wiki page', 'SomePage');

        $data = $lib->get_relation($id);

        $this->assertEquals('tiki.test.sem.related', $data['relation']);
    }

    public function testRemoveSingle(): void
    {
        $lib = new RelationLib();
        $id = $lib->add_relation('tiki.test.sem.related', 'test wiki page', 'HomePage', 'test wiki page', 'SomePage');

        $lib->remove_relation($id);

        $this->assertFalse($lib->get_relation($id));
    }

    public function testObjectRetrieval(): void
    {
        $lib = new RelationLib();
        $srcItemId = self::$testItems[0];
        $targetItemId = self::$testItems[1];
        $lib->add_relation('tiki.test.related', 'trackeritem', $srcItemId, 'trackeritem', $targetItemId);

        $relations = $lib->getObjectRelations('trackeritem', $srcItemId, 'tiki.test.related');

        $this->assertEquals(1, count($relations));

        $rel = $relations[0];
        $this->assertEquals('trackeritem', $rel->target->type);
        $this->assertEquals($targetItemId, $rel->target->itemId);
        $this->assertEquals(null, $rel->getMetadataItemId());
    }

    public function testInvertObjectRetrieval(): void
    {
        $lib = new RelationLib();
        $srcItemId = self::$testItems[0];
        $targetItemId = self::$testItems[1];
        $lib->add_relation('tiki.test.related', 'trackeritem', $srcItemId, 'trackeritem', $targetItemId);

        $relations = $lib->getObjectRelations('trackeritem', $targetItemId, 'tiki.test.related.invert');

        $this->assertEquals(1, count($relations));
        $this->assertEquals($srcItemId, $relations[0]->target->itemId);
    }

    public function testDoubleInvertObjectRetrieval(): void
    {
        $lib = new RelationLib();
        $srcItemId = self::$testItems[2];
        $targetItemId = self::$testItems[3];
        $lib->add_relation('tiki.test.related.invert', 'trackeritem', $srcItemId, 'trackeritem', $targetItemId);

        $nonInvertedSrcItemId = self::$testItems[0];
        $nonInvertedTargetItemId = self::$testItems[1];
        $lib->add_relation('tiki.test.related', 'trackeritem', $nonInvertedSrcItemId, 'trackeritem', $nonInvertedTargetItemId);

        $relations = $lib->getObjectRelations('trackeritem', $srcItemId, 'tiki.test.related', true);
        $this->assertEquals(1, count($relations));
        $this->assertEquals($targetItemId, $relations[0]->target->itemId);

        $relations = $lib->getObjectRelations('trackeritem', $nonInvertedSrcItemId, 'tiki.test.related.invert', true);
        $this->assertEquals(1, count($relations));
        $this->assertEquals($nonInvertedTargetItemId, $relations[0]->target->itemId);
    }

    private function removeId($data)
    {
        foreach ($data as & $row) {
            unset($row['relationId']);
        }

        return $data;
    }
}
