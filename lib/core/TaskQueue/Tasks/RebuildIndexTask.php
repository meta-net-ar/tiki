<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\TaskQueue\Tasks;

use Exception;
use Tiki\Search\SearchIndexRebuilder;
use Tiki\TaskQueue\Exception\QueueManagerException;
use TikiLib;

class RebuildIndexTask extends QueuedAbstractTask
{
    /**
     * Executes the task.
     * @return mixed The result of the task execution.
     */
    public function execute(): mixed
    {
        $params = $this->getParams();
        $logLevel = isset($params['params']['--log']) ? 2 : 0;

        try {
            $searchIndexRebuilder = new SearchIndexRebuilder();
            $result = $searchIndexRebuilder->rebuildIndex($logLevel, false);

            // Format the result for output
            $bufferedOutput = $this->getBufferedOutput();
            if (! empty($result)) {
                $unifiedsearchlib = TikiLib::lib('unifiedsearch');
                $unifiedsearchlib->formatStats($result, function ($line) use ($bufferedOutput) {
                    $bufferedOutput->writeln($line);
                });
            }

            return $bufferedOutput->fetch();
        } catch (Exception $e) {
            throw new QueueManagerException('Failed to rebuild search index: ' . $e->getMessage());
        }
    }
}
