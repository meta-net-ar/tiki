<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
//this script may only be included - so its better to die if called directly.
use Tiki\Lib\core\Scheduler\Output\SchedulerRunOutput;

if (str_contains($_SERVER['SCRIPT_NAME'], basename(__FILE__))) {
    header('location: index.php');
    exit;
}

class SchedulersLib extends TikiLib
{
    /**
     * Limit of the name field of the tiki_scheduler table
     */
    public const MAX_SCHEDULER_NAME_LENGTH = 255;
    /**
     * Limit of the description field of the tiki_scheduler table
     */
    public const MAX_SCHEDULER_DESCRIPTION_LENGTH = 255;
    /**
     * Let a list of schedulers
     *
     * @param int       $schedulerId    The Scheduler Id
     * @param string    $status         The Scheduler current status
     * @param array     $conditions     Extra conditions to query
     *
     * @return array    An array of schedulers or the scheduler details if $schedulerId is not null
     */
    public function get_scheduler($schedulerId = null, $status = null, $conditions = [])
    {

        $schedulersTable = $this->table('tiki_scheduler');

        if ($status) {
            $conditions['status'] = $status;
        }

        if ($schedulerId) {
            $conditions['id'] = $schedulerId;
            return $schedulersTable->fetchRow([], $conditions);
        }

        return $schedulersTable->fetchAll([], $conditions);
    }

    /**
     * Save scheduler details
     *
     * @param string $name The scheduler name
     * @param string|null $description The scheduler description text
     * @param string $task The scheduler task (check Scheduler_Item::$availableTasks)
     * @param string|null $params The task parameters
     * @param string $run_time The cron run time
     * @param string $status The scheduler status (active, inactive)
     * @param int $re_run 0 or 1 to run case failed
     * @param int $run_only_once 0 or 1 to run a scheduler only once
     * @param int|null $scheduler_id The scheduler id (optional)
     * @param int|null $creation_date schedule created date
     * @param string $user_run_now user that request a run now in background
     * @param int $enable_send_notification_override 0 or 1 to enable notification override for this job
     * @return int    The scheduler id
     */
    public function set_scheduler($name, $description, $task, $params, $run_time, $status, $re_run, $run_only_once, $scheduler_id = null, $creation_date = null, $user_run_now = null, $enable_send_notification_override = 0)
    {

        $values = [
            'name' => $name,
            'description' => $description,
            'task' => $task,
            'params' => $params,
            'run_time' => $run_time,
            'status' => $status,
            're_run' => $re_run ? 1 : 0,
            'run_only_once' => $run_only_once ? 1 : 0,
            'user_run_now' => $user_run_now,
            'enable_send_notification_override' => $enable_send_notification_override ? 1 : 0,
        ];

        $schedulersTable = $this->table('tiki_scheduler');

        if (! $scheduler_id) {
            $values["creation_date"] = $creation_date ?? time();
            return $schedulersTable->insert($values);
        } else {
            $schedulersTable->update($values, ['id' => $scheduler_id]);
            return $scheduler_id;
        }
    }

    /**
     * Get the info of the last scheduler runs
     *
     * @param int $scheduler_id The scheduler id
     * @param int $limit        The number of runs to return
     *
     * @return array An array with the scheduler runs found
     */
    public function get_scheduler_runs($scheduler_id, $limit = 10, $offset = -1)
    {
        if (! is_numeric($limit)) {
            $limit = -1;
        }

        $schedulersRunTable = $this->table('tiki_scheduler_run');

        $runs = $schedulersRunTable->fetchAll([], ['scheduler_id' => $scheduler_id], $limit, $offset, ['id' => 'DESC']);
        if (! empty($runs) && $runs[0]['status'] == 'running') {
            $runs[0]['output'] = SchedulerRunOutput::getTempLog($runs[0]['id']);
        }

        return $runs;
    }

    /**
     * Get one-time scheduler items (bg jobs) with their run status
     *
     * @return array An array with bg jobs found and their last run status
     */
    public function get_jobs()
    {
        $query = "select s.*, sr.`start_time`, sr.`end_time`, sr.`status` as run_status, sr.`output`
            from `tiki_scheduler` s
            left join `tiki_scheduler_run` sr on s.`id`=sr.`scheduler_id`
            left join `tiki_scheduler_run` sr2 on s.`id`=sr.`scheduler_id` AND sr.id < sr2.id
            where s.`run_only_once` = 1 and sr2.`id` is null
            order by s.`id` desc";
        return $this->fetchAll($query);
    }

    /**
     * Count the number of runs in logs for a given scheduler id.
     *
     * @param $schedulerId
     *
     * @return int
     */
    public function countRuns($schedulerId = null)
    {
        $schedulersRunTable = $this->table('tiki_scheduler_run');
        if ($schedulerId === null) {
            return $schedulersRunTable->fetchCount([]);
        }
        return $schedulersRunTable->fetchCount(['scheduler_id' => $schedulerId]);
    }

