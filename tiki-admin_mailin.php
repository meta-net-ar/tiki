<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
$inputConfiguration = [
    [
        'staticKeyFilters'            => [
            'mailin_autocheckFreq'    => 'int',  //post
            'mailin_autocheck'        => 'alpha', //post
            'set_auto'                => 'alpha', //post
        ],
    ],
];
require_once('tiki-setup.php');
use Tiki\Sections;
$section = Sections::SECTION_ADMIN;
Sections::setCurrentSection($section);
$access->check_feature('feature_mailin');
$access->check_permission(['tiki_p_admin_mailin']);

$mailinlib = TikiLib::lib('mailin');

// List
$accounts = $mailinlib->list_mailin_accounts(0, -1, 'account_asc', '');
$smarty->assign('accounts', $accounts['data']);

if (isset($_REQUEST['mailin_autocheck'])) {
    if (
        $_REQUEST['mailin_autocheck'] == 'y' && ! (preg_match('/[0-9]+/', $_REQUEST['mailin_autocheckFreq'])
            && $_REQUEST['mailin_autocheckFreq'] > 0)
    ) {
        Feedback::warning(tra('Frequency should be a positive integer!'));
    } else {
        $tikilib->set_preference('mailin_autocheck', $_REQUEST['mailin_autocheck']);
        $tikilib->set_preference('mailin_autocheckFreq', $_REQUEST['mailin_autocheckFreq']);
        if ($prefs['mailin_autocheck'] == 'y') {
            Feedback::warning(sprintf(
                tra('Mail-in accounts set to be checked every %s minutes'),
                $prefs['mailin_autocheckFreq']
            ));
        } else {
            Feedback::warning(tra('Automatic Mail-in accounts checking disabled'));
        }
    }
}

$artlib = TikiLib::lib('art');
$headerlib = TikiLib::lib('header');
$trklib = TikiLib::lib('trk');

$smarty->assign('mailin_types', $mailinlib->list_available_types());
// check package availability

$headerlib->add_cssfile(NODE_PUBLIC_DIST_PATH . '/fieldslinker/dist/fieldsLinker.css');
$headerlib->add_jsfile(NODE_PUBLIC_DIST_PATH . '/fieldslinker/dist/fieldsLinker.js', true);
$headerlib->add_css('#original {width: 100%;}');

$smarty->assign('mailin_types', $mailinlib->list_available_types());

// disallow robots to index page:
$smarty->assign('metatag_robots', 'NOINDEX, NOFOLLOW');

$smarty->assign('mid', 'tiki-admin_mailin.tpl');
$smarty->display('tiki.tpl');
