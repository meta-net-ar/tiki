<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Search\MySql;

use Search_MySql_Index;
use TikiDb;

class AggregationTest extends \Search\Index\AggregationTestBase
{
    protected function setUp(): void
    {
        $this->index = new Search_MySql_Index(TikiDb::get(), 'test_index_agg');
        $this->index->destroy();
        $this->populate($this->index);
    }
}
