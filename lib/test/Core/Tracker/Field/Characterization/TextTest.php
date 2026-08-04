<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Lib\Test\Core\Tracker\Field\Characterization;

use Tiki\Lib\Test\TestHelpers\Tracker\FieldCharacterizationTestCase;

/**
 * Characterization of Tracker_Field_Text (type 't').
 *
 * Records current behavior — see FieldCharacterizationTestCase for the
 * format. The exemplar for simple value-like field types.
 *
 * @group integration
 * @group tracker
 */
class TextTest extends FieldCharacterizationTestCase
{
    protected static function typeCode(): string
    {
        return 't';
    }

    protected static function fieldSpecs(): array
    {
        return [[
            'name' => 'Text field',
            'permName' => 'charText',
            'type' => 't',
        ]];
    }

    public static function inputCaseProvider(): array
    {
        return [
            'plain value' => [
                ['{{ins}}' => 'hello world'],
                ['value' => 'hello world'],
            ],
            'empty value' => [
                ['{{ins}}' => ''],
                ['value' => ''],
            ],
            'absent input falls back to stored value (empty on a new item)' => [
                [],
                ['value' => ''],
            ],
            'numeric string passes through unchanged' => [
                ['{{ins}}' => '007'],
                ['value' => '007'],
            ],
        ];
    }

    public static function roundTripCaseProvider(): array
    {
        return [
            'plain' => ['hello', 'hello', 'hello'],
            'empty' => ['', '', ''],
            'utf8 preserved' => ['héllo wörld — ok', 'héllo wörld — ok', 'héllo wörld — ok'],
        ];
    }

    public static function renderCaseProvider(): array
    {
        return [
            'plain value' => [
                'hello',
                'hello',
                ['name="{{ins}}"', 'value="hello"'],
                ['hello'],
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
