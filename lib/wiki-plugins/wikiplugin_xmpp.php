<?php
// (c) Copyright 2002-2016 by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function wikiplugin_xmpp_info()
{
	return array(
		'name' => tra('XMPP'),
		'documentation' => 'PluginXMPP',
		'description' => tra('Hold a chat session using XMPP'),
		'format' => 'html',
		'prefs' => array( 'wikiplugin_xmpp', 'xmpp_feature' ),
		'iconname' => 'webchat',
		'introduced' => 5,
		'tags' => array( 'basic' ),
		'params' => array(
		),
	);
}

function wikiplugin_xmpp( $data, $params )
{
	global $prefs, $user;
	$headerlib = TikiLib::lib('header');
	$xmpplib = TikiLib::lib('xmpp');

	// Converse.js overwrite Tiki jQuery breaks other libraries, so we have to
	// save tikiJQuery before loading converse.js. After load converse.js, we
	// can restore tikiJQuery to $.
	// TODO: talk to JC and ask to release a version without jQuery bundled
	$headerlib->add_jq_onready(
		'var tikiJQuery = $;'

		.'jQuery("<link>")'
		.'    .attr("rel", "stylesheet")'
		.'    .attr("href", "vendor/jcbrand/converse.js/css/converse.css")'
		.'    .appendTo(document.body);'

		.'jQuery("<script>")'
		.'    .attr("type", "text/javascript")'
		.'    .attr("src", "vendor/jcbrand/converse.js/dist/converse.js")'
		.'    .appendTo(document.body);'

		.'jQuery(window).on("converse-loaded", function (evt, converse) {'
		.'	window.$ = tikiJQuery;'
		.'	converse.initialize({'
		.'		bosh_service_url: "' . $xmpplib->server_http_bind . '",'
		.'		authentication: "prebind",'
		.'		prebind_url: $.service("xmpp", "prebind"),'
		.'		i18n: locales.en,'
		.'		show_controlbox_by_default: false,'
		.'		roster_groups: false'
		.'	});'
		.'});'
	);
	
	return '';
}
