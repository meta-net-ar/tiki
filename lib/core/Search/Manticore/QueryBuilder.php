<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Search\Manticore;

use Search_Expr_Token as Token;
use Search_Expr_And as AndX;
use Search_Expr_Or as OrX;
use Search_Expr_Not as NotX;
use Search_Expr_Range as Range;
use Search_Expr_Initial as Initial;
use Search_Expr_MoreLikeThis as MoreLikeThis;
use Search_Expr_ImplicitPhrase as ImplicitPhrase;
use Search_Expr_Distance as Distance;

class QueryBuilder
{
    private $index;
    private $pdo_client;
    private $factory;
    private $fieldBuilder;
    private $match;
    private $select;

    public function __construct(Index $index)
    {
        $this->index = $index;
        $this->pdo_client = $index->getPdoClient();
        $this->factory = new TypeFactory();
        $this->fieldBuilder = new \Search_MySql_FieldQueryBuilder();
        $this->fieldBuilder->setBooleanOrOperator(' | ');
        $this->fieldBuilder->setEscapeCallback([$this, 'escapeQueryString']);
        $this->match = [];
        $this->select = [];
    }

    public function build(\Search_Expr_Interface $expr)
    {
        $subq = $expr->walk($this);

        if ($this->match) {
            $query = "match('" . implode(' ', $this->match) . "')";
            if ($subq) {
                $query .= ' and ' . $subq;
            }
        } elseif (! empty($subq['match'])) {
            $query = "match('" . $subq['match'] . "')";
        } else {
            $query = $subq;
        }

        return [
            'query' => $query,
            'select' => $this->select,
        ];
    }

