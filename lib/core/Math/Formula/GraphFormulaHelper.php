<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Math\Formula;

use Math_Formula_Parser;
use Math_Formula_Parser_Exception;
use Math_Formula_Runner;
use Tiki\Math\Formula\Function\Arithmetic;

/**
 * Graph formula: sanitize, Math_Formula parse/evaluate, legacy-syntax migration hints.
 */
class GraphFormulaHelper
{
    public const SYNTAX_DOC_URL = 'https://doc.tiki.org/Mathematical-Calculation-Tracker-Field';

    private const STRIP_CHARS = ['`', "'", '"', '&', '[', ']', '$', '{', '}'];

    /**
     * @return callable(float|int $x): float|int
     */
    public static function compile(string $original): callable
    {
        $element = self::parseElement($original);

        $runner = new Math_Formula_Runner([
            '__graph_arithmetic__' => Arithmetic::resolver(),
            'Math_Formula_Function_' => null,
        ]);
        $runner->setFormula($element);

        return static function ($x) use ($runner, $original) {
            $runner->setVariables(['x' => $x]);
            try {
                return $runner->evaluate();
            } catch (\Math_Formula_Exception $e) {
                throw new GraphFormulaException($original, $e->getMessage());
            }
        };
    }

    /**
     * @throws GraphFormulaException
     */
    public static function parseElement(string $original): \Math_Formula_Element
    {
        $clean = self::sanitize($original);
        if ($clean === '') {
            throw new GraphFormulaException($original, tra('Empty formula'));
        }

        $parser = new Math_Formula_Parser();

        try {
            return $parser->parse($clean);
        } catch (Math_Formula_Parser_Exception $e) {
            throw new GraphFormulaException($original, $e->getMessage());
        }
    }

    public static function sanitize(string $formula): string
    {
        return trim(str_replace(self::STRIP_CHARS, array_fill(0, count(self::STRIP_CHARS), ''), $formula));
    }

    /**
     * Heuristic: formula looks like the old PHP-ish syntax rather than Tiki Math S-expressions.
     */
    public static function looksLegacy(string $formula): bool
    {
        $formula = trim($formula);
        if ($formula === '' || $formula[0] === '(') {
            return false;
        }

        return (bool) preg_match(
            '/\b[a-zA-Z_]\w*\s*\(|[+\-*\/%]|^[a-zA-Z_]\w*$|^-?(?:\d+(?:\.\d*)?|\.\d+)(?:[eE][+-]?\d+)?$/',
            $formula
        );
    }

    public static function invalidMessage(string $formula, string $detail): string
    {
        $message = tr('Invalid graph formula: %0', $detail);

        if (self::looksLegacy($formula)) {
            $message .= ' ' . tr(
                'Spreadsheet graph formulas use Tiki Math syntax (S-expressions), for example %0 instead of %1. See %2 for the syntax reference.',
                '(sin x)',
                'sin(x)',
                self::SYNTAX_DOC_URL
            );
        }

        return $message;
    }
}
