<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * Handler class for ItemLink
 *
 * Letter key: ~r~
 *
 */
class Tracker_Field_ItemLink extends \Tracker\Field\AbstractItemField implements \Tracker\Field\SynchronizableInterface, \Tracker\Field\ExportableInterface, Search_FacetProvider_Interface, \Tracker\Field\FilterableInterface, \Tracker\Field\EnumerableInterface
{
    private const CASCADE_NONE = 0;
    private const CASCADE_CATEG = 1;
    private const CASCADE_STATUS = 2;
    private const CASCADE_DELETE = 4;

    public static function getTrackerFieldClass(): string
    {
        return \Tracker\Field\TrackerFieldItemLink::class;
    }

    public static function getManagedTypesInfo(): array
    {
        return [
            'r' => [
                'name' => tr('Item Link'),
                'description' => tr('Link to another item, similar to a foreign key'),
                'help' => 'Items-List-and-Item-Link-Tracker-Fields',
                'prefs' => ['trackerfield_itemlink'],
                'tags' => ['advanced'],
                'default' => 'y',
                'supported_changes' => ['r', 'REL'],
                'params' => [
                    'trackerId' => [
                        'name' => tr('Tracker ID'),
                        'description' => tr('Tracker to link to'),
                        'filter' => 'int',
                        'legacy_index' => 0,
                        'profile_reference' => 'tracker',
                    ],
                    'fieldId' => [
                        'name' => tr('Field ID'),
                        'description' => tr('Default field to display'),
                        'filter' => 'int',
                        'legacy_index' => 1,
                        'profile_reference' => 'tracker_field',
                        'parent' => 'trackerId',
                        'parentkey' => 'tracker_id',
                        'sort_order' => 'position_nasc',
                    ],
                    'linkToItem' => [
                        'name' => tr('Display'),
                        'description' => tr('How the link to the item should be rendered'),
                        'filter' => 'int',
                        'options' => [
                            0 => tr('Value'),
                            1 => tr('Link'),
                        ],
                        'legacy_index' => 2,
                    ],
                    'displayFieldsListType' => [
                        'name' => tr('Display type'),
                        'description' => tr('Display options in a dropdown, a transfer list or use table to display multiple fields'),
                        'filter' => 'alpha',
                        'options' => [
                            'dropdown' => tr('Dropdown'),
                            'table' => tr('Table'),
                            'transfer' => tr('Transfer'),
                        ],
                        'legacy_index' => 14,
                    ],
                    'lazyload' => [
                        'name' => tr('Lazy Load'),
                        'description' => tr('Load the list of items based on the user query.'),
                        'filter' => 'int',
                        'options' => [
                            0 => tr('No'),
                            1 => tr('Yes'),
                        ],
                        'default' => 0,
                        'depends' => [
                            'field' => 'displayFieldsListType',
                            'value' => 'dropdown',
                            'pref' => 'elementplus_select'
                        ],
                    ],
                    'displayFieldsList' => [
                        'name' => tr('Multiple Fields'),
                        'description' => tr('Display the values from multiple fields instead of a single one.'),
                        'separator' => '|',
                        'filter' => 'int',
                        'legacy_index' => 3,
                        'profile_reference' => 'tracker_field',
                        'parent' => 'trackerId',
                        'parentkey' => 'tracker_id',
                        'sort_order' => 'position_nasc',
                    ],
                    'displayFieldsListFormat' => [
                        'name' => tr('Format for Customising Multiple Fields'),
                        'description' => tr('Uses the translate function to replace %0 etc with the field values. E.g. "%0 any text %1"'),
                        'filter' => 'text',
                        'depends' => [
                            'field' => 'displayFieldsList'
                        ],
                    ],
                    'filterable' => [
                        'name' => tr('Filterable'),
                        'description' => tr('Allow the user to filter items within the transfer list'),
                        'filter' => 'int',
                        'options' => [
                            0 => tr('No'),
                            1 => tr('Yes'),
                        ],
                        'depends' => [
                            'field' => 'displayFieldsListType',
                            'value' => 'transfer'
                        ],
                    ],
                    'filterPlaceholder' => [
                        'name' => tr('Filter Placeholder'),
                        'description' => tr('Placeholder text for the filter input'),
                        'filter' => 'text',
                        'depends' => [
                            'field' => 'filterable',
                            'value' => '1'
                        ],
                    ],
                    'sourceListTitle' => [
                        'name' => tr('Source List Title'),
                        'description' => tr('Title for the source list'),
                        'filter' => 'text',
                        'depends' => [
                            'field' => 'displayFieldsListType',
                            'value' => 'transfer'
                        ],
                    ],
                    'targetListTitle' => [
                        'name' => tr('Target List Title'),
                        'description' => tr('Title for the target list'),
                        'filter' => 'text',
                        'depends' => [
                            'field' => 'displayFieldsListType',
                            'value' => 'transfer'
                        ],
                    ],
                    'ordering' => [
                        'name' => tr('Ordering'),
                        'description' => tr('Allow re-ordering of items in the list'),
                        'filter' => 'int',
                        'options' => [
                            0 => tr('No'),
                            1 => tr('Yes'),
                        ],
                        'depends' => [
                            'field' => 'displayFieldsListType',
                            'value' => 'transfer'
                        ],
                    ],
                    'displayFieldsListSort' => [
                        'name' => tr('Default Sort Order'),
                        'description' => tr('Sort the items in the dropdown list.'),
                        'filter' => 'alpha',
                        'options' => [
                            'itemId' => tr('Item ID'),
                            'formatted' => tr('Formatted Value'),
                        ],
                        'depends' => [
                            'field' => 'displayFieldsListType',
                            'value' => 'dropdown'
                        ],
                    ],
                    'trackerListOptions' => [
                        'name' => tr('Plugin TrackerList options'),
                        'description' => tr('Override one or more options of Plugin TrackerList to customize displayed table at item edit time (e.g. editable, tsfilters, etc.)'),
                        'filter' => 'text',
                        'type' => 'textarea',
                        'depends' => [
                            'field' => 'displayFieldsListType',
                            'value' => 'table'
                        ],
                        'legacy_index' => 15,
                        'profile_reference' => 'tracker_field_string',
                    ],
                    'status' => [
                        'name' => tr('Status Filter'),
                        'description' => tr('Limit the available items to a selected set'),
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
                    'linkPage' => [
                        'name' => tr('Link Page'),
                        'description' => tr('Link to a wiki page instead of directly to the item'),
                        'filter' => 'pagename',
                        'legacy_index' => 5,
                        'profile_reference' => 'wiki_page',
                    ],
                    'addItems' => [
                        'name' => tr('Add Items'),
                        'description' => tr('Display text to allow new items to be added - e.g. "Add item..." (requires jQuery-UI)'),
                        'filter' => 'text',
                        'legacy_index' => 6,
                    ],
                    'addItemsWikiTpl' => [
                        'name' => tr('Add Item Template Page'),
                        'description' => tr('Wiki page to use as a Pretty Tracker template'),
                        'filter' => 'pagename',
                        'legacy_index' => 7,
                        'profile_reference' => 'wiki_page',
                        'depends' => [
                            'field' => 'addItems'
                        ],
                    ],
                    'preSelectFieldHere' => [
                        'name' => tr('Preselect item based on value in this field'),
                        'description' => tr('Preselect item based on value in specified field ID of item being edited'),
                        'filter' => 'int',
                        'legacy_index' => 8,
                        'profile_reference' => 'tracker_field',
                        'parent' => 'input[name=trackerId]',
                        'parentkey' => 'tracker_id',
                        'sort_order' => 'position_nasc',
                    ],
                    'preSelectFieldThere' => [
                        'name' => tr('Preselect based on the value in this remote field'),
                        'description' => tr('Match preselect item to this field ID in the tracker that is being linked to'),
                        'filter' => 'int',
                        'legacy_index' => 9,
                        'profile_reference' => 'tracker_field',
                        'parent' => 'trackerId',
                        'parentkey' => 'tracker_id',
                        'sort_order' => 'position_nasc',
                        'depends' => [
                            'field' => 'preSelectFieldHere'
                        ],
                    ],
                    'preSelectFieldMethod' => [
                        'name' => tr('Preselection matching method'),
                        'description' => tr('Method to use to match fields for preselection purposes'),
                        'filter' => 'alpha',
                        'options' => [
                            'exact' => tr('Exact Match'),
                            'partial' => tr('Field here is part of field there'),
                            'domain' => tr('Match domain, used for URL fields'),
                            'crossSelect' => tr('Cross select. Load all matching items in the remote tracker'),
                            'crossSelectWildcards' => tr('Cross select. Load all matching items in the remote tracker plus wildcards'),
                        ],
                        'depends' => [
                            'field' => 'preSelectFieldHere'
                        ],
                        'legacy_index' => 10,
                    ],
                    'displayOneItem' => [
                        'name' => tr('One item per value'),
                        'description' => tr('Display only one item for each label (at random, needed for filtering records in a dynamic items list) or all items'),
                        'filter' => 'alpha',
                        'options' => [
                            'multi' => tr('Displays all the items for a same label with a notation value (itemId)'),
                            'one' => tr('Display only one item for each label'),
                        ],
                        'legacy_index' => 11,
                    ],
                    'selectMultipleValues' => [
                        'name' => tr('Select multiple values'),
                        'description' => tr('Allow the user to select multiple values'),
                        'filter' => 'int',
                        'options' => [
                            0 => tr('No'),
                            1 => tr('Yes'),
                        ],
                        'depends' => [
                            'field' => 'displayFieldsListType',
                            'value' => 'dropdown'
                        ],
                        'legacy_index' => 12,
                    ],
                    'indexRemote' => [
                        'name' => tr('Index remote fields'),
                        'description' => tr('Index one or multiple fields from the master tracker along with the child, separated by |'),
                        'separator' => '|',
                        'filter' => 'int',
                        'legacy_index' => 13,
                        'profile_reference' => 'tracker_field',
                        'parent' => 'trackerId',
                        'parentkey' => 'tracker_id',
                    ],
                    'cascade' => [
                        'name' => tr('Cascade actions'),
                        'description' => tr("Elements to cascade when the master is updated or deleted. Categories may conflict if multiple item links are used to different items attempting to manage the same categories. Same for status."),
                        'filter' => 'int',
                        'options' => [
                            self::CASCADE_NONE => tr('No'),
                            self::CASCADE_CATEG => tr('Categories'),
                            self::CASCADE_STATUS => tr('Status'),
                            self::CASCADE_DELETE => tr('Delete'),
                            (self::CASCADE_CATEG | self::CASCADE_STATUS) => tr('Categories and status'),
                            (self::CASCADE_CATEG | self::CASCADE_DELETE) => tr('Categories and delete'),
                            (self::CASCADE_DELETE | self::CASCADE_STATUS) => tr('Delete and status'),
                            (self::CASCADE_CATEG | self::CASCADE_STATUS | self::CASCADE_DELETE) => tr('All'),
                        ],
                        'legacy_index' => 14,
                    ],
                    'duplicateCascade' => [
                        'name' => tr('Cascade duplicate action'),
                        'description' => tr('Duplicate (Duplicate tracker items feature) an item in the master/parent tracker (Tracker to link to) silently creates a slave/child item in this tracker (No by default).'),
                        'filter' => 'int',
                        'options' => [
                            0 => tr('No'),
                            1 => tr('Yes'),
                        ],
                        'default' => '0',
                        'depends' => [
                            'pref' => 'tracker_clone_item',
                        ],
                        'legacy_index' => 15,
                    ],
                ],
            ],
        ];
    }

    public function getFieldData(array $requestData = []): array
    {
        $string_id = $this->getInsertId();
        if (isset($requestData[$string_id])) {
            $value = $requestData[$string_id];
        } elseif (isset($requestData[$string_id . '_old'])) {
            $value = '';
        } else {
            $value = $this->getValue();
        }

        $data = [
            'value' => $value,
        ];

        if ($this->canHaveMultipleValues() && ! is_array($data['value'])) {
            $data['value'] = explode(',', $data['value']);
        }

        return $data;
    }

    public function addValue($value)
    {
        $existing = $this->getValue();
        if (! is_array($existing)) {
            $existing = explode(',', $existing);
        }
        if (! in_array($value, $existing)) {
            $existing[] = $value;
        }
        return implode(',', $existing);
    }

    public function removeValue($value)
    {
        $existing = $this->getValue();
        if (! is_array($existing)) {
            $existing = explode(',', $existing);
        }
        $existing = array_filter($existing, function ($v) use ($value) {
            return $v != $value;
        });
        return implode(',', $existing);
    }

    public function useSelector()
    {
        global $prefs;

        if ($prefs['feature_search'] != 'y') {
            return false;
        }

        if ($this->canHaveMultipleValues()) {
            return false;
        }

        if ($this->trackerField->getOption('displayOneItem') === 'one') {
            return false;
        }

        if ($this->trackerField->getOption('preSelectFieldMethod') === 'crossSelect' || $this->trackerField->getOption('preSelectFieldMethod') === 'crossSelectWildcards') {
            return false;
        }

        return true;
    }

    public function renderInput($context = [])
    {
        $trackerId = $this->trackerField->getOption('trackerId');
        $trackerPerms = Perms::get('tracker', $trackerId);

        $trackerId = (int) $trackerId;

        if (! $trackerId) {
            Feedback::error(
                tr(
                    'ItemsLink field %0 on tracker %1 doesn\'t have a destination tracker set yet in its trackerId option.',
                    $this->getTrackerFieldInstance()->getPermName(),
                    $this->getTrackerDefinition()->getId()
                )
            );
            return;
        }

        if ($this->useSelector()) {
            $value = $this->getValue();
            $placeholder = tr(TikiLib::lib('object')->get_title('tracker', $trackerId));
            $status = implode(' OR ', str_split($this->trackerField->getOption('status', 'opc'), 1));
            $value = $value ? "trackeritem:$value" : null;

            $format = $this->formatForObjectSelector();

            $sort = TikiLib::lib('trk')->get_default_sort_order($trackerId, true, true);

            $template = $this->renderTemplate('trackerinput/itemlink_selector.tpl', $context, [
                'placeholder' => $placeholder,
                'status' => $status,
                'selector_value' => $value,
                'selector_id' => 'item' . $this->getItemId() . $this->getInsertId(),
                'format' => $format,
                'createTrackerItems' => $trackerPerms->create_tracker_items,
                'sort' => $sort,
            ]);

            return $template;
        }

        if ($this->trackerField->getOption('lazyload')) {
            $list = [];
            foreach ($this->getValue() as $value) {
                if ($value) {
                    $list[$value] = $this->getItemLabel($value);
                }
            }
        } else {
            $list = $this->getPossibleItemValues();
        }

        $data = [
            'list' => $list,
            'listFields' => implode(',', $this->trackerField->getOption('displayFieldsList')),
            'displayFieldsListType' => $this->trackerField->getOption('displayFieldsListType'),
            'createTrackerItems' => $trackerPerms->create_tracker_items,
        ];

        $servicelib = TikiLib::lib('service');
        if ($this->getItemId()) {
            $data['next'] = $servicelib->getUrl([
                'controller' => 'tracker',
                'action' => 'update_item',
                'trackerId' => $this->getConfiguration('trackerId'),
                'itemId' => $this->getItemId(),
            ]);
        } else {
            $data['next'] = $servicelib->getUrl([
                'controller' => 'tracker',
                'action' => 'insert_item',
                'trackerId' => $this->getConfiguration('trackerId'),
            ]);
        }

        $data['selectMultipleValues'] = (bool) $this->trackerField->getOption('selectMultipleValues');

        // 'crossSelect' overrides the preselection reference, which is enabled, when a cross reference Item Link <-> Item Link
        //  When selecting a value another item link can provide the relation, then the cross link can point to several records having the same linked value.
        //  Example Contact and Report links to a Company. Report also links to Contact. When selecting Contact, Only Contacts in the same company as the Report is linked to, should be made visible.
        //  When 'crossSelect' is enabled
        //      1) The dropdown list is no longer disabled (else disabled)
        //      2) All rows in the remote tracker matching the criterea are displayed in the dropdown list (else only 1 row is displayed)
        $method = $this->trackerField->getOption('preSelectFieldMethod');
        if ($method == 'crossSelect' || $method == 'crossSelectWildcards') {
            $data['crossSelect'] = 'y';
        } else {
            $data['crossSelect'] = 'n';
        }

        // Prepare for 'crossSelect'
        $linkValue = false;     // Value which links the tracker items
        if ($data['crossSelect'] === 'y') {
            // Check if itemId is set / used.
            // If not, it must be set here
            $itemData = $this->getItemData();
            if (empty($itemData['itemId'])) {
                if (! empty($_REQUEST['itemId'])) {
                    $linkValue = $_REQUEST['itemId'];
                }
            } else {
                $linkValue = $itemData['itemId'];
            }
        }

        if ($preselection = $this->getPreselection($linkValue)) {
            $data['preselection'] = $preselection;
            $data['preselection_value'] = TikiLib::lib('trk')->get_item_value($this->getConfiguration('trackerId'), $this->getItemId(), $this->trackerField->getOption('preSelectFieldHere'));
        } else {
            $preselection = $data['preselection'] = [];
            $data['preselection_value'] = "";
        }

        $data['filter'] = $this->buildFilter();

        if ($data['crossSelect'] === 'y' && ! empty($preselection) && is_array($preselection)) {
            if ($this->trackerField->getOption('displayFieldsListType') === 'table') {
                // nothing to do, list is loaded dynamically via plugin trackerlist
            } else {
                $data['list'] = array_intersect_key($data['list'], array_flip($preselection));
            }
        }

        if ($this->trackerField->getOption('preSelectFieldThere') && $this->trackerField->getOption('preSelectFieldMethod') != 'crossSelectWildcards') {
            $data['predefined'] = $this->getItemsToClone();
        } else {
            $data['predefined'] = [];
        }

        if ($data['displayFieldsListType'] === 'table') {
            $data['trackerListOptions'] = [
                'trackerId' => $trackerId,
                'fields' => implode(':', $this->trackerField->getOption('displayFieldsList')),
                'editableall' => 'y',
                'showlinks' => 'y',
                'sortable' => 'type:reset',
                'sortList' => '[1,0]',
                'tsfilters' => 'type:nofilter',
                'tsfilteroptions' => 'type:reset',
                'tspaginate' => 'max:5',
                'checkbox' => '/' . $this->getInsertId() . '//////y/' . implode(',', is_array($this->getValue()) ? $this->getValue() : [$this->getValue()]),
                'ignoreRequestItemId' => 'y',
                'url' => $servicelib->getUrl([
                    'controller' => 'tracker',
                    'action' => 'update_item',
                    'trackerId' => $trackerId,
                    'itemId' => '#itemId',
                ])
            ];
            if ($this->trackerField->getOption('preSelectFieldThere')) {
                $data['trackerListOptions']['filterfield'] = $this->trackerField->getOption('preSelectFieldThere');
                $data['trackerListOptions']['exactvalue'] = $data['preselection_value'];
                if ($this->trackerField->getOption('preSelectFieldMethod') == 'crossSelectWildcards') {
                    $data['trackerListOptions']['exactvalue'] = 'or(*,' . $data['trackerListOptions']['exactvalue'] . ')';
                }
            }
            if ($this->trackerField->getOption('trackerListOptions')) {
                $parser = new WikiParser_PluginArgumentParser();
                $arguments = $parser->parse($this->trackerField->getOption('trackerListOptions'));
                $data['trackerListOptions'] = array_merge($data['trackerListOptions'], $arguments);
                if (! empty($arguments['editable']) && empty($arguments['editableall'])) {
                    $data['trackerListOptions']['editableall'] = 'n';
                }
                if (isset($arguments['checkbox']) && (empty($arguments['checkbox']) || $arguments['checkbox'] == 'n')) {
                    $data['trackerListOptions']['checkbox'] = '/' . $this->getInsertId() . '//////y/' . implode(',', $data['preselection']);
                }
            }
        }

        if ($this->trackerField->getOption('fieldId') && $this->trackerField->getOption('addItems')) {
            $definition = Tracker_Definition::get($trackerId);
            $fieldArray = $definition->getField($this->trackerField->getOption('fieldId'));
            $data['otherFieldPermName'] = $this->getFieldReference($fieldArray);
        }

        if ($this->trackerField->getOption('displayFieldsListType') === 'transfer') {
            $filterPlaceholder = $this->trackerField->getOption('filterPlaceholder') ?: 'Enter keyword';
            $sourceListTitle = $this->trackerField->getOption('sourceListTitle') ?: 'List';
            $targetListTitle = $this->trackerField->getOption('targetListTitle') ?: 'Selected';

            $smarty = TikiLib::lib('smarty');

            return smarty_function_jstransfer_list([
                'fieldName' => $this->getHTMLFieldName(),
                'data' => $data['list'],
                'defaultSelected' => $this->getValue(),
                'sourceListTitle' => $sourceListTitle,
                'targetListTitle' => $targetListTitle,
                'filterable' => $this->trackerField->getOption('filterable'),
                'filterPlaceholder' => $filterPlaceholder,
                'ordering' => $this->trackerField->getOption('ordering'),
                'cardinalityParam' => $this->getConfiguration('validationParam'),
                'validationMessage' => $this->getConfiguration('validationMessage')
            ], $smarty->getEmptyInternalTemplate());
        }

        return $this->renderTemplate('trackerinput/itemlink.tpl', $context, $data);
    }

    /**
     * the labels on the select will not necessarily be the title field, so offer the object_selector the correct format string
     * also used to format the proper string for Relations field conversion which again uses object_selector
     */
    private function formatForObjectSelector()
    {
        $displayFieldsListArray = $this->getDisplayFieldsListArray();
        $definition = Tracker_Definition::get($this->trackerField->getOption('trackerId'));
        if (! $definition) {
            Feedback::error(tr('ItemLink: Tracker %0 not found for field "%1"', $this->trackerField->getOption('trackerId'), $this->getConfiguration('permName')));
            return '';
        }
        if ($displayFieldsListArray) {
            array_walk($displayFieldsListArray, function (&$field) use ($definition) {
                $fieldArray = $definition->getField($field);
                if (! $fieldArray) {
                    $message = tr('ItemLink: Field %0 not found for field "%1"', $field, $this->getConfiguration('permName'));
                    $field = '<div class="alert alert-danger">' . $message . '</div>';
                } else {
                    $field = '{tracker_field_' . $this->getFieldReference($fieldArray) . '}';
                }
            });
            if ($format = $this->trackerField->getOption('displayFieldsListFormat')) {
                $format = tra($format, '', false, $displayFieldsListArray);
            } else {
                $format = implode(' ', $displayFieldsListArray);
            }
        } else {
            $fieldArray = $definition->getField($this->trackerField->getOption('fieldId'));
            if (! $fieldArray) {
                $message = tr('ItemLink: Field %0 not found for field "%1"', $this->trackerField->getOption('fieldId'), $this->getConfiguration('permName'));
                $format = '<div class="alert alert-danger">' . $message . '</div>';
            } elseif (! $format = $this->trackerField->getOption('displayFieldsListFormat')) {
                $format = '{tracker_field_' . $this->getFieldReference($fieldArray) . '} (itemId:{object_id})';
            }
        }
        return $format;
    }

    /**
     * DynamicList and ItemLink fields return the permName_text version to render the actual label
     *
     * @param $fieldArray
     * @return string
     */
    private function getFieldReference($fieldArray)
    {
        global $prefs;

        if ($fieldArray['type'] == 'r' || $fieldArray['type'] == 'w' || $fieldArray['type'] == 'l') {
            // TODO categories etc
            return $fieldArray['permName'] . '_text';
        } else {
            return $fieldArray['permName'];
        }
    }

    private function buildFilter()
    {
        return [
            'tracker_id' => $this->trackerField->getOption('trackerId'),
        ];
    }

    public function renderOutput($context = [])
    {
        $smarty = TikiLib::lib('smarty');

        $item = $this->getValue();
        $label = $this->renderInnerOutput($context);

        if ($item && ! is_array($item) && (! isset($context['list_mode']) || $context['list_mode'] !== 'csv') && $this->trackerField->getOption('fieldId')) {
            if ($this->trackerField->getOption('linkPage')) {
                $link = smarty_function_object_link(
                    [
                        'type' => 'wiki page',
                        'id' => $this->trackerField->getOption('linkPage') . '&itemId=' . $item,  // add itemId param TODO properly
                        'title' => $label,
                    ],
                    $smarty->getEmptyInternalTemplate()
                );
                // decode & and = chars
                return str_replace(['%26', '%3D'], ['&', '='], $link);
            } else {
                return parent::renderOutput($context);
            }
        } elseif (isset($context['list_mode']) && $context['list_mode'] == 'csv' && $item) {
            if ($label) {
                return $label;
            } else {
                return $item;
            }
        } elseif ($label) {
            return $label;
        }
    }

    /**
     * Render the content of the item(s) linked to by this ItemLink
     */
    protected function renderInnerOutput($context = []): string
    {

        $item = $this->getValue();

        if (! is_array($item)) {
            // single value item field
            $items = [$item];
        } else {
            // item field has multiple values
            $items = $item;
        }

        $labels = [];
        foreach ($items as $i) {
            $labels[] = $this->getItemLabel($i, $context);
        }

        if ($this->trackerField->getOption('displayFieldsListType') === 'table' && $context['list_mode'] !== 'csv') {
            $headers = [];
            $trackerId = (int) $this->trackerField->getOption('trackerId');
            $definition = Tracker_Definition::get($trackerId);
            if ($fields = $this->getDisplayFieldsListArray()) {
                foreach ($fields as $fieldId) {
                    $field = $definition->getField($fieldId);
                    $headers[] = $field['name'];
                }
            }
            $label = '<table class="table table-condensed" style="background:none;"><thead><tr><td>' . tra($this->getTableDisplayFormat(), '', false, $headers) . '</td></tr></thead><tbody>' . implode('', $labels) . '</tbody></table>';
        } else {
            $label = implode(', ', $labels);
        }

        return $label;
    }

    public function getDocumentPart(Search_Type_Factory_Interface $typeFactory)
    {
        $value = $this->getValue();
        $label = $this->getItemLabel($value, ['list_mode' => 'csv']);
        $baseKey = $this->getBaseKey();

        if ($this->trackerField->getOption('selectMultipleValues')) {
            $baseValue = $typeFactory->multivalue(is_array($value) ? $value : explode(',', $value));
        } else {
            $baseValue = $typeFactory->identifier($value);
        }

        $out = [
            $baseKey => $baseValue,
            "{$baseKey}_text" => $typeFactory->sortable($label),
        ];

        $indexRemote = array_filter($this->trackerField->getOption('indexRemote', []));

        if (count($indexRemote) && is_numeric($value) && $value > 0) {
            $trklib = TikiLib::lib('trk');
            $trackerId = $this->trackerField->getOption('trackerId');
            $item = $trklib->get_tracker_item($value);

            if (! $item) {
                Feedback::error(
                    tr(
                        'Remote item %0 not found in ItemLink fieldId: %1 on itemId: %2',
                        $value,
                        $this->trackerField->getOption('fieldId'),
                        $this->getItemId()
                    )
                );
                return $out;
            }
            $definition = Tracker_Definition::get($trackerId);
            $factory = $definition->getFieldFactory();
            foreach ($indexRemote as $fieldId) {
                $field = $definition->getField($fieldId);
                $handler = $factory->getHandler($field, $item);

                foreach ($handler->getDocumentPart($typeFactory) as $key => $field) {
                    if (str_starts_with($key, 'tracker_field')) {
                        $key = $baseKey . substr($key, strlen('tracker_field'));
                        $out[$key] = $field;
                    }
                }
            }
        }

        return $out;
    }

    public function getProvidedFields(): array
    {
        $baseKey = $this->getBaseKey();
        $fields = [$baseKey, "{$baseKey}_text"];

        $handlers = $this->getRemoteHandlers();
        foreach ($handlers as $handler) {
            foreach ($handler->getProvidedFields() as $key) {
                $fields[] = $baseKey . substr($key, strlen('tracker_field'));
            }
        }

        return $fields;
    }

    public function getProvidedFieldTypes(): array
    {
        $baseKey = $this->getBaseKey();
        $fields = [
            $baseKey => $this->trackerField->getOption('selectMultipleValues') ? 'multivalue' : 'identifier',
            "{$baseKey}_text" => 'sortable'
        ];

        $handlers = $this->getRemoteHandlers();
        foreach ($handlers as $handler) {
            foreach ($handler->getProvidedFieldTypes() as $field => $type) {
                $fields[$baseKey . substr($field, strlen('tracker_field'))] = $type;
            }
        }

        return $fields;
    }

    public function getGlobalFields(): array
    {
        $baseKey = $this->getBaseKey();
        $fields = ["{$baseKey}_text" => true];

        $handlers = $this->getRemoteHandlers();
        foreach ($handlers as $handler) {
            foreach ($handler->getGlobalFields() as $key => $flag) {
                $fields[$baseKey . substr($key, strlen('tracker_field'))] = $flag;
            }
        }

        return $fields;
    }

    protected function getRemoteHandlers()
    {
        $handlers = [];

        $trackerId = $this->trackerField->getOption('trackerId');
        $indexRemote = array_filter($this->trackerField->getOption('indexRemote') ?: []);

        if (count($indexRemote)) {
            if ($definition = Tracker_Definition::get($trackerId)) {
                $factory = $definition->getFieldFactory();

                foreach ($indexRemote as $fieldId) {
                    $field = $definition->getField($fieldId);
                    $handlers[] = $factory->getHandler($field);
                }
            }
        }

        return $handlers;
    }

    public function getItemValue($itemId)
    {
        return $label = TikiLib::lib('object')->get_title('trackeritem', $itemId);
    }

    private function getItemLabel($itemIds, $context = ['list_mode' => ''])
    {
        $items = explode(',', $itemIds);

        $trklib = TikiLib::lib('trk');

        $fulllabel = '';

        foreach ($items as $itemId) {
            if (empty($itemId)) {
                continue;
            }

            $item = $trklib->get_tracker_item($itemId);

            if (! $item) {
                trigger_error(sprintf("Data integrity error: Item %s on tracker %s has an ItemLink field that links to a non-existent item %s", $this->getItemId(), $this->getTrackerDefinition()->getId(), $itemId), E_USER_WARNING);
                $label = tr("Link to deleted itemId %0", $itemId);
            } else {
                $trackerId = (int) $this->trackerField->getOption('trackerId');
                $status = $this->trackerField->getOption('status', 'opc');

                $parts = [];

                if ($fields = $this->getDisplayFieldsListArray()) {
                    foreach ($fields as $fieldId) {
                        if (isset($item[$fieldId])) {
                            $parts[] = $fieldId;
                        }
                    }
                } else {
                    $fieldId = $this->trackerField->getOption('fieldId');

                    if (isset($item[$fieldId])) {
                        $parts[] = $fieldId;
                    }
                }


                if (count($parts)) {
                    if ($this->trackerField->getOption('displayFieldsListType') === 'table' && $context['list_mode'] !== 'csv') {
                        $label = "<tr><td>" . $trklib->concat_item_from_fieldslist(
                            $trackerId,
                            $itemId,
                            $parts,
                            $status,
                            '</td><td>',
                            $context['list_mode'],
                            false,
                            $this->getTableDisplayFormat(),
                            $item
                        ) . "</td></tr>";
                    } else {
                        $label = $trklib->concat_item_from_fieldslist(
                            $trackerId,
                            $itemId,
                            $parts,
                            $status,
                            ' ',
                            $context['list_mode'] ?? '',
                            ! $this->trackerField->getOption('linkToItem'),
                            $this->trackerField->getOption('displayFieldsListFormat'),
                            $item
                        );
                    }
                } else {
                    $label = TikiLib::lib('object')->get_title('trackeritem', $itemId);
                }
            }

            if ($label) {
                if (! empty($fulllabel)) {
                    $fulllabel .= ', ';
                }
                $fulllabel .= $label;
            }
        }

        return $fulllabel;
    }

    public function canHaveMultipleValues()
    {
        return (bool) ($this->trackerField->getOption('displayFieldsListType') === 'transfer' || $this->trackerField->getOption('displayFieldsListType') === 'table' || $this->trackerField->getOption("selectMultipleValues"));
    }

    public function getPossibleItemValues()
    {
        if ($displayFieldsList = $this->getDisplayFieldsListArray()) {
            if ($this->trackerField->getOption('displayFieldsListType') === 'table') {
                $list = TikiLib::lib('trk')->get_fields_from_fieldslist(
                    $this->trackerField->getOption('trackerId'),
                    $displayFieldsList
                );
            } else {
                $list = TikiLib::lib('trk')->concat_all_items_from_fieldslist(
                    $this->trackerField->getOption('trackerId'),
                    $displayFieldsList,
                    $this->trackerField->getOption('status', 'opc'),
                    ' ',
                    'csv',
                    true,
                    $this->trackerField->getOption('displayFieldsListFormat')
                );
                $list = $this->handleDuplicates($list);
                if ($this->getOption('displayFieldsListSort') == 'formatted') {
                    natsort($list);
                }
            }
        } else {
            $list = TikiLib::lib('trk')->get_all_items(
                $this->trackerField->getOption('trackerId'),
                $this->trackerField->getOption('fieldId'),
                $this->trackerField->getOption('status', 'opc'),
                $this->getValue(),
                'csv'
            );
            $list = $this->handleDuplicates($list);
        }

        return $list;
    }

    private function handleDuplicates($list)
    {
        $uniqueList = array_unique($list);
        if ($this->trackerField->getOption('displayOneItem') != 'multi') {
            $value = (int) $this->getValue();
            if ($value && isset($list[$value]) && ! isset($uniqueList[$value])) {
                // if we already have a value set make sure we return the correct itemId one
                $uniqueList = [];
                foreach ($list as $itemId => $label) {
                    if (! in_array($label, $uniqueList)) {
                        if ($label === $list[$value]) {
                            $uniqueList[$value] = $label;
                        } else {
                            $uniqueList[$itemId] = $label;
                        }
                    }
                }
            }
            return $uniqueList;
        } elseif ($uniqueList != $list) {
            $newlist = [];
            foreach ($list as $itemId => $label) {
                if (in_array($label, $newlist)) {
                    $label = $label . " ($itemId)";
                }
                $newlist[$itemId] = $label;
            }

            return $newlist;
        } else {
            return $list;
        }
    }

    private function getTableDisplayFormat()
    {
        $format = $this->trackerField->getOption('displayFieldsListFormat');
        if (! $format) {
            $cnt = count($this->getDisplayFieldsListArray());
            $format = '%' . implode(',%', range(0, $cnt - 1));
        }
        return str_replace(',', '</td><td>', $format);
    }

    public function importRemote($value)
    {
        return $value;
    }

    public function exportRemote($value)
    {
        return $value;
    }

    public function importRemoteField(array $info, array $syncInfo)
    {
        $sourceOptions = Tracker_Options::fromSerialized($info['options'], $info);
        $trackerId = $sourceOptions->getParam('trackerId', 0);
        $fieldId = $sourceOptions->getParam('fieldId', 0);
        $status = $sourceOptions->getParam('status', 'opc');

        $info['type'] = 'd';
        $info['options'] = json_encode(['options' => $this->getRemoteItemLinks($syncInfo, $trackerId, $fieldId, $status)]);

        return $info;
    }

    private function getRemoteItemLinks($syncInfo, $trackerId, $fieldId, $status)
    {
        $client = new Services_ApiClient($syncInfo['provider']);
        $items = $client->getResultLoader($client->route('trackers-view', ['trackerId' => $trackerId]), ['status' => $status]);
        $result = $client->post($client->route('trackerfields-update', ['trackerId' => $trackerId, 'fieldId' => $fieldId]));

        $permName = $result['field']['permName'];
        if (empty($permName)) {
            return '';
        }

        $parts = [];
        foreach ($items as $item) {
            $parts[] = $item['itemId'] . '=' . $item['fields'][$permName];
        }

        return $parts;
    }

    private function getPreselection($linkValue = false)
    {
        $trklib = TikiLib::lib('trk');

        $localField = $this->trackerField->getOption('preSelectFieldHere');
        $remoteField = $this->trackerField->getOption('preSelectFieldThere');
        $method = $this->trackerField->getOption('preSelectFieldMethod');
        $localTrackerId = $this->getConfiguration('trackerId');
        $remoteTrackerId = $this->trackerField->getOption('trackerId');

        $localValue = $trklib->get_item_value($localTrackerId, $this->getItemId(), $localField);

        if ($method == 'domain') {
            if (! preg_match('@^(?:http://)?([^/]+)@i', $localValue, $matches)) {
                return '';
            }
            $host = $matches[1];
            preg_match('/[^.]+\.[^.]+$/', $host, $matches);
            $domain = $matches[0];
            if (strlen($domain) > 6) {
                // avoid com.sg or similar country subdomains
                $localValue = $domain;
            } else {
                $localValue = $host;
            }
        }

        if ($method == 'domain' || $method == 'partial') {
            $partial = true;
        } else {
            $partial = false;
        }

        // If $linkValue is specified, it means get_all_item_id should be called,
        //  which can match a set of linked values. Not just 1
        if (! empty($linkValue)) {
            // get_all_item_id always collects all matching links. $partial is ignored
            //  Use the local value in the search, when it's available
            $value = empty($localValue) ? $linkValue : $localValue;
            $data = $trklib->get_all_item_id($remoteTrackerId, $remoteField, $value);
        } else {
            $data = $trklib->get_item_id($remoteTrackerId, $remoteField, $localValue, $partial);
        }
        return $data;
    }

    public function handleSave($value, $oldValue)
    {
        if ($this->canHaveMultipleValues()) {
            if (is_array($value)) {
                $value = implode(',', $value);
            }
        }

        return [
            'value' => $value,
        ];
    }

    public function itemsRequireRefresh($trackerId, $modifiedFields)
    {
        if ($this->trackerField->getOption('trackerId') != $trackerId) {
            return false;
        }

        $usedFields = array_merge(
            [$this->trackerField->getOption('fieldId')],
            $this->trackerField->getOption('indexRemote', []),
            $this->getDisplayFieldsListArray()
        );

        $intersect = array_intersect($usedFields, $modifiedFields);

        return count($intersect) > 0;
    }

    public function cascadeCategories($trackerId)
    {
        return $this->cascade($trackerId, self::CASCADE_CATEG);
    }

    public function cascadeStatus($trackerId)
    {
        return $this->cascade($trackerId, self::CASCADE_STATUS);
    }

    public function cascadeDelete($trackerId)
    {
        return $this->cascade($trackerId, self::CASCADE_DELETE);
    }

    private function cascade($trackerId, $flag)
    {
        if ($this->trackerField->getOption('trackerId') != $trackerId) {
            return false;
        }

        return ($this->trackerField->getOption('cascade') & $flag) > 0;
    }

    public function watchCompare($old, $new)
    {
        if ($this->canHaveMultipleValues()) {
            if (! is_array($old)) {
                $old = explode(',', $old);
            }
            if (! is_array($new)) {
                $new = explode(',', $new);
            }
            return parent::watchCompareList($old, $new, function ($item) {
                return $this->getItemLabel($item, ['list_mode' => 'csv']);
            });
        } else {
            $o = $this->getItemLabel($old);
            $n = $this->getItemLabel($new);
            return parent::watchCompare($o, $n);    // then compare as text
        }
    }

    /**
     * @return mixed
     */
    public function getDisplayFieldsListArray()
    {
        global $user, $tiki_p_admin_trackers;

        $fields = [];
        $option = $this->trackerField->getOption('displayFieldsList');
        if (! is_array($option)) {
            $option = [$option];
        }
        // filter by user-visible fields
        $remoteTrackerId = (int) $this->trackerField->getOption('trackerId');
        $definition = Tracker_Definition::get($remoteTrackerId);
        if ($definition) {
            foreach (array_filter($option) as $fieldId) {
                if (! $definition->hasFieldId($fieldId)) {
                    Feedback::error(tr('ItemLink field "%0": displayFieldsList field ID #%1 not found', $this->getConfiguration('permName'), $fieldId));
                    $trackerId = $this->getConfiguration('trackerId');
                    $remoteTrackerId = $this->trackerField->getOption('trackerId');
                    $itemId = $this->getItemId();
                    trigger_error("ItemLink data integrity error: tracker item {$itemId} in tracker {$trackerId} has an ItemLink pointing to non-existent fieldId {$fieldId} in remote tracker {$remoteTrackerId}");
                    continue;
                }
                $field = $definition->getFieldInfoFromFieldId($fieldId);
                if (
                    $field['isPublic'] == 'y' && ($field['isHidden'] == 'n' || $field['isHidden'] == 'c' || $field['isHidden'] == 'p' || $field['isHidden'] == 'a' || $tiki_p_admin_trackers == 'y')
                    && $field['type'] != 'x' && $field['type'] != 'h' && ($field['type'] != 'p' || $field['options_array'][0] != 'password')
                    && (empty($field['visibleBy']) or array_intersect(TikiLib::lib('tiki')->get_user_groups($user), $field['visibleBy']) || $tiki_p_admin_trackers == 'y')
                ) {
                    $fields[] = $fieldId;
                }
            }
        } else {
            Feedback::error(tr('ItemLink field "%0": Tracker ID #%1 not found', $this->getConfiguration('permName'), $remoteTrackerId));
        }
        return $fields;
    }

    /***
     * Generate facets for search results
     *
     * @return array
     */
    public function getFacets()
    {
        if ($this->canHaveMultipleValues()) {
            return [];
        }

        $baseKey = $this->getBaseKey();

        static $map = [];
        if (! $map) {
            $list = $this->getPossibleItemValues();
            foreach ($list as $id => $label) {
                $map[crc32($id)] = $id;
            }
        }

        return [
            Search_Query_Facet_Term::fromField($baseKey)
                ->setLabel($this->getConfiguration('name'))
                ->setValueMap($map)
                ->setRenderCallback([$this, 'getItemValue']),
        ];
    }

    public function getTabularSchema()
    {
        $schema = new Tracker\Tabular\Schema($this->getTrackerDefinition());
        $permName = $this->getConfiguration('permName');
        $name = $this->getConfiguration('name');

        if ($this->canHaveMultipleValues()) {
            $itemIdLookup = function ($itemId) {
                return TikiLib::lib('trk')->get_item_value($this->getConfiguration('trackerId'), $itemId, $this->getConfiguration('fieldId'));
            };
            $schema->addNew($permName, 'id')
                ->setLabel($name)
                ->addQuerySource('itemId', 'object_id')
                ->setRenderTransform(function ($value, $extra) use ($itemIdLookup) {
                    return $itemIdLookup($extra['itemId']);
                })
                ->setParseIntoTransform(function (&$info, $value) use ($permName) {
                    $info['fields'][$permName] = $value;
                });

            $fullLookup = new Tracker\Tabular\Schema\CachedLookupHelper();
            $fullLookup->setLookup(function ($value) {
                return $this->getItemLabel($value, ['list_mode' => 'csv']);
            });
            $schema->addNew($permName, 'lookup')
                ->setLabel($name)
                ->setReadOnly(true)
                ->addQuerySource('itemId', 'object_id')
                ->addQuerySource('text', "tracker_field_{$permName}_text")
                ->setRenderTransform(function ($value, $extra) use ($fullLookup, $itemIdLookup) {
                    if (isset($extra['text'])) {
                        return $extra['text'];
                    } else {
                        $values = [];
                        $itemIds = $itemIdLookup($extra['itemId']);
                        foreach (explode(',', $itemIds) as $itemId) {
                            $values[] = $fullLookup->get($itemId);
                        }
                        return implode(', ', $values);
                    }
                });

            if ($fieldId = $this->trackerField->getOption('fieldId')) {
                $simpleField = Tracker\Tabular\Schema\CachedLookupHelper::fieldLookup($fieldId);
                $invertField = Tracker\Tabular\Schema\CachedLookupHelper::fieldInvert($fieldId);

                // if using displayFieldsList then only export the 'value' of the field, i.e. the title of the linked item
                $useTextLabel = empty(array_filter($this->trackerField->getOption('displayFieldsList')));

                $getRenderTransformFunction = function ($separator) use ($simpleField, $useTextLabel, $itemIdLookup) {
                    return function ($value, $extra) use ($separator, $simpleField, $useTextLabel, $itemIdLookup) {
                        if (isset($extra['text']) && $useTextLabel) {
                            return $extra['text'];
                        } else {
                            $values = [];
                            $itemIds = $itemIdLookup($extra['itemId']);
                            foreach (explode(',', $itemIds) as $itemId) {
                                $values[] = $simpleField->get($itemId);
                            }
                            return implode($separator, $values);
                        }
                    };
                };

                $getParseIntoTransformFunction = function ($separator) use ($permName, $invertField) {
                    return function (&$info, $value) use ($separator, $permName, $invertField) {
                        $itemIds = [];
                        foreach (explode($separator, (string) $value) as $val) {
                            if ($id = $invertField->get($val)) {
                                $itemIds[] = $id;
                            }
                        }
                        $info['fields'][$permName] = implode(',', $itemIds);
                    };
                };

                foreach (["\n" => 'new line', ' ' => 'space', ', ' => 'comma', '; ' => 'semicolon'] as $separator => $separator_name) {
                    $column = $schema->addNew($permName, 'lookup-simple ' . $separator_name . ' separated')
                        ->setLabel($name)
                        ->addIncompatibility($permName, 'id')
                        ->addQuerySource('itemId', "object_id")
                        ->setRenderTransform($getRenderTransformFunction($separator))
                        ->setParseIntoTransform($getParseIntoTransformFunction($separator));
                    if ($separator == ', ') {
                        $column->addQuerySource('text', "tracker_field_{$permName}_text");
                    }
                }
            }

            $schema->addNew($permName, 'name')
                ->setLabel($name)
                ->setReadOnly(true)
                ->addQuerySource('itemId', 'object_id')
                ->setRenderTransform(function ($value) use ($fullLookup, $itemIdLookup) {
                    $values = [];
                    $itemIds = $itemIdLookup($extra['itemId']);
                    foreach (explode(',', $itemIds) as $itemId) {
                        $values[] = $fullLookup->get($itemId);
                    }
                    return implode(', ', $values);
                });
        } else {
            $schema->addNew($permName, 'id')
                ->setLabel($name)
                ->setRenderTransform(function ($value) {
                    return $value;
                })
                ->setParseIntoTransform(function (&$info, $value) use ($permName) {
                    $info['fields'][$permName] = $value;
                });

            $fullLookup = new Tracker\Tabular\Schema\CachedLookupHelper();
            $fullLookup->setLookup(function ($value) {
                return $this->getItemLabel($value);
            });
            $schema->addNew($permName, 'lookup')
                ->setLabel($name)
                ->setReadOnly(true)
                ->addQuerySource('text', "tracker_field_{$permName}_text")
                ->setRenderTransform(function ($value, $extra) use ($fullLookup) {
                    if (isset($extra['text'])) {
                        return $extra['text'];
                    } else {
                        return $fullLookup->get($value);
                    }
                });

            if ($fieldId = $this->trackerField->getOption('fieldId')) {
                $simpleField = Tracker\Tabular\Schema\CachedLookupHelper::fieldLookup($fieldId);
                $invertField = Tracker\Tabular\Schema\CachedLookupHelper::fieldInvert($fieldId);

                // if using displayFieldsList then only export the 'value' of the field, i.e. the title of the linked item
                $useTextLabel = empty(array_filter($this->trackerField->getOption('displayFieldsList')));

                $schema->addNew($permName, 'lookup-simple')
                    ->setLabel($name)
                    ->addIncompatibility($permName, 'id')
                    ->addQuerySource('text', "tracker_field_{$permName}_text")
                    ->setRenderTransform(function ($value, $extra) use ($simpleField, $useTextLabel) {
                        if (isset($extra['text']) && $useTextLabel) {
                            return $extra['text'];
                        } else {
                            return $simpleField->get($value);
                        }
                    })
                    ->setParseIntoTransform(function (&$info, $value) use ($permName, $invertField) {
                        if ($id = $invertField->get($value)) {
                            $info['fields'][$permName] = $id;
                        }
                    });
            }

            // linked items have their own tabular sync, so use that to map fields
            $remoteSchema = null;
            $definition = Tracker_Definition::get($this->trackerField->getOption('trackerId'));
            if ($definition) {
                try {
                    $tabulars = $definition->getSynchronizedTabulars();
                    if ($tabulars) {
                        $remoteSchema = TikiLib::lib('tabular')->getSchema($definition, $tabulars[0]);
                    }
                } catch (Exception $e) {
                    Feedback::error($e->getMessage());
                }
            }

            if ($remoteSchema) {
                $schema->addNew($permName, 'lookup-advanced')
                    ->setLabel($name)
                    ->addIncompatibility($permName, 'id')
                    ->addQuerySource('text', "tracker_field_{$permName}_text")
                    ->setRenderTransform(function ($value, $extra) {
                        $useTextLabel = empty(array_filter($this->trackerField->getOption('displayFieldsList')));
                        if (isset($extra['text']) && $useTextLabel) {
                            return $extra['text'];
                        } else {
                            return $this->getItemLabel($value, ['list_mode' => 'csv']);
                        }
                    })
                    ->setParseIntoTransform(function (&$info, $value, $extra = null) use ($permName, $remoteSchema) {
                        if ($extra) {
                            $remoteItem = [];
                            foreach ($remoteSchema->getColumns() as $column) {
                                if (isset($extra[$column->getRemoteField()])) {
                                    $field = $remoteSchema->getDefinition()->getFieldFromPermname($column->getField());
                                    if ($field) {
                                        $remoteItem[$field['fieldId']] = $extra[$column->getRemoteField()];
                                    }
                                }
                            }
                            $items = TikiLib::lib('trk')->list_items($remoteSchema->getDefinition()->getConfiguration('trackerId'), 0, 1, '', '', array_keys($remoteItem), '', '', '', array_values($remoteItem), '', null, true, true);
                            if ($items['count'] > 0) {
                                $info['fields'][$permName] = $items['data'][0]['itemId'];
                            } else {
                                $remoteInfo = [
                                    'itemId' => false,
                                    'status' => $remoteSchema->getDefinition()->getConfiguration('newItemStatus'),
                                    'fields' => $remoteItem,
                                    'skip_sync' => true,
                                ];
                                $utilities = new Services_Tracker_Utilities();
                                $info['fields'][$permName] = $utilities->insertItem($remoteSchema->getDefinition(), $remoteInfo);
                            }
                        }
                    });
            }

            $schema->addNew($permName, 'name')
                ->setLabel($name)
                ->setReadOnly(true)
                ->setRenderTransform(function ($value) {
                    return $this->getItemLabel($value, ['list_mode' => 'csv']);
                });
        }

        return $schema;
    }

    public function getFilterCollection()
    {
        $collection = new Tracker\Filter\Collection($this->getTrackerDefinition());
        $permName = $this->getConfiguration('permName');
        $name = $this->getConfiguration('name');
        $baseKey = $this->getBaseKey();
        $multivalue = $this->canHaveMultipleValues();

        $collection->addNew($permName, 'selector')
            ->setLabel($name)
            ->setControl(new Tracker\Filter\Control\ObjectSelector("tf_{$permName}_os", [
                'type' => 'trackeritem',
                'tracker_status' => implode(' OR ', str_split($this->trackerField->getOption('status', 'opc'), 1)),
                'tracker_id' => $this->trackerField->getOption('trackerId'),
                '_placeholder' => tr(TikiLib::lib('object')->get_title('tracker', $this->trackerField->getOption('trackerId'))),
                '_format' => $this->formatForObjectSelector(),
            ]))
            ->setApplyCondition(function ($control, Search_Query $query) use ($baseKey, $multivalue) {
                $value = $control->getValue();

                if ($value) {
                    $value = str_replace('trackeritem:', '', $value);
                    if ($multivalue) {
                        $query->filterMultivalue((string) $value, $baseKey);
                    } else {
                        $query->filterIdentifier((string) $value, $baseKey);
                    }
                }
            });

        $collection->addNew($permName, 'multiselect')
            ->setLabel($name)
            ->setControl(new Tracker\Filter\Control\ObjectSelector(
                "tf_{$permName}_ms",
                [
                    'type' => 'trackeritem',
                    'tracker_status' => implode(' OR ', str_split($this->trackerField->getOption('status', 'opc'), 1)),
                    'tracker_id' => $this->trackerField->getOption('trackerId'),
                    '_placeholder' => tr(TikiLib::lib('object')->get_title('tracker', $this->trackerField->getOption('trackerId'))),
                    '_format' => $this->formatForObjectSelector(),
                ],
                true
            ))  // for multi
            ->setApplyCondition(function ($control, Search_Query $query) use ($permName, $baseKey, $multivalue) {
                $value = $control->getValue();

                if ($value) {
                    $value = array_map(function ($v) {
                        return str_replace('trackeritem:', '', $v);
                    }, $value);
                    if ($multivalue) {
                        $sub = $query->getSubQuery("ms_$permName");
                        foreach ($value as $v) {
                            if ($v) {
                                $sub->filterMultivalue((string) $v, $baseKey);
                            }
                        }
                    } else {
                        $query->filterMultivalue(implode(' OR ', $value), $baseKey);
                    }
                }
            });

        $indexRemote = array_filter($this->trackerField->getOption('indexRemote') ?: []);
        if (count($indexRemote)) {
            $trklib = TikiLib::lib('trk');
            $trackerId = $this->trackerField->getOption('trackerId');
            $item = $trklib->get_tracker_item($this->getItemId());

            $definition = Tracker_Definition::get($trackerId);
            $factory = $definition->getFieldFactory();
            foreach ($indexRemote as $fieldId) {
                $field = $definition->getField($fieldId);
                $handler = $factory->getHandler($field, $item);

                if ($handler instanceof \Tracker\Field\FilterableInterface) {
                    $handler->setBaseKeyPrefix($permName . '_');
                    $sub = $handler->getFilterCollection();
                    $collection->addCloned($permName, $sub);
                }
            }
        }

        return $collection;
    }

    /**
     * Convert all items data to the only supported field type: Relations
     * This needs to be quick as it can run on potentially huge dataset, thus the raw db layer.
     * Also converts the relevant field options which are:
     *  - relation = trackername.fieldpermname.items
     *  - filter = ItemLink trackerId, trackeritem object type and status filter if different than all
     *  - format = converts multiple fields and format (if any) or uses the selected display field
     *
     * @param string $type - the field type to convert to
     * @return array - converted field options
     * @throws Exception
     */
    public function convertFieldTo($type)
    {
        if ($type !== 'REL') {
            throw new Exception(tr("Unsupported field conversion type: from %0 to %1", $this->getConfiguration('type'), $type));
        }

        $trklib = TikiLib::lib('trk');
        $relationlib = TikiLib::lib('relation');

        $trackerId = $this->getConfiguration('trackerId');
        $fieldId = $this->getConfiguration('fieldId');
        $remoteTrackerId = $this->trackerField->getOption('trackerId');

        $tracker = $trklib->get_tracker($trackerId);

        $relation = preg_replace("/[^a-z0-9]/", "", strtolower($tracker['name']));
        $relation .= '.' . strtolower($this->getConfiguration('permName'));
        $relation .= '.' . 'items';

        $suffix = 1;
        while ($relationlib->relation_exists($relation, 'trackeritem')) {
            $relation = rtrim($relation, '0..9') . ($suffix++);
        }

        $format = $this->formatForObjectSelector();
        $filter = 'tracker_id=' . $remoteTrackerId . '&object_type=trackeritem';
        $status = $this->trackerField->getOption('status');
        if ($status != 'opc') {
            $filter .= '&tracker_status=' . (implode(' OR ', str_split($status)));
        }

        $tx = $trklib->begin();
        $data = $trklib->fetchAll("SELECT tti.`itemId`, ttif.`value`
            FROM `tiki_tracker_items` tti, `tiki_tracker_item_fields` ttif
            WHERE tti.`trackerId` = ?
                AND tti.`itemId` = ttif.`itemId`
                AND ttif.`fieldId` = ?", [$trackerId, $fieldId]);
        foreach ($data as $row) {
            $itemId = $row['itemId'];
            $remoteIds = array_filter(explode(',', trim($row['value'])));
            $value = implode(
                "\n",
                array_map(function ($v) {
                    return "trackeritem:" . trim($v);
                }, $remoteIds)
            );
            $trklib->table('tiki_tracker_item_fields')->update(
                ['value' => $value],
                ['itemId' => $itemId, 'fieldId' => $fieldId]
            );
            foreach ($remoteIds as $id) {
                $relationlib->add_relation($relation, 'trackeritem', $itemId, 'trackeritem', $id);
            }
        }
        $tx->commit();
        return [
            'relation' => $relation,
            'filter' => $filter,
            'format' => $format
        ];
    }

    /**
     * Retrieve remote tracker items that should be available to be cloned instead of starting
     * with an empty item when user wants to add a new remote item.
     * @return array - formatter: itemId => item label
     */
    private function getItemsToClone()
    {
        $trackerId = $this->trackerField->getOption('trackerId');
        $definition = Tracker_Definition::get($trackerId);
        $utilities = new Services_Tracker_Utilities();
        $result = [];

        $predefined = TikiLib::lib('trk')->get_all_item_id($trackerId, $this->trackerField->getOption('preSelectFieldThere'), '*');
        foreach ($predefined as $itemId) {
            $item = $utilities->getItem($trackerId, $itemId);
            $result[$itemId] = $utilities->getTitle($definition, $item);
        }
        $result = $this->handleDuplicates($result);
        asort($result);

        return $result;
    }
}
