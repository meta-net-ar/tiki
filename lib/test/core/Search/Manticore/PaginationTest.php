<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Search\Manticore;

class PaginationTest extends \SearchIndexPagination
{
    use IndexBuilder;

    protected function setUp(): void
    {
        $this->index = $this->createIndex('_pagination');
        $this->index->destroy();
    }

    protected function tearDown(): void
    {
        if ($this->index) {
            $this->index->destroy();
        }
    }
}
