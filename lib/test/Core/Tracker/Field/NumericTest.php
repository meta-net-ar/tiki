<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace TikiTests;

class TrackerFieldNumericTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider separatorProvider
     */
    public function testSeparatorOptionsAreTranslatedBeforeFormatting(string $decPoint, string $thousands, string $expected): void
    {
        $this->assertSame($expected, $this->render(1234567.89, 2, $decPoint, $thousands));
    }

    public static function separatorProvider(): array
    {
        return [
            'shorthand: comma decimal, space thousands' => ['c', 's', '1 234 567,89'],
            'shorthand: dot decimal, comma thousands' => ['d', 'c', '1,234,567.89'],
            'shorthand: comma decimal, dot thousands' => ['c', 'd', '1.234.567,89'],
            'shorthand: space decimal, dot thousands' => ['s', 'd', '1.234.567 89'],
            'literal: comma decimal, space thousands' => [',', ' ', '1 234 567,89'],
            'literal: dot decimal, comma thousands' => ['.', ',', '1,234,567.89'],
        ];
    }

    public function testNoSeparatorOptionsFallsBackToPlainValue(): void
    {
        $this->assertSame('1234567.89', $this->render(1234567.89, '', '', ''));
    }

    public function testThousandsOnlyRoundsToWholeNumber(): void
    {
        $this->assertSame('1,234,568', $this->render(1234567.89, '', '', 'c'));
    }

    public function testNonNumericValueIsReturnedUntouched(): void
    {
        $this->assertSame('not a number', $this->render('not a number', 2, 'c', 's'));
    }

    /**
     * @dataProvider prependAppendProvider
     */
    public function testPrependAndAppendWrapTheFormattedValue(string $prepend, string $append, string $expected): void
    {
        $this->assertSame($expected, $this->render(1234.5, 2, 'c', 's', $prepend, $append));
    }

    public static function prependAppendProvider(): array
    {
        return [
            'prepend only' => ['~', '', '~1 234,50'],
            'append only' => ['', ' g', '1 234,50 g'],
            'both' => ['~', ' g', '~1 234,50 g'],
        ];
    }

    private function render(int|float|string $value, int|string $decimals, string $decPoint, string $thousands, string $prepend = '', string $append = ''): string
    {
        $options = [
            'decimals' => $decimals,
            'dec_point' => $decPoint,
            'thousands' => $thousands,
            'prepend' => $prepend,
            'append' => $append,
        ];

        $field = $this->getMockBuilder(\Tracker_Field_Numeric::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getValue', 'getOption'])
            ->getMock();

        $field->method('getValue')->willReturn($value);
        $field->method('getOption')->willReturnCallback(
            function (string $name, $default = null) use ($options) {
                return $options[$name] ?? $default;
            }
        );

        return (string) $field->renderInnerOutput();
    }
}
