<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * @group integration
 */
class MLModelTest extends TikiTestCase
{
    protected static $trklib;
    protected static $mllib;
    protected static $trackerId;
    protected static $old_prefs;
    protected static $labels;
    protected static $mlt;

    private const SAMPLES = [
        'The objective of the clean-up is to provide an homogeneous interface to access permissions in a way that is simple and efficient.',
        'The interface used should not reflect how the permissions are stored.',
        'The previous interfaces used multiple functions across different libraries that were confusing and caused frequent WYSIWYCA problems in addition to being inefficient when filtering lists of objects.',
        'The permissions used on categories were confusing and lacked the customizability expected in TikiWiki.',
    ];

    public static function setUpBeforeClass(): void
    {
        global $prefs;
        self::$old_prefs = $prefs;
        $prefs['feature_trackers'] = 'y';
        $prefs['feature_machine_learning'] = 'y';

        parent::setUpBeforeClass();
        self::$trklib = TikiLib::lib('trk');
        self::$mllib = TikiLib::lib('ml');

        // create a tracker and a field
        self::$trackerId = self::$trklib->replace_tracker(null, 'Test Tracker', '', [], 'n');
        self::assertNotEmpty(self::$trackerId, "Check the tracker was created properly");
        $fieldId = self::$trklib->replace_tracker_field(
            self::$trackerId,
            0,
            'Name',
            't',
            'y',
            'y',
            'y',
            'y',
            'n',
            'y',
            10,
            '',
            '',
            '',
            null,
            '',
            null,
            null,
            'n',
            '',
            '',
            '',
            'test_name'
        );
        self::assertNotEmpty($fieldId, "Check the field was created properly");

        $definition = Tracker_Definition::get(self::$trackerId);
        $fields = $definition->getFields();

        foreach (self::SAMPLES as $sample) {
            $fields[0]['value'] = $sample;
            $itemId = self::$trklib->replace_item(self::$trackerId, 0, ['data' => $fields], 'o');
            self::$labels[] = self::$trklib->get_isMain_value(self::$trackerId, $itemId);
        }

        $mlmId = self::$mllib->set_model(null, [
            'name' => 'MLT',
            'sourceTrackerId' => self::$trackerId,
            'labelField' => 'itemTitle',
            'trackerFields' => [$fields[0]['fieldId']],
            'payload' => self::$mllib->predefined('MLT')
        ]);
        self::$mlt = self::$mllib->get_model($mlmId);

        // impersonate admin
        new Perms_Context('admin');
        $perms = Perms::getInstance();
        $perms->setGroups(['Admins']);
    }

    public static function tearDownAfterClass(): void
    {
        global $prefs;
        $prefs['feature_trackers'] = self::$old_prefs['feature_trackers'];
        $prefs['feature_machine_learning'] = self::$old_prefs['feature_machine_learning'];

        parent::tearDownAfterClass();
        self::$trklib->remove_tracker(self::$trackerId);
        self::$mllib->delete_model(self::$mlt['mlmId']);

        $builder = new Perms_Builder();
        Perms::set($builder->build());
    }

    public function testTrain(): void
    {
        self::$mllib->train(self::$mlt);
        self::$mllib->ensureModelTrained(self::$mlt);
        $cachedModel = TikiLib::lib('cache')->getCached(self::$mlt['mlmId'], 'mlmodel');
        $this->assertNotEmpty($cachedModel);
    }

    public function testTrainOnEmptyDataset(): void
    {
        self::$mllib->set_model(self::$mlt['mlmId'], [
            'trackerFields' => [],
            'name' => '',
            'sourceTrackerId' => self::$trackerId,
        ]);
        $mlt = self::$mllib->get_model(self::$mlt['mlmId']);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No data found');
        self::$mllib->train($mlt);

        self::$mllib->set_model(self::$mlt['mlmId'], [
            'trackerFields' => self::$mlt['trackerFields'],
        ]);
    }

    public function testModelUse(): void
    {
        self::$mllib->train(self::$mlt);

        $itemObject = Tracker_Item::newItem(self::$mlt['sourceTrackerId']);
        $processedFields = $itemObject->prepareInput(new JitFilter([
            'ins_' . self::$mlt['trackerFields'][0] => 'homogeneous interface and libraries',
        ]));

        $results = self::$mllib->probaSample(self::$mlt, $processedFields);
        $this->assertEquals(
            [self::$labels[0], self::$labels[2], self::$labels[1], self::$labels[3]],
            array_keys($results)
        );
    }
}
