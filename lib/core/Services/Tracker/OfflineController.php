<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

use Tiki\Lib\Theme\ThemeLib;

class Services_Tracker_OfflineController
{
    /**
     * Returns the section for use with certain features like banning
     * @return string
     */
    public function getSection()
    {
        return 'trackers';
    }

    public function action_cache($input)
    {
        global $user;
        $result = ['trackers' => [], 'user_prefs' => []];
        $should_cache = [];
        $trackers = TikiLib::lib('trk')->get_trackers_options(null, 'allowOffline', 'y');
        foreach ($trackers as $row) {
            $itemObject = Tracker_Item::newItem($row['trackerId']);
            if (! $itemObject->canModify()) {
                continue;
            }
            $definition = Tracker_Definition::get($row['trackerId']);
            $info = $definition->getInformation();
            $info['offline'] = true;
            $info['fields'] = $itemObject->prepareInput($input);
            foreach ($info['fields'] as &$field) {
                $handler = $definition->getFieldFactory()->getHandler($field);
                if ($handler instanceof Tracker\Field\EnumerableInterface) {
                    $field['possibilities'] = $handler->getPossibleItemValues();
                    $field['canHaveMultipleValues'] = $handler->canHaveMultipleValues();
                }
                if ($handler instanceof Tracker_Field_ItemLink) {
                    $field['displayFieldsList'] = $handler->getDisplayFieldsListArray();
                }
                if ($handler instanceof Tracker_Field_ItemsList || $handler instanceof Tracker_Field_DynamicList) {
                    $should_cache[] = $handler->getOption('trackerId');
                }
            }
            $info['status_types'] = $definition->getStatusTypes();
            $info['show_status'] = ($definition->getConfiguration('showStatus', 'n') == 'y' && $definition->getConfiguration('showStatusAdminOnly', 'n') == 'n') || ($definition->getConfiguration('showStatusAdminOnly', 'n') == 'y' && Perms::get()->admin_trackers);
            $result['trackers'][] = $info;
        }
        foreach ($should_cache as $trackerId) {
            $definition = Tracker_Definition::get($trackerId);
            $info = $definition->getInformation();
            $info['fields'] = $definition->getFields();
            foreach ($info['fields'] as &$field) {
                $handler = $definition->getFieldFactory()->getHandler($field);
                if ($handler instanceof Tracker\Field\EnumerableInterface) {
                    $field['possibilities'] = $handler->getPossibleItemValues();
                    $field['canHaveMultipleValues'] = $handler->canHaveMultipleValues();
                }
                if ($handler instanceof Tracker_Field_ItemLink) {
                    $field['displayFieldsList'] = $handler->getDisplayFieldsListArray();
                }
            }
            $info['existing_items'] = [];
            foreach (TikiLib::lib('trk')->get_all_tracker_items($trackerId) as $itemId) {
                $itemObject = Tracker_Item::fromId($itemId);
                $item = [];
                foreach ($info['fields'] as $field) {
                    $item[$field['fieldId']] = $itemObject->getFieldOutput($field);
                }
                $info['existing_items'][$itemId] = $item;
            }
            $found = false;
            foreach ($result['trackers'] as $key => $tracker) {
                if ($tracker['trackerId'] == $trackerId) {
                    $result['trackers'][$key]['existing_items'] = $info['existing_items'];
                    $found = true;
                    break;
                }
            }
            if (! $found) {
                $info['offline'] = false;
                $result['trackers'][] = $info;
            }
        }
        $result['user_prefs']['use_24hr_clock'] = TikiLib::lib('userprefs')->get_user_clock_pref($user);
        list($theme_active, $theme_option_active) = ThemeLib::getActiveThemeAndOption();
        $theme_css = ThemeLib::getThemeCssFilePath($theme_active, $theme_option_active);
        if (! $theme_css) {
            $theme_css = ThemeLib::getThemeCssFilePath($theme_active);
        }
        $result['user_prefs']['theme_css'] = $theme_css;
        $result['user_prefs']['language'] = TikiLib::lib('tiki')->get_language();
        $result['user_prefs']['timezone'] = TikiLib::lib('tiki')->get_display_timezone();
        return $result;
    }

