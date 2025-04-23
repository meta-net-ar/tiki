<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Tests\Tracker\Tabular;

use \PHPUnit\Framework\TestCase;
use \Tracker\Tabular\ODBCManager;
use ReflectionClass;

class ODBCManagerTest extends TestCase
{
    protected $mock;
    protected $fillFieldsFromConfig;
    protected $reverseMapFieldsFromConfig;

    public function setUp(): void
    {
        $this->mock = new ODBCManager(json_decode(str_replace("\n", '', '{
"permanent_values": {"permField": "permVal"},
"value_mappings": {
    "type":{"-1":"y","0":"n"},
    "location1":{"1":"San Francisco"},
    "location2":{"1":"Venice","~replace~":"location1"},
    "location3":{"1":"Sydney","~replace~":"location1"},
    "location4":{"1":"London","~replace~":"location1"},
    "location5":{"1":"Paris","~replace~":"location1"},
    "location6":{"1":"~all:San Francisco,Venice,Sydney,London,Paris~","~replace~":"location1"},
    "type1":{"1":"830"},
    "type2":{"1":"920","~replace~":"type1"},
    "type3":{"1":"1100","~replace~":"type1"},
    "type4":{"1":"~type4_desc~","~replace~":"type1"},
    "type5":{"1":"120","~replace~":"type1"},
    "type4_desc":[]
}}'), true));
        $reflection = new ReflectionClass($this->mock);
        $this->fillFieldsFromConfig = $reflection->getMethod('fillFieldsFromConfig');
        $this->fillFieldsFromConfig->setAccessible(true);
        $this->reverseMapFieldsFromConfig = $reflection->getMethod('reverseMapFieldsFromConfig');
        $this->reverseMapFieldsFromConfig->setAccessible(true);
    }

    public function testPermanentValues()
    {
        $result = $this->fillFieldsFromConfig->invoke($this->mock, []);
        $this->assertEquals(['permField' => 'permVal'], $result);
    }

    public function testPlainConversion()
    {
        $result = $this->fillFieldsFromConfig->invoke($this->mock, ['type' => 'y']);
        $this->assertEquals('-1', $result['type']);
    }

    public function testLocationConversion()
    {
        $result = $this->fillFieldsFromConfig->invoke($this->mock, ['location1' => 'San Francisco']);
        $this->assertEquals('1', $result['location1']);
        $this->assertEquals('', $result['location2']);
    }

    public function testLocationFieldConversion()
    {
        $result = $this->fillFieldsFromConfig->invoke($this->mock, ['location1' => 'London']);
        $this->assertEquals('', $result['location1']);
        $this->assertEquals('', $result['location2']);
        $this->assertEquals('1', $result['location4']);
    }

    public function testLocationAllConversion()
    {
        $result = $this->fillFieldsFromConfig->invoke($this->mock, ['location1' => 'Venice,San Francisco,London,Paris,Sydney']);
        $this->assertEquals('', $result['location1']);
        $this->assertEquals('', $result['location2']);
        $this->assertEquals('1', $result['location6']);
    }

    public function testLocationSomeConversion()
    {
        $result = $this->fillFieldsFromConfig->invoke($this->mock, ['location1' => 'London,Paris']);
        $this->assertEquals('', $result['location1']);
        $this->assertEquals('', $result['location4']);
        $this->assertEquals('', $result['location5']);
    }

    public function testTypeConversion()
    {
        $result = $this->fillFieldsFromConfig->invoke($this->mock, ['type1' => '830']);
        $this->assertEquals('1', $result['type1']);
        $this->assertEquals('', $result['type2']);
        $this->assertEquals('', $result['type4']);
        $this->assertEquals('', $result['type4_desc']);
    }

    public function testTypeOtherConversion()
    {
        $result = $this->fillFieldsFromConfig->invoke($this->mock, ['type1' => 'nonexistent']);
        $this->assertEquals('', $result['type1']);
        $this->assertEquals('', $result['type2']);
        $this->assertEquals('1', $result['type4']);
        $this->assertEquals('nonexistent', $result['type4_desc']);
    }

    public function testReverseConversion()
    {
        $result = $this->reverseMapFieldsFromConfig->invoke($this->mock, ['type' => '-1']);
        $this->assertEquals('y', $result['type']);
    }

    public function testReverseLocationConversion()
    {
        $result = $this->reverseMapFieldsFromConfig->invoke($this->mock, ['location1' => '0', 'location3' => '1']);
        $this->assertEquals('Sydney', $result['location1']);
    }

    public function testReverseLocationAllConversion()
    {
        $result = $this->reverseMapFieldsFromConfig->invoke($this->mock, ['location1' => '0', 'location6' => '1']);
        $this->assertEquals('San Francisco,Venice,Sydney,London,Paris', $result['location1']);
    }

    public function testReverseTypeConversion()
    {
        $result = $this->reverseMapFieldsFromConfig->invoke($this->mock, ['type1' => '0', 'type3' => '1']);
        $this->assertEquals('1100', $result['type1']);
    }

    public function testReverseTypeOtherConversion()
    {
        $result = $this->reverseMapFieldsFromConfig->invoke($this->mock, ['type1' => '0', 'type4' => '1', 'type4_desc' => 'nonexistent']);
        $this->assertEquals('nonexistent', $result['type1']);
    }
}
