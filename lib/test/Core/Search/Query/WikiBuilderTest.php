<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Test\Core\Search\Query;

use PHPUnit\Framework\TestCase;
use Search_Index_Memory;
use Search_Query;
use Search_Query_WikiBuilder;
use JitFilter;
use Search_Expr_Not;
use Search_Expr_And;
use Search_Expr_Or;
use Search_Expr_Token;

class WikiBuilderTest extends TestCase
{
    public function testOperatorsProduceDifferentStructures()
    {
        $index1 = new Search_Index_Memory();
        $query1 = new Search_Query();
        $mockInput1 = new JitFilter(['contents' => 'word1 word2 word3', 'filter' => 'test_filter']);
        $wikiBuilder1 = new Search_Query_WikiBuilder($query1, $mockInput1);
        $wikiBuilder1->wpquery_filter_editable($query1, 'text', ['operator' => 'AND', 'field' => 'contents']);
        $query1->search($index1);
        $andResult = $index1->getLastQuery();

        $index2 = new Search_Index_Memory();
        $query2 = new Search_Query();
        $mockInput2 = new JitFilter(['contents' => 'word1 word2 word3', 'filter' => 'test_filter']);
        $wikiBuilder2 = new Search_Query_WikiBuilder($query2, $mockInput2);
        $wikiBuilder2->wpquery_filter_editable($query2, 'text', ['operator' => 'OR', 'field' => 'contents']);
        $query2->search($index2);
        $orResult = $index2->getLastQuery();

        $index3 = new Search_Index_Memory();
        $query3 = new Search_Query();
        $mockInput3 = new JitFilter(['contents' => 'word1 word2 word3', 'filter' => 'test_filter']);
        $wikiBuilder3 = new Search_Query_WikiBuilder($query3, $mockInput3);
        $wikiBuilder3->wpquery_filter_editable($query3, 'text', ['operator' => 'NOT', 'field' => 'contents']);
        $query3->search($index3);
        $notResult = $index3->getLastQuery();

        $this->assertNotNull($andResult, 'AND result should not be null');
        $this->assertNotNull($orResult, 'OR result should not be null');
        $this->assertNotNull($notResult, 'NOT result should not be null');

        $this->assertTrue($this->exprHasInstance($notResult, Search_Expr_Not::class), 'NOT operator should produce a Search_Expr_Not');

        // Operators should produce different structures
        $this->assertNotEquals($andResult, $orResult, 'AND and OR should produce different structures');
        $this->assertNotEquals($andResult, $notResult, 'AND and NOT should produce different structures');
        $this->assertNotEquals($orResult, $notResult, 'OR and NOT should produce different structures');
    }

    public function testNotOperatorRequiresFilterKey()
    {
        $index1 = new Search_Index_Memory();
        $query1 = new Search_Query();
        $mockInput1 = new JitFilter(['contents' => 'test value']);
        $wikiBuilder1 = new Search_Query_WikiBuilder($query1, $mockInput1);
        $wikiBuilder1->wpquery_filter_editable($query1, 'text', ['operator' => 'NOT', 'field' => 'contents']);
        $query1->search($index1);
        $resultWithoutFilter = $index1->getLastQuery();

        $index2 = new Search_Index_Memory();
        $query2 = new Search_Query();
        $mockInput2 = new JitFilter(['contents' => 'test value', 'filter' => 'test_filter']);
        $wikiBuilder2 = new Search_Query_WikiBuilder($query2, $mockInput2);
        $wikiBuilder2->wpquery_filter_editable($query2, 'text', ['operator' => 'NOT', 'field' => 'contents']);
        $query2->search($index2);
        $resultWithFilter = $index2->getLastQuery();

        $this->assertNotEquals($resultWithoutFilter, $resultWithFilter);

        $this->assertFalse($this->exprHasInstance($resultWithoutFilter, Search_Expr_Not::class), 'Without filter key, NOT should not be present');
        $this->assertTrue($this->exprHasInstance($resultWithFilter, Search_Expr_Not::class), 'With filter key, NOT should be present');
    }


    public function testOperatorStructures()
    {
        $subQuery1 = new Search_Query(null, 'and');
        $subQuery1->filterContent('term1', ['contents']);
        $subQuery1->filterContent('term2', ['contents']);
        $subQuery1->filterContent('term3', ['contents']);
        $index1 = new Search_Index_Memory();
        $subQuery1->search($index1);
        $andResult = $index1->getLastQuery();
        $this->assertNotNull($andResult, 'andResult should not be null');

        $subQuery2 = new Search_Query(null, 'or');
        $subQuery2->filterContent('term1', ['contents']);
        $subQuery2->filterContent('term2', ['contents']);
        $subQuery2->filterContent('term3', ['contents']);
        $index2 = new Search_Index_Memory();
        $subQuery2->search($index2);
        $orResult = $index2->getLastQuery();
        $this->assertNotNull($orResult, 'orResult should not be null');

        $andTokenCount = $this->countTokens($andResult);
        $orTokenCount  = $this->countTokens($orResult);

        $this->assertEquals(3, $andTokenCount, 'AND case should contain exactly 3 token expressions');
        $this->assertEquals(3, $orTokenCount, 'OR case should contain exactly 3 token expressions');

        $this->assertTrue(
            $andResult instanceof Search_Expr_And || $this->exprHasInstance($andResult, Search_Expr_And::class),
            'AND result should be or contain Search_Expr_And'
        );
        $this->assertTrue(
            $orResult instanceof Search_Expr_Or || $this->exprHasInstance($orResult, Search_Expr_Or::class),
            'OR result should be or contain Search_Expr_Or'
        );

        $this->assertNotEquals($this->serializeExpr($andResult), $this->serializeExpr($orResult));
    }

    private function countTokens($expr)
    {
        $count = 0;
        if ($expr instanceof Search_Expr_Token) {
            return 1;
        }

        if (method_exists($expr, 'walk')) {
            $expr->walk(function ($current) use (&$count) {
                if ($current instanceof Search_Expr_Token) {
                    $count++;
                }
                return $current;
            });
        }

        return $count;
    }

    private function exprHasInstance($expr, string $className): bool
    {
        if ($expr === null) {
            return false;
        }
        if (is_object($expr) && $expr instanceof $className) {
            return true;
        }

        $found = false;
        if (method_exists($expr, 'walk')) {
            $expr->walk(function ($current) use (&$found, $className) {
                if ($current instanceof $className) {
                    $found = true;
                }
                return $current;
            });
        }

        return $found;
    }

    private function serializeExpr($expr)
    {
        if (method_exists($expr, 'getSerializedParts')) {
            return get_class($expr) . ':' . $expr->getSerializedParts();
        }

        return var_export($expr, true);
    }
}
