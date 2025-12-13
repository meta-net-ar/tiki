<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
use Tiki\Lib\core\Scheduler\DefaultSchedulers;

$inputConfiguration = [
    [
        'staticKeyFilters'               => [
            'scheduler'                  => 'word',           //post
            'scheduler_name'             => 'string',         //post
            'scheduler_description'      => 'string',         //post
            'scheduler_task'             => 'string',         //post
            'scheduler_status'           => 'string',         //post
            'scheduler_rerun'            => 'alpha',          //post
            'scheduler_run_only_once'    => 'alpha',          //post
            'enable_send_notification_override' => 'alpha',   //post
            'new_scheduler'              => 'alpha',          //post
            'editscheduler'              => 'digits',         //post
            'scheduler_time'             => 'string',         //post
            'offset'                     => 'digits',         //get
            'numrows'                    => 'digits',         //post
            'logs'                       => 'string',         //post
            'consolidated_logs'          => 'digits',         //get
            'add'                        => 'alpha',          //get
            'filter'                     => 'string',         //get
            'save'                       => 'alpha',          //post
            'name'                       => 'string',         //get
            'description'                => 'string',         //get
            'task'                       => 'string',         //get
            'run_time'                   => 'string',         //get
            'status'                     => 'string',         //get
            're_run'                     => 'string',         //get
            'action'                     => 'alpha',          //post
            'checked'                    => 'array',          //post
        ],
    ],
];
require_once('tiki-setup.php');

$defaultSchedulers = new DefaultSchedulers();
$defaultSchedulers->checkAndUpdate();

function saveScheduler()
{
    $schedLib = TikiLib::lib('scheduler');

    $addTask = true;
    $errors = [];

    $params = '';
    $name = $_POST['scheduler_name'];
    $description = $_POST['scheduler_description'];
    $task = $_POST['scheduler_task'];
    $runTime = trim($_POST['scheduler_time']);
    $status = $_POST['scheduler_status'];
    $reRun = $_POST['scheduler_rerun'] ?? 0;
    $reRun = $reRun == 'on' ? 1 : 0;
    $runOnlyOnce = $_POST['scheduler_run_only_once'] ?? 0;
    $runOnlyOnce = $runOnlyOnce == 'on' ? 1 : 0;
    $enableSendNotificationOverride = $_POST['enable_send_notification_override'] ?? 0;
    $enableSendNotificationOverride = $enableSendNotificationOverride == 'on' ? 1 : 0;

    if (empty($name)) {
        $errors[] = tra('Name is required');
        $addTask = false;
    }

    if (empty($task)) {
        $errors[] = tra('Task is required');
        $addTask = false;
    } else {
        $className = 'Scheduler_Task_' . $task;
        if (! class_exists($className)) {
            Feedback::error(tra('An error occurred; please contact the administrator.'));
            $access = TikiLib::lib('access');
            $access->redirect('tiki-admin_schedulers.php');
            die;
        }

        $logger = new Tiki_Log('Schedulers', \Psr\Log\LogLevel::ERROR);
        $class = new $className($logger);

        $taskName = strtolower($class->getTaskName());
        $taskParams = $class->getParams();

        foreach ($taskParams as $key => $param) {
            if (empty($param['required'])) {
                continue;
            }

            $httpParamName = $taskName . '_' . $key;
            if (empty($_POST[$httpParamName])) {
                $errors[] = sprintf(tra('%s is required'), $param['name']);
                $addTask = false;
            }

            $validateMethod = 'validate' . str_replace(' ', '', ucwords(str_replace('_', ' ', $key)));

            if (method_exists($class, $validateMethod)) {
                $validationResult = $class->$validateMethod($_POST[$httpParamName]);
                if (is_string($validationResult)) {
                    $errors[] = $validationResult;
                    $addTask = false;
                }
            }
        }

        $params = $class->parseParams();
    }

    if (empty($runTime)) {
        $errors[] = tra('Run Time is required');
        $addTask = false;
    }

    if (! Scheduler_Utils::validate_cron_time_format($runTime)) {
        $errors[] = tra('Run Time format is invalid');
        $addTask = false;
    }

    if (empty('status')) {
        $errors[] = tra('Status cannot be empty');
        $addTask = false;
    }

    $schedulerinfo = [];

    if ($addTask) {
        $scheduler = ! empty($_POST['scheduler']) ? $_POST['scheduler'] : null;

        $schedLib->set_scheduler($name, $description, $task, $params, $runTime, $status, $reRun, $runOnlyOnce, $scheduler, null, null, $enableSendNotificationOverride);
        if ($scheduler) {
            $feedback = sprintf(tra('Scheduler %s was updated.'), $name);
        } else {
            $feedback = sprintf(tra('Scheduler %s was created.'), $name);
        }

        Feedback::success($feedback);
        $access = TikiLib::lib('access');
        $access->redirect('tiki-admin_schedulers.php');
        die;
    } else {
        if (! empty($errors)) {
            Feedback::error(['mes' => $errors]);
        }
    }

    $schedulerinfo['name'] = $name;
    $schedulerinfo['description'] = $description;
    $schedulerinfo['task'] = $task;
    $schedulerinfo['run_time'] = $runTime;
    $schedulerinfo['status'] = $status;
    $schedulerinfo['re_run'] = $reRun;
    $schedulerinfo['enable_send_notification_override'] = $enableSendNotificationOverride;
    $schedulerinfo['params'] = json_decode($params, true);

    return $schedulerinfo;
}
$access = TikiLib::lib('access');
$access->check_feature('feature_scheduler');
$access->check_permission(['tiki_p_admin_schedulers']);

