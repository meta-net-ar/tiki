<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\TaskQueue;

use Tiki\TaskQueue\Exception\QueueManagerException;
use Tiki\TaskQueue\Tasks\QueuedAbstractTask;
use TikiDb;
use Exception;

class QueueManager implements QueuedInterface
{
    private $queuedTaskDb;

    /**
     * Constructor for QueueManager.
     * Initializes the queued tasks database table.
     */
    public function __construct()
    {
        $this->queuedTaskDb = TikiDb::get()->table('tiki_queued_tasks');
    }

    /**
     * Get the count of queued tasks based on optional where conditions.
     *
     * @param array $where Optional where conditions for filtering tasks.
     * @return int The count of queued tasks.
     * @throws QueueManagerException If database operation fails.
     */
    public function getCountOfQueuedTasks(array $where = []): int
    {
        try {
            $tx = TikiDb::get()->begin();
            $count = $this->queuedTaskDb->fetchCount($where);
            $tx->commit();
            return $count;
        } catch (Exception $e) {
            throw new QueueManagerException(
                sprintf('Failed to get count of queued tasks: %s', $e->getMessage())
            );
        }
    }

    /**
     * Get queued tasks by their IDs.
     *
     * @param array $cols Optional columns to fetch.
     * @param array $ids Array of task IDs to fetch.
     * @return array Array of task rows.
     * @throws QueueManagerException If database operation fails.
     */
    public function getQueuedTasksByIds($cols = [], $ids = [])
    {
        try {
            $tx = TikiDb::get()->begin();
            $rows = $this->queuedTaskDb->fetchAll($cols, ['id' => $this->queuedTaskDb->in($ids)]);
            $tx->commit();
            return $rows;
        } catch (Exception $e) {
            throw new QueueManagerException(
                sprintf('Failed to get queued tasks by IDs: %s', $e->getMessage())
            );
        }
    }

    /**
     * Fetch all queued tasks with optional filtering, ordering, and pagination.
     *
     * @param array $cols Optional columns to fetch.
     * @param array $where Optional where conditions for filtering.
     * @param array $order Optional ordering conditions.
     * @param int $limit Optional limit for pagination.
     * @param int $offset Optional offset for pagination.
     * @return array Array of task rows.
     * @throws QueueManagerException If database operation fails.
     */
    public function fetchAllQueuedTasks($cols = [], $where = [], $order = [], $limit = -1, $offset = -1)
    {
        try {
            $tx = TikiDb::get()->begin();
            $order = empty($order) ? ['id' => 'ASC'] : $order;
            $rows = $this->queuedTaskDb->fetchAll($cols, $where, $limit, $offset, $order);
            $tx->commit();
            return $rows;
        } catch (Exception $e) {
            throw new QueueManagerException(
                sprintf('Failed to fetch all queued tasks: %s', $e->getMessage())
            );
        }
    }

    /**
     * Mark a task as processing and set the start time.
     *
     * @param int $taskId The ID of the task to mark as processing.
     * @throws QueueManagerException If database operation fails.
     */
    public function markTaskAsProcessing($taskId)
    {
        try {
            $tx = TikiDb::get()->begin();
            $startTime = (new \DateTime())->format('Y-m-d H:i:s');
            $this->queuedTaskDb->update([
                'status' => QueuedTaskSettings::IN_PROGRESS,
                'started_at' => $startTime
            ], ['id' => $taskId]);
            $tx->commit();
        } catch (Exception $e) {
            throw new QueueManagerException(
                sprintf('Failed to mark task %s as processing: %s', $taskId, $e->getMessage())
            );
        }
    }

    /**
     * Mark a task as completed and set the end time and result.
     *
     * @param int $taskId The ID of the task to mark as completed.
     * @param mixed $result The result of the completed task.
     * @throws QueueManagerException If database operation fails.
     */
    public function markTaskAsCompleted($taskId, $result)
    {
        try {
            $tx = TikiDb::get()->begin();
            $endTime = (new \DateTime())->format('Y-m-d H:i:s');
            $this->queuedTaskDb->update([
                'status' => QueuedTaskSettings::COMPLETED,
                'result' => $result,
                'ended_at' => $endTime
            ], ['id' => $taskId]);
            $tx->commit();
        } catch (Exception $e) {
            throw new QueueManagerException(
                sprintf('Failed to mark task %s as completed: %s', $taskId, $e->getMessage())
            );
        }
    }

    /**
     * Mark a task as failed and set the error result.
     *
     * @param int $taskId The ID of the task to mark as failed.
     * @param string $error The error message or result.
     * @throws QueueManagerException If database operation fails.
     */
    public function markTaskAsFailed($taskId, $error)
    {
        try {
            $tx = TikiDb::get()->begin();
            $this->queuedTaskDb->update([
                'status' => QueuedTaskSettings::FAILED,
                'result' => $error
            ], ['id' => $taskId]);
            $tx->commit();
        } catch (Exception $e) {
            throw new QueueManagerException(
                sprintf('Failed to mark task %s as failed: %s', $taskId, $e->getMessage())
            );
        }
    }

