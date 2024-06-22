<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Search\Manticore;

class QueryRepositoryTest extends \Search\Index\QueryRepositoryTest
{
    use IndexBuilder;

    private $old_prefs;

    protected function setUp(): void
    {
        global $prefs;
        $this->old_prefs = $prefs;
        $prefs['storedsearch_enabled'] = 'y';
        $prefs['unified_engine'] = 'manticore';

        $this->index = $this->createIndex('_query_repository');
        $this->index->destroy();

        $this->populate($this->index);
    }

    protected function tearDown(): void
    {
        global $prefs;
        $prefs = $this->old_prefs;

        if ($this->index) {
            $this->index->destroy();
            $index = $this->createIndex('pq');
            if ($index) {
                $index->destroy();
            }
        }
    }
}
