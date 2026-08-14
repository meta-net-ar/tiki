<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Search\Manticore;

use TikiLib;
use Search_Index_Interface;

trait IndexBuilder
{
    protected ?Search_Index_Interface $indexBuilderLastIndexCreated = null;

    protected function getIndex()
    {
        if ($this->indexBuilderLastIndexCreated !== null) {
            return $this->indexBuilderLastIndexCreated;
        }

        return $this->createIndex();
    }

    protected function createIndex($suffix = '')
    {
        $dsn = empty(getenv('MANTICORE_DSN')) ? 'http://127.0.0.1' : getenv('MANTICORE_DSN');
        $http_port = empty(getenv('MANTICORE_HTTP_PORT')) ? '9308' : getenv('MANTICORE_HTTP_PORT');
        $mysql_port = empty(getenv('MANTICORE_MYSQL_PORT')) ? '9306' : getenv('MANTICORE_MYSQL_PORT');

        try {
            $http_client = new Client($dsn, $http_port);
            $mysql_client = new PdoClient($dsn, $mysql_port);
        } catch (Exception $e) {
            $this->markTestSkipped('Manticore needs to be available on ' . $dsn . ':' . $mysql_port . ' for the test to run. Error: ' . $e->getMessage());
        }

        $status = $mysql_client->getStatus();
        if (! isset($status['version'])) {
            $this->markTestSkipped('Manticore needs to be available on ' . $dsn . ':' . $mysql_port . ' for the test to run.');
        }

        $this->indexBuilderLastIndexCreated = new Index($http_client, $mysql_client, 'test_index' . $suffix);
        TikiLib::lib('unifiedsearch')->replaceIndexCache('data', $this->indexBuilderLastIndexCreated);
        TikiLib::lib('unifiedsearch')->setAvailableFields([]); // forces recalc for each index

        return $this->indexBuilderLastIndexCreated;
    }
}