$auto_query_args = [];
$cookietab = 1;

$auto_query_args = [
    'offset',
    'numrows',
    'scheduler',
    'logs',
    'filter',
    'consolidated_logs',
];

$schedLib = TikiLib::lib('scheduler');
$schedulerTasks = Scheduler_Item::getAvailableTasks();
$scheduler = 0;

$tikilib = TikiLib::lib('tiki');
$numOfLogs = $tikilib->get_preference('scheduler_keep_logs');

if ((isset($_POST['new_scheduler']) || (isset($_POST['editscheduler']) && isset($_POST['scheduler']))) && $access->checkCsrf()) {
    // If scheduler saved, it redirects to the schedulers page, cleaning the add/edit scheduler form.
    $schedulerinfo = saveScheduler();
    $cookietab = 2;
} elseif (isset($_REQUEST['scheduler']) and $_REQUEST['scheduler']) {
    $schedulerinfo = $schedLib->get_scheduler($_REQUEST['scheduler']);

    if (empty($schedulerinfo)) {
        $schedulerinfo['name'] = '';
        $schedulerinfo['description'] = '';
        $schedulerinfo['task'] = '';
        $schedulerinfo['params'] = [];
        $schedulerinfo['run_time'] = '';
        $schedulerinfo['status'] = '';
        $schedulerinfo['re_run'] = '';
        $schedulerinfo['enable_send_notification_override'] = '';
    } else {
        $scheduler = $_REQUEST['scheduler'];
        $schedulerinfo['params'] = json_decode($schedulerinfo['params'], true);
        $offset = empty($_REQUEST['offset']) ? 0 : $_REQUEST['offset'];
        $numRows = empty($_REQUEST['numrows']) ? $maxRecords : $_REQUEST['numrows'];

        $schedulerRuns = $schedLib->get_scheduler_runs($scheduler, $numRows, $offset);
        $runsCount = $schedLib->countRuns($scheduler);

        if ($runsCount > $numOfLogs && $numOfLogs > 0) {
            $runsCount = $numOfLogs;
        }

        $smarty->assign_by_ref('count', $runsCount);

        // Check if last run is still running and can be stopped.
        if (
            ! empty($schedulerRuns[0]) &&
            $schedulerRuns[0]['status'] == 'running' &&
            $schedulerRuns[0]['start_time'] + 600 < time()
        ) {
            // If the task is running for more than 10min, maybe it's stucked.
            $schedulerRuns[0]['can_stop'] = true;
        }
    }

    if (isset($_REQUEST['logs'])) {
        $cookietab = '3';
    } else {
        $cookietab = '2';
    }
} else {
    $schedulerinfo['name'] = $_GET['name'] ?? '';
    $schedulerinfo['description'] = $_GET['description'] ?? '';
    $schedulerinfo['task'] = $_GET['task'] ?? '';
    $schedulerinfo['run_time'] = $_GET['run_time'] ?? '';
    $schedulerinfo['status'] = $_GET['status'] ?? '';
    $schedulerinfo['re_run'] = $_GET['re_run'] ?? '';
    $schedulerinfo['enable_send_notification_override'] = $_GET['enable_send_notification_override'] ?? '';

    $logger = new Tiki_Log('Schedulers', \Psr\Log\LogLevel::ERROR);

    foreach ($schedulerTasks as $name => $schedulerTask) {
        $className = 'Scheduler_Task_' . $name;
        $class = new $className($logger);

        foreach ($class->getParams() as $paramName => $param) {
            $schedulerinfo['params'][$paramName] = $_GET[$paramName] ?? '';
        }
    }
    $scheduler = 0;
    $offset = empty($_REQUEST['offset']) ? 0 : $_REQUEST['offset'];
    $numRows = empty($_REQUEST['numrows']) ? $maxRecords : $_REQUEST['numrows'];

    // Fetch consolidated logs for the Logs tab (tabs use client-side navigation)
    $consolidatedLogs = $schedLib->getConsolidatedLogs($numRows, $offset);
    $consolidatedLogsCount = $schedLib->countRuns();
    if ($consolidatedLogsCount > $numOfLogs && $numOfLogs > 0) {
        $consolidatedLogsCount = $numOfLogs;
    }
    $smarty->assign_by_ref('consolidatedLogsCount', $consolidatedLogsCount);
    $smarty->assign_by_ref('consolidatedLogs', $consolidatedLogs);

    // Set tab to Logs tab when paginating through consolidated logs
    if (isset($_REQUEST['consolidated_logs'])) {
        $cookietab = '5';
    }
}

