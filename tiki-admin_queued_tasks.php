<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

use Tiki\TaskQueue\Exception\QueueManagerException;
use Tiki\TaskQueue\QueueManager;

require_once('tiki-setup.php');

$access = TikiLib::lib('access');
$access->check_feature('feature_queued_tasks');
$isAjax = $access->is_xml_http_request();
$userId = $_SESSION['u_info']['id'];
$offset = $_REQUEST['offset'] ?? 0;
$maxRecords = $prefs['maxRecords'];
$jobs = [];
$cant = 0;
$jobId = $_REQUEST['id'];

try {
    $queueManager = new QueueManager();
    $where = ['owner' => $userId];
    if (! empty($jobId)) {
        $where['id'] = $jobId;
    }
    $queuedTasksObjs = $queueManager->getQueuedTasks([], $where, ['id' => 'DESC'], $maxRecords, $offset);
    $jobs = array_map(function ($task) {
        return $task->toArray();
    }, $queuedTasksObjs);
    $cant = $queueManager->getCountOfQueuedTasks($where);
} catch (QueueManagerException $e) {
    Feedback::error(tr("Error while fetching queued tasks: %0", $e->getMessage()));
}

if ($isAjax) {
    header('Content-Type: application/json');
    echo json_encode($jobs);
} else {
    setcookie("queued_offset", $offset);
    $smarty->assign('jobs', $jobs);
    $smarty->assign('jobId', $jobId);
    $smarty->assign('offset', $offset);
    $smarty->assign('maxRecords', $maxRecords);
    $smarty->assign('cant', $cant);
    $smarty->assign('metatag_robots', 'NOINDEX, NOFOLLOW');
    $smarty->assign('mid', 'queuedtasks/tiki-admin_queued_tasks.tpl');
    $smarty->display('tiki.tpl');
}
