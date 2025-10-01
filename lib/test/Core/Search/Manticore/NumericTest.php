<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Search\Manticore;

class NumericTest extends \SearchIndexNumeric
{
    use IndexBuilder;

    protected function setUp(): void
    {
        $this->index = $this->createIndex('_numeric');
        $this->index->destroy();

        $this->populate($this->index);
    }

    protected function tearDown(): void
    {
        if ($this->index) {
            $this->index->destroy();
        }
    }

    public function testNoMatchLesserVersionPortion()
    {
        $this->addWarning('Manticore finds lesser version numeric strings in contrast with Elasticsearch.');
        $this->assertResultCount(1, '5.3');
    }
}
