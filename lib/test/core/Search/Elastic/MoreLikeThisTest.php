<?php

namespace Search\Elastic;

use Search_Elastic_Connection;
use Search_Elastic_Index;

class Search_Elastic_MoreLikeThisTest extends \Search\AbstractMoreLikeThis
{
    protected function getIndex()
    {
        $elasticSearchHost = empty(getenv('ELASTICSEARCH_HOST')) ? 'localhost' : getenv('ELASTICSEARCH_HOST');
        $connection = Search_Elastic_Connection::build('http://' . $elasticSearchHost . ':9200');
        $connection->startBulk();

        $status = $connection->getStatus();
        if (! $status->ok) {
            $this->markTestSkipped('Elasticsearch needs to be available on ' . $elasticSearchHost . ':9200 for the test to run.');
        }

        return new Search_Elastic_Index($connection, 'test_index');
    }
}
