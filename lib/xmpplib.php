<?php
// (c) Copyright 2002-2016 by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class XMPPLib {
	
	function __construct() {
		global $prefs;
		
		$this->server_host = $prefs['xmpp_server_host'];
		$this->server_http_bind = $prefs['xmpp_server_http_bind'];
	}
	
	function pre_bind($username) {
		
	}
}