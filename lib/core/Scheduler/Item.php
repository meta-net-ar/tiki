<?php
// (c) Copyright 2002-2017 by authors of the Tiki Wiki/CMS/Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Scheduler_Item
{

	public $id;
	public $name;
	public $description;
	public $task;
	public $params;
	public $run_time;
	public $status;
	public $re_run;

	const STATUS_ACTIVE = 'active';
	const STATUS_INACTIVE = 'inactive';

	static $availableTasks = array(
		'IndexRebuildTask' => 'IndexRebuild',
		'ListExecuteTask' => 'ListExecute',
	);

	public function __construct($id, $name, $description, $task, $params, $run_time, $status, $re_run)
	{
		$this->id = $id;
		$this->name = $name;
		$this->description = $description;
		$this->task = $task;
		$this->params = $params;
		$this->run_time = $run_time;
		$this->status = $status;
		$this->re_run = $re_run;
	}

	public static function getAvailableTasks()
	{
		return self::$availableTasks;
	}

	/**
	 * @return array
	 */
	public function execute()
	{

		$query = <<<SQL
SELECT status
FROM tiki_scheduler_run
WHERE scheduler_id = ?
ORDER BY id DESC
LIMIT 1;
SQL;

		$db = TikiDb::get();
		$status = $db->getOne($query, [$this->id]);

		if ($status == 'running') {
			// @todo add register to log
			return array(
				'status' => 'failed',
				'message' => tra('Scheduler task already running.')
			);
		}

		$class = 'Scheduler_Task_' . $this->task;
		if (class_exists($class)) {
			$task = new $class();
			$startTime = time();

			$query = <<<SQL
INSERT INTO tiki_scheduler_run (scheduler_id, start_time, status)
VALUES (?, ?, 'running');
SQL;

			$db->query($query, [$this->id, $startTime]);

			$result = $task->execute($this->params);

			$executionStatus = $result ? 'done' : 'failed';
			$errorMessage = $task->getError();
			$endTime = time();

			$query = <<<SQL
UPDATE tiki_scheduler_run
SET status = ?, end_time = ?, output = ?
WHERE scheduler_id = ?
AND status = 'running'
AND start_time = ?
LIMIT 1;
SQL;

			$db->query($query, [$executionStatus, $endTime, $errorMessage, $this->id, $startTime]);

			return array(
				'status' => $executionStatus,
				'message' => $errorMessage,
			);
		}

		return array(
			'status' => 'failed',
			'message' => $class . ' not found.',
		);
	}
}