    public function action_sync($input)
    {
        $util = new Services_Tracker_Utilities();

        $errors = [];
        $success = [];

        $itemIdMapping = [];

        $data = json_decode($input->data->none(), true);
        foreach ($data as $index => $row) {
            try {
                $trackerId = $row['trackerId'];
                if (! $trackerId) {
                    throw new Exception(tr('Missing trackerId parameter for row %0', $index + 1));
                }

                $definition = Tracker_Definition::get($trackerId);
                if (! $definition) {
                    throw new Exception(tr('Tracker definition not found for tracker %0', $trackerId));
                }

                $itemObject = Tracker_Item::newItem($trackerId);
                if (! $itemObject->canModify()) {
                    throw new Exception(tr('No permissions to insert items in tracker %0', $trackerId));
                }
            } catch (Exception $e) {
                $errors[] = $e->getMessage();
            }
            $success_item = ['trackerId' => $trackerId, 'items' => []];
            foreach ($row['items'] as $item_index => $item) {
                try {
                    $itemObject = Tracker_Item::newItem($trackerId);
                    $fields = $itemObject->getDefinition()->getFields();
                    foreach ($fields as $field) {
                        $handler = $definition->getFieldFactory()->getHandler($field);
                        if ($field['type'] == 'FG' && ! empty($item[$handler->getInsertId()])) {
                            $gal_info = TikiLib::lib('filegal')->get_file_gallery_info($handler->getOption('galleryId'));
                            $files = $item[$handler->getInsertId()];
                            $uploadedIds = [];
                            foreach ($files as $file) {
                                $fileId = TikiLib::lib('filegal')->upload_single_file($gal_info, $file['name'], $file['size'], $file['type'], base64_decode($file['data']));
                                if (! $fileId) {
                                    $errors[] = tr('Tracker %0: failed uploading file %1', $trackerId, $file['name']);
                                } else {
                                    $uploadedIds[] = $fileId;
                                }
                            }
                            $item[$handler->getInsertId()] = implode(',', $uploadedIds);
                        }
                    }
                    $itemInput = new JitFilter($item);
                    $processedFields = $itemObject->prepareInput($itemInput);
                    $fields = [];
                    foreach ($processedFields as $k => $f) {
                        $permName = $f['permName'];
                        $fieldValue = $f['value'] ?? '';
                        if ($f['type'] === 'a') {
                            foreach ((array) $fieldValue as & $value) {
                                $value = TikiLib::lib('tiki')->convertAbsoluteLinksToRelative($value);
                            }
                        }
                        $fields[$permName] = $fieldValue;
                    }
                    $itemId = $util->insertItem(
                        $definition,
                        [
                            'status' => $itemInput->status->word(),
                            'fields' => $fields,
                            'processedFields' => $processedFields
                        ]
                    );
                    if ($itemId) {
                        $itemIdMapping[$itemInput->offlineAutoId->raw()] = $itemId;
                        TikiLib::lib('unifiedsearch')->processUpdateQueue();
                        $success_item['items'][] = $item_index;
                    } else {
                        throw new Exception(tr('Tracker item could not be created.'));
                    }
                } catch (Exception $e) {
                    $errors[] = 'Tracker ' . $trackerId . ': ' . $e->getMessage();
                }
            }
            $success[] = $success_item;
        }

        $escapedItemIds = implode(',', array_map(function ($itemId) {
            return intval($itemId);
        }, $itemIdMapping));
        foreach ($itemIdMapping as $offlineAutoId => $itemId) {
            TikiDb::get()->query("UPDATE tiki_tracker_item_fields SET value = ? WHERE value = ? AND itemId in ($escapedItemIds)", [$itemId, $offlineAutoId]);
        }

        return compact('errors', 'success');
    }
}
