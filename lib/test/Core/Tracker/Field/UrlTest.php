<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace TikiTests;

class TrackerFieldUrlTest extends \PHPUnit\Framework\TestCase
{
    private $oldStrictValidationPref;

    protected function setUp(): void
    {
        global $prefs;

        $this->oldStrictValidationPref = $prefs['trackerfield_url_strict_validation'] ?? null;
    }

    protected function tearDown(): void
    {
        global $prefs;

        if ($this->oldStrictValidationPref === null) {
            unset($prefs['trackerfield_url_strict_validation']);
        } else {
            $prefs['trackerfield_url_strict_validation'] = $this->oldStrictValidationPref;
        }
    }

    /**
     * @dataProvider supportedWikiSyntaxProvider
     */
    public function testIsWikiSyntaxLinkSupportedInputs(string $value): void
    {
        $this->assertTrue($this->invokeIsWikiSyntaxLink(trim($value)));
    }

    /**
     * @dataProvider unsupportedWikiSyntaxProvider
     */
    public function testIsWikiSyntaxLinkUnsupportedInputs(string $value): void
    {
        $this->assertFalse($this->invokeIsWikiSyntaxLink(trim($value)));
    }

    public static function supportedWikiSyntaxProvider(): array
    {
        return [
            'wikilink' => ['((PageName))'],
            'wikilink with spaces around input' => ['  ((PageName))  '],
            'external link with label' => ['[https://example.org|Example]'],
            'external link with spaces around input' => ['  [https://example.org|Example]  '],
        ];
    }

    public static function unsupportedWikiSyntaxProvider(): array
    {
        return [
            'plain url' => ['https://example.org'],
            'single parenthesis syntax is unsupported' => ['(PageName)'],
            'broken wikilink prefix only' => ['((PageName'],
            'broken bracket syntax suffix only' => ['https://example.org|Example]'],
            'escaped bracket syntax is out of scope' => ['\[https://example.org|Example]'],
            'mixed content around syntax is out of scope' => ['prefix ((PageName)) suffix'],
        ];
    }

    public function testStrictValidationPrefDisabledAllowsInvalidUrlValues(): void
    {
        global $prefs;

        $prefs['trackerfield_url_strict_validation'] = 'n';

        $this->assertTrue($this->invokeIsValid('not a valid url'));
    }

    public function testStrictValidationPrefEnabledRejectsInvalidUrlValues(): void
    {
        global $prefs;

        $prefs['trackerfield_url_strict_validation'] = 'y';

        $this->assertSame(tr('Invalid URL syntax.'), $this->invokeIsValid('not a valid url'));
    }

    private function invokeIsWikiSyntaxLink(string $value): bool
    {
        $method = new \ReflectionMethod(\Tracker_Field_Url::class, 'isWikiSyntaxLink');
        $method->setAccessible(true);

        return (bool) $method->invoke(null, $value);
    }

    private function invokeIsValid(string $value)
    {
        $field = $this->getMockBuilder(\Tracker_Field_Url::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getFieldId', 'getValue'])
            ->getMock();

        $field->method('getFieldId')->willReturn(1);
        $field->method('getValue')->willReturn('');

        return $field->isValid([
            1 => ['value' => $value],
        ]);
    }
}
