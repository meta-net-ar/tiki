<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
use Search_Expr_Token as Token;
use Search_Expr_And as AndX;
use Search_Expr_Or as OrX;
use Search_Expr_Not as NotX;
use Search_Expr_MoreLikeThis as MoreLikeThis;

class Search_MySql_FieldQueryBuilder
{
    private $invert = false;
    private $boolean_or = ' ';
    private $escapeCallback;

    public function build(Search_Expr_Interface $expr, Search_Type_Factory_Interface $factory)
    {
        $invert = false;
        $string = $expr->walk(
            function ($node, $childNodes) use ($factory, &$invert) {
                if ($node instanceof Token) {
                    $string = $node->getValue($factory)->getValue();
                    if (is_array($string)) {
                        $string = implode(' ', $string);
                    }
                    if ($this->escapeCallback) {
                        $string = call_user_func($this->escapeCallback, $string);
                    }
                    if (false === strpos($string, ' ') && substr($string, 0, 1) !== '-') {
                        return $string;
                    } else {
                        return '"' . $string . '"';
                    }
                } elseif ($node instanceof OrX) {
                    foreach ($childNodes as $node) {
                        if ($node[0] == '-') {
                            throw new Search_MySql_QueryException('Semantic impossible to express.');
                        }
                    }
                    return (count($childNodes) == 1)
                        ? reset($childNodes)
                        : '(' . implode($this->boolean_or, $childNodes) . ')';
                } elseif ($node instanceof AndX) {
                    $negatives = 0;
                    foreach ($childNodes as $node) {
                        if ($node[0] == '-') {
                            $negatives++;
                        }
                    }

                    // When all the conditions are negative, the index will score 0 (bad)
                    if ($negatives == count($childNodes)) {
                        $childNodes = array_map(
                            function ($node) {
                                return substr($node, 1);
                            },
                            $childNodes
                        );
                        $invert = true;

                        return (count($childNodes) == 1)
                            ? reset($childNodes)
                            : '(' . implode(' ', $childNodes) . ')';
                    }
                    return (count($childNodes) == 1)
                        ? reset($childNodes)
                        : '(+' . implode(' +', $childNodes) . ')';
                } elseif ($node instanceof NotX) {
                    return '-' . reset($childNodes);
                } elseif ($node instanceof MoreLikeThis) {
                    return implode(' ', $childNodes);
                } else {
                    throw new Search_MySql_QueryException('Expression not supported: ' . get_class($node));
                }
            }
        );
        $this->invert = $invert;

        $string = str_replace('+-', '-', $string);
        return $string;
    }

    public function isInverted()
    {
        return $this->invert;
    }

    public function setBooleanOrOperator($op = ' ')
    {
        $this->boolean_or = $op;
    }

    public function setEscapeCallback($cb)
    {
        $this->escapeCallback = $cb;
    }
}
