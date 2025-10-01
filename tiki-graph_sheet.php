<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

use Tiki\Lib\GraphEngine\GDGRenderer;
use Tiki\Lib\GraphEngine\GridBasedGraphic;
use Tiki\Lib\GraphEngine\PDFLibGRenderer;
use Tiki\Lib\GraphEngine\PSGRenderer;
use Tiki\Lib\Sheet\Sheet;
use Tiki\Lib\Sheet\DatabaseHandler;

$inputConfiguration = [
    [
        'staticKeyFilters'                => [
        'sheetId'                         => 'int',               //get
        'graphic'                         => 'word',              //get
        'renderer'                        => 'word',              //get
        'title'                           => 'word',              //get
        'width'                           => 'word',              //get
        'height'                          => 'word',              //get
        'format'                          => 'word',              //get
        'orientation'                     => 'word',              //get
        'independant'                     => 'bool',              //get
        'vertical'                        => 'bool',              //get
        'horizontal'                      => 'bool',              //get
        ],
        'staticKeyFiltersForArrays' => [
            'series'                => 'striptags',
        ],
    ],
];
require_once('tiki-setup.php');

$sheetlib = TikiLib::lib('sheet');

/**
 * @param $serie
 * @param $sheet
 * @return array
 */
function handle_series($serie, &$sheet)
{
    if (! $range = $sheet->getRange($serie)) {
        $range = array_map('trim', explode(',', $serie));
    }

    if (! is_array($range)) {
        return [];
    }

    return $range;
}

// Various validations {{{1

$access->check_feature('feature_sheet');
$access->check_feature('feature_jquery_ui');

if (! isset($_REQUEST['sheetId'])) {
    Feedback::errorAndDie(tra('No sheet specified.'), \Laminas\Http\Response::STATUS_CODE_403);
}

try {
    $info = $sheetlib->get_sheet_info($_REQUEST['sheetId']);
} catch (Exception $e) {
    Feedback::errorAndDie(tra($e->getMessage()), \Laminas\Http\Response::STATUS_CODE_400);
}

$objectperms = Perms::get('sheet', $_REQUEST['sheetId']);
if ($tiki_p_admin != 'y' && ! $objectperms->view_sheet && ! ($user && $info['author'] == $user)) {
    Feedback::errorAndDie(tra('Permission denied'), \Laminas\Http\Response::STATUS_CODE_403);
}

// This condition will be removed when a php-based renderer will be written
if (! function_exists('pdf_new') && ! function_exists('imagepng')) {
    Feedback::errorAndDie(tra('No valid renderer found. GD or PDFLib required.'), \Laminas\Http\Response::STATUS_CODE_404);
}
// }}}1

$valid_graphs = [ 'PieChartGraphic', 'MultilineGraphic', 'MultibarGraphic', 'BarStackGraphic' ];
$valid_renderers = [ 'PNG', 'JPEG', 'PDF', 'PS' ];

if (! empty($_REQUEST['graphic']) && ! in_array($_REQUEST['graphic'], $valid_graphs)) {
    Feedback::errorAndDie(tra('Unknown Graphic.'), \Laminas\Http\Response::STATUS_CODE_409);
}
if (! empty($_REQUEST['renderer']) && ! in_array($_REQUEST['renderer'], $valid_renderers)) {
    Feedback::errorAndDie(tra('Unknown Renderer.'), \Laminas\Http\Response::STATUS_CODE_409);
}

$smarty->assign('sheetId', $_REQUEST["sheetId"]);

$smarty->assign('title', $info['title']);
$smarty->assign('description', $info['description']);

$smarty->assign('page_mode', 'form');

// Process the insertion or modification of a gallery here

$sheetId = $_REQUEST['sheetId'];

