<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Lib\Test\Core\Tracker\Field\Characterization;

use Tiki\Lib\Test\TestHelpers\Tracker\FieldCharacterizationTestCase;

/**
 * Characterization of Tracker_Field_TextArea (type 'a').
 *
 * TextArea extends Text but adds a getFieldData() that runs the value through
 * the parser's save-plugins pass (a no-op for plain text) and truncates in
 * list mode. Records current behavior — see FieldCharacterizationTestCase.
 *
 * @group integration
 * @group tracker
 */
class TextAreaTest extends FieldCharacterizationTestCase
{
    protected static function typeCode(): string
    {
        return 'a';
    }

    protected static function fieldSpecs(): array
    {
        return [[
            'name' => 'Text area field',
            'permName' => 'charTextArea',
            'type' => 'a',
        ]];
    }

    public static function inputCaseProvider(): array
    {
        return [
            'plain multi-line value' => [
                ['{{ins}}' => "line one\nline two"],
                ['value' => "line one\nline two"],
            ],
            'empty value' => [
                ['{{ins}}' => ''],
                ['value' => ''],
            ],
            'absent input falls back to stored value (empty on a new item)' => [
                [],
                ['value' => ''],
            ],
        ];
    }

    public static function roundTripCaseProvider(): array
    {
        return [
            'plain' => ['hello area', 'hello area', 'hello area'],
            'empty' => ['', '', ''],
            'multi-line preserved' => ["a\nb\nc", "a\nb\nc", "a\nb\nc"],
            'utf8 preserved' => ['héllo — wörld', 'héllo — wörld', 'héllo — wörld'],
        ];
    }

    public static function renderCaseProvider(): array
    {
        // Only the CSV path is pinned: TextArea's full renderInput() goes
        // through the {textarea} Smarty block (editor toolbar / quicktags),
        // which cannot run headless — see rendersFullHtml(). Input/output
        // markers are therefore not exercised (empty arrays).
        return [
            'plain value' => [
                'hello area',
                'hello area',
                [],
                [],
            ],
            'empty value' => [
                '',
                '',
                [],
                [],
            ],
        ];
    }

    protected static function rendersFullHtml(): bool
    {
        return false;
    }
}
