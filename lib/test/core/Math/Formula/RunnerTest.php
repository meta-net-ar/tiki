<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Math_Formula_RunnerTest extends TikiTestCase
{
    private $runner;

    protected function setUp(): void
    {
        $this->runner = new Math_Formula_Runner(
            [
                'Math_Formula_Function_' => null,
                'Math_Formula_DummyFunction_' => null,
            ]
        );
    }

    public function testSimpleOperations()
    {
        $this->runner->setFormula('(mul (add foobar 2) test-variable)');
        $required = $this->runner->inspect();

        $this->assertEquals(['foobar', 'test-variable'], $required);
    }

    public function testSimpleOperationPreparsed()
    {
        $parser = new Math_Formula_Parser();
        $element = $parser->parse('(mul (add 1 2) test)');

        $this->runner->setFormula($element);
        $required = $this->runner->inspect();

        $this->assertEquals(['test'], $required);
    }

    public function testUnknownOperator()
    {
        $this->expectException('Math_Formula_Runner_Exception');
        $this->runner->setFormula('(foobar abc)');
        $this->runner->inspect();
    }

    public function testNoFormulaSpecified()
    {
        $this->expectException('Math_Formula_Runner_Exception');
        $this->runner->inspect();
    }

    public function testSimpleEvaluation()
    {
        $this->runner->setFormula('(add 1 2)');
        $this->assertEquals(3, $this->runner->evaluate());
    }

    public function testSum()
    {
        $this->runner->setFormula('(add list)');
        $this->runner->setVariables(['list' => [1,2,3]]);
        $this->assertEquals(6, $this->runner->evaluate());
    }

    public function testMin()
    {
        $this->runner->setFormula('(min -10 0 20)');
        $this->assertEquals(-10, $this->runner->evaluate());

        $this->runner->setFormula('(min 10 20)');
        $this->assertEquals(10, $this->runner->evaluate());
    }

    public function testMax()
    {
        $this->runner->setFormula('(max -10 0 20)');
        $this->assertEquals(20, $this->runner->evaluate());

        $this->runner->setFormula('(max -10 -5)');
        $this->assertEquals(-5, $this->runner->evaluate());
    }

    public function testWithVariables()
    {
        $this->runner->setFormula('(mul foobar 2)');
        $this->runner->setVariables(['foobar' => 2.5,]);
        $this->assertEquals(5, $this->runner->evaluate());
    }

    public function testProductList()
    {
        $this->runner->setFormula('(mul list)');
        $this->runner->setVariables(['list' => [2.5,2,4]]);
        $this->assertEquals(20, $this->runner->evaluate());
    }

    public function testMissingVariable()
    {
        $this->expectException('Math_Formula_Exception');
        $this->runner->setFormula('(mul foobar 2)');
        $this->runner->evaluate();
    }

    public function testSearchingForConfiguration()
    {
        $this->runner->setFormula('(testop (object test 123) (concat 456))');
        $this->runner->setVariables(['test' => 'aaa']);

        $this->assertEquals(['test'], $this->runner->inspect());

        $this->assertEquals('aaa123456', $this->runner->evaluate());
    }

    public function testInvalidData()
    {
        $this->expectException('Math_Formula_Exception');
        $this->runner->setFormula('(testop (object test) (concat 456))');

        $this->runner->inspect();
    }

    public function testCamelCaseOperation()
    {
        $this->runner->setFormula('(forty-two)');
        $this->assertEquals(42, $this->runner->evaluate());
    }

    public function testEmptyMap()
    {
        $this->runner->setFormula('(map)');

        $this->assertEquals([], $this->runner->evaluate());
    }

    public function testGenerateMap()
    {
        $this->runner->setFormula('(map (a A) (b B))');
        $this->runner->setVariables(['A' => 1, 'B' => 2]);

        $this->assertEquals(['a' => 1, 'b' => 2], $this->runner->evaluate());
    }

    public function testEquals()
    {
        $this->runner->setFormula('(equals test 123)');

        $this->runner->setVariables(['test' => 123]);
        $this->assertEquals(1, $this->runner->evaluate());

        $this->runner->setVariables(['test' => 456]);
        $this->assertEquals(0, $this->runner->evaluate());
    }

    public function testNotEquals()
    {
        $this->runner->setFormula('(not-equals test 123)');

        $this->runner->setVariables(['test' => 123]);
        $this->assertEquals(0, $this->runner->evaluate());

        $this->runner->setVariables(['test' => 456]);
        $this->assertEquals(1, $this->runner->evaluate());
    }

    public function testContains()
    {
        $this->runner->setFormula('(contains test (1,2))');

        $this->runner->setVariables(['test' => 2]);
        $this->assertEquals(1, $this->runner->evaluate());

        $this->runner->setVariables(['test' => 3]);
        $this->assertEquals(0, $this->runner->evaluate());

        $this->runner->setFormula('(contains test args.values_by_permname.version)');

        $this->runner->setVariables(['test' => 2, 'args' => ['values_by_permname' => ['version' => "1,2"]]]);
        $this->assertEquals(1, $this->runner->evaluate());

        $this->runner->setVariables(['test' => 3, 'args' => ['values_by_permname' => ['version' => "1,2"]]]);
        $this->assertEquals(0, $this->runner->evaluate());
    }

    public function testIf()
    {
        $this->runner->setFormula('(if condition then else)');
        $this->runner->setVariables(
            [
                'condition' => 1,
                'then' => 123,
                'else' => 456,
            ]
        );

        $this->assertEquals(123, $this->runner->evaluate());

        $this->runner->setVariables(
            [
                'condition' => 0,
                'then' => 123,
                'else' => 456,
            ]
        );

        $this->assertEquals(456, $this->runner->evaluate());
    }

    public function testIfWithoutElse()
    {
        $this->runner->setFormula('(if condition then)');
        $this->runner->setVariables(
            [
                'condition' => 1,
                'then' => 123,
            ]
        );

        $this->assertEquals(123, $this->runner->evaluate());

        $this->runner->setVariables(
            [
                'condition' => 0,
                'then' => 123,
            ]
        );

        $this->assertEquals(0, $this->runner->evaluate());
    }

    public function testAnd()
    {
        $this->runner->setFormula('(and)');
        $this->assertEquals(0, $this->runner->evaluate());

        $this->runner->setFormula('(and 0)');
        $this->assertEquals(0, $this->runner->evaluate());

        $this->runner->setFormula('(and 1 1 0 1 1)');
        $this->assertEquals(0, $this->runner->evaluate());

        $this->runner->setFormula('(and 1 1 1 1 0)');
        $this->assertEquals(0, $this->runner->evaluate());

        $this->runner->setFormula('(and 1)');
        $this->assertEquals(1, $this->runner->evaluate());

        $this->runner->setFormula('(and 1 1 1 2 1)');
        $this->assertEquals(1, $this->runner->evaluate());
    }

    public function testOr()
    {
        $this->runner->setFormula('(or)');
        $this->assertEquals(0, $this->runner->evaluate());

        $this->runner->setFormula('(or 0)');
        $this->assertEquals(0, $this->runner->evaluate());

        $this->runner->setFormula('(or 1 1 0 1 1)');
        $this->assertEquals(1, $this->runner->evaluate());

        $this->runner->setFormula('(or 1 1 1 1 0)');
        $this->assertEquals(1, $this->runner->evaluate());

        $this->runner->setFormula('(or 1)');
        $this->assertEquals(1, $this->runner->evaluate());

        $this->runner->setFormula('(or 0 0 0 0 0)');
        $this->assertEquals(0, $this->runner->evaluate());
    }

    public function testExtractParts()
    {
        $this->runner->setFormula('(split-list (content string) (separator :) (keys object-type object-id))');
        $this->runner->setVariables(
            [
                'string' => "wiki page:HomePage\ntrackeritem:2\ntrackeritem:3",
            ]
        );

        $this->assertEquals([
            ['object-type' => 'wiki page', 'object-id' => 'HomePage'],
            ['object-type' => 'trackeritem', 'object-id' => '2'],
            ['object-type' => 'trackeritem', 'object-id' => '3'],
        ], $this->runner->evaluate());
    }

    public function testSplitWithSingleKey()
    {
        $this->runner->setFormula('(split-list (content string) (separator ,) (key id))');
        $this->runner->setVariables(
            [
                'string' => "214,266,711",
            ]
        );

        $this->assertEquals([
            ['id' => '214'],
            ['id' => '266'],
            ['id' => '711'],
        ], $this->runner->evaluate());
    }

    public function testMapList()
    {
        $this->runner->setFormula('(for-each (list list) (formula (mul a b c)))');
        $this->runner->setVariables([
            'c' => 10,
            'list' => [
                ['a' => 1, 'b' => 2],
                ['a' => 2, 'b' => 3],
                ['a' => 3, 'b' => 4],
            ],
        ]);

        $this->assertEquals([20, 60, 120], $this->runner->evaluate());
    }

    public function testAverageList()
    {
        $this->runner->setFormula('(avg a b)');
        $this->runner->setVariables([
            'a' => 1,
            'b' => [3, 5, 7],
        ]);

        $this->assertEquals(4, $this->runner->evaluate());
    }

    /**
     * @dataProvider stringConcats
     * @param $in
     * @param $out
     */
    public function testStringConcat($in, $out)
    {
        $this->runner->setFormula($in);
        $this->runner->setVariables([
            'a' => 'hello',
            'b' => 'world',
        ]);

        $this->assertEquals($out, $this->runner->evaluate());
    }

    public static function stringConcats()
    {
        return [
            ['(str a b)', 'a b'],
            ['(str (mul 3 2)b)', '6 b'],
            ['(str Say: (eval a b) !)', 'Say: hello world !'],
            ['(concat "Say: " a " " b " !")', 'Say: hello world !'],
            ['(str "Say:" a b "!")', 'Say: a b !'],
        ];
    }

    public function testStrToTime()
    {
        $this->runner->setFormula('(str-to-time (str +1 day) a)');
        $this->runner->setVariables(['a' => strtotime('2024-11-20')]);
        $this->assertEquals('2024-11-21', date('Y-m-d', $this->runner->evaluate()));
    }

    public function testStrToTimeDateDiffNonWorkingDays()
    {
        global $prefs;
        $origPrefs = $prefs;

        $prefs['calendar_holidays'] = $calendarId = TikiLib::lib('calendar')->set_calendar(0, 'admin', 'Holidays', '');

        // setup recurring weekends as holidays
        $weekends = new CalRecurrence();
        $weekends->setCalendarId($calendarId);
        $weekends->setName('Weekends');
        $weekends->setLang('en');
        $weekends->setPriority(1);
        $weekends->setStart(strtotime('2024-12-01'));
        $weekends->setEnd(strtotime('2024-12-01'));
        $weekends->setStartPeriod(strtotime('2024-12-01'));
        $weekends->setAllday(true);
        $weekends->setWeekly(true);
        $weekends->setWeekdays('SU,SA');
        $weekends->setEndPeriod(strtotime('2025-01-31'));
        $weekends->save();

        // setup recurring new year day as holiday
        $newyear = new CalRecurrence();
        $newyear->setCalendarId($calendarId);
        $newyear->setName('New Year');
        $newyear->setLang('en');
        $newyear->setPriority(1);
        $newyear->setStart(strtotime('2025-01-01'));
        $newyear->setEnd(strtotime('2025-01-01'));
        $newyear->setStartPeriod(strtotime('2025-01-01'));
        $newyear->setAllday(true);
        $newyear->setYearly(true);
        $newyear->setNbRecurrences(2);
        $newyear->setYearlyType('date');
        $newyear->setDateOfYear('101');
        $newyear->save();

        // setup a one-time public holiday
        TikiLib::lib('calendar')->set_item('admin', 0, [
            'calendarId' => $calendarId,
            'start' => strtotime('2024-12-24 00:00:00'),
            'end' => strtotime('2024-12-26 23:59:59'),
            'name' => 'Christmas',
            'description' => '',
        ]);

        $this->runner->setFormula('(str-to-time (str +10 working days) a)');
        $this->runner->setVariables(['a' => strtotime('2024-12-20')]);
        $evaluated = $this->runner->evaluate();

        $this->runner->setFormula('(round (div (date-diff a b) 86400))');
        $this->runner->setVariables(['a' => strtotime('2024-12-30'), 'b' => strtotime('2024-12-20')]);
        $calendarDiff = $this->runner->evaluate();

        $this->runner->setFormula('(round (div (date-diff a b 1) 86400))');
        $this->runner->setVariables(['a' => strtotime('2024-12-30'), 'b' => strtotime('2024-12-20')]);
        $workingDiff = $this->runner->evaluate();
        $this->runner->setVariables(['a' => '2024-12-20', 'b' => '2024-12-30']);
        $negativeWorkingDateDiff = $this->runner->evaluate();

        // cleanup
        $weekends->delete(strtotime('2024-01-01'));
        $newyear->delete(strtotime('2024-01-01'));
        TikiLib::lib('calendar')->drop_calendar($calendarId);
        $prefs = $origPrefs;

        // asset calculations are correct
        $this->assertEquals('2025-01-09', date('Y-m-d', $evaluated));
        $this->assertEquals(10, $calendarDiff);
        $this->assertEquals(3, $workingDiff);
        $this->assertEquals(-3, $negativeWorkingDateDiff);
    }
}
