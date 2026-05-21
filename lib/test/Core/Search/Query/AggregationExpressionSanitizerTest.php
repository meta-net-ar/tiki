<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Search\Query;

use PHPUnit\Framework\TestCase;
use Search\Query\Aggregation\ExpressionSanitizer;
use InvalidArgumentException;

class AggregationExpressionSanitizerTest extends TestCase
{
    private ExpressionSanitizer $sanitizer;

    protected function setUp(): void
    {
        $this->sanitizer = new ExpressionSanitizer();
    }

    /**
     * Anything in this list is intended to pass through untouched - they
     * represent realistic reporting formulas. The sanitizer is supposed
     * to be liberal with legit math/string/date expressions and only
     * block injection vectors.
     */
    public function safeExpressionProvider(): array
    {
        return [
            'simple arithmetic' => ['amount * quantity'],
            'sum over column' => ['SUM(amount)'],
            'ratio of metrics' => ['revenue / NULLIF(orders, 0)'],
            'case expression' => ['CASE WHEN status = 1 THEN amount ELSE 0 END'],
            'window function' => ['SUM(amount) OVER (PARTITION BY vendor ORDER BY ts)'],
            'extract from date' => ['EXTRACT(YEAR FROM created_at)'],
            'left function' => ['LEFT(name, 3)'],
            'right function' => ['RIGHT(name, 3)'],
            'left join keyword as column ref via extract' => ['EXTRACT(MONTH FROM ts) + 1'],
            'string concat' => ["CONCAT(first_name, ' ', last_name)"],
            'cast' => ['CAST(amount AS DECIMAL)'],
            'nested calls' => ['ROUND(AVG(amount), 2)'],
            'comparison' => ['amount > 1000'],
            'logical' => ['amount > 0 AND quantity IS NOT NULL'],
            'in list' => ['status IN (1, 2, 3)'],
            'string literal' => ["status = 'open'"],
        ];
    }

    /** @dataProvider safeExpressionProvider */
    public function testSafeExpressionsAreAcceptedUnchanged(string $expr)
    {
        $this->assertSame(trim($expr), $this->sanitizer->sanitize($expr));
    }

    /**
     * Each of these is a known SQL-injection pattern. They must all
     * raise InvalidArgumentException with a descriptive message - the
     * sanitizer is the last line of defense before the compiler splices
     * the string into a SELECT clause.
     */
    public function unsafeExpressionProvider(): array
    {
        return [
            'second statement' => ['1; DROP TABLE users', 'multiple statements'],
            'line comment' => ['amount -- bypass', 'comments'],
            'block comment' => ['amount /* bypass */ + 1', 'comments'],
            'hash comment' => ['amount # bypass', 'comments'],
            'backtick identifier' => ['`mysql`.`user`', 'backtick'],
            'session variable' => ['@@version', 'session/user variables'],
            'user variable' => ['@x := 1', 'session/user variables'],
            'backslash escape' => ["amount\\nfoo", 'backslash'],
            'union select' => ['1 UNION SELECT password FROM users', 'forbidden keyword "select"'],
            'into outfile' => ["1 INTO OUTFILE '/tmp/x'", 'forbidden keyword "into"'],
            'load_file call' => ["LOAD_FILE('/etc/passwd')", 'forbidden keyword "load_file"'],
            'benchmark dos' => ['BENCHMARK(1000000, MD5(1))', 'forbidden keyword "benchmark"'],
            'sleep dos' => ['SLEEP(10)', 'forbidden keyword "sleep"'],
            'database lookup' => ['DATABASE()', 'forbidden keyword "database"'],
            'version lookup' => ['VERSION()', 'forbidden keyword "version"'],
            'drop attempt' => ['DROP TABLE x', 'forbidden keyword "drop"'],
            'subquery select' => ['(SELECT 1)', 'forbidden keyword "select"'],
        ];
    }

    /** @dataProvider unsafeExpressionProvider */
    public function testUnsafeExpressionsAreRejected(string $expr, string $expectedFragment)
    {
        try {
            $this->sanitizer->sanitize($expr);
            $this->fail(sprintf('expected sanitize(%s) to throw', var_export($expr, true)));
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsStringIgnoringCase($expectedFragment, $e->getMessage());
        }
    }

    public function testEmptyExpressionIsRejected()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('empty');
        $this->sanitizer->sanitize('   ');
    }

    public function testTooLongExpressionIsRejected()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('too long');
        $this->sanitizer->sanitize(str_repeat('a + ', 2000) . 'b');
    }

    public function testUnbalancedParenthesesAreRejected()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('parentheses');
        $this->sanitizer->sanitize('SUM(amount');
    }

    public function aggregateProvider(): array
    {
        return [
            ['SUM(amount)', true],
            ['avg(amount)', true],
            ['COUNT(*)', true],
            ['MIN(ts)', true],
            ['MAX(ts)', true],
            ['GROUP_CONCAT(name)', true],
            ['STDDEV(amount)', true],
            ['JSON_ARRAYAGG(name)', true],
            ['amount * quantity', false],
            ['ROUND(amount, 2)', false],
            ['CASE WHEN x THEN 1 ELSE 0 END', false],
            // a column literally named "summary" must not be mistaken for SUM(
            ['summary + 1', false],
        ];
    }

    /** @dataProvider aggregateProvider */
    public function testIsAggregateDetection(string $expr, bool $expected)
    {
        $this->assertSame($expected, $this->sanitizer->isAggregate($expr));
    }

    public function testExtractIdentifiersStripsKeywordsAndFunctions()
    {
        $idents = $this->sanitizer->extractIdentifiers('SUM(amount) / NULLIF(COUNT(*), 0) + revenue');
        sort($idents);
        $this->assertSame(['amount', 'revenue'], $idents);
    }

    public function testExtractIdentifiersHandlesCaseExpression()
    {
        $idents = $this->sanitizer->extractIdentifiers(
            'CASE WHEN status = 1 THEN amount ELSE fallback END'
        );
        sort($idents);
        $this->assertSame(['amount', 'fallback', 'status'], $idents);
    }

    public function testExtractIdentifiersPreservesOriginalCase()
    {
        $idents = $this->sanitizer->extractIdentifiers(
            'AVG(tracker_field_mixedCase - tracker_field_mixedCase2)'
        );
        sort($idents);
        $this->assertSame(
            ['tracker_field_mixedCase', 'tracker_field_mixedCase2'],
            $idents
        );
    }

    public function testExtractIdentifiersDeduplicatesCaseInsensitively()
    {

        $idents = $this->sanitizer->extractIdentifiers('Vendor + VENDOR + vendor');
        $this->assertSame(['Vendor'], $idents);
    }

    public function testExtractIdentifiersOnEmptyStringReturnsEmptyArray()
    {
        $this->assertSame([], $this->sanitizer->extractIdentifiers(''));
    }
}
