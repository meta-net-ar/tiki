<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
$inputConfiguration = [[
        'staticKeyFilters'  => [
            'itemId'        => 'int',
            'fieldId'       => 'int',
            'version'       => 'int',
            'offset'        => 'int',
            'diff_style'    => 'word',
        ]],
            ['catchAllUnset' => null],
];
require_once('tiki-setup.php');
use Tiki\Sections;
$section = Sections::SECTION_TRACKERS;
$access->check_feature('feature_trackers');

if (empty($_REQUEST["itemId"])) {
    Feedback::errorAndDie(tra("No tracker item indicated"), \Laminas\Http\Response::STATUS_CODE_400);
}
$broker = TikiLib::lib('service')->getBroker();
$broker->process('tracker', 'item_history', $jitRequest);
