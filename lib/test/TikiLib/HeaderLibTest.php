<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Tests\HeaderLib;

use TikiLib,

HeaderLib;

class HeaderLibTest extends \PHPUnit\Framework\TestCase
{
    private $firstJs = "//First normalJS\nsomeVariable = new Array();";
    private $secondJs = "//Second normalJS\nsomeOtherVariable = null;";
    protected function setUp(): void
    {
        TikiLib::lib('cache')->empty_cache();
    }

    public function testTwoJsCanBeAdded(): void
    {
        $headerLib = new HeaderLib();
        $headerLib->add_js($this->firstJs)->add_js($this->secondJs);
        $output = trim($headerLib->output_js(false));
        $expected = trim($this->firstJs . "\n" . trim($this->secondJs));
        $this->assertEquals($expected, $output);
    }

    public function testTwoJsCanBeAddedSameAtSameRank(): void
    {
        $headerLib = new HeaderLib();
        $headerLib->add_js($this->firstJs, 10)->add_js($this->secondJs, 10);
        $output = trim($headerLib->output_js(false));
        $expected = trim($this->firstJs . "\n" . trim($this->secondJs));
        $this->assertEquals($expected, $output);
    }

    public function testNoDuplicates(): void
    {
        $headerLib = new HeaderLib();
        $headerLib->add_js($this->firstJs)->add_js($this->firstJs);
        $output = trim($headerLib->output_js(false));
        $expected = trim($this->firstJs);
        $this->assertEquals($expected, $output, "duplicate js should not be duplicated");
    }
    public function testNoDuplicatesWithDifferentRank(): void
    {
        $headerLib = new HeaderLib();
        $headerLib->add_js($this->firstJs, 10)->add_js($this->firstJs, 20);
        $output = trim($headerLib->output_js(false));
        $expected = trim($this->firstJs);
        $this->assertEquals($expected, $output, "duplicate js should should not be duplicated, even if added at different rank");
    }
    public function testAddedAtRightRank(): void
    {
        $headerLib = new HeaderLib();
        $headerLib->add_js($this->firstJs, 20)->add_js($this->secondJs, 10);
        $output = trim($headerLib->output_js(false));
        $expected = trim($this->secondJs) . "\n" . trim($this->firstJs);
        $this->assertEquals($expected, $output, "Javascript should respect rank, even if added out of order");
    }
}
