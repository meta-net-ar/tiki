<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Test\Core\Recommendation;

use PHPUnit\Framework\TestCase;
use Tiki\Recommendation\Debug\SourceDocument;
use Tiki\Recommendation\Recommendation;
use Tiki\Recommendation\RecommendationSet;

class RecommendationSetTest extends TestCase
{
    public function testAddFiltersRecommendations()
    {
        $set = new RecommendationSet('X');
        $set->add(new SourceDocument('forum post', 1));
        $set->add(new Recommendation('forum post', 42));

        $expect = new RecommendationSet('X');
        $expect->addDebug(new SourceDocument('forum post', 1));
        $expect->add(new Recommendation('forum post', 42));

        $this->assertEquals($expect, $set);
    }

    public function testDebugInformationExcludedFromIteration()
    {
        $set = new RecommendationSet('X');
        $set->addDebug(new SourceDocument('forum post', 1));
        $set->add($rec = new Recommendation('forum post', 42));

        $out = [];
        foreach ($set as $r) {
            $out[] = $r;
        }

        $this->assertSame($out, [$rec]);
    }
}
