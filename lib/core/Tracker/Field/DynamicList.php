<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
use Tracker\Tabular\Schema;

 /**
 * Handler class for DynamicList
 *
 * Letter key: ~w~
 *
 */
// TODO: validate parameters (several required)
class Tracker_Field_DynamicList extends \Tracker\Field\AbstractField implements \Tracker\Field\ExportableInterface
{
    public static function getManagedTypesInfo(): array
    {
        return [
            'w' => [
                'name' => tra('Dynamic Items List'),
                'description' => tra('Dynamically update a selection list based on linked data from another tracker.'),
                'help' => 'Dynamic-items-list',
                'prefs' => ['trackerfield_dynamiclist'],
                'tags' => ['advanced'],
                'default' => 'n',
                'params' => [
                    'trackerId' => [
                        'name' => tr('Tracker ID'),
                        'description' => tr('Tracker to link with'),
                        'filter' => 'int',
                        'legacy_index' => 0,
                        'profile_reference' => 'tracker',
                    ],
                    'filterFieldIdThere' => [
                        'name' => tr('Field ID (Other tracker)'),
                        'description' => tr('Field ID to link with in the other tracker'),
                        'filter' => 'int',
                        'legacy_index' => 1,
                        'profile_reference' => 'tracker_field',
                        'parent' => 'trackerId',
                        'parentkey' => 'tracker_id',
                    ],
                    'filterFieldIdHere' => [
                        'name' => tr('Field ID (This tracker)'),
                        'description' => tr('Field ID to link with in the current tracker'),
                        'filter' => 'int',
                        'legacy_index' => 2,
                        'profile_reference' => 'tracker_field',
                        'parent' => 'input[name=trackerId]',
                        'parentkey' => 'tracker_id',
                    ],
                    'listFieldIdThere' => [
                        'name' => tr('Listed Field'),
                        'description' => tr('Field ID to be displayed in the dropdown list.'),
                        'filter' => 'int',
                        'legacy_index' => 3,
                        'profile_reference' => 'tracker_field',
                        'parent' => 'trackerId',
                        'parentkey' => 'tracker_id',
                    ],
                    'statusThere' => [
                        'name' => tr('Status Filter'),
                        'description' => tr('Restrict listed items to specific statuses.'),
                        'filter' => 'alpha',
                        'options' => [
                            'opc' => tr('all'),
                            'o' => tr('open'),
                            'p' => tr('pending'),
                            'c' => tr('closed'),
                            'op' => tr('open, pending'),
                            'pc' => tr('pending, closed'),
                        ],
                        'legacy_index' => 4,
                    ],
                    'hideBlank' => [
                        'name' => tr('Hide blank'),
                        'description' => tr('Hide first blank option, thus preselecting the first available option.'),
                        'filter' => 'int',
                        'options' => [
                            0 => tr('No'),
                            1 => tr('Yes'),
                        ],
                        'legacy_index' => 5,
                    ],
                    'selectMultipleValues' => [
                        'name' => tr('Select multiple values'),
                        'description' => tr('Allow the user to select multiple values'),
                        'filter' => 'int',
                        'options' => [
                            0 => tr('No'),
                            1 => tr('Yes'),
                        ],
                        'legacy_index' => 6,
                    ],
                    'linkToItems' => [
                        'name' => tr('Display'),
                        'description' => tr('How the link to the items should be rendered'),
                        'filter' => 'int',
                        'options' => [
                            0 => tr('Value'),
                            1 => tr('Link'),
                        ],
                        'legacy_index' => 7,
                    ]
                ],
            ],
        ];
    }

    public function getFieldData(array $requestData = [])
    {
        $ins_id = $this->getInsertId();
        $data = [
            'value' => (isset($requestData[$ins_id]))
                ? $requestData[$ins_id]
                : $this->getValue(),
        ];

        if ($this->getOption('selectMultipleValues') && ! is_array($data['value'])) {
            $data['value'] = explode(',', $data['value']);
        }

        return $data;
    }

