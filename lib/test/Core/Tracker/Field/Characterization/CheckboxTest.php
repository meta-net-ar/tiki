<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Lib\Test\Core\Tracker\Field\Characterization;

use Tiki\Lib\Test\TestHelpers\Tracker\FieldCharacterizationTestCase;

/**
 * Characterization of Tracker_Field_Checkbox (type 'c').
 *
 * getFieldData() normalizes to the two-value domain 'y'/'n': any non-empty
 * input other than the literal 'n' becomes 'y', the side-channel '<ins>_old'
 * marks an unchecked box as 'n', and an empty/absent input falls back to the
 * stored value (defaulting to 'n'). Records current behavior — see
 * FieldCharacterizationTestCase.
 *
 * @group integration
 * @group tracker
 */
class CheckboxTest extends FieldCharacterizationTestCase
{
    protected static function typeCode(): string
    {
        return 'c';
    }

    protected static function fieldSpecs(): array
    {
        return [[
            'name' => 'Checkbox field',
            'permName' => 'charCheckbox',
            'type' => 'c',
        ]];
    }

    public static function inputCaseProvider(): array
    {
        return [
            'checked via "on"' => [
                ['{{ins}}' => 'on'],
                ['value' => 'y'],
            ],
            'checked via "y"' => [
                ['{{ins}}' => 'y'],
                ['value' => 'y'],
            ],
            'explicit "n" stays "n"' => [
                ['{{ins}}' => 'n'],
                ['value' => 'n'],
            ],
            '_old marker with no new input means unchecked' => [
                ['{{ins}}_old' => 'y'],
                ['value' => 'n'],
            ],
            'absent input falls back to default "n"' => [
                [],
                ['value' => 'n'],
            ],
        ];
    }

    public static function roundTripCaseProvider(): array
    {
        return [
            'checked' => ['y', 'y', 'y'],
            'unchecked' => ['n', 'n', 'n'],
        ];
    }

    public static function renderCaseProvider(): array
    {
        return [
            'checked' => [
                'y',
                'y',
                ['name="{{ins}}"'],
                ['Yes'],
            ],
            'unchecked' => [
                'n',
                'n',
                ['name="{{ins}}"'],
                ['No'],
            ],
        ];
    }
}
