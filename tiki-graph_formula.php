<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

use Tiki\Lib\GraphEngine\GDGRenderer;
use Tiki\Lib\GraphEngine\MultilineGraphic;
use Tiki\Lib\GraphEngine\PDFLibGRenderer;
use Tiki\Math\Formula\GraphFormulaException;
use Tiki\Math\Formula\GraphFormulaHelper;

$inputConfiguration = [
    [
        'staticKeyFilters' => [
            'w'     => 'int',
            'h'     => 'int',
            's'     => 'int',
            'min'   => 'float',
            'max'   => 'float',
            't'     => 'word',
            'title' => 'text',
            'p'     => 'word',
            'o'     => 'word',
        ],
        'staticKeyFiltersForArrays' => [
            'f' => 'text',
        ],
    ],
];
require_once('tiki-setup.php');

$access->check_feature('feature_sheet');
$access->check_permission('feature_sheet');

if (
    ! ( is_numeric($_GET['w'])
    && is_numeric($_GET['h'])
    && is_numeric($_GET['s'])
    && $_GET['s'] <= 500 && $_GET['s'] > 0
    && is_numeric($_GET['min'])
    && is_numeric($_GET['max'])
    && is_array($_GET['f'])
    && $_GET['min'] < $_GET['max']
    && $_GET['w'] >= 100
    && $_GET['h'] >= 100 )
) {
    Feedback::errorAndDie(tra('Invalid graph parameters.'), \Laminas\Http\Response::STATUS_CODE_400);
}

switch ($_GET['t']) {
    case 'png':
        $renderer = new GDGRenderer($_GET['w'], $_GET['h']);
        break;
    case 'pdf':
        $renderer = new PDFLibGRenderer($_GET['p'], $_GET['o']);
        break;
    default:
        Feedback::errorAndDie(tra('Invalid graph output type.'), \Laminas\Http\Response::STATUS_CODE_400);
}

$graph = new MultilineGraphic();
$graph->setTitle($_GET['title'] ?? '');

$size = ($_GET['max'] - $_GET['min']) / $_GET['s'];

$data = [];
foreach (array_values($_GET['f']) as $key => $formula) {
    try {
        $evaluator = GraphFormulaHelper::compile($formula);
    } catch (GraphFormulaException $e) {
        Feedback::errorAndDie($e->getUserMessage(), \Laminas\Http\Response::STATUS_CODE_400);
    }

    $data['x'] = [];
    $data['y' . $key] = [];

    for ($x = $_GET['min']; $_GET['max'] > $x; $x += $size) {
        $data['x'][] = $x;
        try {
            $data['y' . $key][] = $evaluator($x);
        } catch (GraphFormulaException $e) {
            Feedback::errorAndDie($e->getUserMessage(), \Laminas\Http\Response::STATUS_CODE_400);
        }
    }
}

$graph->setData($data);
$graph->draw($renderer);

$renderer->httpOutput("graph.{$_GET['t']}");
