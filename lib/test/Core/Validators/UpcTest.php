<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace test\Core\Validators;

use TikiLib;

class UpcTest extends \PHPUnit\Framework\TestCase
{
    public $validatorslib;

    protected function setUp(): void
    {
        $this->validatorslib = TikiLib::lib('validators');
    }

    public function testEmptyValuesShouldNotBeValidUpc()
    {
        $this->validatorslib->setInput("");
        $this->assertNotSame(true, $this->validatorslib->validateInput("upc"));
        $this->validatorslib->setInput(null);
        $this->assertNotSame(true, $this->validatorslib->validateInput("upc"));
    }

    public function testValidUpcShouldPassValidation()
    {
        // UPC-A: 12 digits (example from Wikipedia: 03600029145x12, where x12=2)
        $this->validatorslib->setInput("036000291452");
        $this->assertSame(true, $this->validatorslib->validateInput("upc"));
        // UPC-A: Another valid example
        $this->validatorslib->setInput("012345678905");
        $this->assertSame(true, $this->validatorslib->validateInput("upc"));
        // UPC-E: 6 digits (compressed version of UPC-A)
        // For "01234": odd=0+2+4=6, even=1+3=4, sum=(6*3)+4=22, M=2, check=8
        $this->validatorslib->setInput("012348");
        $this->assertSame(true, $this->validatorslib->validateInput("upc"));
    }

    public function testInvalidUpcShouldNotPassValidation()
    {
        // Non-numeric
        $this->validatorslib->setInput("01234567890A");
        $this->assertNotSame(true, $this->validatorslib->validateInput("upc"));
        // Wrong length (not 12 or 6)
        $this->validatorslib->setInput("1234567890");
        $this->assertNotSame(true, $this->validatorslib->validateInput("upc"));
        $this->validatorslib->setInput("12345678"); // 8 digits (old incorrect UPC-E length)
        $this->assertNotSame(true, $this->validatorslib->validateInput("upc"));
        // Wrong check digit
        $this->validatorslib->setInput("036000291451"); // should be 2
        $this->assertNotSame(true, $this->validatorslib->validateInput("upc"));
    }
}
