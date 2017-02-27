<?php
// (c) Copyright 2002-2016 by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function wikiplugin_casperjs_info()
{
	return array(
		'name' => tra('CasperJS'),
		'documentation' => 'CasperJS',
		'description' => tra('Enables to run CasperJS scripts from tiki'),
		'prefs' => array('wikiplugin_casperjs'),
		'body' => tra('text'),
		'introduced' => 1,
		'iconname' => 'monitor',
		'tags' => array('basic'),
		'validate' => 'arguments',
		'params' => array(),
	);
}

function wikiplugin_casperjs($data, $params)
{
	$htmlResult = '';

	$actionKey = md5(serialize(array($data, $params)));

// Generate Link
	$label = tra('Execute CasperJS script');
	$urlParts = parse_url($_SERVER['REQUEST_URI']);
	$path = isset($urlParts['path']) ? $urlParts['path'] : '/';
	if (isset($urlParts['query'])) {
		parse_str($urlParts['query'], $requestParams);
	} else {
		$requestParams = array();
	}
	$requestParams[$actionKey] = $label;
	$requestParamStr = http_build_query($requestParams, null, '&');
	$link = '<a href='.$path.'?'.$requestParamStr.' class="btn btn-default btn-sm">'.$label.'</a>';

	$htmlResult .= $link;

	$executionResult = '';
// Process request if the action key is set
	if (isset($_REQUEST[$actionKey])) {
		$executionResult .= "<h3>CasperJs Execution Details</h3>";
		$matches = WikiParser_PluginMatcher::match($data);
		foreach ($matches as $match) {
			if ($match->getName() === 'source') {
				$runner = new WikiPlugin_Casperjs_Runner();
				$result = $runner->run($match->getBody());
				$executionResult .= "<strong>CasperJS Script</strong>";
				$executionResult .= "<pre>";
				$executionResult .= $result->getCasperJsScript();
				$executionResult .= "</pre>";
				$executionResult .= "<strong>Console Output</strong>";
				$executionResult .= "<pre>";
				$executionResult .= "$ ".$result->getCommandLine()."\n";
				if (is_array($result->getScriptOutput()) && count($result->getScriptOutput()) > 0) {
					foreach ($result->getScriptOutput() as $line) {
						$executionResult .= $line;
					}
				}
				$executionResult .= "</pre>";
				$executionResult .= "<strong>Tiki Bridge Variables</strong>";
				$executionResult .= "<pre>";
				foreach ($result->getScriptResults() as $key => $value) {
					if (!is_string($value)) {
						$value = print_r($value, true);
					}
					$valueToPrint = str_replace('=&gt;', '=>', htmlspecialchars($value));
					if (strlen($valueToPrint) > 200) {
						$valueToPrint = substr($valueToPrint, 0, 200);
						$valueToPrint .= " ...";
					}
					$executionResult .= $key." => ".$valueToPrint."\n";
				}
				$executionResult .= "</pre>";
			}
		}
	}

	$htmlResult .= $executionResult;

	return $htmlResult;
}
