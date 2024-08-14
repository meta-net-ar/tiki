<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Math_Formula_Function_Subtotal extends Math_Formula_Function
{
    public function evaluate($element)
    {
        $allowed = ['list', 'group', 'aggregate', 'order', 'limit', 'separators', 'formula', 'having', 'transformers'];

        if ($extra = $element->getExtraValues($allowed)) {
            $this->error(tr('Unexpected values: %0', implode(', ', $extra)));
        }

        $list = $element->list;
        if (! $list || count($list) != 1) {
            $this->error(tra('Field must be provided and contain one argument: list'));
        }
        $list = $this->evaluateChild($list[0]);

        $group = $element->group;
        if (! $group || count($group) != 1) {
            $this->error(tra('Field must be provided and contain one argument: group.'));
        }
        $group = $group[0];

        $aggregate = $element->aggregate;
        if (! $aggregate || count($aggregate) < 1) {
            $this->error(tra('Field must be provided and contain at least one argument: aggregate.'));
        }

        $order = $element->order;
        if ($order && count($order) != 1 && count($order) != 2) {
            $this->error(tra('Order element expects at most 2 arguments: the order field - either \'group\' or the position of the aggregate column starting from one or a formula; the second argument can be empty or \'asc\' for ascending order or \'desc\' for descending order.'));
        }
        if ($order) {
            $direction = $order[1] ?? 'asc';
            $order = $order[0];
        } else {
            $order = $direction = null;
        }

        $limit = $element->limit;
        if ($limit && (count($limit) < 1 || count($limit) > 3)) {
            $this->error(tra('Limit element expects at most 3 arguments: limit number, top tier as a formula or \'group\' to return just the top sorted items and 3rd argument string to group the rest into one field or \'ignore\' to ignore them'));
        }

        $separators = $element->separators;
        if (! $separators || count($separators) != 2) {
            $separators = ["|", "\n"];
        } else {
            $separators = [$this->evaluateChild($separators[0]), $this->evaluateChild($separators[1])];
        }

        $formula = $element->formula;
        if (! $formula) {
            $formula = [];
        }

        $having = $element->having;
        if (! $having) {
            $having = [];
        }

        $transformers = $element->transformers;
        if (! $transformers) {
            $transformers = [];
        }

        $out = [];

        // group values by field
        if (is_array($list)) {
            foreach ($list as $values) {
                $group_value = trim($this->evaluateChild($group, $values));
                if (! isset($out[$group_value])) {
                    $out[$group_value] = ['group' => $group_value];
                    foreach ($aggregate as $position => $field) {
                        $out[$group_value][$position] = [];
                    }
                }
                foreach ($aggregate as $position => $field) {
                    if (is_string($field) && ! isset($values[$field])) {
                        $value = 0;
                    } else {
                        $value = $this->evaluateChild($field, $values);
                    }
                    $out[$group_value][$position][] = $value;
                }
            }
        }

        // evaluate aggregate function for each field
        foreach ($out as $group_value => $rows) {
            $previous = [];
            foreach ($aggregate as $position => $field) {
                $simple = false;
                if (is_string($formula[$position])) {
                    $function = str_replace(' ', '', ucwords(str_replace('-', ' ', $formula[$position] ?? 'add')));
                    $class = 'Math_Formula_Function_' . $function;
                    if (class_exists($class)) {
                        $op = new $class();
                        $out[$group_value][$position] = $op->evaluateTemplate($rows[$position], function ($child) {
                            return $child;
                        });
                        $simple = true;
                    }
                }
                if (! $simple) {
                    $out[$group_value][$position] = $this->evaluateChild($formula[$position], array_merge($previous, ['$1' => $rows[$position]]));
                }
                // process having clause
                if (isset($having[$position])) {
                    $pass = $this->evaluateChild($having[$position], ['$1' => $out[$group_value][$position]]);
                    if (! $pass) {
                        unset($out[$group_value]);
                        break;
                    }
                }
                // transform
                if (isset($transformers[$position])) {
                    $out[$group_value][$position] = $this->evaluateChild($transformers[$position], array_merge($previous, ['$1' => $out[$group_value][$position]]));
                }
                if (is_string($formula[$position])) {
                    $previous['$' . $formula[$position]] = $out[$group_value][$position];
                }
            }
        }

        if ($order === 'group') {
            if ($direction == 'asc') {
                ksort($out);
            } else {
                krsort($out);
            }
        } elseif (is_numeric($order)) {
            $order = intval($order);
            usort($out, function ($el1, $el2) use ($order, $direction) {
                if (! isset($el1[$order - 1]) || ! isset($el2[$order - 1])) {
                    return 0;
                }
                $el1 = $el1[$order - 1];
                $el2 = $el2[$order - 1];
                $result = strnatcasecmp($el1, $el2);
                if ($direction == 'desc') {
                    $result = 1 - $result;
                }
                return $result;
            });
        } else {
            uksort($out, function ($group1, $group2) use ($out, $order, $direction) {
                $variables = $this->prepareGroupRowWithVariables($group1, $out[$group1]);
                $el1 = $this->evaluateChild($order, $variables);
                $variables = $this->prepareGroupRowWithVariables($group2, $out[$group2]);
                $el2 = $this->evaluateChild($order, $variables);
                $result = strnatcasecmp($el1, $el2);
                if ($direction == 'desc') {
                    $result = 1 - $result;
                }
                return $result;
            });
        }

        if ($limit) {
            $top = $limit[1] ?? 'group';
            $rest = $limit[2] ?? 'ignore';
            $limit = $limit[0];
            if ($top == 'group') {
                if ($rest != 'ignore') {
                    $others = array_slice($out, $limit);
                    $label = $this->evaluateChild($rest);
                    $out = array_slice($out, 0, $limit);
                    $out[$label] = ['group' => $label];
                    foreach ($others as $row) {
                        foreach ($row as $position => $value) {
                            if (! is_numeric($position)) {
                                continue;
                            }
                            if (! isset($out[$label][$position])) {
                                $out[$label][$position] = 0;
                            }
                            if (is_numeric($value)) {
                                $out[$label][$position] += floatval($value);
                            }
                        }
                    }
                } else {
                    $out = array_slice($out, 0, $limit);
                }
            } else {
                $top_results = [];
                foreach ($out as $group => $row) {
                    $variables = $this->prepareGroupRowWithVariables($group, $row);
                    $value = $this->evaluateChild($top, $variables);
                    $top_results[$group] = $value;
                }
                arsort($top_results);
                $groups_to_keep = array_slice(array_keys($top_results), 0, $limit);
                $result = [];
                if ($rest != 'ignore') {
                    $others = ['group' => $this->evaluateChild($rest)];
                }
                foreach ($out as $group => $row) {
                    if (in_array($group, $groups_to_keep)) {
                        $result[$group] = $row;
                    } elseif ($rest != 'ignore') {
                        foreach ($row as $position => $value) {
                            if (! is_numeric($position)) {
                                continue;
                            }
                            if (! isset($others[$position])) {
                                $others[$position] = 0;
                            }
                            if (is_numeric($value)) {
                                $others[$position] += floatval($value);
                            }
                        }
                    }
                }
                if ($rest != 'ignore') {
                    $result[$this->evaluateChild($rest)] = $others;
                }
                $out = $result;
            }
        }

        return implode($separators[1], array_map(function ($row) use ($separators) {
            return implode($separators[0], $row);
        }, $out));
    }

    private function prepareGroupRowWithVariables($group, $row)
    {
        $variables = ['group' => $group];
        foreach ($row as $position => $value) {
            if (! is_numeric($position)) {
                continue;
            }
            $variables['$' . (intval($position) + 1)] = $value;
        }
        return $variables;
    }
}
