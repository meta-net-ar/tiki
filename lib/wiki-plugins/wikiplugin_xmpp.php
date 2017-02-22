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

	$headerlib->add_cssfile('vendor/jcbrand/converse.js/css/converse.css');
	$headerlib->add_jsfile('vendor/jcbrand/converse.js/dist/converse.js');

	$headerlib->add_jq_onready(
		 'jQuery(window).on("converse-loaded", function (evt, converse) {'
		.'	converse.initialize({'
		.'		bosh_service_url: "' . $xmpplib->server_http_bind . '",'
		.'		authentication: "prebind",'
		.'		prebind_url: "/tiki-json.php?lib=xmpp&action=prebind",'
		.'		i18n: locales.en,'
		.'		show_controlbox_by_default: false,'
		.'		roster_groups: false'
		.'	});'
		.'});'
	);
	
	return $out;
}
