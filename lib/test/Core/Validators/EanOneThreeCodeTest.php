<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace test\Core\Validators;

use TikiLib;

class EanOneThreeCodeTest extends \PHPUnit\Framework\TestCase
{
    public $validatorslib;

    protected function setUp(): void
    {
        $this->validatorslib = TikiLib::lib('validators');
    }

    public function testNullValuesShouldNotBeValidEan13Code()
    {
        $this->validatorslib->setInput(0);
        $this->assertNotSame(true, $this->validatorslib->validateInput("eanOneThreeCode")); // as assertEquals to check the type, let's use AssertSame because the validator returns mixed type
        $this->validatorslib->setInput("");
        $this->assertNotSame(true, $this->validatorslib->validateInput("eanOneThreeCode"));
    }

    public function testNonNumericValuesShouldNotBeValidEan13Code()
    {
        $this->validatorslib->setInput("222345F2");
        $this->assertNotSame(true, $this->validatorslib->validateInput("eanOneThreeCode"));
        $this->validatorslib->setInput("616180091152e");
        $this->assertNotSame(true, $this->validatorslib->validateInput("eanOneThreeCode"));
    }

    public function testInValidEAN13CodeShouldNotPassValidationAndProperValidationMessageWillBeReturned()
    {
        //refer to https://en.wikipedia.org/wiki/International_Article_Number
        $this->validatorslib->setInput(5901234123447); //wrong checksum digit
        $this->assertNotSame(true, $this->validatorslib->validateInput("eanOneThreeCode"));
        $this->validatorslib->setInput(158965784215); //incorrect length
        $this->assertNotSame(true, $this->validatorslib->validateInput("eanOneThreeCode"));
        $this->validatorslib->setInput(15896578421578); //incorrect length
        $this->assertNotSame(true, $this->validatorslib->validateInput("eanOneThreeCode"));
    }

    public function testValidEan13CodeShouldPassValidation()
    {
        //refer to https://en.wikipedia.org/wiki/International_Article_Number
        $this->validatorslib->setInput(5901234123457);
        $this->assertSame(true, $this->validatorslib->validateInput("eanOneThreeCode"));
        $this->validatorslib->setInput(6161100911529);
        $this->assertSame(true, $this->validatorslib->validateInput("eanOneThreeCode"));
    }
}
