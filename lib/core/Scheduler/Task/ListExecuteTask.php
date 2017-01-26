<?php
// (c) Copyright 2002-2017 by authors of the Tiki Wiki/CMS/Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Scheduler_Task_ListExecuteTask
{

	private $errorMessage;

	/**
	 * @param $params
	 * @return array
	 */
	private function parseParams($params)
	{
		$regex = '/"([^"]*)" *"([^"]*)"/';

		preg_match($regex, $params, $matches);

		$parsedParams = array(
			'page' => $matches[1],
			'action' => $matches[2],
		);

		return $parsedParams;
	}

	function execute($params)
	{
		$params = $this->parseParams($params);

		$page = $params['page'];
		$action = $params['action'];

		$tikilib = \TikiLib::lib('tiki');
		if (!$pageInfo = $tikilib->get_page_info($page)) {
			$this->errorMessage = "Page $page not found.";
			return false;
		}

		$_POST['list_action'] = $action;
		$_POST['objects'] = array('ALL');

		$tikilib->parse_data($pageInfo['data']);

		return true;
	}

	public function getError()
	{
		return $this->errorMessage;
	}

}
