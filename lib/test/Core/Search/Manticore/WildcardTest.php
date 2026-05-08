<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Search\Manticore;

use ReflectionMethod;
use Search_Expr_Token;
use Search_Query;

/**
 * Verifies how the Manticore QueryBuilder turns wildcard ("*foo*") and
 * regex-special user input into RE2 patterns inside REGEX() calls.
 *
 * The object selector (and similar callers) wrap user input with "*" to
 * emulate substring search. Because Manticore evaluates these patterns
 * with RE2, a leading "*" is invalid and previously caused a
 * "no argument for repetition operator: *" parse error.
 */
class WildcardTest extends \PHPUnit\Framework\TestCase
{
    use IndexBuilder;

    public $index;
    private $old_prefs;

    protected function setUp(): void
    {
        global $prefs;
        $this->old_prefs = $prefs;

        $this->index = $this->createIndex('_wildcard');
        $this->index->destroy();

        $typeFactory = $this->index->getTypeFactory();
        $this->index->addDocument(
            [
                'object_type' => $typeFactory->identifier('trackeritem'),
                'object_id' => $typeFactory->identifier('1'),
                'title' => $typeFactory->plaintext('Michaela Smith'),
                'tracker_field_name' => $typeFactory->sortable('Michaela Smith'),
            ]
        );
        $this->index->addDocument(
            [
                'object_type' => $typeFactory->identifier('trackeritem'),
                'object_id' => $typeFactory->identifier('2'),
                'title' => $typeFactory->plaintext('John Doe'),
                'tracker_field_name' => $typeFactory->sortable('John Doe'),
            ]
        );
        $this->index->addDocument(
            [
                'object_type' => $typeFactory->identifier('trackeritem'),
                'object_id' => $typeFactory->identifier('3'),
                'title' => $typeFactory->plaintext('first.middle last'),
                'tracker_field_name' => $typeFactory->sortable('first.middle last'),
            ]
        );
        $this->index->addDocument(
            [
                'object_type' => $typeFactory->identifier('trackeritem'),
                'object_id' => $typeFactory->identifier('4'),
                'title' => $typeFactory->plaintext('firstXmiddle last'),
                'tracker_field_name' => $typeFactory->sortable('firstXmiddle last'),
            ]
        );
    }

    protected function tearDown(): void
    {
        global $prefs;
        $prefs = $this->old_prefs;

        if ($this->index) {
            $this->index->destroy();
        }
    }

    public function testLeadingAndTrailingWildcardsAreAccepted()
    {
        $query = $this->newQuery();
        $query->filterContent('*michaela*', 'tracker_field_name');
        $this->assertCount(1, $query->search($this->index));
    }

    public function testWildcardOnlyMatchesAnything()
    {
        $query = $this->newQuery();
        $query->filterContent('*', 'tracker_field_name');
        $this->assertCount(4, $query->search($this->index));
    }

    public function testInnerWildcardActsAsGlob()
    {
        $query = $this->newQuery();
        $query->filterContent('first*last', 'tracker_field_name');
        // Should match both "first.middle last" and "firstXmiddle last".
        $this->assertCount(2, $query->search($this->index));
    }

    public function testRegexMetacharactersAreTreatedLiterally()
    {
        $query = $this->newQuery();
        $query->filterContent('first.middle', 'tracker_field_name');
        $this->assertCount(1, $query->search($this->index));
    }

    public function testWildcardToRe2EscapesMetacharacters()
    {
        $convert = new ReflectionMethod(QueryBuilder::class, 'wildcardToRe2');
        $builder = new QueryBuilder($this->index);

        $cases = [
            // input              => expected RE2 body
            ''                    => '',
            'michaela'            => 'michaela',
            '*michaela*'          => '.*michaela.*',
            'foo*bar'             => 'foo.*bar',
            '*'                   => '.*',
            'foo.bar'             => 'foo\\.bar',
            'a+b'                 => 'a\\+b',
            'a(b)c'               => 'a\\(b\\)c',
        ];

        foreach ($cases as $input => $expected) {
            $this->assertSame(
                $expected,
                $convert->invoke($builder, $input),
                'wildcardToRe2 for input ' . var_export($input, true)
            );
        }
    }

    public function testQuoteRegexBuildsExpectedPattern()
    {
        $quote = new ReflectionMethod(QueryBuilder::class, 'quoteRegex');

        $builder = new QueryBuilder($this->index);

        $token = new Search_Expr_Token('*michaela*');
        $token->setType('plaintext');
        $token->setField('tracker_field_name');

        $this->assertSame(
            "'(?i).*michaela.*'",
            $quote->invoke($builder, $token, '(?i)')
        );

        $token = new Search_Expr_Token('mich');
        $token->setType('plaintext');
        $token->setField('tracker_field_name');

        $this->assertSame(
            "'(?i)^mich'",
            $quote->invoke($builder, $token, '(?i)^')
        );
    }

    private function newQuery(): Search_Query
    {
        $query = new Search_Query();
        \TikiLib::lib('unifiedsearch')->initQuery($query);
        $query->filterType('trackeritem');
        return $query;
    }
}
