<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

use Search\ResultSet\AggregationResult;
use Tiki\Smarty\SmartyTiki;

class Search_Formatter_Plugin_SmartyTemplate implements Search_Formatter_Plugin_Interface
{
    private $templateFile;
    private $changeDelimiters;
    private $data = [];
    private $fields = [];
    private string $editable;
    private string $editableId;
    private ?string $context;
    private $wysiwyg = null;

    public function __construct($templateFile, $changeDelimiters = false)
    {
        $this->templateFile = $templateFile;
        $this->changeDelimiters = (bool) $changeDelimiters;
        $this->editable = '';
    }

    public function setData(array $data)
    {
        $this->data = $data;
    }

    public function getFields()
    {
        return $this->fields;
    }

    public function setFields(array $fields)
    {
        $this->fields = $fields;
    }

    public function setEditable(string $editable, string $id)
    {
        $this->editable = $editable;
        $this->editableId = $id;
    }

    public function setWysiwyg($wysiwyg)
    {
        $this->wysiwyg = $wysiwyg;
    }

    public function setContext(?string $context)
    {
        $this->context = $context;
    }

    public function getFormat()
    {
        return self::FORMAT_HTML;
    }

    public function prepareEntry($entry)
    {
        return $entry->getPlainValues();
    }

    public function renderEntries(Search_ResultSet $entries)
    {
        if ($this->editable && isset($_REQUEST[$this->editableId])) {
            return $this->wrapEditableByContext($_REQUEST[$this->editableId]);
        }

        $smarty = new SmartyTiki();

        if ($this->changeDelimiters) {
            $smarty->setLeftDelimiter('{{');
            $smarty->setRightDelimiter('}}');
        }
        // Format date fields in entries based on column settings, mainly used in PluginList.
        /**
            * `$this->data["column"]` is usually set by templates like:
            * {OUTPUT(template="table")}
            *     {column field="status" label="Status"}
            *     {column field="name" label="Name"}
            * {OUTPUT}
            *
            * For fields that are dates (from `$entries->getDateFields()`), we wrap the value in
            * `<span class='text-nowrap'>` to keep it on one line. If the column doesn't have "mode" set
            * to "raw", we set it to avoid further formatting.
        */
        // Make Date type field elements not wrap
        if (! empty($this->data['column'])) {
            foreach ($entries as $key_row => $entry) {
                if (isset($this->data['column']['field'])) {
                    $columns = [&$this->data['column']];
                } else {
                    $columns = &$this->data['column'];
                }
                foreach ($columns as &$column) {
                    if (in_array($column["field"], $entries->getDateFields())) {
                        $entries[$key_row][$column["field"]] = "<span class='text-nowrap'>" . $entry[$column["field"]] . "</span>";
                        if (! isset($column["mode"]) || $column["mode"] !== "raw") {
                            $column["mode"] = "raw";
                        }
                    }
                }
            }
        }

        foreach ($this->data as $key => $value) {
            $smarty->assign($key, $value);
        }

        $smarty->assign('base_url', $GLOBALS['base_url']);
        $smarty->assign('prefs', $GLOBALS['prefs']);
        $smarty->assign('user', $GLOBALS['user']);
        $smarty->assign('results', $entries);
        $smarty->assign(
            'facets',
            array_map(
                function ($facet) {
                    return array_filter([
                        'name' => $facet->getName(),
                        'label' => $facet->getLabel(),
                        'options' => $facet->getOptions(),
                        'operator' => $facet->getOperator(),
                    ]);
                },
                $entries->getFacets()
            )
        );
        // Server-side aggregations ({group} / {metric}) flattened to plain
        // arrays so templates can iterate them directly.
        $aggregations = [];
        foreach ($entries->getAggregationResults() as $aggName => $aggResult) {
            $aggregations[$aggName] = $this->aggregationToArray($aggResult);
        }
        $smarty->assign('aggregations', $aggregations);
        $smarty->assign('count', count($entries));
        $smarty->assign('offset', $entries->getOffset());
        $smarty->assign('offsetplusone', $entries->getOffset() + 1);
        $smarty->assign('offsetplusmaxRecords', $entries->getOffset() + $entries->getMaxRecords());
        $smarty->assign('maxRecords', $entries->getMaxRecords());
        $smarty->assign('id', $entries->getId());
        $smarty->assign('tsOn', $entries->getTsOn());
        $tsettings = $entries->getTsSettings();
        if (is_array($tsettings) && isset($tsettings['math'])) {
            $smarty->assign('tstotals', $tsettings['math']['totals']);
            $smarty->assign('tscols', $tsettings['columns']);
        }
        global $jitRequest;
        if (! empty($jitRequest)) {
            $adddata = $jitRequest->adddata->text();
            if (! is_null($adddata)) {
                $smarty->assign('adddata', json_decode($adddata, true));
            }
        }

        $r = $smarty->fetch($this->templateFile);

        if ($this->editable) {
            $r = $this->wrapEditableByContext($r);
        }

        return $r;
    }

    /**
     * Recursively flatten an AggregationResult tree so Smarty
     * templates can iterate it without dealing with PHP objects.
     */
    private function aggregationToArray(AggregationResult $result): array
    {
        if (! $result->isBucket()) {
            return [
                'kind' => $result->getKind(),
                'is_bucket' => false,
                'value' => $result->getValue(),
            ];
        }
        $buckets = [];
        foreach ($result->getBuckets() as $bucket) {
            $children = [];
            foreach ($bucket['children'] as $childName => $childResult) {
                $children[$childName] = $this->aggregationToArray($childResult);
            }
            $buckets[] = [
                'key' => $bucket['key'],
                'doc_count' => $bucket['doc_count'],
                'metrics' => $bucket['metrics'],
                'children' => $children,
            ];
        }
        $out = [
            'kind' => $result->getKind(),
            'is_bucket' => true,
            'buckets' => $buckets,
            'sum_other_doc_count' => $result->getSumOtherDocCount(),
        ];
        $groupLabel = $result->getLabel();
        if ($groupLabel !== null) {
            $out['label'] = $groupLabel;
        }
        $metricLabels = $result->getMetricLabels();
        if ($metricLabels !== []) {
            $out['metric_labels'] = $metricLabels;
        }
        return $out;
    }

    private function wrapEditableByContext($content)
    {
        if ($this->context !== 'actions') {
            // Determine wysiwyg value: explicit setting takes priority,
            // otherwise default to false for inline, true for block/dialog
            if ($this->wysiwyg !== null) {
                $wysiwygValue = $this->wysiwyg;
            } else {
                $wysiwygValue = $this->editable === 'inline' ? false : true;
            }

            return new Tiki_Render_Editable(
                $content,
                [
                    'layout' => $this->editable,
                    'field' => [
                        'id' => $this->editableId,
                        'type' => 'form',
                        'wysiwyg' => $wysiwygValue,
                    ],
                ],
            );
        } else {
            return $content;
        }
    }
}
