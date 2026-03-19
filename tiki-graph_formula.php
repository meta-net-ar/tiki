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

require_once('tiki-setup.php');

/**
 * @param string $formula
 * @return callable
 */
function convert_formula($formula)
{
    $parser = new Math_Formula_Parser();
    try {
        $element = $parser->parse(trim($formula));
    } catch (Math_Formula_Parser_Exception $e) {
        die('Invalid formula: ' . htmlspecialchars($e->getMessage()));
    }

    $runner = new Math_Formula_Runner(['Math_Formula_Function_' => null]);
    $runner->setFormula($element);

    return function ($x) use ($runner) {
        $runner->setVariables(['x' => $x]);
        return $runner->evaluate();
    };
}

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
    die;
}

switch ($_GET['t']) {
    case 'png':
        $renderer = new GDGRenderer($_GET['w'], $_GET['h']);
        break;
    case 'pdf':
        $renderer = new PDFLibGRenderer($_GET['p'], $_GET['o']);
        break;
    default:
        die;
}

$graph = new MultilineGraphic();
$graph->setTitle($_GET['title']);

$size = ($_GET['max'] - $_GET['min']) / $_GET['s'];

$data = [];
foreach (array_values($_GET['f']) as $key => $formula) {
    $formula = convert_formula($formula);

    $data['x'] = [];
    $data['y' . $key] = [];

    for ($x = $_GET['min']; $_GET['max'] > $x; $x += $size) {
        $data['x'][] = $x;
        $data['y' . $key][] = $formula($x);
    }
}

$graph->setData($data);
$graph->draw($renderer);

$renderer->httpOutput("graph.{$_GET['t']}");