    public function handleSave($value, $oldValue)
    {
        // if selectMultipleValues is enabled, convert the array of options to string before saving the field value in the db
        if ($this->getOption('selectMultipleValues')) {
            if (is_array($value)) {
                $value = implode(',', $value);
            }
        } else {
            $value = (int) $value;
        }

        return [
            'value' => $value,
        ];
    }

    public function renderInput($context = [])
    {
        // REFACTOR: can't use list-tracker_field_values_ajax.php yet as it doesn't seem to filter


        // Modified to support multiple dynamic item list fields bound to the same $filterFieldIdHere
        // When changing  $filterFieldValueHere (i.e combobox) the $originalValue will be send as part of the request to the backend.
        // The backend returns an json array('request' => $requestData, 'response' => $responseData).
        // This way we can keep the default $originalValue, even when changing the selection forth and back.
        // It fixes also the issue that, if more than one dynamic item list fields are set and use the same
        // $filterFieldIdHere, then the initial value was wrong due to multiple fires of the handler.

        $filterFieldIdHere = trim($this->getOption('filterFieldIdHere'));
        $trackerIdThere = $this->getOption('trackerId');
        $listFieldIdThere = $this->getOption('listFieldIdThere');
        $filterFieldIdThere = $this->getOption('filterFieldIdThere');
        $statusThere = $this->getOption('statusThere');
        $isMandatory = $this->getConfiguration('isMandatory');
        $insertId = $this->getInsertId();
        $originalValue = $this->getConfiguration('value');
        $hideBlank = $this->getOption('hideBlank');
        $selectMultipleValues = $this->getOption('selectMultipleValues', 0);
        $linkToItems = $this->getOption('linkToItems', 0);

        $filterFieldValueHere = $originalValue;
        if (! empty($context['itemId'])) {
            $itemInfo = TikiLib::lib('trk')->get_tracker_item($context['itemId']);
            if (! empty($itemInfo) && ! empty($itemInfo[$filterFieldIdHere])) {
                $filterFieldValueHere = $itemInfo[$filterFieldIdHere];
            }
        }

        if ($filterFieldIdHere == $this->getConfiguration('fieldId')) {
            return tr('*** ERROR: Field ID (This tracker) cannot be the same: %0 ***', $filterFieldIdHere);
        }

        if (! TikiLib::lib('trk')->get_tracker_field($listFieldIdThere)) {
            return tr('*** ERROR: Field %0 not found ***', $listFieldIdThere);
        }

        if ($this->getOption('selectMultipleValues')) {
            if (is_array($originalValue)) {
                $originalValue = implode(',', $originalValue);
            }
            $filterFieldValueHere = explode(',', $originalValue);
            $multiple = ' multiple="multiple"';
            $insertId .= '[]';
        } else {
            $multiple = '';
        }

        TikiLib::lib('header')->add_jq_onready(
            '
$("body").on("change", "input[name=ins_' . $filterFieldIdHere . '], select[name=ins_' . $filterFieldIdHere . ']", function(e) {
    $( "select[name=\'' . $insertId . '\']").parent().tikiModal(tr("Loading..."));
    $.getJSON(
        "tiki-tracker_http_request.php",
        {
            filterFieldIdHere: ' . $filterFieldIdHere . ',
            trackerIdThere: ' . $trackerIdThere . ',
            listFieldIdThere: ' . $listFieldIdThere . ',
            filterFieldIdThere: ' . $filterFieldIdThere . ',
            statusThere: "' . $statusThere . '",
            mandatory: "' . $isMandatory . '",
            insertId: "' . $insertId . '",  // need to pass $insertId in case we have more than one field bound to the same eventsource
            originalValue:  "' . $originalValue . '",
            hideBlank: ' . (int)$hideBlank . ',
            selectMultipleValues: ' . $selectMultipleValues . ',
            filterFieldValueHere: $(this).val(), // We need the field value for the fieldId filterfield for the item $(this).val,
            linkToItems: ' . $linkToItems . '
        },
        
        // callback
        function(data, status) {
            if (data && data.request && data.response) {
                targetDDL = "select[name=\'" + data.request.insertId + "\']";
                $ddl = $(targetDDL);
                $ddl.empty();
                
                var v, l;
                response = data.response;
                $.each( response, function (i,data) {
                    if (data && data.length > 1) {
                        v = data[0];
                        l = data[1];
                    } else {
                        v = ""
                        l = "";
                    }
                    $ddl.append(
                        $("<option/>")
                            .val(v)
                            .text(l)
                    );
                }); // each
                    
                if (data.request.originalValue) {
                    $.each(data.request.originalValue.split(","), function(i,e){
                        $("option[value=\'" + e + "\']", $ddl).prop("selected", true);
                    });
                }
            }

            if (jqueryTiki.select2) {
                $ddl.trigger("change.select2");
            }
            $ddl.trigger("change");
            $ddl.parent().tikiModal();
        } // callback
    );  // getJSON
});

if( $("input[name=ins_' . $filterFieldIdHere . '], select[name=ins_' . $filterFieldIdHere . ']").length == 0 ) {
    // inline edit fix
    $("<input type=\"hidden\" name=\"ins_' . $filterFieldIdHere . '\">").val(' . json_encode($filterFieldValueHere) . ').insertBefore("select[name=\'' . $insertId . '\']").trigger("change");
}
        '
        ); // add_jq_onready
        TikiLib::lib('header')->add_jq_onready('
$("input[name=ins_' . $filterFieldIdHere . '], select[name=ins_' . $filterFieldIdHere . ']").trigger("change", "initial");
', 1);

        return '<select class="form-control"' . $multiple . ' name="' . $insertId . '"></select>';
    }




    // If you make changes here check also tiki-tracker_http_request.php as long as it is not integrated in ajax-services
    // @TODO Move parts of this to getFieldData()
    public function renderInnerOutput($context = [])
    {
        $trklib = TikiLib::lib('trk');
        // remote tracker and remote field
        $trackerIdThere = $this->getOption('trackerId');
        $definition = Tracker_Definition::get($trackerIdThere);
        if (empty($definition)) {
            return tr('*** ERROR: No remote tracker selected for DynamicList Field %0 ***', $this->getConfiguration('fieldId'));
        }
        $listFieldIdThere = $this->getOption('listFieldIdThere');
        $listFieldThere = $definition->getField($listFieldIdThere);

        // $listFieldThere above does not return any value for fieldtype category. Maybe a bug?
        if (! isset($listFieldThere)) {
            $listFieldThere = $trklib->get_tracker_field($listFieldIdThere);
        }

        if (empty($listFieldThere)) {
            return tr('*** ERROR: Field %0 not found ***', $listFieldIdThere);
        }

        $remoteItemIds = $this->getValue();
        if ($this->getOption('selectMultipleValues') && ! is_array($remoteItemIds)) {
            $remoteItemIds = explode(',', $remoteItemIds);
            $remoteItemIds = array_filter($remoteItemIds);
        }
        $output = '';
        if (! array_key_exists('list_mode', $context)) {
            $context['list_mode'] = '';
        }

        // If the request method = GET i.e there is no request for a csv export
        if (! empty($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'GET') {
            if ($this->getOption('linkToItems')) {
                $context['linkToItems'] = 'y';
            }
        }

        foreach ((array) $remoteItemIds as $remoteItemId) {
            $itemInfo = $trklib->get_tracker_item($remoteItemId);

            if ($context['list_mode'] == 'csv' || (isset($context['search_render']) && $context['search_render'] == 'y')) {
                $output .= $output ? ', ' : '';
            } else {
                $output .= $output ? '<br>' : '';
            }

            switch ($listFieldThere['type']) {
                // e = category
                // d = dropdown
                case 'e':
                case 'd':
                    //$listFieldThere = array_merge($listFieldThere, array('value' => $remoteItemId));
                    $handler = $trklib->get_field_handler($listFieldThere, $itemInfo);
                    // array selected_categories etc.
                    if ($handler) {
                        $valueField = $handler->getFieldData();
                    } else {
                        Feedback::error(tr('DynamicList field: Field "%0" not found', $listFieldThere['permName']));
                    }
                    // for some reason, need to apply the values back, otherwise renderOutput does not return a value - bug or intended?
                    $listFieldThere = array_merge($listFieldThere, $valueField);
                    $handler = $trklib->get_field_handler($listFieldThere, $itemInfo);
                    if ($handler) {
                        $labelField = $handler->renderOutput($context);
                        $output .= $labelField;
                    } else {
                        Feedback::error(tr('DynamicList field: Field "%0" not found', $listFieldThere['permName']));
                    }
                    break;

                // r = item-link requires $listFieldThere = array_merge($listFieldThere, array('value' => $remoteItemId));
                case 'r':
                    $listFieldThere = array_merge($listFieldThere, ['value' => $remoteItemId]);
                    $handler = $trklib->get_field_handler($listFieldThere, $itemInfo);
                    // do not inherit showlinks settings from remote items.
                    if ($handler) {
                        $labelField = $handler->renderOutput($context);
                        $output .= $labelField;
                    } else {
                        Feedback::error(tr('DynamicList field: Field "%0" not found', $listFieldThere['permName']));
                    }
                    break;

                //l = item-list
                case 'l':
                    // show selected item of that list - requires match in tiki-tracker_http_request.php
                    //$listFieldThere = array_merge($listFieldThere, array('value' => $remoteItemId));
                    $handler = $trklib->get_field_handler($listFieldThere);
                    if ($handler) {
                        $displayFieldIdThere = $handler->getOption('displayFieldIdThere');
                    } else {
                        Feedback::error(tr('DynamicList field: Field "%0" not found', $listFieldThere['permName']));
                    }
                    // do not inherit showlinks settings from remote items.
                    foreach ($displayFieldIdThere as $displayFieldId) {
                        $displayField = $trklib->get_tracker_field($displayFieldId);
                        // not shure why this is needed - and only for some fieldtypes?
                        //renderOutput() in abstract checks only $this->definition['value'], not $this->itemdata
                        $displayField = array_merge($displayField, ['value' => $itemInfo[$displayFieldId]]);
                        $handler = $trklib->get_field_handler($displayField, $itemInfo);
                        if ($handler) {
                            $labelFields[] = $handler->renderOutput($context);
                        } else {
                            Feedback::error(tr('DynamicList field: Field "%0" not found', $displayField['permName']));
                        }
                    }
                    $labelField = implode(' ', $labelFields);
                    $output .= $labelField;
                    break;


                // i.e textfield - requires $listFieldThere = array_merge($listFieldThere, array('value' => $itemInfo[$listFieldIdThere]));
                default:
                    if (isset($itemInfo[$listFieldIdThere])) {
                        $listFieldThere = array_merge($listFieldThere, ['value' => $itemInfo[$listFieldIdThere]]);
                    }
                    $handler = $trklib->get_field_handler($listFieldThere, $itemInfo);
                    // do not inherit showlinks settings from remote items.
                    if ($handler) {
                        $labelField = $handler->renderOutput($context);
                        $output .= $labelField;
                    } else {
                        Feedback::error(tr('DynamicList field: Field "%0" not found', $listFieldThere['permName']));
                    }
                    break;
            }
        }
        return $output;
    }

    public function getDocumentPart(Search_Type_Factory_Interface $typeFactory)
    {
        $item = $this->getValue();
        $baseKey = $this->getBaseKey();

        $out = [
            "{$baseKey}_text" => $typeFactory->sortable($this->renderInnerOutput(['list_mode' => 'csv'])),
        ];
        if ($this->getOption('selectMultipleValues') && ! is_array($item)) {
            $out[$baseKey] = $typeFactory->multivalue(explode(',', $item));
        } else {
            $out[$baseKey] = $typeFactory->identifier($item);
        }

        return $out;
    }

    public function getProvidedFields(): array
    {
        $baseKey = $this->getBaseKey();
        return [$baseKey, "{$baseKey}_text"];
    }

    public function getProvidedFieldTypes(): array
    {
        $baseKey = $this->getBaseKey();
        return [
            $baseKey => $this->getOption('selectMultipleValues') ? 'multivalue' : 'identifier',
            "{$baseKey}_text" => 'sortable'
        ];
    }

    public function getGlobalFields(): array
    {
        $baseKey = $this->getBaseKey();
        return ["{$baseKey}_text" => true];
    }

    public function getItemList()
    {
        return TikiLib::lib('trk')->get_all_items(
            $this->getOption('trackerId'),
            $this->getOption('listFieldIdThere'),
            $this->getOption('statusThere', 'opc')
        );
    }

    public function getPossibleItemValues()
    {
        return $this->getItemList();
    }

    /**
     * Get tabular schema
     * @return Schema
     */
    public function getTabularSchema()
    {
        $schema = new Tracker\Tabular\Schema($this->getTrackerDefinition());
        $permName = $this->getConfiguration('permName');
        $name = $this->getConfiguration('name');

        $schema->addNew($permName, 'multi-id')
            ->setLabel($name)
            ->setReadOnly(true)
            ->setRenderTransform(function ($value) {
                if (is_array($value)) {
                    $value = implode(';', $value);
                }

                return $value;
            })
            ->setParseIntoTransform(function (&$info, $value) use ($permName) {
                $info['fields'][$permName] = $value;
            });

        $schema->addNew($permName, 'multi-name')
            ->setLabel($name)
            ->addQuerySource('itemId', 'object_id')
            ->setReadOnly(true)
            ->setRenderTransform(function ($value, $extra) {
                if (is_string($value) && empty($value)) {
                    $field = $this->getTrackerDefinition()->getFieldFromPermName($this->getConfiguration('permName'));
                    $factory = $this->getTrackerDefinition()->getFieldFactory();
                    $handler = $factory->getHandler($field, ['itemId' => $extra['itemId']]);
                    $value = $handler->getItemIds();
                }

                $labels = $this->getItemLabels($value, ['list_mode' => 'csv']);
                return implode(';', $labels);
            })
            ->setParseIntoTransform(function (&$info, $value) use ($permName) {
                $info['fields'][$permName] = $value;
            });

        return $schema;
    }

    private function getItemIds()
    {
        $trklib = TikiLib::lib('trk');
        $trackerId = (int) $this->getOption('trackerId');

        $filterFieldIdHere = (int) $this->getOption('fieldIdHere');
        $filterFieldIdThere = (int) $this->getOption('fieldIdThere');

        $filterFieldHere = $this->getTrackerDefinition()->getField($filterFieldIdHere);
        $filterFieldThere = $trklib->get_tracker_field($filterFieldIdThere);

        $sortFieldIds = $this->getOption('sortField');
        if (is_array($sortFieldIds)) {
            $sortFieldIds = array_filter($sortFieldIds);
        } else {
            $sortFieldIds = [];
        }
        $status = $this->getOption('status', 'opc');
        $tracker = Tracker_Definition::get($trackerId);



        // note: if itemlink or dynamic item list is used, than the final value to compare with must be calculated based on the current itemid

        $technique = 'value';

        // not sure this is working
        // r = item link
        if ($tracker && $filterFieldThere && (! $filterFieldIdHere || $filterFieldThere['type'] === 'r' || $filterFieldThere['type'] === 'w')) {
            if ($filterFieldThere['type'] === 'r' || $filterFieldThere['type'] === 'w') {
                $technique = 'id';
            }
        }

        // not sure this is working
        // q = Autoincrement
        if ($filterFieldHere['type'] == 'q' && isset($filterFieldHere['options_array'][3]) && $filterFieldHere['options_array'][3] == 'itemId') {
            $technique = 'id';
        }

        if ($technique == 'id') {
            $itemId = $this->getItemId();
            if (! $itemId) {
                $items = [];
            } else {
                $items = $trklib->get_items_list($trackerId, $filterFieldIdThere, $itemId, $status, false, $sortFieldIds);
            }
        } else {
            // when this is an item link or dynamic item list field, localvalue contains the target itemId
            $localValue = $this->getData($filterFieldIdHere);
            if (! $localValue) {
                // in some cases e.g. pretty tracker $this->getData($filterFieldIdHere) is not reliable as the info is not there
                // Note: this fix only works if the itemId is passed via the template
                $itemId = $this->getItemId();
                $localValue = $trklib->get_item_value($trackerId, $itemId, $filterFieldIdHere);
            }
            if (! $filterFieldThere && $filterFieldHere && ( $filterFieldHere['type'] === 'r' || $filterFieldHere['type'] === 'w' ) && $localValue) {
                // itemlink/dynamic item list field in this tracker pointing directly to an item in the other tracker
                return [$localValue];
            }
            // r = item link - not sure this is working
            if ($filterFieldHere['type'] == 'r' && isset($filterFieldHere['options_array'][0]) && isset($filterFieldHere['options_array'][1])) {
                $localValue = $trklib->get_item_value($filterFieldHere['options_array'][0], $localValue, $filterFieldHere['options_array'][1]);
            }

            // w = dynamic item list - localvalue is the itemid of the target item. so rewrite.
            if ($filterFieldHere['type'] == 'w') {
                $localValue = $trklib->get_item_value($trackerId, $localValue, $filterFieldIdThere);
            }
            // u = user selector, might be mulitple users so need to find multiple values
            if (
                $filterFieldHere['type'] == 'u' && ! empty($filterFieldHere['options_map']['multiple'])
                && $localValue
            ) {
                if (! is_array($localValue)) {
                    $theUsers = explode(',', $localValue);
                } else {
                    $theUsers = $localValue;
                }
                $items = [];
                foreach ($theUsers as $theUser) {
                    $items = array_merge(
                        $items,
                        $trklib->get_items_list($trackerId, $filterFieldIdThere, $theUser, $status, false, $sortFieldIds)
                    );
                }

                return $items;
            }
            // e = category, might be mulitple categories so need to find multiple values
            if ($filterFieldHere['type'] == 'e' && $localValue) {
                if (! is_array($localValue)) {
                    $categories = explode(',', $localValue);
                } else {
                    $categories = $localValue;
                }
                $items = [];
                foreach ($categories as $category) {
                    $items = array_merge(
                        $items,
                        $trklib->get_items_list($trackerId, $filterFieldIdThere, $category, $status, true, $sortFieldIds)
                    );
                }

                return $items;
            }
            // Skip nulls
            if ($localValue) {
                $items = $trklib->get_items_list($trackerId, $filterFieldIdThere, $localValue, $status, false, $sortFieldIds);
            } else {
                $items = [];
            }
        }

        return $items;
    }

    /**
     * Get value of displayfields from given array of itemIds
     * @param array $items
     * @param array $context
     * @return array array of values by itemId
     * @throws Exception
     */
    private function getItemLabels($items, $context = ['list_mode' => ''])
    {
        $trackerId = (int) $this->getOption('trackerId');

        $definition = Tracker_Definition::get($trackerId);
        if (! $definition) {
            return [];
        }

        $list = [];
        $trklib = TikiLib::lib('trk');

        if (! is_array($items)) {
            $items = [$items];
        }

        foreach ($items as $itemId) {
            $item = $trklib->get_tracker_item($itemId);
            $list[$itemId] = $item[$this->getOption('listFieldIdThere')];
        }

        return $list;
    }
}
