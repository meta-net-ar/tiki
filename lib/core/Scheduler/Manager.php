<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
use Psr\Log\LoggerInterface;

class Scheduler_Manager
{
    private $logger;
    private $hasTempFolderOwnership = true;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public static function queueJob(string $name, string $task, array $params, $runTime = "* * * * *")
    {
        $className = 'Scheduler_Task_' . $task;
        if (! class_exists($className)) {
            throw new Scheduler\Exception\TaskParamException(tr("Scheduler task class not defined: %0", $task));
        }

        $logger = new Tiki_Log('Schedulers', \Psr\Log\LogLevel::ERROR);
        $class = new $className($logger);

        foreach ($class->getParams() as $key => $param) {
            if (empty($param['required'])) {
                continue;
            }

            if (empty($params[$key])) {
                throw new Scheduler\Exception\TaskParamException(tr("Parameter missing for scheduler class %0: %1", $task, $param['name']));
            }
        }

        $schedLib = TikiLib::lib('scheduler');
        $schedLib->set_scheduler($name, "", $task, json_encode($params), $runTime, 'active', false, true);
    }

    public function run()
    {
        global $tikilib;

        // Get all active schedulers
        $schedLib = TikiLib::lib('scheduler');
        $schedulersTable = TikiDb::get()->table('tiki_scheduler');
        $conditions['status'] = $schedulersTable->expr('($$ = ? OR user_run_now IS NOT NULL)', ['active']);
        $activeSchedulers = $schedLib->get_scheduler(null, null, $conditions);

        $this->logger->info(sprintf("Found %d active scheduler(s).", sizeof($activeSchedulers)));

        $runTasks = [];
        $reRunTasks = [];

        $activeSchedulers = array_map(function ($schedulerData) {
            return Scheduler_Item::fromArray($schedulerData, $this->logger);
        }, $activeSchedulers);

        // Check for stalled tasks
        foreach ($activeSchedulers as $schedulerTask) {
            if ($schedulerTask->isStalled()) {
                $this->logger->info(tr("Scheduler %0 (id: %1) is stalled", $schedulerTask->name, $schedulerTask->id));

                //Attempt to heal
                $notify = $tikilib->get_preference('scheduler_notify_on_healing', 'y');
                $schedulerTask->heal('Scheduler was healed by cron', $notify);
            }
        }

        if (count($activeSchedulers) > 0) {
            $tikilib->set_preference('scheduler_last_run_timestamp', time());
        }

        foreach ($activeSchedulers as $schedulerTask) {
            try {
                if ($this->shouldRun($schedulerTask)) {
                    $runTasks[] = $schedulerTask;
                    $this->logger->info(sprintf("Run scheduler %s", $schedulerTask->name));
                    continue;
                }
            } catch (\Scheduler\Exception\CrontimeFormatException $e) {
                $this->logger->error(sprintf(tra("Skip scheduler %s - %s"), $schedulerTask->name, $e->getMessage()));
                continue;
            }

            // Check which tasks should run if they failed previously (last execution)
            if ($schedulerTask->re_run) {
                $reRunTasks[] = $schedulerTask;
                continue;
            }

            $this->logger->info(sprintf("Skip scheduler %s - Not scheduled to run at this time", $schedulerTask->name));
        }

        foreach ($reRunTasks as $task) {
            $status = $schedLib->get_run_status($task->id);
            if ($status == 'failed') {
                $this->logger->info(sprintf("Re-run scheduler %s - Last run has failed", $task->name));
                $runTasks[] = $task;
            }
        }

        if (empty($runTasks)) {
            $this->logger->notice("No active schedulers were found to run at this time.");
        } else {
            $this->logger->debug(sprintf("Total of %d schedulers to run.", sizeof($runTasks)));
        }

        foreach ($runTasks as $runTask) {
            $schedulerTask = $runTask;

            if (! $this->hasTempFolderOwnership) {
                $runRecord = $schedLib->start_scheduler_run($schedulerTask->id);
                $writingPermissionsError = tra('The console command "scheduler:run" refuses to run the task as it is running as a different system user of the owner of tiki files.');

                $this->logger->error(sprintf(tra("***** Scheduler %s - FAILED *****\n%s"), $schedulerTask->name, $writingPermissionsError));
                $schedLib->end_scheduler_run($schedulerTask->id, $runRecord['run_id'], 'failed', $writingPermissionsError);

                continue;
            }

            $this->logger->notice(sprintf(tra('***** Running scheduler %s *****'), $schedulerTask->name));
            $result = $schedulerTask->execute();

            if ($result['status'] == 'failed') {
                $this->logger->error(sprintf(tra("***** Scheduler %s - FAILED *****\n%s"), $schedulerTask->name, $result['message']));
            } else {
                $this->logger->notice(sprintf(tra("***** Scheduler %s - OK *****"), $schedulerTask->name));
            }
        }
    }

    /**
     * Heal a specific or all stalled schedulers
     *
     * @param $schedulerId
     *   A specific scheduler id to heal
     */
    public function heal($schedulerId = null)
    {
        $schedLib = TikiLib::lib('scheduler');
        $schedulers = $schedLib->get_scheduler($schedulerId, 'active');

        if (empty($schedulers) && $schedulerId) {
            $this->logger->error(tr("Scheduler with id %0 does not exist or is not active", $schedulerId));
            return;
        }

        if ($schedulerId != null) {
            $schedulers = [$schedulers];
        }

        foreach ($schedulers as $scheduler) {
            $item = Scheduler_Item::fromArray($scheduler, $this->logger);

            if ($item->isStalled()) {
                $this->logger->notice(tr("Scheduler `%0` (id: %1) is stalled", $item->name, $item->id));

                if ($item->heal('Scheduler healed through command', false, true)) {
                    $this->logger->notice(tr("Scheduler `%0` (id: %1) was healed", $item->name, $item->id));
                } else {
                    $this->logger->notice(tr("Scheduler `%0` (id: %1) was not healed", $item->name, $item->id));
                }
            } else {
                $this->logger->notice(tr("Scheduler %0 (id: %1) is not stalled, no need to heal", $item->name, $item->id));
            }
        }
    }

    /**
     * Sets flag that running system user
     * has ownership permissions on temp/cache folder
     * @param bool $hasTempFolderOwnership
     */
    public function setHasTempFolderOwnership(bool $hasTempFolderOwnership)
    {
        $this->hasTempFolderOwnership = $hasTempFolderOwnership;
    }

    /**
     *
     */
    public function shouldRun(Scheduler_Item $scheduler): bool
    {
        if (! empty($scheduler->user_run_now)) {
            return true;
        }

        if ($lastRun = $scheduler->getLastRun()) {
            $lastRunDate = $lastRun['end_time'];
        } else {
            $lastRunDate = isset($scheduler->creation_date) ? $scheduler->creation_date : time();
        }

        $lastRunDate = (int)($lastRunDate - ($lastRunDate % 60));
        $lastShould = $scheduler->getPreviousRunDate();

        return (isset($lastRunDate) && $lastShould >= $lastRunDate);
    }
}
