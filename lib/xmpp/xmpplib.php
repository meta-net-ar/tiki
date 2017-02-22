<?php
// (c) Copyright 2002-2016 by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$


class TikiXmppPrebind extends XmppPrebind {
	function set_encryption_mechanism($encryption) {
		$this->encryption = $encryption;
	}
}


class XMPPLib extends TikiLib {
	
	function __construct() {
		global $prefs;
		
		$this->server_host = $prefs['xmpp_server_host'];
		$this->server_http_bind = $prefs['xmpp_server_http_bind'];

		if ( !class_exists('XmppPrebind') ) {
			throw new Exception(
				"class 'XmppPrebind' does not exists."
				. " Install with `composer require candy-chat/xmpp-prebind-php:dev-master`.",
				1
			);
		}
	}
	
	function get_user_password($user) {
		$query  = "SELECT value FROM `tiki_user_preferences` WHERE `user`=?";
		$query .= " AND `prefName`='xmpp_password';";

		$result = $this->query($query, array($user));
		$ret = $result->fetchRow();

		if( count($ret) === 1 ) {
			return $ret['value'];
		}
	}
	
	function prebind($user) {
		global $prefs;
		$action = $_GET['action'];

		if(empty($this->server_host) ||  empty($this->server_http_bind)){
			return array();
		}

		$userprefslib = TikiLib::lib('userprefs');
		$cryptlib = TikiLib::lib('crypt');
		
		$xmpp_username = $user;
		$xmpp_password = $this->get_user_password($user);
	
		$xmppPrebind = new TikiXmppPrebind(
			$this->server_host,
			$this->server_http_bind,
			'tikiwiki',
			false, false
		);

		$xmppPrebind->connect($xmpp_username, $xmpp_password);
		$xmppPrebind->set_encryption_mechanism(XmppPrebind::ENCRYPTION_PLAIN);
		$xmppPrebind->auth();
		$sessionInfo = $xmppPrebind->getSessionInfo();
		
		return $sessionInfo;
	}
}

// $events->bind('tiki.user.login', 'tiki.view');

class TikiJsonResponse {

	function get() {
		global $user;
		$xmpplib = TikiLib::lib('xmpp');
		return json_encode($xmpplib->prebind($user));
	}

}