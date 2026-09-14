<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Services_Tracker_CalendarController
{
    /**
     * Returns the section for use with certain features like banning
     * @return string
     */
    public function getSection()
    {
        return 'trackers';
    }

    public function action_list($input)
    {
        global $user;
        static $id = 0;
        ++$id;

        $unifiedsearchlib = TikiLib::lib('unifiedsearch');
        $index = $unifiedsearchlib->getIndex();

        $beginFieldName = $input->beginField->word();
        $endFieldName = $input->endField->word();
        $start = 'tracker_field_' . $beginFieldName;
        $end = 'tracker_field_' . $endFieldName;
        $hasStartField = ! empty($beginFieldName) && $beginFieldName !== 'null';
        $hasEndField = ! empty($endFieldName) && $endFieldName !== 'null';
        $titleFieldName = $input->title->word();
        $title = ($titleFieldName && $titleFieldName !== 'null') ? 'tracker_field_' . $titleFieldName : null;
        $descriptionFieldName = $input->description->word();
        $description = ($descriptionFieldName && $descriptionFieldName !== 'null') ? 'tracker_field_' . $descriptionFieldName : null;

        $resource = null;
        if ($resourceFieldName = $input->resourceField->word()) {
            if ($resourceFieldName !== 'null') {
                $resource = 'tracker_field_' . $resourceFieldName;
            }
        }

        $coloring = null;
        if ($coloringFieldName = $input->coloringField->word()) {
            if ($coloringFieldName !== 'null') {
                $coloring = 'tracker_field_' . $coloringFieldName;
            }
        }

        $query = $unifiedsearchlib->buildQuery([]);

        if (is_numeric($input->start->string())) {
            $useTimestamp = true;
            $from = $input->start->int();
            $to = $input->end->int();
        } else {
            $useTimestamp = false;
            $timezone = $input->timezone->string();
            preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}$/', $input->start->string(), $matches);

            if ($input->start->string() === $matches[0]) {
                $from = strtotime($input->start->iso8601() . ' ' . $timezone);
                $to = strtotime($input->end->iso8601() . ' ' . $timezone);
            } else {
                $from = strtotime($input->start->isodate());
                $to = strtotime($input->end->isodate());
            }
        }

        if ($hasStartField) {
            $query->filterRange(0, $to, $start);
        }
        if ($hasEndField) {
            $query->filterRange($from, $to + 1000 * 365 * 86400, $end);
        }
        $maxRecords = $input->maxRecords->int() ?: null;
        $query->setRange(0, $maxRecords);

        $innerMatches = [];
        if ($body = $input->filters->none()) {
            $builder = new Search_Query_WikiBuilder($query, $input);
            $innerMatches = WikiParser_PluginMatcher::match($body);
            $builder->apply($innerMatches);
        }

        $result = $query->search($index);

        $response = [];

        $fields = [];
        $beginDateFieldHandler = null;
        $endDateFieldHandler = null;
        if ($definition = Tracker_Definition::get($input->trackerId->int())) {
            $factory = $definition->getFieldFactory();

            if ($hasStartField) {
                $beginFieldInfo = $definition->getField($beginFieldName);
                if ($beginFieldInfo) {
                    $beginDateFieldHandler = $factory->getHandler($beginFieldInfo);
                }
            }

            if ($hasEndField) {
                $endFieldInfo = $definition->getField($endFieldName);
                if ($endFieldInfo) {
                    $endDateFieldHandler = $factory->getHandler($endFieldInfo);
                }
            }

            foreach ($definition->getPopupFields() as $fieldId) {
                if ($field = $definition->getField($fieldId)) {
                    $fields[] = $field;
                }
            }
        }

        $hasFormat = false;
        foreach ($innerMatches as $match) {
            if ($match->getName() === 'format') {
                $hasFormat = true;
                break;
            }
        }
        if ($hasFormat) {
            // Note: reusing the ReportTemplate plugin leaves the field values alone and returns the
            // \Search_Formatter_ValueFormatter::getPlainValues version
            $plugin = new Search_Formatter_Plugin_ReportTemplate($body);

            $formatBuilder = new Search_Formatter_Builder();
            $formatBuilder->setId('wptrackercalendar-' . $id);
            $formatBuilder->setCount($result->count());
            $formatBuilder->apply($innerMatches);
            $formatBuilder->setFormatterPlugin($plugin);
            $formatter = $formatBuilder->getFormatter(! empty($result->errorInQuery));
            $entries = $formatter->getPopulatedList($result, false);
        } else {
            $entries = [];
        }

        $trklib = TikiLib::lib('trk');
        foreach ($result as $index => $row) {
            if ($entries) {
                $row = array_merge($row->getArrayCopy(), $entries[$index]);
            }
            $item = Tracker_Item::fromId($row['object_id']);

            if (empty($row['description'])) {
                $row['description'] = '';
                foreach ($fields as $field) {
                    if ($item->canViewField($field['fieldId'])) {
                        $val = trim(
                            $trklib->field_render_value(
                                [
                                    'field'   => $field,
                                    'item'    => $item->getData(),
                                    'process' => 'y',
                                ]
                            )
                        );
                        if ($val) {
                            if (count($fields) > 1) {
                                $row['description'] .= "<h5>{$field['name']}</h5>";
                            }
                            $row['description'] .= $val;
                        }
                    }
                }
            }

            $colormap = base64_decode($input->colormap->word());

            $startValue = ($hasStartField && isset($row[$start])) ? $row[$start] : null;
            $endValue = ($hasEndField && isset($row[$end])) ? $row[$end] : null;

            if ($startValue === null && $endValue === null) {
                continue;
            }

            // If only one of start or end is provided, use that value for both to ensure the event appears on the calendar,
            $dtStart = $this->getTimestamp($startValue ?? $endValue);
            $dtEnd = $this->getTimestamp($endValue ?? $startValue);

            // If end is before start, treat as a single instant event by using the start value for both
            if ($dtEnd < $dtStart) {
                $dtEnd = $dtStart;
            }

            $beginIsDateOnly = $beginDateFieldHandler instanceof Tracker_Field_DateTime
                && $beginDateFieldHandler->isDateOnlyCalendarValue();
            $endIsDateOnly = $endDateFieldHandler instanceof Tracker_Field_DateTime
                && $endDateFieldHandler->isDateOnlyCalendarValue();

            // Determine if event is date-only
            // If only one field is specified, check that field; if both are specified, both must be date-only
            if ($beginDateFieldHandler !== null && $endDateFieldHandler !== null) {
                $isDateOnlyEvent = $beginIsDateOnly && $endIsDateOnly;
            } elseif ($beginDateFieldHandler !== null) {
                $isDateOnlyEvent = $beginIsDateOnly;
            } elseif ($endDateFieldHandler !== null) {
                $isDateOnlyEvent = $endIsDateOnly;
            } else {
                $isDateOnlyEvent = false;
            }

            $response[] = [
                'id'               => $row['object_id'],
                'trackerId'        => $row['tracker_id'] ?? null,
                'title'            => ($title && isset($row[$title])) ? $row[$title] : ($row['title'] ?? ''),
                'extendedProps'      => ['description' => ($description && isset($row[$description])) ? $row[$description] : ($row['description'] ?? '')],
                'url'              => \SmartyTiki\Modifier\Sefurl::apply($row['object_id'], $row['object_type']),
                // For all-day events, return date-only strings so FullCalendar does not apply timezone conversions that can shift the visible day.
                'allDay'           => $isDateOnlyEvent,
                'start'            => $isDateOnlyEvent ? gmdate('Y-m-d', $dtStart) : ($useTimestamp ? $dtStart : TikiLib::date_format("c", $dtStart, $user, 5, false)),
                'end'              => $isDateOnlyEvent ? gmdate('Y-m-d', $dtEnd) : ($useTimestamp ? $dtEnd : TikiLib::date_format("c", $dtEnd, $user, 5, false)),
                'editable'         => $item->canModify(),
                'color'            => ($coloring && isset($row[$coloring])) ? ($row[$coloring] ?: $row['coloring'] ?? '') : ($this->getColor($row[$coloring] ?? '', $colormap)),
                'textColor'        => '#000',
                'resourceId'       => $resource && isset($row[$resource]) ? strtolower($row[$resource]) : '',
                'resourceEditable' => true,
            ];
        }

        return $response;
    }

    private function getTimestamp($value)
    {
        if (preg_match('/^\d{14}$/', $value)) {
            // Facing a date formated as YYYYMMDDHHIISS as indexed in lucene
            // Always stored as UTC
            return date_create_from_format('YmdHise', $value . 'UTC')->getTimestamp();
        } elseif (is_numeric($value)) {
            return $value;
        } else {
            return strtotime($value . ' UTC');
        }
    }

    private function getColor($value, $colormap)
    {
        static $colors = ['#6cf', '#6fc', '#c6f', '#cf6', '#f6c', '#fc6'];
        static $map = [];

        if (empty($map) && ! empty($colormap)) {
            foreach (explode('|', $colormap) as $color) {
                $colorMapParts = explode(',', $color);
                $map[trim($colorMapParts[0])] = trim($colorMapParts[1]);
            }
        }

        if (! isset($map[$value])) {
            $color = array_shift($colors);
            $colors[] = $color;
            $map[$value] = $color;
        }

        return $map[$value];
    }
}
