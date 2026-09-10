<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace TikiTests;

class TrackerFieldComputedTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider formulaProvider
     */
    public function testFormulaEvaluation(string $formula, array $values, int|float $expected): void
    {
        $this->assertSame($expected, $this->evaluate($formula, $values));
    }

    public static function formulaProvider(): array
    {
        return [
            'multiplication' => ['#1 * #2', ['1' => '1234567.89', '2' => '1'], 1234567.89],
            'addition' => ['#1 + #2', ['1' => '2', '2' => '3'], 5],
            'subtraction' => ['#1 - #2', ['1' => '9', '2' => '4'], 5],
            'division' => ['#1 / #2', ['1' => '10', '2' => '4'], 2.5],
            'parentheses' => ['(#1 + #2) * 2', ['1' => '2', '2' => '3'], 10],
            'literal only, no field reference' => ['2 + 3', [], 5],
            'empty operand counts as zero' => ['#1 * #2', ['1' => '10', '2' => ''], 0],
            'whitespace operand counts as zero' => ['#1 * #2', ['1' => '10', '2' => '  '], 0],
            'unset operand counts as zero' => ['#1 * #2', ['1' => '10'], 0],
            'non numeric operand counts as zero' => ['#1 * #2', ['1' => '10', '2' => 'abc'], 0],
            'comma decimal operand' => ['#1 * #2', ['1' => '12,5', '2' => '2'], 25.0],
        ];
    }

    /**
     * @dataProvider rejectedFormulaProvider
     */
    public function testDisallowedFormulasAreRejected(string $formula, array $values): void
    {
        $this->assertNull($this->evaluate($formula, $values));
    }

    public static function rejectedFormulaProvider(): array
    {
        return [
            'statement separator' => ['1 + 2 ; phpinfo()', []],
            'function call' => ['phpinfo()', []],
            'variable' => ['$GLOBALS', []],
            'unresolved placeholder text' => ['#<field id> * 2', []],
            'letters from a field reference that does not resolve' => ['#1 * #2abc', ['1' => '1']],
        ];
    }

    private function evaluate(string $formula, array $values): int|float|null
    {
        $method = new \ReflectionMethod(\Tracker_Field_Computed::class, 'evaluateFormula');

        return $method->invoke(null, $formula, $values);
    }
}
