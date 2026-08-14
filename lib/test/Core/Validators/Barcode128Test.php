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

    public function testEmptyValuesShouldNotBeValidBarcode128()
    {
        $this->validatorslib->setInput("");
        $this->assertNotSame(true, $this->validatorslib->validateInput("barcode128"));
        $this->validatorslib->setInput(null);
        $this->assertNotSame(true, $this->validatorslib->validateInput("barcode128"));
    }

    public function testValidBarcode128ShouldPassValidation()
    {
        // Code 128 can encode all ASCII characters (0-127)
        $this->validatorslib->setInput("0");
        $this->assertSame(true, $this->validatorslib->validateInput("barcode128"));
        $this->validatorslib->setInput("123456789012");
        $this->assertSame(true, $this->validatorslib->validateInput("barcode128"));
        $this->validatorslib->setInput("ABC123");
        $this->assertSame(true, $this->validatorslib->validateInput("barcode128"));
        $this->validatorslib->setInput("Test-123");
        $this->assertSame(true, $this->validatorslib->validateInput("barcode128"));
        $maxLengthString = str_repeat("A", 255);
        $this->validatorslib->setInput($maxLengthString);
        $this->assertSame(true, $this->validatorslib->validateInput("barcode128"));
    }

    public function testInvalidBarcode128ShouldNotPassValidation()
    {
        $longString = str_repeat("A", 256);
        $this->validatorslib->setInput($longString);
        $this->assertNotSame(true, $this->validatorslib->validateInput("barcode128"));
        $this->validatorslib->setInput("Test" . chr(128));
        $this->assertNotSame(true, $this->validatorslib->validateInput("barcode128"));
    }
}
