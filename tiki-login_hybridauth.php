<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
//author : aris002@yahoo.co.uk

$inputConfiguration = [
    [
        'staticKeyFilters'     => [
        'provider'          => 'word',               //post
        ],
    ],
];
require_once('tiki-setup.php');
use Tiki\Sections;
$section = Sections::SECTION_MY_TIKI;
Sections::setCurrentSection($section);

global $prefs;

$access = TikiLib::lib('access');
$access->check_feature('feature_socialnetworks');
$access->check_feature('hybridauth_login_enabled');

use Tiki\Lib\Socnets\Util;
use Tiki\Lib\Socnets\TikiHybrid;

$auto_query_args = [];

use Hybridauth\Storage\Session;

$providerName = '';
$adapter = null;
$tikihybridi = null;
Util::logclear();

try {
    $storage = new Session();
    $error = false;

    //
    // Event 1: User clicked SIGN-IN link
    //
    //if (isset($_REQUEST['provider'])) //TODO some say it is not safe?
    if (isset($_GET['provider'])) {
        $provider = $_GET['provider'];
        //TODO Validate here provider exists in the $prefs?
        $storage->set('provider', $provider);
        $tikihybridi = new TikiHybrid($provider);

        Util::log2('login GET provider=', $provider);

        //header('Location: tiki-index.php');
        //die;
    }

    //
    // Event 2: Provider returns via CALLBACK
    //
    if ($provider = $storage->get('provider')) {
        Util::log2('Provider returns via CALLBACK storage provider:', $provider);
        $tikihybridi->adapter->authenticate();

        $storage->set('provider', null);

        $tikihybridi->login();
        $tikihybridi->adapter->disconnect();
        $tikihybridi = null;
    }
} catch (Throwable $e) {
    Feedback::error(tr('Social network authentication failed. Please try again or contact the site administrator.'));

    TikiLib::lib('errortracking')->captureException($e);
    header('Location: tiki-index.php');
    exit;
}

$smarty->assign('metatag_robots', 'NOINDEX, NOFOLLOW');
$smarty->display("tiki.tpl");
