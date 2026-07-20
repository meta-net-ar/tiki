<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Lib\Test\Core\Tracker\Field\Characterization;

use Tiki\Lib\Test\TestHelpers\Tracker\FieldCharacterizationTestCase;

/**
 * Characterization of Tracker_Field_Dropdown (type 'd'; the class also
 * registers 'D', 'R' and 'M').
 *
 * The exemplar for quirky field types: getFieldData() returns extra keys
 * ('selected', 'possibilities'), consumes the side-channel inputs
 * 'other_<ins>' (free-text override) and '<ins>_old' (clear marker), and the
 * type is Enumerable.
 *
 * @group integration
 * @group tracker
 */
class DropdownTest extends FieldCharacterizationTestCase
{
    protected static function typeCode(): string
    {
        return 'd';
    }

    protected static function fieldSpecs(): array
    {
        return [[
            'name' => 'Dropdown field',
            'permName' => 'charDropdown',
            'type' => 'd',
            'options' => ['options' => ['red', 'green', 'blue']],
        ]];
    }

    public static function inputCaseProvider(): array
    {
        return [
            'single selection' => [
                ['{{ins}}' => 'green'],
                ['value' => 'green', 'selected' => ['green']],
            ],
            'array input imploded to csv' => [
                ['{{ins}}' => ['red', 'blue']],
                ['value' => 'red,blue', 'selected' => ['red', 'blue']],
            ],
            '"other" free text wins over the selection' => [
                ['other_{{ins}}' => 'purple', '{{ins}}' => 'red'],
                ['value' => 'purple', 'selected' => ['purple']],
            ],
            '_old marker with no new input means cleared' => [
                ['{{ins}}_old' => 'red'],
                ['value' => '', 'selected' => []],
            ],
            'absent input falls back to default (empty here)' => [
                [],
                ['value' => ''],
            ],
            'value outside the options list is NOT rejected (pins current behavior)' => [
                ['{{ins}}' => 'mauve'],
                ['value' => 'mauve', 'selected' => ['mauve']],
            ],
            'possibilities lists the configured options' => [
                ['{{ins}}' => 'red'],
                ['possibilities' => ['red' => 'red', 'green' => 'green', 'blue' => 'blue']],
            ],
        ];
    }

    public static function roundTripCaseProvider(): array
    {
        return [
            'single' => ['green', 'green', 'green'],
            'multiple as csv' => ['red,blue', 'red,blue', 'red,blue'],
            'empty' => ['', '', ''],
        ];
    }

    public static function renderCaseProvider(): array
    {
        return [
            'selected option' => [
                'green',
                'green',
                ['<select', 'name="{{ins}}"'],
                ['green'],
            ],
        ];
    }

    protected static function expectedEnumeration(): array
    {
        return ['red' => 'red', 'green' => 'green', 'blue' => 'blue'];
    }
}