    /**
     * Get consolidated logs from all schedulers with scheduler names
     *
     * @param int $limit  The number of runs to return
     * @param int $offset The offset for pagination
     *
     * @return array An array with the consolidated scheduler runs found
     */
    public function getConsolidatedLogs($limit = 100, $offset = 0)
    {
        if (! is_numeric($limit)) {
            $limit = 100;
        }
        if (! is_numeric($offset)) {
            $offset = 0;
        }

        $query = "SELECT sr.*, sr.output as scheduler_output, s.name as scheduler_name
            FROM `tiki_scheduler_run` sr
            LEFT JOIN `tiki_scheduler` s ON sr.scheduler_id = s.id
            ORDER BY sr.id DESC
            LIMIT " . intval($offset) . ", " . intval($limit);

        $runs = $this->fetchAll($query);

        // Handle running tasks with temp log output
        foreach ($runs as $key => $run) {
            if ($run['status'] !== 'running') {
                $runs[$key]['output'] = SchedulerRunOutput::getTempLog($run['id']);
            }
        }

        return $runs;
    }

    /**
     * Get scheduler last run status
     *
     * @param int $scheduler_id The Scheduler Id
     *
     * @return bool|mixed
     */
    public function get_run_status($scheduler_id)
    {
        $schedulersRunTable = $this->table('tiki_scheduler_run');
        return $schedulersRunTable->fetchOne('status', ['scheduler_id' => $scheduler_id], ['id' => 'DESC']);
    }

    /**
     * Get scheduler last run stalled status from database
     *
     * @param int $scheduler_id The Scheduler Id
     *
     * @return bool|mixed
     */
    public function getStalled($scheduler_id)
    {
        $schedulersRunTable = $this->table('tiki_scheduler_run');
        return $schedulersRunTable->fetchOne('stalled', ['scheduler_id' => $scheduler_id], ['id' => 'DESC']);
    }

    /**
     * Get failed runs
     *
     * @param int $scheduler_id The Scheduler Id
     *
     * @return bool|mixed
     */
    public function getFailedRuns($timeBack)
    {
        $schedulersRunTable = $this->table('tiki_scheduler_run');

        return $schedulersRunTable->fetchAll(
            ['start_time', 'end_time', 'output', 'scheduler_id'],
            [
                'start_time' => $schedulersRunTable->greaterThan($timeBack),
                'status' => 'failed'
            ]
        );
    }

    /**
     * Mark scheduler run as active (running)
     *
     * @param string    $scheduler_id   The scheduler id
     * @param int|null  $start_time     Run start time in timestamp format
     *
     * @return array    An array with the run id and start time
     */
    public function start_scheduler_run($scheduler_id, $start_time = null)
    {
        if (empty($start_time)) {
            $start_time = time();
        }

        $schedulersRunTable = $this->table('tiki_scheduler_run');
        $runId = $schedulersRunTable->insert([
            'scheduler_id' => $scheduler_id,
            'start_time' => $start_time,
            'status' => 'running'
        ]);

        return [
            'run_id' => $runId,
            'start_time' => $start_time
        ];
    }

    /**
     * Mark scheduler run as finished
     *
     * @param int       $scheduler_id       The scheduler id
     * @param int       $run_id             The scheduler run id
     * @param string    $executionStatus    The execution status (done, failed)
     * @param string    $errorMessage       The output message
     * @param int|null  $end_time           The run end time in timestamp format
     * @param int       $healed                 The run end time in timestamp format
     *
     * @return int  The end time in timestamp format
     */
    public function end_scheduler_run($scheduler_id, $run_id, $executionStatus, $errorMessage, $end_time = null, $healed = 0)
    {
        if (empty($end_time)) {
            $end_time = time();
        }

        // TEXT column has a 65,535 byte limit. Truncate if output exceeds this size.
        $maxOutputBytes = 65535;
        $truncationMsg = "\n\n[Output truncated - exceeded TEXT column limit]";
        $maxContentBytes = $maxOutputBytes - strlen($truncationMsg);

        if (strlen($errorMessage) > $maxOutputBytes) {
            $errorMessage = substr($errorMessage, 0, $maxContentBytes) . $truncationMsg;
        }

        $schedulersRunTable = $this->table('tiki_scheduler_run');
        $schedulersRunTable->update([
            'status' => $executionStatus,
            'output' => $errorMessage,
            'end_time' => $end_time,
            'healed' => $healed,
        ], [
            'scheduler_id' => $scheduler_id,
            'status' => 'running',
            'id' => $run_id,
        ]);

        $schedulersTable = $this->table('tiki_scheduler');
        $schedulersTable->update([
            'user_run_now' => null
        ], [
            'id' => $scheduler_id
        ]);
        return $end_time;
    }

    /**
     * Remove old scheduler runs
     *
     * @param int   $schedulerId    The scheduler id
     * @param int   $runId          The run id to keep since, older ids (less than) will be deleted.
     */
    public function removeLogs($schedulerId, $runId)
    {
        $schedulersRunTable = $this->table('tiki_scheduler_run');
        $schedulersRunTable->deleteMultiple(['scheduler_id' => $schedulerId, 'id' => $schedulersRunTable->lesserThan($runId)]);
    }

