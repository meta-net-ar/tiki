<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Search\Formatter\Plugin;

use Search_Formatter_Plugin_Interface;
use Search_Formatter_ValueFormatter;
use Search_ResultSet;
use WikiParser_PluginArgumentParser;
use WikiParser_PluginMatcher;

/**
 * Renders the OUTPUT body once, with the named scalar aggregation results
 * exposed as plain values to the standard {display name="..." format="..."}
 * blocks (number, date, plain, currency, ...).
 *
 *   {LIST()}
 *     ...
 *     {OUTPUT(aggregation="y")}
 *     Average days: {display name="avg_days" format="number" decimals="0"}
 *     {OUTPUT}
 *   {LIST}
 *
 * Bucket aggregations are skipped here; use aggregate_table.tpl or chartjs.tpl
 * for those.
 */
class AggregationTemplate implements Search_Formatter_Plugin_Interface
{
    private $template;
    private $format;

    public function __construct($template)
    {
        $this->template = WikiParser_PluginMatcher::match($template);
        $this->format = self::FORMAT_WIKI;
    }

    public function setRaw($isRaw)
    {
        $this->format = $isRaw ? self::FORMAT_HTML : self::FORMAT_WIKI;
    }

    public function getFormat()
    {
        return $this->format;
    }

    public function getFields()
    {
        return [];
    }

    public function prepareEntry($valueFormatter)
    {
        return '';
    }

    public function renderEntries(Search_ResultSet $entries)
    {
        $values = [];
        foreach ($entries->getAggregationResults() as $name => $agg) {
            if ($agg->isBucket()) {
                continue;
            }
            $values[$name] = $agg->getValue();
        }

        $valueFormatter = new Search_Formatter_ValueFormatter($values);
        $parser = new WikiParser_PluginArgumentParser();

        $matches = clone $this->template;
        foreach ($matches as $match) {
            if ($match->getName() !== 'display') {
                continue;
            }
            $arguments = $parser->parse($match->getArguments());
            $name = $arguments['name'] ?? null;
            if ($name === null) {
                $match->replaceWith('');
                continue;
            }
            $format = $arguments['format'] ?? 'plain';
            unset($arguments['name'], $arguments['format']);
            if (! array_key_exists($name, $values)) {
                $match->replaceWith((string)($arguments['default'] ?? ''));
                continue;
            }
            $match->replaceWith((string)$valueFormatter->$format($name, $arguments));
        }

        return $matches->getText();
    }
}
