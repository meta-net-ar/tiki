<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Services\Report;

use Perms;
use Services_Exception_Denied;
use Services_Exception_Disabled;
use Services_Exception_MissingValue;
use Services_Exception_NotFound;
use TikiLib;
use Tracker_Definition;

class Controller
{
    public function setUp()
    {
        Services_Exception_Disabled::check('feature_wiki');
        Services_Exception_Disabled::check('feature_trackers');
    }

    public function getSection()
    {
        return 'wiki page';
    }

    public function action_wizard($input)
    {
        $trklib = TikiLib::lib('trk');
        $trackers = $trklib->list_trackers();

        return [
            'title' => tr('New Report'),
            'trackers' => $trackers['data'] ?? [],
        ];
    }

    public function action_get_fields($input)
    {
        $trackerId = $input->trackerId->int();

        if (! $trackerId) {
            throw new Services_Exception_MissingValue('trackerId');
        }

        $perms = Perms::get('tracker', $trackerId);
        if (! $perms->view_trackers) {
            throw new Services_Exception_Denied(tr("You don't have permission to view the tracker"));
        }

        $definition = Tracker_Definition::get($trackerId);
        if (! $definition) {
            throw new Services_Exception_NotFound();
        }

        $fields = $definition->getFields();
        $result = [];
        foreach ($fields as $field) {
            $result[] = [
                'fieldId' => $field['fieldId'],
                'permName' => $field['permName'],
                'name' => $field['name'],
                'type' => $field['type'],
            ];
        }

        return ['fields' => $result];
    }

    public function action_generate($input)
    {
        $trackerId = $input->trackerId->int();
        $reportType = $input->reportType->text();
        $fields = $input->asArray('fields');
        $groupField = $input->groupField->text();
        $valueField = $input->valueField->text();
        $metricOp = $input->metricOp->text();
        $chartType = $input->chartType->text();
        $title = $input->title->text();
        $orientation = $input->orientation->text();
        $coverPage = $input->coverPage->int();

        if (! $trackerId) {
            throw new Services_Exception_MissingValue('trackerId');
        }
        if (! $reportType) {
            throw new Services_Exception_MissingValue('reportType');
        }

        if (! $fields) {
            $fields = [];
        }

        $syntax = $this->generateSyntax($reportType, $trackerId, $fields, [
            'groupField' => $groupField,
            'valueField' => $valueField,
            'metricOp' => $metricOp ?: 'count',
            'chartType' => $chartType ?: 'bar',
            'title' => $title,
            'orientation' => $orientation ?: 'P',
            'coverPage' => $coverPage,
        ]);

        return ['syntax' => $syntax];
    }

    private function generateSyntax($type, $trackerId, $fields, $options)
    {
        $title = $options['title'] ?: tr('Report');
        $orientation = $options['orientation'];
        $coverPage = $options['coverPage'];

        $pdfBlock = '';

        if ($coverPage || $orientation !== 'P') {
            $pdfParams = [];
            if ($orientation !== 'P') {
                $pdfParams[] = "orientation=\"$orientation\"";
            }
            if ($coverPage) {
                $pdfParams[] = 'coverpage="y"';
                $pdfParams[] = "coverpage_text_settings=\"$title|" . tr('Generated Report') . "|C|||0|\"";
            }
            $pdfBlock = "{PDF(" . implode(' ', $pdfParams) . ")}{PDF}\n\n";
        }

        switch ($type) {
            case 'simple_table':
                return $this->generateSimpleTable($trackerId, $fields, $title, $pdfBlock);
            case 'aggregation_table':
                return $this->generateAggregationTable($trackerId, $fields, $options, $title, $pdfBlock);
            case 'chart':
                return $this->generateChart($trackerId, $fields, $options, $title, $pdfBlock);
            case 'full_report':
                return $this->generateFullReport($trackerId, $fields, $options, $title, $pdfBlock);
            default:
                return $this->generateSimpleTable($trackerId, $fields, $title, $pdfBlock);
        }
    }

    private function generateSimpleTable($trackerId, $fields, $title, $pdfBlock)
    {
        $columns = '';
        foreach ($fields as $field) {
            $columns .= "    {column label=\"$field\" field=\"tracker_field_$field\"}\n";
        }

        return <<<WIKI
{$pdfBlock}!!! $title

{LIST()}
  {filter type="trackeritem"}
  {filter field="tracker_id" exact="$trackerId"}
  {OUTPUT(template="table")}
$columns  {OUTPUT}
{LIST}
WIKI;
    }

    private function generateAggregationTable($trackerId, $fields, $options, $title, $pdfBlock)
    {
        $groupField = $options['groupField'];
        $valueField = $options['valueField'];
        $metricOp = $options['metricOp'];

        $groupName = "by_$groupField";
        $metricName = $metricOp . '_' . ($valueField ?: 'items');
        $metricField = $metricOp === 'count' ? '' : " field=\"tracker_field_$valueField\"";

        return <<<WIKI
{$pdfBlock}!!! $title

{LIST()}
  {filter type="trackeritem"}
  {filter field="tracker_id" exact="$trackerId"}
  {group name="$groupName" field="tracker_field_$groupField"}
  {metric name="$metricName" op="$metricOp"$metricField}
  {OUTPUT(template="aggregate_table")}
  {OUTPUT}
{LIST}
WIKI;
    }

    private function generateChart($trackerId, $fields, $options, $title, $pdfBlock)
    {
        $groupField = $options['groupField'];
        $valueField = $options['valueField'];
        $metricOp = $options['metricOp'];
        $chartType = $options['chartType'];

        $groupName = "by_$groupField";
        $metricName = $metricOp . '_' . ($valueField ?: 'items');
        $metricField = $metricOp === 'count' ? '' : " field=\"tracker_field_$valueField\"";

        return <<<WIKI
{$pdfBlock}!!! $title

{LIST()}
  {filter type="trackeritem"}
  {filter field="tracker_id" exact="$trackerId"}
  {group name="$groupName" field="tracker_field_$groupField"}
  {metric name="$metricName" op="$metricOp"$metricField}
  {OUTPUT(template="chartjs")}
    {chart type="$chartType" agg="$groupName" value="$metricName" label="tracker_field_$groupField" title="$title"}
  {OUTPUT}
{LIST}
WIKI;
    }

    private function generateFullReport($trackerId, $fields, $options, $title, $pdfBlock)
    {
        $groupField = $options['groupField'];
        $valueField = $options['valueField'];
        $metricOp = $options['metricOp'];
        $chartType = $options['chartType'];

        $groupName = "by_$groupField";
        $metricName = $metricOp . '_' . ($valueField ?: 'items');
        $metricField = $metricOp === 'count' ? '' : " field=\"tracker_field_$valueField\"";

        $columns = '';
        foreach ($fields as $field) {
            $columns .= "    {column label=\"$field\" field=\"tracker_field_$field\"}\n";
        }

        return <<<WIKI
{$pdfBlock}!!! $title

{LIST()}
  {filter type="trackeritem"}
  {filter field="tracker_id" exact="$trackerId"}
  {group name="$groupName" field="tracker_field_$groupField"}
  {metric name="$metricName" op="$metricOp"$metricField}
  {OUTPUT(template="chartjs")}
    {chart type="$chartType" agg="$groupName" value="$metricName" label="tracker_field_$groupField" title="$title"}
  {OUTPUT}
{LIST}

---

{LIST()}
  {filter type="trackeritem"}
  {filter field="tracker_id" exact="$trackerId"}
  {OUTPUT(template="table")}
$columns  {OUTPUT}
{LIST}
WIKI;
    }
}