    /**
     * Save a queued task to the database.
     *
     * @param array $task The task data to save.
     * @return int The ID of the saved task.
     * @throws QueueManagerException If database operation fails.
     */
    public function saveQueuedTask($task)
    {
        try {
            $tx = TikiDb::get()->begin();
            $taskId = $this->queuedTaskDb->insert($task);
            $tx->commit();
            return $taskId;
        } catch (Exception $e) {
            throw new QueueManagerException(
                sprintf('Failed to save task: %s', $e->getMessage())
            );
        }
    }

    /**
     * Delete a queued task by ID.
     *
     * @param int $taskId The ID of the task to delete.
     * @return bool True if deletion was successful, false otherwise.
     * @throws QueueManagerException If deletion fails.
     */
    public function deleteQueuedTask(int $taskId): bool
    {
        try {
            $tx = TikiDb::get()->begin();
            $deleted = $this->queuedTaskDb->delete(['id' => $taskId]);
            $tx->commit();
            return $deleted->numrows > 0;
        } catch (Exception $e) {
            throw new QueueManagerException(
                sprintf('Failed to delete task %s: %s', $taskId, $e->getMessage())
            );
        }
    }

    /**
     * Queue a task for processing.
     *
     * @param QueuedAbstractTask $task The task to queue.
     * @return QueuedAbstractTask The queued task object.
     * @throws QueueManagerException If queuing fails or task ID generation fails.
     */
    public function queueTask(QueuedAbstractTask $task): QueuedAbstractTask
    {
        try {
            $taskId = $this->saveQueuedTask([
                'type' => $task->getType(),
                'params' => json_encode($task->getParams()),
                'owner' => $_SESSION['u_info']['id'] ?? null,
            ]);

            if (empty($taskId)) {
                throw new QueueManagerException(tr('Failed to generate a valid integer ID for the task.'));
            }

            $queuedTask = $this->getQueuedTasks([], ['id' => $taskId]);

            return reset($queuedTask);
        } catch (QueueManagerException $e) {
            throw new QueueManagerException(
                sprintf('Failed to queue task: %s', $e->getMessage())
            );
        }
    }

    /**
     * Fetch all Queued tasks.
     *
     * @return array The list of tasks.
     */
    public function getQueuedTasks($cols = [], $where = [], $order = [], $limit = -1, $offset = -1): array
    {
        $tasks = [];
        try {
            $taskRows = $this->fetchAllQueuedTasks($cols, $where, $order, $limit, $offset);
            if (! empty($taskRows)) {
                foreach ($taskRows as $taskRow) {
                    $taskRow['created_at'] = \SmartyTiki\Modifier\TikiShortDateTime::apply($taskRow['created_at'], '', 'n');
                    $taskRow['started_at'] = ! empty($taskRow['started_at']) ? \SmartyTiki\Modifier\TikiShortDateTime::apply($taskRow['started_at'], '', 'n') : null;
                    $taskRow['ended_at'] = ! empty($taskRow['ended_at']) ? \SmartyTiki\Modifier\TikiShortDateTime::apply($taskRow['ended_at'], '', 'n') : null;
                    $tasks[] = $this->createTaskFromRow($taskRow);
                }
            }
        } catch (QueueManagerException $e) {
            throw new QueueManagerException($e->getMessage());
        }

        return $tasks;
    }

    /**
     * Create a task object from a database row.
     *
     * @param array $taskRow The database row for the task.
     * @return QueuedAbstractTask The created task object.
     * @throws QueueManagerException If task class does not exist or is invalid.
     */
    private function createTaskFromRow(array $taskRow): QueuedAbstractTask
    {
        $className = $taskRow['type'];
        $class = "Tiki\\TaskQueue\\Tasks\\$className";

        if (! class_exists($class)) {
            throw new QueueManagerException(tr("Task class %0 does not exist.", $class));
        }

        $queuedTask = new $class(
            json_decode($taskRow['params'], true),
            $taskRow['id'],
            $taskRow['type'],
            $taskRow['status'],
            $taskRow['result'],
            $taskRow['created_at'],
            $taskRow['started_at'],
            $taskRow['ended_at']
        );

        if (! $queuedTask instanceof QueuedAbstractTask) {
            throw new QueueManagerException(tr("Task class %0 is not a valid QueuedTask.", $class));
        }

        return $queuedTask;
    }

    /**
     * Process a queued task by executing it and updating its status.
     *
     * @param QueuedAbstractTask $task The task to process.
     * @return mixed The result of the task execution.
     * @throws QueueManagerException If task processing fails.
     */
    public function processTask(QueuedAbstractTask $task): mixed
    {
        try {
            $this->markTaskAsProcessing($task->getId());
            $result = $task->execute();
            $this->markTaskAsCompleted($task->getId(), $result);
            return $result;
        } catch (QueueManagerException $e) {
            $this->markTaskAsFailed($task->getId(), $e->getMessage());
            throw new QueueManagerException(
                tr("Processing task %0 failed: %1", $task->getId(), $e->getMessage())
            );
        }
    }
}
