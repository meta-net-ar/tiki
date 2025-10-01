<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

use Tiki\Lib\GraphEngine\GDGRenderer;
use Tiki\Lib\GraphEngine\MultibarGraphic;

require_once('tiki-setup.php');

$access->check_feature('feature_stats', '', 'general');
$access->check_permission('tiki_p_view_stats');

$statslib = TikiLib::lib('stats');

//Define the object
if (isset($_REQUEST["type"])) {
    if ($_REQUEST["type"] == "daily") {
        $renderer = new GDGRenderer(450, 400);
        $graph = new MultibarGraphic();
        $data = $statslib->get_daily_usage_chart_data();
        $graph->setTitle(tra('Daily Usage'));
        $graph->setData(['x' => $data['xdata'], 'y0' => $data['ydata']]);
        $graph->setParam('grid-independant-location', 'vertical');
        $graph->setParam('grid-independant-major-font', 'Normal-Text');
        $graph->setParam('grid-independant-major-guide', false);
    }
} else {
    $renderer = new GDGRenderer(450, 300);
    $graph = new MultibarGraphic();
    $data = $tikilib->get_usage_chart_data();
    $graph->setTitle(tra('Usage'));
    $graph->setData(['x' => $data['xdata'], 'y0' => $data['ydata']]);
    $graph->setParam('grid-independant-location', 'vertical');
    $graph->setParam('grid-independant-major-font', 'Normal-Text');
    $graph->setParam('grid-independant-major-guide', false);
}
$graph->draw($renderer);
$renderer->httpOutput('stats.png');