    public function __invoke($node, $childNodes)
    {
        $exception = null;

        if ($node instanceof ImplicitPhrase) {
            $node = $node->getBasicOperator();
        }

        $fields = $this->getFields($node);

        if ($node instanceof Token && count($fields) == 1 && $this->getQuoted($node) === $this->pdo_client->quote('')) {
            $field = $this->getField($node);
            Index::addSearchedField($node->getField(), 'others');
            $mapping = $this->index ? $this->index->getFieldMapping($field) : new stdClass();
            if (isset($mapping['types']) && (in_array('multi', $mapping['types']) || in_array('mva', $mapping['types']))) {
                $key = 'tf_' . uniqid();
                $this->select[$key] = 'LENGTH(' . $field . ')';
                return "$key = 0";
            } else {
                $value = $this->getQuoted($node);
                return "$field = $value";
            }
        }

        try {
            if (! $node instanceof NotX && count($fields) == 1 && $this->isFullText($node)) {
                $query = $this->fieldBuilder->build($node, $this->factory);
                if ($node instanceof MoreLikeThis) {
                    $type = $node->getObjectType();
                    $object = $node->getObjectId();
                    $query = $this->getDocumentContent($type, $object);
                }

                if (preg_match('/^[\d\.]+$/', $query)) {
                    // version strings should be phrases
                    $query = '"' . $query . '"';
                }
                $query = "@{$fields[0]} $query";
                if ($this->fieldBuilder->isInverted()) {
                    $query = "!($query)";
                }
                $originalFields = [];
                $node->walk(
                    function ($node) use (&$originalFields) {
                        if (method_exists($node, 'getField')) {
                            $originalFields[] = $node->getField();
                        }
                    }
                );
                Index::addSearchedField($originalFields[0], 'fulltext');
                return ['match' => $query];
            }
        } catch (\Search_MySql_QueryException $e) {
            // Try to build the query with the SQL logic when fulltext is not an option
            $exception = $e;
        }

        if (count($childNodes) === 0 && ($node instanceof AndX || $node instanceof OrX)) {
            return '';
        } elseif (count($childNodes) === 1 && ($node instanceof AndX || $node instanceof OrX)) {
            return reset($childNodes);
        } elseif ($node instanceof OrX) {
            $matches = [];
            $non_fulltext = array_filter($childNodes, function ($elem) use (&$matches) {
                if (! empty($elem['match'])) {
                    $matches[] = $elem['match'];
                    return false;
                }
                return ! empty($elem);
            });
            if ($matches) {
                if ($non_fulltext) {
                    throw new \Exception(tr('Manticore doesn\'t support fulltext search combined with OR filtering. Please use fulltext only fields in the default content fields.'));
                }
                $this->match[] = '(' . implode(') | (', $matches) . ')';
                return '';
            } elseif ($non_fulltext) {
                $result = '(' . implode(' OR ', $non_fulltext) . ')';
                while (preg_match('/\(\(([^)]* OR [^)]*)\) OR ([^()]*)\)/', $result, $m)) {
                    $result = '(' . $m[1] . ' OR ' . $m[2] . ')';
                }
                while (preg_match('/\(\(([^)]* OR [^)]*)\) OR (\([^)]*\))\)/', $result, $m)) {
                    $result = '(' . $m[1] . ' OR ' . $m[2] . ')';
                }
                return $result;
            } else {
                return '';
            }
        } elseif ($node instanceof AndX) {
            $matches = [];
            $non_fulltext = array_filter($childNodes, function ($elem) use (&$matches) {
                if (! empty($elem['match'])) {
                    $matches[] = $elem['match'];
                    return false;
                }
                return ! empty($elem);
            });
            if ($matches) {
                $this->match[] = '(' . implode(') (', $matches) . ')';
            }
            if ($non_fulltext) {
                $result = '(' . implode(' AND ', $non_fulltext) . ')';
                while (preg_match('/\(\(([^)]* AND [^)]*)\) AND ([^()]*)\)/', $result, $m)) {
                    $result = '(' . $m[1] . ' AND ' . $m[2] . ')';
                }
                while (preg_match('/\(\(([^)]* AND [^)]*)\) AND (\([^)]*\))\)/', $result, $m)) {
                    $result = '(' . $m[1] . ' AND ' . $m[2] . ')';
                }
                return $result;
            } else {
                return '';
            }
        } elseif ($node instanceof NotX) {
            $inverted = array_map(function ($query) {
                if (! empty($query['match'])) {
                    $query['match'] = preg_replace('/@[^ ]+/', '\0 !', $query['match']);
                    return $query;
                }
                if (preg_match('/^(REGEX|GEODIST)/', $query)) {
                    $key = 'tf_' . uniqid();
                    $this->select[$key] = $query;
                    return "$key = 0";
                }
                $query = preg_replace_callback(
                    '/\s(AND|OR)\s/',
                    function ($matches) {
                        return $matches[1] === 'AND' ? ' OR ' : ' AND ';
                    },
                    $query
                );
                $query = str_replace(' = ', ' <> ', $query);
                $query = str_replace(' < ', ' >= ', $query);
                $query = str_replace(' > ', ' <= ', $query);
                $query = str_replace(' <= ', ' > ', $query);
                $query = str_replace(' >= ', ' < ', $query);
                $query = preg_replace('/(?<!NOT) BETWEEN /', ' NOT BETWEEN ', $query);
                $query = preg_replace('/ANY\(([^)]+)\) IN /', 'ALL(\1) NOT IN ', $query);
                return $query;
            }, $childNodes);
            return reset($inverted);
        } elseif ($node instanceof Token) {
            return $this->handleToken($node);
        } elseif ($node instanceof Initial) {
            $field = $this->getField($node);
            Index::addSearchedField($node->getField(), 'others');
            $value = $this->getQuoted($node, '^');
            return "REGEX($field, $value)";
        } elseif ($node instanceof Range) {
            $field = $this->getField($node);
            Index::addSearchedField($node->getField(), 'others');
            $raw = $this->getRaw($node->getToken('from'));
            if ($raw === "" || is_null($raw)) {
                $to = $this->getQuoted($node->getToken('to'));
                return "$field <= $to";
            } else {
                $from = $this->getQuoted($node->getToken('from'));
                $to = $this->getQuoted($node->getToken('to'));
                if ($this->isFullText($node)) {
                    return "($field >= $from AND $field <= $to)";
                } else {
                    return "$field BETWEEN $from AND $to";
                }
            }
        } elseif ($node instanceof Distance) {
            $field = $this->getField($node);
            Index::addSearchedField($node->getField(), 'others');
            // TODO: test this, possibly convert from jsonencoded to 2 lan/lon fields
            return "GEODIST({$node->getLat()}, {$node->getLon()}, '{$field}.lat', '{$field}.lon') < {$node->getDistance()}";
        } else {
            // Throw initial exception if fallback fails
            throw $exception ?: new \Exception(tr('Feature not supported: %0', get_class($node)));
        }
    }

