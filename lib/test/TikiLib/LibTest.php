<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class TikiLib_LibTest extends TikiTestCase
{
    public function testLibShouldReturnInstanceOfTikiLib(): void
    {
        $this->assertInstanceOf(TikiLib::class, TikiLib::lib('tiki'));
    }

    public function testLibShouldReturnInstanceOfCalendar(): void
    {
        $this->assertInstanceOf(CalendarLib::class, TikiLib::lib('calendar'));
    }

    public function testLibShouldReturnNullForInvalidClass(): void
    {
        $this->expectException(\Exception::class); // expect an exception to be thrown
        $this->expectExceptionMessage(tr("%0 library not found. This may be due to a typo or caused by a recent update.", 'invalidClass'), TikiLib::class . '::lib', 'invalidClass');
        set_error_handler(function ($errno, $errstr) {
            throw new \Exception($errstr); // throw an exception when an error occurs
        });

        try {
            TikiLib::lib('invalidClass');
        } catch (\Exception $e) {
            throw $e; // rethrow the exception
        } finally {
            restore_error_handler(); // restore the previous error handler for the next tests
        }
    }
}
