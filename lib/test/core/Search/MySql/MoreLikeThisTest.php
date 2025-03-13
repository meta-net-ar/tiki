<?php

namespace Search\MySql;

use Search_MySql_Index;
use TikiDb;

class MoreLikeThisTest extends \Search\AbstractMoreLikeThis
{
    protected function getIndex()
    {
        return new Search_MySql_Index(TikiDb::get(), 'test_index');
    }
}
