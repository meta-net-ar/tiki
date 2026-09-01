<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Lib\Test\Core\Tiki\Profile;

use TikiLib;
use Tiki_Profile_InstallHandler_TrackerItem;
use Tiki_Profile_Object;
use TestableTikiLib;
use TikiTestCase;

class TrackerItemInstallHandlerTest extends TikiTestCase
{
    private $overrideLibs;

    protected function setUp(): void
    {
        parent::setUp();
        $this->overrideLibs = new TestableTikiLib();
    }

    protected function tearDown(): void
    {
        $this->overrideLibs = null;
        parent::tearDown();
    }

    /**
     * @dataProvider invalidItemsListValues
     */
    public function testDoInstallSkipsInvalidItemsListValue(array $values): void
    {
        $trklib = $this->createMock(get_class(TikiLib::lib('trk')));
        $trklib->expects($this->once())
            ->method('list_tracker_fields')
            ->with(1)
            ->willReturn([
                'data' => [
                    ['fieldId' => 10, 'permName' => 'items_field', 'type' => 'l'],
                    ['fieldId' => 11, 'permName' => 'title', 'type' => 't'],
                ],
            ]);
        $trklib->expects($this->once())
            ->method('replace_item')
            ->with(
                1,
                0,
                $this->callback(function (array $fields): bool {
                    $fieldsById = array_column($fields['data'], null, 'fieldId');
                    $this->assertArrayNotHasKey(10, $fieldsById);
                    $this->assertSame('Task 1', $fieldsById[11]['value']);
                    return true;
                }),
                'o'
            )
            ->willReturn(123);

        $this->overrideLibs->overrideLibs(['trk' => $trklib]);

        $object = $this->getMockBuilder(Tiki_Profile_Object::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getData'])
            ->getMock();
        $object->method('getData')->willReturn([
            'tracker' => 1,
            'status' => 'open',
            'values' => $values,
        ]);

        $handler = new class ($object, false) extends Tiki_Profile_InstallHandler_TrackerItem {
            public function replaceReferences(mixed &$data): void
            {
            }
        };

        $this->assertSame(123, $handler->doInstall());
    }

    public static function invalidItemsListValues(): array
    {
        return [
            'rendered value' => [
                [
                    ['items_field', 'tm2'],
                    ['title', 'Task 1'],
                ],
            ],
            'omitted value' => [
                [
                    ['title', 'Task 1'],
                ],
            ],
        ];
    }

    public function testDoInstallPassesItemIdsForItemsListField(): void
    {
        $trklib = $this->createMock(get_class(TikiLib::lib('trk')));
        $trklib->expects($this->once())
            ->method('list_tracker_fields')
            ->with(1)
            ->willReturn([
                'data' => [
                    ['fieldId' => 10, 'permName' => 'items_field', 'type' => 'l'],
                ],
            ]);
        $trklib->expects($this->once())
            ->method('replace_item')
            ->with(
                1,
                0,
                $this->callback(function (array $fields): bool {
                    $this->assertSame([12, 34], $fields['data'][0]['value']);
                    return true;
                }),
                'o'
            )
            ->willReturn(123);

        $this->overrideLibs->overrideLibs(['trk' => $trklib]);

        $object = $this->getMockBuilder(Tiki_Profile_Object::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getData'])
            ->getMock();
        $object->method('getData')->willReturn([
            'tracker' => 1,
            'status' => 'open',
            'values' => [
                ['items_field', [12, 34]],
            ],
        ]);

        $handler = new class ($object, false) extends Tiki_Profile_InstallHandler_TrackerItem {
            public function replaceReferences(mixed &$data): void
            {
            }
        };

        $this->assertSame(123, $handler->doInstall());
    }
}
