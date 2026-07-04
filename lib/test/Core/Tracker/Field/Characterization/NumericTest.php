<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Lib\Test\Core\Tracker\Field\Characterization;

use Tiki\Lib\Test\TestHelpers\Tracker\FieldCharacterizationTestCase;

/**
 * Characterization of Tracker_Field_Numeric (type 'n').
 *
 * getFieldData() passes the raw request value straight through with no
 * numeric validation; formatting happens only at render time. Records
 * current behavior — see FieldCharacterizationTestCase.
 *
 * @group integration
 * @group tracker
 */
class NumericTest extends FieldCharacterizationTestCase
{
    protected static function typeCode(): string
    {
        return 'n';
    }

    protected static function fieldSpecs(): array
    {
        return [[
            'name' => 'Numeric field',
            'permName' => 'charNumeric',
            'type' => 'n',
        ]];
    }

    public static function inputCaseProvider(): array
    {
        return [
            'integer value' => [
                ['{{ins}}' => '42'],
                ['value' => '42'],
            ],
            'decimal value' => [
                ['{{ins}}' => '3.5'],
                ['value' => '3.5'],
            ],
            'non-numeric string passes through unchanged (pins current behavior)' => [
                ['{{ins}}' => 'not a number'],
                ['value' => 'not a number'],
            ],
            'empty value' => [
                ['{{ins}}' => ''],
                ['value' => ''],
            ],
        ];
    }

    public static function roundTripCaseProvider(): array
    {
        return [
            'integer' => ['42', '42', '42'],
            'decimal' => ['3.5', '3.5', '3.5'],
            'zero' => ['0', '0', '0'],
            'empty' => ['', '', ''],
        ];
    }

    public static function renderCaseProvider(): array
    {
        return [
            'integer value' => [
                '42',
                '42',
                ['name="{{ins}}"'],
                ['42'],
            ],
            'empty value' => [
                '',
                '',
                ['name="{{ins}}"'],
                [],
            ],
        ];
    }
}
