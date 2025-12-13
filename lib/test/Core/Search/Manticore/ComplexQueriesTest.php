<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Search\Manticore;

use Search_Query;
use Search_Expr_Or;
use Search_Expr_Token;

class ComplexQueriesTest extends \PHPUnit\Framework\TestCase
{
    use IndexBuilder;

    public $index;
    private $old_prefs;

    protected function setUp(): void
    {
        global $prefs;
        $this->old_prefs = $prefs;

        $this->index = $this->createIndex('_complex_queries');
        $this->index->destroy();

        $typeFactory = $this->index->getTypeFactory();
        $this->index->addDocument(
            [
                'object_type' => $typeFactory->identifier('wiki page'),
                'object_id' => $typeFactory->identifier('HomePage'),
                'title' => $typeFactory->plaintext('Unified index'),
                'contents' => $typeFactory->plaintext('unified index other content'),
                'freetags' => $typeFactory->multivalue(['cool', 'page']),
                'freetags_text' => $typeFactory->plaintext('cool page'),
            ]
        );
        $this->index->addDocument(
            [
                'object_type' => $typeFactory->identifier('trackeritem'),
                'object_id' => $typeFactory->identifier('12'),
                'title' => $typeFactory->plaintext('expense'),
                'tracker_id' => $typeFactory->identifier('49'),
                'tracker_field_ExpenseDatePaid' => $typeFactory->timestamp(time() - 86400),
                'tracker_field_ExpenseLifecycleStatus_text' => $typeFactory->plaintext('Paid'),
                'tracker_field_ExpenseType' => $typeFactory->plaintext('Consulting'),
            ]
        );
        $this->index->addDocument(
            [
                'object_type' => $typeFactory->identifier('trackeritem'),
                'object_id' => $typeFactory->identifier('13'),
                'title' => $typeFactory->plaintext('expense'),
                'tracker_id' => $typeFactory->identifier('49'),
                'tracker_field_ExpenseDatePaid' => $typeFactory->timestamp(time() - 86400),
                'tracker_field_ExpenseLifecycleStatus_text' => $typeFactory->plaintext('Paid'),
                'tracker_field_ExpenseType' => $typeFactory->plaintext('Statement'),
            ]
        );
        $this->index->addDocument(
            [
                'object_type' => $typeFactory->identifier('trackeritem'),
                'object_id' => $typeFactory->identifier('44'),
                'title' => $typeFactory->plaintext('revenue'),
                'tracker_id' => $typeFactory->identifier('52'),
                'tracker_field_RevenueDatePaid' => $typeFactory->timestamp(time() - 12000),
                'tracker_field_RevenueLifecycleStatus_text' => $typeFactory->plaintext('Invoiced'),
            ]
        );
        $this->index->addDocument(
            [
                'object_type' => $typeFactory->identifier('trackeritem'),
                'object_id' => $typeFactory->identifier('55'),
                'title' => $typeFactory->plaintext('revenue'),
                'tracker_id' => $typeFactory->identifier('52'),
                'tracker_field_RevenueDatePaid' => $typeFactory->timestamp(time() - 12000),
                'tracker_field_RevenueLifecycleStatus_text' => $typeFactory->plaintext(''),
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

    public function testDefaultContent()
    {
        global $prefs;
        $prefs['unified_default_content'] = ['title', 'contents', 'freetags_text'];

        $query = \TikiLib::lib('unifiedsearch')->buildQuery(['content' => 'Unified index']);
        $this->assertCount(1, $query->search($this->index));

        $query = \TikiLib::lib('unifiedsearch')->buildQuery(['content' => 'cool page']);
        $this->assertCount(1, $query->search($this->index));
    }

    public function testDefaultContentMixedFields()
    {
        global $prefs;
        $prefs['unified_default_content'] = ['title', 'contents', 'freetags'];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('fulltext search combined with OR filtering');

        $query = \TikiLib::lib('unifiedsearch')->buildQuery(['content' => 'Unified index']);
        $builder = new QueryBuilder($this->index);
        $builder->build($query->getExpr());
    }

    public function testMultipleMustNotsWithOr()
    {
        $query = new \Search_Query();
        \TikiLib::lib('unifiedsearch')->initQuery($query);
        $query->filterType('trackeritem');
        $query->filterContent(implode(' OR ', [49, 52]), 'tracker_id');
        $query->filterRange(time() - 10 * 86400, time(), 'tracker_field_ExpenseDatePaid,tracker_field_RevenueDatePaid');
        $query->filterContent('NOT ', 'tracker_field_ExpenseLifecycleStatus_text,tracker_field_RevenueLifecycleStatus_text');
        $query->filterContent('NOT Statement', 'tracker_field_ExpenseType');
        $query->filterContent('NOT "Business Travel"', 'tracker_field_ExpenseType');

        $this->assertCount(2, $query->search($this->index));
    }

    public function testNestedOrStatements()
    {
        $query = new \Search_Query();
        \TikiLib::lib('unifiedsearch')->initQuery($query);
        $query->filterType('trackeritem');
        $query->filterContent(implode(' OR ', [49, 52]), 'tracker_id');
        $subq = $query->getSubQuery('status');
        $subsubq = $subq->getSubQuery('expense');
        $subsubq->filterIdentifier('Paid', 'tracker_field_ExpenseLifecycleStatus_text');
        $subsubq->filterIdentifier('Invoiced', 'tracker_field_ExpenseLifecycleStatus_text');
        $subsubq = $subq->getSubQuery('revenue');
        $subsubq->filterIdentifier('Paid', 'tracker_field_RevenueLifecycleStatus_text');
        $subsubq->filterIdentifier('Invoiced', 'tracker_field_RevenueLifecycleStatus_text');

        $this->assertCount(3, $query->search($this->index));
    }
}
