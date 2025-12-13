<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Search\Manticore;

class SortTest extends \SearchIndexSort
{
    use IndexBuilder;

    private $unified_stopwords;

    protected function setUp(): void
    {
        global $prefs;
        $this->unified_stopwords = $prefs['unified_stopwords'];
        $prefs['unified_stopwords'] = [];

        $this->index = $this->createIndex('_sort');
        $this->index->destroy();

        $this->populate($this->index);
    }

    protected function tearDown(): void
    {
        global $prefs;
        $prefs['unified_stopwords'] = $this->unified_stopwords;

        if ($this->index) {
            $this->index->destroy();
        }
    }
}
