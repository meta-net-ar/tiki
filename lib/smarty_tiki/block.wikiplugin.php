<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 *
 */

//this script may only be included - so its better to die if called directly.
if (strpos($_SERVER["SCRIPT_NAME"], basename(__FILE__)) !== false) {
	header("location: index.php");
	exit;
}

function smarty_block_wikiplugin($params, $content, $smarty, $repeat = false)
{

	if ($repeat) {
		return '';
	}

	if (! isset($params['_name'])) {
		return '<div class="alert alert-warning">' . tra('Plugin name not specified.') . '</div>';
	}

	$name = $params['_name'];
	unset($params['_name']);

	if (! empty($params['_compactArguments_'])) {
		$params = $params['_compactArguments_'];
	}

	// render the block as wiki syntax (useful when using inside other wikiplugin block)
	// IN: {wikiplugin _name="tracker" trackerId="18" url="this-page?itemId=$result.object_id" _wikiSyntax=y}{/wikiplugin}
	// OUT: {TRACKER (trackerId="18" url="this-page?itemId=$result.object_id")}{TRACKER}
	$wikiSyntax = isset($params['_wikiSyntax']) && $params['_wikiSyntax'] == 'y';
	unset($params['_wikiSyntax']);

	if ($wikiSyntax) {
		$options = '';
		foreach ($params as $key => $value) {
			$options .= sprintf( ' %s=%s', $key, escapeshellarg($value));
		}

		return sprintf('{%1$s(%2$s)}%3$s{%1$s}', strtoupper($name), $options, $content);
	}

	$parserlib = TikiLib::lib('parser');
	$out = $parserlib->plugin_execute(
		$name,
		$content,
		$params,
		0,
		false,
		[
			'context_format' => 'html',
			'ck_editor' => false,
			'is_html' => true
		]
	);
	$parserlib->setOptions();
	return $out;
}
