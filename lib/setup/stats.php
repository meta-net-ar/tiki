<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

use Tiki\Sections;

if (basename($_SERVER['SCRIPT_NAME']) === basename(__FILE__)) {
    die('This script may only be included.');
}

$section = Sections::getCurrentSection();

if ($prefs['feature_referer_stats'] == 'y') {
    if (isset($_SERVER['HTTP_REFERER'])) {
        $pref = parse_url($_SERVER['HTTP_REFERER']);
        if (isset($pref['host']) && ! str_contains($_SERVER['SERVER_NAME'], $pref['host'])) {
            $tikilib->register_referer($pref['host'], $_SERVER['HTTP_REFERER']);
        }
    }
}

if (StatsLib::is_stats_hit()) {
    if (! isset($section) or ( ! Sections::isCurrentSection(Sections::SECTION_CHAT) and ! Sections::isCurrentSection(Sections::SECTION_LIVESUPPORT) )) {
        $statslib = TikiLib::lib('stats');
        $statslib->add_pageview();
    }
}
