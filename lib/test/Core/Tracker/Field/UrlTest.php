<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace TikiTests;

class TrackerFieldUrlTest extends \PHPUnit\Framework\TestCase
{
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

    private function invokeIsWikiSyntaxLink(string $value): bool
    {
        $method = new \ReflectionMethod(\Tracker_Field_Url::class, 'isWikiSyntaxLink');
        $method->setAccessible(true);

        return (bool) $method->invoke(null, $value);
    }
}
