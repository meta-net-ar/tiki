<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Services\QueueManager;

use Tiki\Command\Application;
use Tiki\Command\TaskQueueProcessCommand;
use Tiki\TaskQueue\QueuedTaskBanner;
use Tiki\TaskQueue\QueuedTaskSettings;
use Tiki\TaskQueue\QueueManager;
use TikiLib;

class QueueManagerController
{
    private $lib;

    public function __construct()
    {
        $this->lib = new QueueManager();
    }

    public function actionUpdateQueuedJobsLiveStatus($input)
    {
        global $prefs;

        // Return flag to stop polling if feature is disabled
        if (($prefs['feature_queued_tasks'] ?? 'n') !== 'y') {
            return ['disabled' => true];
        }

        $response = [
            'jobs' => [],
            'user_active_jobs' => 0,
        ];

        $jobsTobeUpdatedIds = array_column(QueuedTaskBanner::get(), 'id');

        if (empty($jobsTobeUpdatedIds)) {
            return $response;
        }

        $jobs = $this->lib->getQueuedTasksByIds(['id', 'status', 'type'], $jobsTobeUpdatedIds);
        $foundJobIds = array_column($jobs, 'id');

        // Clear banners for jobs that no longer exist in the database
        foreach ($jobsTobeUpdatedIds as $jobId) {
            if (! in_array($jobId, $foundJobIds)) {
                QueuedTaskBanner::clear($jobId);
            }
        }

        foreach ($jobs as $job) {
            $tempArray = [];
            $tempArray['id'] = $job['id'];
            $tempArray['status'] = $job['status'];
            $tempArray['page'] = QueuedTaskSettings::getPageByJobType($job['type']);

            // Render the status message using Smarty template
            $smarty = TikiLib::lib('smarty');
            $smarty->assign('jobId', $job['id']);
            $smarty->assign('status', $job['status']);
            $smarty->assign('webProcessingDisabled', ($prefs['queued_tasks_js_processing_disabled'] ?? 'n') === 'y');
            $tempArray['mes'] = $smarty->fetch('queuedtasks/tiki-admin_queued_banner.tpl');

            // Update session with current status
            QueuedTaskBanner::update($job['id'], [
                'status' => $job['status'],
                'mes' => $tempArray['mes']
            ]);

            if ($job['status'] === 'Pending' || $job['status'] === 'InProgress') {
                $response['user_active_jobs']++;
            }

            $response['jobs'][] = $tempArray;
        }

        return $response;
    }

    public function actionProcessPendingTasks($input)
    {
        global $prefs;

        // Check if feature is enabled
        if (($prefs['feature_queued_tasks'] ?? 'n') !== 'y') {
            return false;
        }

        // Check if web processing is disabled (use CLI instead)
        if (($prefs['queued_tasks_js_processing_disabled'] ?? 'n') === 'y') {
            return false;
        }

        session_write_close();
        $console = new Application();
        $console->add(new TaskQueueProcessCommand());
        $console->setDefaultCommand('taskqueue:process');
        $console->run();
        return true;
    }
}
