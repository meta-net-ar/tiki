<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

use Tiki\Lib\GraphEngine\GDGRenderer;
use Tiki\Lib\GraphEngine\MultilineGraphic;

$inputConfiguration = [
    [
        'staticKeyFilters'         => [
        'days'                     => 'int',             //get
        ],
    ],
];
require_once('tiki-setup.php');
$access->check_feature('feature_stats');
$access->check_permission('tiki_p_view_stats');

//Define the object
$renderer = new GDGRenderer(450, 300);
$graph = new MultilineGraphic();
$graph->setTitle(tra('Pageviews'));
//Set some data
if (! isset($_REQUEST["days"])) {
    $_REQUEST["days"] = 7;
}

$statslib = TikiLib::lib('stats');
$data = $statslib->get_pv_chart_data($_REQUEST["days"]);

foreach ($data['xdata'] as $key => $date) {
     $data['xdata'][$key] = strtotime($date) / 24 / 3600;
}
$graph->setData(['x' => $data['xdata'], 'y0' => $data['ydata']]);
$graph->setParam('grid-independant-major-font', false);
$graph->setParam('grid-independant-major-guide', false);
$graph->draw($renderer);
$renderer->httpOutput('stats.png');