if (isset($_REQUEST['title'])) {
    $cache_file = TEMP_CACHE_PATH . '/tsge_' . md5($_SERVER['REQUEST_URI']);

    switch ($_REQUEST['renderer']) {
        case 'PNG':
            $renderer = new GDGRenderer($_REQUEST['width'], $_REQUEST['height'], 'png');
            $ext = 'png';
            break;

        case 'JPEG':
            $renderer = new GDGRenderer($_REQUEST['width'], $_REQUEST['height'], 'jpg');
            $ext = 'jpg';
            break;

        case 'PDF':
            $renderer = new PDFLibGRenderer($_REQUEST['format'], $_REQUEST['orientation']);
            $ext = 'pdf';
            break;

        case 'PS':
            $renderer = new PSGRenderer($_REQUEST['format'], $_REQUEST['orientation']);
            $ext = 'ps';
            break;
        default:
            Feedback::errorAndDie(tra('You must select a renderer.'), \Laminas\Http\Response::STATUS_CODE_409);
    }

    if (file_exists($cache_file) && time() - filemtime($cache_file) < 3600) {
        $renderer->httpOutput("graph.$ext");
        readfile($cache_file);
        exit;
    }

    $handler = new DatabaseHandler($sheetId);
    $grid = new Sheet();
    $grid->import($handler);

    $graph = $_REQUEST['graphic'];
    $graph = new $graph();

    // Create Output
    $series = [];
    foreach ($_REQUEST['series'] as $key => $value) {
        if (! empty($value)) {
            $s = handle_series($value, $grid);
            if (count($s) > 0) {
                $series[$key] = $s;
            }
        }
    }

    if (! $graph->setData($series)) {
        Feedback::errorAndDie(tra('Invalid Series for current graphic.'), \Laminas\Http\Response::STATUS_CODE_409);
    }

    if (! empty($_REQUEST['title'])) {
        $graph->setTitle($_REQUEST['title']);
    }

    if (isset($_REQUEST['independant'])) {
        $graph->setParam('grid-independant-location', $_REQUEST['independant']);
        $graph->setParam('grid-vertical-position', $_REQUEST['vertical']);
        $graph->setParam('grid-horizontal-position', $_REQUEST['horizontal']);
    }

    $graph->draw($renderer);

    ob_start();
    $renderer->httpOutput("graph.$ext");
    $content = ob_get_contents();
    ob_end_flush();

    file_put_contents($cache_file, $content);

    exit;
} else {
    if (isset($_GET['graphic']) && in_array($_GET['graphic'], $valid_graphs)) {
        $graph = $_GET['graphic'];
        $g = new $graph();
        $series = [];
        foreach (array_keys($g->getRequiredSeries()) as $s) {
            if ($s == 'y0') {
                $series[] = 'y0';
                $series[] = 'y1';
                $series[] = 'y2';
                $series[] = 'y3';
                $series[] = 'y4';
            } else {
                $series[] = $s;
            }
        }

        $smarty->assign('mode', 'param');
        $smarty->assign('series', $series);
        $smarty->assign('graph', $graph);
        $smarty->assign('renderer', $_REQUEST['renderer']);

        $handler = new DatabaseHandler($sheetId);
        $grid = new Sheet($_REQUEST['sheetId']);
        $grid->import($handler);

        $dataGrid = $grid->getTableHtml(true);

        require_once('lib/Sheet/grid.php');
        $sheetlib->setup_jquery_sheet();
        $headerlib->add_jq_onready(
            '$("div.tiki_sheet").sheet($.extend($.sheet.tikiOptions, {editable: false}));'
        );

        $smarty->assign('dataGrid', $dataGrid);

        if (function_exists('pdf_new')) {
            $smarty->assign('format', $_GET['format']);
            $smarty->assign('orientation', $_GET['orientation']);
        }

        if (function_exists('imagepng')) {
            $smarty->assign('im_width', $_GET['width']);
            $smarty->assign('im_height', $_GET['height']);
        }

        if (is_a($g, GridBasedGraphic::class)) {
            $smarty->assign('showgridparam', true);
        }
    } else {
        $smarty->assign('mode', 'graph');
        $smarty->assign('hasgd', function_exists('imagepng') && function_exists('imagejpeg'));
        $smarty->assign('haspdflib', function_exists('pdf_new'));
        $smarty->assign('hasps', function_exists('ps_new'));
    }
}

// Display the template
$smarty->assign('mid', 'tiki-graph-sheets.tpl');
$smarty->display("tiki.tpl");
