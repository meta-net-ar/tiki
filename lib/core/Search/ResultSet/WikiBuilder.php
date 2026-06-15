<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Search_ResultSet_WikiBuilder
{
    private $result;
    private $paginationArguments;

    public function __construct(Search_ResultSet $result)
    {
        $this->result = $result;
    }

    public function setPaginationArguments($paginationArguments)
    {
        $this->paginationArguments = $paginationArguments;
    }

    public function apply(WikiParser_PluginMatcher $matches)
    {
        $argumentParser = new WikiParser_PluginArgumentParser();

        foreach ($matches as $match) {
            $name = $match->getName();
            if ($name == 'group') {
                $arguments = $argumentParser->parse($match->getArguments());

                if ($this->isReportingEngineGroup($arguments)) {
                    continue;
                }

                $field = $arguments['field'] ?? 'aggregate';
                $collect = explode(',', $arguments['collect'] ?? 'user');
                $this->result->groupBy($field, $collect);
            }
            if ($name == 'aggregate') {
                $arguments = $argumentParser->parse($match->getArguments());

                $fields = explode(',', $arguments['fields'] ?? '');
                $totals = explode(',', $arguments['totals'] ?? '');
                $this->result->aggregate($fields, $totals);
            }
        }

        if ($this->paginationArguments) {
            $this->result->setMaxResults($this->paginationArguments['max']);
        }
    }

    /**
     * New reporting-engine {group} blocks use parameters like name=,
     * size=, order= and are handled by the Aggregation\WikiBuilder.
     * Old-style {group} blocks use collect= for in-memory grouping.
     */
    private function isReportingEngineGroup(array $arguments): bool
    {
        if ($this->result->hasAggregations()) {
            return true;
        }

        $newStyleKeys = ['name', 'size', 'order', 'min', 'missing', 'label', 'others', 'total', 'palette'];
        foreach ($newStyleKeys as $key) {
            if (isset($arguments[$key])) {
                return true;
            }
        }
        return false;
    }
}
