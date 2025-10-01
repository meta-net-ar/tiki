<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace test\Core\Validators;

use TikiLib;

class Barcode128Test extends \PHPUnit\Framework\TestCase
{
    public $validatorslib;

    protected function setUp(): void
    {
        $this->validatorslib = TikiLib::lib('validators');
    }

    public function testNullValuesShouldNotBeValidBarcode128()
    {
        $this->validatorslib->setInput(0);
        $this->assertNotSame(true, $this->validatorslib->validateInput("barcode128"));
        $this->validatorslib->setInput("");
        $this->assertNotSame(true, $this->validatorslib->validateInput("barcode128"));
    }

    public function testNonNumericValuesShouldNotBeValidBarcode128()
    {
        $this->validatorslib->setInput("ABC123");
        $this->assertNotSame(true, $this->validatorslib->validateInput("barcode128"));
        $this->validatorslib->setInput("123456A");
        $this->assertNotSame(true, $this->validatorslib->validateInput("barcode128"));
    }

    public function testInvalidBarcode128ShouldNotPassValidation()
    {
        $this->validatorslib->setInput("12345678901234");
        $this->assertNotSame(true, $this->validatorslib->validateInput("barcode128"));
    }

    public function testValidBarcode128ShouldPassValidation()
    {
        $this->validatorslib->setInput("123456789012");
        $this->assertSame(true, $this->validatorslib->validateInput("barcode128"));
        $this->validatorslib->setInput("1234567890123");
        $this->assertSame(true, $this->validatorslib->validateInput("barcode128"));
    }
}
