<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function wikiplugin_trackeritemcopy_info()
{
    return [
        'name' => tra('Copy Tracker Item'),
        'documentation' => 'PluginTrackerItemCopy',
        'description' => tra('Copy a tracker item'),
        'prefs' => ['wikiplugin_trackeritemcopy', 'feature_trackers'],
        'validate' => 'all',
        'filter' => 'wikicontent',
        'iconname' => 'copy',
        'introduced' => 7,
        'tags' => [ 'experimental' ],
        'params' => [
            'trackerId' => [
                'required' => true,
                'name' => tra('Tracker ID'),
                'description' => tr(
                    'Tracker from which to copy item, joined tracker ids separated by %0:%1',
                    '<code>',
                    '</code>'
                ),
                'since' => '7.0',
                'filter' => 'text',
                'separator' => ':',
                'profile_reference' => 'tracker',
            ],
            'linkFieldIds' => [
                'required' => false,
                'name' => tra('Link Field IDs'),
                'description' => tr('Fields links that are related to this tracker that you would like to join on,
                    separated by %0:%1', '<code>', '</code>'),
                'since' => '8.0',
                'filter' => 'text',
                'separator' => ':',
                'profile_reference' => 'tracker_field',
            ],
            'copyFieldIds' => [
                'required' => true,
                'name' => tra('Copy Field IDs'),
                'description' => tr('Field IDs to copy old value of, separated by %0:%1, joined fields separated by
                    %0|%1', '<code>', '</code>'),
                'since' => '7.0',
                'filter' => 'text',
                'separator' => ['|', ':'],
                'profile_reference' => 'tracker_field',
            ],
            'updateFieldIds' => [
                'required' => false,
                'name' => tra('Update Field IDs'),
                'description' => tr('Field IDs to update with new values specified, separated by %0:%1, joined fields
                    separated by %0|%1', '<code>', '</code>'),
                'since' => '7.0',
                'filter' => 'text',
                'separator' => ['|', ':'],
                'profile_reference' => 'tracker_field',
            ],
            'updateFieldValues' => [
                'required' => false,
                'name' => tra('New Values'),
                'description' => tr('New values to replace for the field IDs specified, separated by %0:%1, joined
                    fields separated by %0|%1. %0randomstring%1 will generate random string; and %0f_xx%1 to use value of
                    field xx of itemId', '<code>', '</code>'),
                'since' => '7.0',
                'filter' => 'text',
                'default' => '',
                'separator' => ['|', ':'],
                'profile_reference' => 'tracker_field',
            ],
            'itemId' => [
                'required' => false,
                'name' => tra('Item ID'),
                'description' => tra('ID of item to make copy of, otherwise input is asked for'),
                'since' => '7.0',
                'filter' => 'text',
                'profile_reference' => 'tracker_item',
            ],
            'copies_on_load' => [
                'required' => false,
                'name' => tra('Make this number of copies on load'),
                'description' => tra('Set the number of copies to make on load of plugin automatically'),
                'since' => '7.0',
                'filter' => 'int',
            ],
            'return_array' => [
                'required' => false,
                'name' => tra('Returns array non-interactively'),
                'advanced' => true,
                'description' => tr('If Yes (%0y%1), returns array of new information instead of displaying results
                    to screen, used in non-interactive mode', '<code>', '</code>'),
                'since' => '7.0',
                'filter' => 'text',
                'default' => '',
            ],
        ],
    ];
}

function wikiplugin_trackeritemcopy($data, $params)
{
    $smarty = TikiLib::lib('smarty');
    $params["linkFieldIds"] = $params["linkFieldIds"] ?? [];

    $trackerId = $params["trackerId"];
    if (! is_array($trackerId)) {
        $trackerId = [$trackerId];
    }
    $copyFieldIds = $params["copyFieldIds"];

    $smarty->assign('itemIdSet', 'n');
    $itemId = 0;

    if (! is_null($params["itemId"])) {
        $itemId = $params["itemId"];
        $smarty->assign('itemIdSet', 'y');
    } elseif (isset($_POST["itemIdToCopy"])) {
        $itemId = $_POST["itemIdToCopy"];
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $items_copy = function ($trackerId, $updateFieldIds, $updateFieldValues, $copyFieldIds, $itemIds, $linkFieldId, $itemLinkId, $copies) {
            $trklib = TikiLib::lib('trk');

            if (is_array($itemIds) == false) {
                $itemIds = [$itemIds];
            }

            foreach ($itemIds as $itemId) {
                $tracker_fields_info = $trklib->list_tracker_fields($trackerId);

                $fieldTypes = [];
                $fieldOptionsArray = [];

                foreach ($tracker_fields_info['data'] as $t) {
                    $fieldTypes[$t['fieldId']] = $t['type'];
                    $fieldOptionsArray[$t['fieldId']] = $t['options_array'];
                }

                $ins_fields["data"] = [];

                if (isset($linkFieldId) && isset($itemLinkId)) {
                    $updateFieldIds[] = $linkFieldId;
                    $updateFieldValues[] = $itemLinkId;
                }

                for ($i = 0, $count_updateFieldIds = count($updateFieldIds); $i < $count_updateFieldIds; $i++) {
                    $ins_fields["data"][] = [
                        'options_array' => $fieldOptionsArray[$updateFieldIds[$i]],
                        'type' => $fieldTypes[$updateFieldIds[$i]],
                        'fieldId' => $updateFieldIds[$i],
                        'value' => $updateFieldValues[$i]
                    ];
                }

                // CUSTOM: this part is totally custom to store admin notes (how to generalize?)
                if (! empty($_POST['admin_notes_for_copy'])) {
                    $ins_fields["data"][] = [
                        'type' => 'a',
                        'fieldId' => 118,
                        'value' => $_POST['admin_notes_for_copy']
                    ];
                }
                // end totally CUSTOM part

                $newitems = [];
                for ($i = 0; $i < $copies; $i++) {
                    // Check for -randomstring- and f_xx
                    $ins_fields_final["data"] = [];
                    foreach ($ins_fields["data"] as $h) {
                        if ($h["value"] == '-randomstring-') {
                            $h["value"] = $trklib->genPass();
                        } elseif (str_starts_with($h["value"], 'f_')) {
                            $sourceFieldId = (int) trim(substr($h["value"], 2));
                            $h["value"] = $trklib->get_item_value($trackerId, $itemId, $sourceFieldId);
                        }
                        $ins_fields_final["data"][] = $h;
                    }
                    $newitemsdata[] = $ins_fields_final["data"];
                    $newitems[] = $trklib->replace_item($trackerId, 0, $ins_fields_final);
                }

                foreach ($newitems as $n) {
                    $trklib->copy_item($itemId, $n, null, $copyFieldIds);
                    $newitemslist .= '  ' . $n;
                }
            }

            return [
                "items" => $newitems,
                "data" => $newitemsdata,
                "list" => $newitemslist
            ];
        };

        $return_array = [];
        $itemIds = [];

        foreach ($trackerId as $key => $trackerIdLeft) {
            //ensure that the fields are set and usable
            if (! is_null($params["updateFieldIds"]) || ! is_null($params["updateFieldValues"])) {
                $updateFieldIds = $params["updateFieldIds"];
                $updateFieldValues = $params["updateFieldValues"];

                foreach ($updateFieldIds as $key => $updateFieldId) {
                    if (count($updateFieldIds[$key]) != count($updateFieldValues[$key])) {
                        return tra('Number of update fields do not match new values');
                    }
                }

                $copyFieldIds[$key] = array_diff($copyFieldIds[$key], $updateFieldIds);
            }

            if ($_SERVER['REQUEST_METHOD'] == 'POST' && $itemId && isset($_POST['copytrackeritem']) && isset($_POST['numberofcopies'])) {
                $copies = (int) $_POST['numberofcopies'];
            } elseif (! is_null($params['copies_on_load'])) {
                $copies = (int) $params['copies_on_load'];
            } else {
                $copies = 0;
            }

            if ($copies > 0) {
                if ($key > 0) {
                    $qry = Tracker_Query::tracker($trackerIdLeft)
                        ->fields($params["linkFieldIds"][0])
                        ->equals([$itemId]);

                    $itemIds = [];
                    foreach ($qry as $linkedItemIds => $item) {
                        $itemIds[] = $linkedItemIds;
                    }
                }

                $return_array[] = $items_copy(
                    $trackerId[$key],
                    $updateFieldIds[$key],
                    $updateFieldValues[$key],
                    $copyFieldIds[$key],
                    (
                        $key == 0 ? $itemId : $itemIds
                    ),
                    (
                        $key == 0 ? null : $params["linkFieldIds"][$key - 1]
                    ),
                    (
                        $key == 0 ? null : $return_array[0]['items'][0]
                    ),
                    $copies
                );
            }
        }

        $smarty->assign('newitemslist', $return_array['list']);

        if ($params['return_array'] == 'y') {
            if (count($return_array) == 1) { //backward compatible
                return $return_array[0];
            } else {
                return $return_array;
            }
        }
    }

    return $smarty->fetch('wiki-plugins/wikiplugin_trackeritemcopy.tpl');
}
