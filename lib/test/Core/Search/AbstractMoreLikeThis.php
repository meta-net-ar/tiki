<?php

namespace Search;

use PHPUnit\Framework\TestCase;
use Search_ContentSource_Static;
use Search_Indexer;
use Search_Query;

abstract class AbstractMoreLikeThis extends TestCase
{
    protected $index;

    abstract protected function getIndex();

    protected function setUp(): void
    {
        $this->index = $this->getIndex();
        $this->index->destroy();

        $this->populate($this->index);
    }

    protected function tearDown(): void
    {
        if ($this->index) {
            $this->index->destroy();
        }
    }

    public function populate($index)
    {
        $data = [
            'X' => [
                'wiki_content' => 'this does not work',
            ],
        ];

        $words = ['hello', 'world', 'some', 'random', 'content', 'populated', 'through', 'automatic', 'sampling'];

        // Generate 50 documents with random words (in a stable way)
        foreach (range(1, 50) as $doc) {
            $parts = [];
            foreach ($words as $key => $word) {
                if ($doc % ($key + 2) === 0) {
                    $parts[] = $word;
                    $parts[] = $word;
                    $parts[] = $word;
                }
            }

            $data[$doc] = [
                'object_type' => 'wiki page',
                'object_id' => $doc,
                'wiki_content' => implode(' ', $parts),
            ];
        }

        $source = new Search_ContentSource_Static(
            $data,
            [
                'object_type' => 'identifier',
                'object_id' => 'identifier',
                'wiki_content' => 'plaintext',
            ]
        );

        $indexer = new Search_Indexer($index);
        $indexer->addContentSource('wiki page', $source);

        $indexer->rebuild();
    }

    public function testObtainSimilarDocument()
    {
        $query = new Search_Query();
        $query->filterSimilar('wiki page', 12);

        $results = $query->search($this->index);

        $this->assertGreaterThan(0, count($results));
    }

    public function testDocumentTooDifferent()
    {
        $query = new Search_Query();
        $query->filterSimilar('wiki page', 'X');

        $results = $query->search($this->index);

        $this->assertCount(0, $results);
    }
}