$smarty->assign_by_ref('numOfLogs', $numOfLogs);
$smarty->assign_by_ref('offset', $offset);
$smarty->assign_by_ref('numrows', $numRows);

$tasks = $schedLib->get_scheduler(null, null, ['run_only_once' => 0]);

$logger = new Tiki_Log('Webcron', \Psr\Log\LogLevel::ERROR);
$stalledTasksCount = 0;

foreach ($tasks as $key => $task) {
    $schedulerItem = Scheduler_Item::fromArray($task, $logger);
    if (! $schedulerItem instanceof Scheduler_Item) {
        continue;
    }

    $tasks[$key]['stalled'] = $schedulerItem->isStalled();
    $tasks[$key]['last_run_status'] = $schedLib->get_run_status($task['id']);
    $tasks[$key]['last_run_stalled'] = $schedLib->getStalled($task['id']);
    if ($tasks[$key]['stalled']) {
        $stalledTasksCount++;
    }
}

// Handle filter parameter and apply server-side filtering
$activeFilter = '';
if (isset($_REQUEST['filter']) && $_REQUEST['filter'] === 'stalled') {
    $activeFilter = 'stalled';
    $tasks = array_filter($tasks, function ($task) {
        return $task['stalled'] === true;
    });
    $tasks = array_values($tasks);
}
$smarty->assign('activeFilter', $activeFilter);

$smarty->assign_by_ref('schedulers', $tasks);
$smarty->assign('stalledTasksCount', $stalledTasksCount);

$jobs = $schedLib->get_jobs();
$smarty->assign_by_ref('jobs', $jobs);

if (isset($_REQUEST['add'])) {
    $cookietab = '2';
}

$headerlib->add_jsfile('lib/jquery_tiki/tiki-schedulers.js');
$smarty->assign('display_timezone', TikiLib::lib('tiki')->get_display_timezone(false));
$smarty->assign('schedulerinfo', $schedulerinfo);
$smarty->assign('schedulerruns', $schedulerRuns ?? []);
$smarty->assign('schedulerId', $scheduler);
$smarty->assign('schedulerTasks', $schedulerTasks);
$smarty->assign('selectedTask', '');
$smarty->assign('run_timezone', TikiLib::lib('tiki')->get_display_timezone(false));
$smarty->assign('schedulerStatus', [
    Scheduler_Item::STATUS_ACTIVE => tra('Active'),
    Scheduler_Item::STATUS_INACTIVE => tra('Inactive'),
]);

// Get notification users for display
$notificationUsers = [];
try {
    $notificationUsers = Scheduler_Utils::getSchedulerNotificationUsers();
} catch (Exception $e) {
    TikiLib::lib('logs')->add_log('Scheduler error', tr('Failed to get notification users: %0', $e->getMessage()));
}
$smarty->assign('notificationUsers', $notificationUsers);

// disallow robots to index page:
$smarty->assign('metatag_robots', 'NOINDEX, NOFOLLOW');
$smarty->assign('mid', 'tiki-admin_schedulers.tpl');
$smarty->display('tiki.tpl');
