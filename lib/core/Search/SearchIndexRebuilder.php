<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Search;

use Feedback;
use Tiki\TaskQueue\Exception\QueueManagerException;
use Tiki\TaskQueue\QueueManager;
use Tiki\TaskQueue\Tasks\RebuildIndexTask;
use Tiki\TaskQueue\QueuedTaskBanner;
use Tiki_MemoryLimit;
use TikiLib;

/**
 * Service class for rebuilding search indexes
 * Centralizes the logic to avoid duplication between controller and command
 */
class SearchIndexRebuilder
{
    /**
     * Executes index rebuild directly or queues it as a task based on configuration
     * This is the main entry point that handles both scenarios
     *
     * @param int $logLevel Log level for the rebuild operation
     * @param bool $cleanupErrors Whether to clean up error messages related to search index
     * @param mixed $progress Progress bar object for console output (optional)
     * @return array|null Statistics from rebuild if executed directly, null if queued
     */
    public function executeOrQueueIndexRebuild(int $logLevel = 0, bool $cleanupErrors = true, $progress = null, $skipErrorTracking = false): ?array
    {
        global $prefs;

        // Check if task system is enabled
        if (($prefs['feature_queued_tasks'] ?? 'n') === 'y') {
            // Queue the task
            $taskParams = [];
            $taskParams['params']['command'] = 'index:rebuild';
            if ($logLevel) {
                $taskParams['params']['--log'] = true;
            }

            try {
                $queueManager = new QueueManager();
                $task = new RebuildIndexTask($taskParams);
                $queuedTask = $queueManager->queueTask($task);
                $taskId = $queuedTask->getId();

                if (! empty($taskId)) {
                    QueuedTaskBanner::note([
                        'id' => $taskId,
                        'page' => 'index_rebuild',
                        'status' => tr('Pending'),
                        'mes' => tr("Your index rebuild task (#%0) has been queued successfully. You can view the status and output on the <a target='_blank' href='tiki-admin_queued_tasks.php'>Queued Tasks</a> page.", $taskId)
                    ]);
                } else {
                    Feedback::error(tr("Failed to add index rebuild into queue"));
                }
            } catch (QueueManagerException $e) {
                Feedback::error(tr('Failed to queue the index rebuild task: ') . $e->getMessage());
            }

            return null; // Indicates task was queued
        } else {
            // Execute directly
            return $this->rebuildIndex($logLevel, $cleanupErrors, $progress, $skipErrorTracking);
        }
    }

    /**
     * Rebuilds the search index with proper memory management and cleanup
     * This is the internal method for direct execution
     *
     * @param int $logLevel Log level for the rebuild operation
     * @param bool $cleanupErrors Whether to clean up error messages related to search index
     * @param mixed $progress Progress bar object for console output (optional)
     * @return array|null Statistics from the rebuild operation
     */
    public function rebuildIndex(int $logLevel = 0, bool $cleanupErrors = true, $progress = null, $skipErrorTracking = false): ?array
    {
        global $prefs;

        $memory_limiter = null;

        // Apply 'Search index rebuild memory limit' setting if available
        if (! empty($prefs['allocate_memory_unified_rebuild'])) {
            $memory_limiter = new Tiki_MemoryLimit($prefs['allocate_memory_unified_rebuild']);
        }

        try {
            $unifiedsearchlib = TikiLib::lib('unifiedsearch');

            // Rebuild the main search index
            $stat = $unifiedsearchlib->rebuild($logLevel, false, $progress, $skipErrorTracking);

            // Handle progress bar if provided
            if ($progress) {
                $progress->setMessage(tr('Rebuilding preferences index'));
                $progress->advance();
            }

            // Invalidate search value formatter cache
            TikiLib::lib('cache')->invalidateAll('search_valueformatter');

            // Also rebuild admin index
            TikiLib::lib('prefs')->rebuildIndex();

            if ($progress) {
                $progress->finish();
            }

            return $stat;
        } finally {
            // Back up original memory limit if possible
            if (isset($memory_limiter)) {
                unset($memory_limiter);
            }

            // Clean error messages related with search index if requested
            if ($cleanupErrors) {
                $this->cleanupSearchIndexErrors();
            }
        }
    }

    /**
     * Cleans up error messages related to search index
     */
    private function cleanupSearchIndexErrors(): void
    {
        $removeIndexErrorsCallback = function ($item) {
            if ($item['type'] == 'error') {
                foreach ($item['mes'] as $me) {
                    if (strpos($me, 'does not exist in the current index') !== false) {
                        return true;
                    }
                }
            }
            return false;
        };

        Feedback::removeIf($removeIndexErrorsCallback);
    }
}
