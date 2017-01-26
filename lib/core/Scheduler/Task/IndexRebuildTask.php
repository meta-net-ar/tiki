<?php
// (c) Copyright 2002-2017 by authors of the Tiki Wiki/CMS/Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Scheduler_Task_IndexRebuildTask
{

	private $errorMessage = '';

	public function execute($params = null)
	{
		$unifiedsearchlib = \TikiLib::lib('unifiedsearch');

		if ($unifiedsearchlib->rebuildInProgress()) {
			$this->errorMessage = 'Index rebuild already in progress';
			return false;
		}

		$result = $unifiedsearchlib->rebuild();

		// Also rebuild admin index
		\TikiLib::lib('prefs')->rebuildIndex();

		if ($result) {
			return true;
		} else {
			$errors = \Feedback::get();
			if (is_array($errors)) {
				foreach ($errors as $message) {
					$this->errorMessage .= $message . "\n";
				}
				$this->errorMessage .= "Search index rebuild failed. Last messages shown above.";
			}
			return false;
		}
	}

	public function getError()
	{
		return $this->errorMessage;
	}

}
