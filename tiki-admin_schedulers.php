<?php
// (c) Copyright 2002-2017 by authors of the Tiki Wiki/CMS/Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function saveScheduler($isUpdate = false)
{

	$addTask = true;
	$errors = [];

	$name = $_POST['scheduler_name'];
	$description = $_POST['scheduler_description'];
	$task = $_POST['scheduler_task'];
	$params = $_POST['scheduler_params'];
	$runTime = $_POST['scheduler_time'];
	$status = $_POST['scheduler_status'];
	$reRun = $_POST['scheduler_rerun'] == 'on' ? 1 : 0;

	if (empty($name)) {
		$errors[] = tra('Scheduler Task name cannot be empty');
		$addTask = false;
	}

	//@todo improve this validation
	if (empty($task)) {
		$errors[] = tra('Scheduler Task task is invalid');
		$addTask = false;
	}

	if (empty($runTime)) {
		$errors[] = tra('Scheduler Task time cannot be empty');
		$addTask = false;
	}

	//@todo add validation task
//	if (isValid($runTime)) {
//		$errors[] = tra('Scheduler Task time is invalid');
//		$addTask = false;
//	}

	if (empty('status')) {
		$errors[] = tra('Scheduler Task status cannot be empty');
		$addTask = false;
	}

	if ($addTask) {

		$values = array(
			$name,
			$description,
			$task,
			$params,
			$runTime,
			$status,
			$reRun,
		);

		if (!$isUpdate) {

			$query = <<<SQL
INSERT INTO `tiki_scheduler` (`name`, `description`, `task`, `params`, `run_time`, `status`, `re_run`) VALUES
(?, ?, ?, ?, ?, ?, ?);
SQL;
			$feedback = sprintf(tra('Scheduler %s was created.'), $name);
		} else {

			$query = <<<SQL
UPDATE tiki_scheduler 
SET name = ?, description = ?, task = ?, params = ?, run_time = ?, status = ?, re_run = ?
WHERE id = ?;
SQL;

			$values[] = $_POST['scheduler'];
			$feedback = sprintf(tra('Scheduler %s was updated.'), $name);
		}

		$db = TikiDb::get();
		$db->query($query, $values);
		Feedback::success($feedback, 'session');

	} else {
		if (!empty($errors)) {
			Feedback::error(['mes' => $errors], 'session');
			$access = TikiLib::lib('access');
			$access->redirect('tiki-admin_schedulers.php');
			die;
		}
	}
}

require_once('tiki-setup.php');

$access = TikiLib::lib('access');
$access->check_permission(array('tiki_p_admin_schedulers'));

$db = TikiDb::get();

if (isset($_POST['new_scheduler'])) {

	// Add a new scheduler tasks
	$addTask = true;
	saveScheduler(false);
	$cookietab = 1;

} else if (isset($_POST['editscheduler']) and isset($_POST['scheduler'])) {
	saveScheduler(true);
	$cookietab = '1';

} else if (isset($_REQUEST['scheduler']) and $_REQUEST['scheduler']) {

	$sql = <<<SQL
SELECT *
FROM tiki_scheduler
WHERE id = ?
SQL;

	$queryResult = $db->query($sql,[$_REQUEST['scheduler']]);

	if ($row = $queryResult->fetchRow()) {
		$schedulerinfo = $row;

		$query = <<<SQL
SELECT *
FROM tiki_scheduler_run
WHERE scheduler_id = ?
ORDER BY id DESC
LIMIT 10;
SQL;

		$result = $db->fetchAll($query, [$_REQUEST['scheduler']]);
		$schedulerRuns = $result;

	} else {
		$schedulerinfo['name'] = '';
		$schedulerinfo['description'] = '';
		$schedulerinfo['task'] = '';
		$schedulerinfo['run_time'] = '';
		$schedulerinfo['status'] = '';
		$schedulerinfo['re_run'] = '';

		$_REQUEST['scheduler'] = 0;
	}

	$cookietab = '2';

} else {
	$schedulerinfo['name'] = '';
	$schedulerinfo['description'] = '';
	$schedulerinfo['task'] = '';
	$schedulerinfo['run_time'] = '';
	$schedulerinfo['status'] = '';
	$schedulerinfo['re_run'] = '';

	$_REQUEST['scheduler'] = 0;
}

$query = <<<SQL
SELECT *
FROM tiki_scheduler
SQL;

$db = TikiDb::get();
$tasks = $db->fetchAll($query);

$smarty->assign_by_ref('schedulers', $tasks);

if (isset($_REQUEST['add'])) {
	$cookietab = '2';
}

//add tablesorter sorting and filtering
$ts = Table_Check::setVars('admin_schedulers', true);

$smarty->assign('schedulerinfo', $schedulerinfo);
$smarty->assign('schedulerruns', isset($schedulerRuns) ? $schedulerRuns : array());
$smarty->assign('schedulerId', $_REQUEST['scheduler']);
$smarty->assign('schedulerTasks', Scheduler_Item::getAvailableTasks());
$smarty->assign('selectedTask', '');
$smarty->assign('schedulerStatus', array(
	Scheduler_Item::STATUS_ACTIVE => tra('Active'),
	Scheduler_Item::STATUS_INACTIVE => tra('Inactive'),
));

$smarty->assign('cookietab', $cookietab);
$smarty->assign('uses_tabs', 'y');
// disallow robots to index page:
$smarty->assign('metatag_robots', 'NOINDEX, NOFOLLOW');
$smarty->assign('mid', 'tiki-admin_schedulers.tpl');
if ($ts['ajax']) {
	$smarty->display('tiki-admin_schedulers.tpl');
} else {
	$smarty->display('tiki.tpl');
}