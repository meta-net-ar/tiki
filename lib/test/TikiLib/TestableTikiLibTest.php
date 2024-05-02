<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class TestableTikiLibTest extends TikiTestCase
{
    public function testOverrideLibShouldChangeValueReturnedByLib(): void
    {
        $obj = new TestableTikiLib();

        $this->assertInstanceOf(TikiLib::class, TikiLib::lib('tiki'));
        $obj->overrideLibs(['tiki' => new stdClass()]);
        $this->assertInstanceOf(stdClass::class, TikiLib::lib('tiki'));
    }

    public function testOverrideLibShouldRestoreDefaultValueAfterObjectDestruction(): void
    {
        $obj = new TestableTikiLib();

        $this->assertInstanceOf(TikiLib::class, TikiLib::lib('tiki'));
        $obj->overrideLibs(['tiki' => new stdClass()]);
        $this->assertInstanceOf(stdClass::class, TikiLib::lib('tiki'));

        unset($obj);
        $this->assertInstanceOf(TikiLib::class, TikiLib::lib('tiki'));
    }

    public function testOverrideLibShouldWorkWithMockObjects(): void
    {
        $obj = new TestableTikiLib();

        $calendarlib = $this->createMock(get_class(TikiLib::lib('calendar')));
        $calendarlib->expects($this->never())->method('get_item');

        $this->assertInstanceOf(CalendarLib::class, TikiLib::lib('calendar'));
        $obj->overrideLibs(['calendar' => $calendarlib]);
        $this->assertStringContainsString('MockObject_CalendarLib_', get_class(TikiLib::lib('calendar')));
    }

    public function testOverrideLibCheckIfLibReturnedToOriginalStateAfterLastTest(): void
    {
        $this->assertInstanceOf(CalendarLib::class, TikiLib::lib('calendar'));
    }
}
