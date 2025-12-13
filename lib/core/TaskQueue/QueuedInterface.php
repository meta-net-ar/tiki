<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\TaskQueue;

use Tiki\TaskQueue\Tasks\QueuedAbstractTask;

/**
 * Interface for queuing and processing tasks.
 */
interface QueuedInterface
{
    /**
     * Queues a task for later processing.
     *
     * @param QueuedAbstractTask $task The task to be queued.
     * @return QueuedAbstractTask The queued task.
     */
    public function queueTask(QueuedAbstractTask $task): QueuedAbstractTask;

    /**
     * Processes a task immediately.
     *
     * @param QueuedAbstractTask $task The task to be processed.
     * @return mixed The result of the task execution.
     */
    public function processTask(QueuedAbstractTask $task): mixed;
}