    private function handleToken($node)
    {
        $field = $this->getField($node);
        Index::addSearchedField($node->getField(), 'others');
        $mapping = $this->index ? $this->index->getFieldMapping($node->getField()) : new stdClass();
        if (isset($mapping['types']) && (in_array('multi', $mapping['types']) || in_array('mva', $mapping['types']))) {
            $terms = $this->getRaw($node, 'multivalue');
            return 'ANY(' . $field . ')' . ' IN (' . implode(',', $terms) . ')';
        } elseif ($node->getType() == 'identifier' || ($mapping && in_array('timestamp', $mapping['types']))) {
            $value = $this->getQuoted($node);
            return "{$field} = $value";
        } else {
            $value = $this->getQuoted($node);
            if (is_array($value)) {
                return '(' . implode(' OR ', array_filter(array_map(function ($v) use ($field) {
                    if (is_scalar($v)) {
                        $v = $this->pdo_client->quote(strval($v));
                    } else {
                        return null;
                    }
                    return "REGEX({$field}, $v)";
                }, $value))) . ')';
            } else {
                return "REGEX({$field}, $value)";
            }
        }
    }

    private function getFields($node)
    {
        $fields = [];
        $node->walk(
            function ($node) use (&$fields) {
                if (method_exists($node, 'getField')) {
                    $fields[$this->getField($node)] = true;
                }
            }
        );

        return array_keys($fields);
    }

    protected function getField($node)
    {
        $field = strtolower($node->getField());
        $this->index->ensureHasField($field);
        return $field;
    }

    private function isFullText($node)
    {
        $fullText = true;
        $node->walk(
            function ($node) use (&$fullText) {
                if ($fullText && method_exists($node, 'getField')) {
                    $mapping = $this->index ? $this->index->getFieldMapping($node->getField()) : [];
                    if (! isset($mapping['options']) || ! in_array('indexed', $mapping['options'])) {
                        $fullText = false;
                    }
                }
                if (method_exists($node, 'getType') && $node->getType() == 'identifier') {
                    $fullText = false;
                }
            }
        );

        return $fullText;
    }

    private function getQuoted($node, $prefix = '')
    {
        $raw = $this->getRaw($node);
        $mapping = $this->index ? $this->index->getFieldMapping($node->getField()) : new stdClass();
        if ($mapping && array_intersect(['float', 'timestamp'], $mapping['types'])) {
            return floatval($raw);
        } elseif (is_string($raw) || ($mapping && in_array('string', $mapping['types']))) {
            return $this->pdo_client->quote($prefix . strval($raw));
        } else {
            return $raw;
        }
    }

    private function getRaw($node, $forceType = null)
    {
        $value = $node->getValue($this->factory);
        if ($forceType && $node->getType() != $forceType) {
            $value = $this->factory->$forceType($value->getValue());
        }
        return $value->getValue();
    }

    public function escapeQueryString($qs)
    {
        // these special chars need double slash escape
        foreach (['!', '$', '(', ')', '-', '/', '<', '@', '\\', '^', '|', '~'] as $special) {
            $qs = str_replace($special, '\\' . $special, $qs);
        }
        // minus at the beginning means exclude term search - leave it unescaped
        if (str_starts_with($qs, '\\\\-')) {
            $qs = '-' . substr($qs, 3);
        }
        // single quotes require only one slash escape
        return addcslashes($qs, "'");
    }

    private function getDocumentContent($type, $object)
    {
        $doc = $this->pdo_client->document($this->index->getIndexTableName(), $type, $object, 'contents');
        if (! empty($doc['contents'])) {
            return $doc['contents'];
        }

        return '';
    }
}
