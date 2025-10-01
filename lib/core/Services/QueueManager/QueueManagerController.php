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
        $response = [];
        $jobsTobeUpdatedIds = array_column(QueuedTaskBanner::get(), 'id');

        if (empty($jobsTobeUpdatedIds)) {
            return $response;
        }

        if (! empty($jobsTobeUpdatedIds)) {
            $jobs = $this->lib->getQueuedTasksByIds(['id', 'status', 'type'], $jobsTobeUpdatedIds);
            foreach ($jobs as $job) {
                $tempArray = [];
                if (! in_array($job['id'], $jobsTobeUpdatedIds)) {
                    QueuedTaskBanner::clear($job['id']);
                }
                $tempArray['id'] = $job['id'];
                $tempArray['status'] = $job['status'];
                $tempArray['page'] = QueuedTaskSettings::getPageByJobType($job['type']);

                // Render the status message using Smarty template
                $smarty = TikiLib::lib('smarty');
                $smarty->assign('jobId', $job['id']);
                $smarty->assign('status', $job['status']);
                $tempArray['mes'] = $smarty->fetch('queuedtasks/tiki-admin_queued_banner.tpl');

                QueuedTaskBanner::update($job['id'], [
                    'status' => $job['status'],
                    'mes' => $tempArray['mes']
                ]);
                $response[] = $tempArray;
            }
        }

        return $response;
    }

    public function actionProcessPendingTasks($input)
    {
        session_write_close();
        $console = new Application();
        $console->add(new TaskQueueProcessCommand());
        $console->setDefaultCommand('taskqueue:process');
        $console->run();
        return true;
    }
}