    /**
     * Set a scheduler run as stalled
     *
     * @param int   $schedulerId    The Scheduler Id
     * @param int   $runId      The run id that is stalled
     */
    public function setSchedulerRunStalled($schedulerId, $runId)
    {
        $schedulersRunTable = $this->table('tiki_scheduler_run');
        $schedulersRunTable->update([
            'stalled' => 1,
        ], [
            'scheduler_id' => $schedulerId,
            'id' => $runId,
            'status' => 'running',
        ]);
    }

    /**
     * Mark the scheduler run as healed, allowing it to be executed next time
     *
     * @param $schedulerId
     * @param $runId
     * @param $message
     */
    public function setSchedulerRunHealed($schedulerId, $runId, $message)
    {
        $this->end_scheduler_run($schedulerId, $runId, 'failed', $message, null, 1);
    }

    /**
     * Remove the scheduler and its runs/logs
     *
     * @param int   $scheduler_id   The Scheduler Id
     */
    public function remove_scheduler($scheduler_id)
    {

        $schedulersRunTable = $this->table('tiki_scheduler_run');
        $schedulersRunTable->deleteMultiple(['scheduler_id' => $scheduler_id]);

        $schedulersTable = $this->table('tiki_scheduler');
        $schedulersTable->delete(['id' => $scheduler_id]);

        $logslib = TikiLib::lib('logs');
        $logslib->add_action('Removed', $scheduler_id, 'scheduler');
    }

    /**
     * Disable a specific scheduler by setting its status as Disabled
     * @param $scheduler_id
     */
    public function setInactive($scheduler_id)
    {
        $schedulerTable = $this->table('tiki_scheduler');
        $schedulerTable->update(['status' => Scheduler_Item::STATUS_INACTIVE], ['id' => $scheduler_id]);
    }

    /**
     * Enable a specific scheduler by setting its status as Enabled
     * @param $scheduler_id
     */
    public function setActive($scheduler_id)
    {
        $schedulerTable = $this->table('tiki_scheduler');
        $schedulerTable->update(['status' => Scheduler_Item::STATUS_ACTIVE], ['id' => $scheduler_id]);
    }

    /**
     * Update a field in the schedular table
     * @param $scheduler_id
     * @param $field
     * @param $value
     */
    public function updateSchedulerField($scheduler_id, $field, $value)
    {
        $schedulerTable = $this->table('tiki_scheduler');
        return $schedulerTable->update([$field => $value], ['id' => $scheduler_id]);
    }

    /**
     * Get scheduler logs
     *
     * @param string $status
     * @return array
     */
    public function getSchedulerFromLogs(?string $status = null)
    {
        global $prefs;

        $schedulerFailures = [];
        $reportMaxDays = $prefs['scheduledTasksReportMaxDays'];
        $db = TikiDb::get();
        $values = [$reportMaxDays];
        $query = 'SELECT ts.*, tsr.*
            FROM `tiki_scheduler` ts
            JOIN `tiki_scheduler_run` tsr ON ts.`id` = tsr.`scheduler_id`
            WHERE tsr.`end_time` >= UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL ? DAY))';

        if (! empty($status)) {
            $query .= ' AND tsr.`status` = ?';
            array_push($values, $status);
        }

        if (! empty($prefs['scheduledTasksReport']) &&  $prefs['scheduledTasksReport'] === 'last_number_of_hours') {
            $query .= ' AND tsr.`end_time` >= UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL ? HOUR))';
            array_push($values, $prefs['scheduledTasksReportHours']);
        } else {
            $where = '';
            if (! empty($status)) {
                $where .= ' AND tsr2.`status` = ?';
                array_push($values, $status);
            }
            array_push($values, $reportMaxDays);
            $query .= ' AND NOT EXISTS (
                SELECT 1
                FROM `tiki_scheduler_run` tsr2
                WHERE tsr2.`scheduler_id` = ts.`id`
                    ' . $where . '
                    AND tsr2.`end_time` >= UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL ? DAY))
                    AND tsr2.`id` > tsr.`id`)';
        }
        $query .= ' ORDER BY tsr.`end_time` DESC;';
        $schedulerFailures = $db->fetchAll($query, $values);

        return $schedulerFailures;
    }

    /**
     * Get the number of minutes since last task run
     *
     * @return int
     */
    public function getMinutesLastRun()
    {
        $query = 'SELECT TIMESTAMPDIFF(MINUTE, FROM_UNIXTIME(`start_time`), FROM_UNIXTIME(UNIX_TIMESTAMP())) AS minutes_since_last_run
            FROM `tiki_scheduler_run`
            ORDER BY `id` DESC LIMIT 1;';
        return $this->getOne($query);
    }

    /**
     * Get number of tasks executed in the last hours
     *
     * @param int $hours
     * @return int
     */
    public function getExecutedTasksbyTime($hours)
    {
        $query = "SELECT COUNT(*) AS tasks_executed_last_hour
            FROM tiki_scheduler_run
            WHERE start_time >= UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL ? HOUR));";
        return $this->getOne($query, [$hours]);
    }
}
