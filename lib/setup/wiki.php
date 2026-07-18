<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
if (basename($_SERVER['SCRIPT_NAME']) === basename(__FILE__)) {
    die('This script may only be included.');
}

// Wiki pagename regexp

if ($prefs['wiki_page_regex'] == 'strict') {
    $page_regex = '([A-Za-z0-9_])([\.: A-Za-z0-9_\-])*([A-Za-z0-9_])';
} elseif ($prefs['wiki_page_regex'] == 'full') {
    $page_regex = '([A-Za-z0-9_]|[\x80-\xFF])([\.: A-Za-z0-9_\-]|[\x80-\xFF])*([A-Za-z0-9_]|[\x80-\xFF])';
} else {
    $page_regex = '([^\n|\(\)])((?!(\)\)|\||\n)).)*?';
}

// find out the page name if url=tiki-index_x.php (can be needed in module)
if (
    str_contains($_SERVER['SCRIPT_NAME'], 'tiki-index.php')
        || str_contains($_SERVER['SCRIPT_NAME'], 'tiki-index_p.php')
        || str_contains($_SERVER['SCRIPT_NAME'], 'tiki-index_raw.php')
) {
    $check = false;
    $userDefaultHomepage = $userlib->get_user_default_homepage($user);
    if ((! isset($_REQUEST['page']) && ! isset($_REQUEST['page_ref_id']) && ! isset($_REQUEST['page_id'])) || (isset($_REQUEST['page']) && $_REQUEST['page'] === $userDefaultHomepage)) {
        $_REQUEST['page'] = $userDefaultHomepage;
        $check = true;
    }

    if (
        $prefs['feature_multilingual'] == 'y'
            && (isset($_REQUEST['page']) || isset($_REQUEST['page_ref_id']) || isset($_REQUEST['page_id']))
    ) { // perhaps we have to go to an another page
        $multilinguallib = TikiLib::lib('multilingual');
        if ($multilinguallib->useBestLanguage()) {
            if (empty($_REQUEST['page_id'])) {
                if (! empty($_REQUEST['page'])) {
                    $info = $tikilib->get_page_info($_REQUEST['page']);
                    if (! empty($info['page_id'])) {
                        $_REQUEST['page_id'] = $info['page_id'];
                    }
                } elseif (! empty($_REQUEST['page_ref_id'])) {
                    $structlib = TikiLib::lib('struct');
                    $info = $structlib->s_get_page_info($_REQUEST['page_ref_id']);
                    if (! empty($info['page_id'])) {
                        $_REQUEST['page_id'] = $info['page_id'];
                    }
                }
            }
            if (! empty($_REQUEST['page_id'])) {
                if ($multilinguallib->useBestLanguage()) {
                    $_REQUEST['page_id'] = $multilinguallib->selectLangObj('wiki page', $_REQUEST['page_id']);
                }
                if (! empty($_REQUEST['page_id'])) {
                    $check = false;
                }
            }
        }
    }

    // If the HomePage does not exist, create it
    if ($check && ! empty($_REQUEST['page'])) {
        TikiLib::lib('wiki')->createDefaultHomePage($_REQUEST['page']);
    }
}
