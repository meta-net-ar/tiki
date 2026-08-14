<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\FunctionHandler;

use Smarty\FunctionHandler\Base;
use Smarty\Template;

class TrackerInput extends Base
{
    public function handle($params, Template $template)
    {
        $trklib = \TikiLib::lib('trk');

        if (isset($params['fieldId'])) {
            $field = $trklib->get_tracker_field($params['fieldId']);
            if (empty($field)) {
                return tr('Field %0 not found', $params['fieldId']);
            }
            $field['ins_id'] = "ins_{$field['fieldId']}";
            $handler = $trklib->get_field_handler($field, $item);
            if ($handler) {
                $field = array_merge($field, $handler->getFieldData());
            }
        } else {
            $field = $params['field'];
        }

        if (isset($params['item'])) {
            $item = $params['item'];
        } elseif (! empty($params['itemId'])) {
            $item = $trklib->get_item_info($params['itemId']);
        } else {
            $item = [];
        }

        $handler = $trklib->get_field_handler($field, $item);

        if ($handler) {
            $context = $params;
            unset($context['item']);
            unset($context['field']);

            $info = '';
            $keyNotAccessible = false;
            $encryptionKeyId = (int)($field['encryptionKeyId'] ?? 0);
            if (! empty($field['encryptionKeyId'])) {
                try {
                    $key = new \Tiki\Encryption\Key($field['encryptionKeyId']);
                    if (! $key->isKeyAccessible()) {
                        $field['value'] = '';
                        $keyNotAccessible = true;
                        if (! empty($field['isMandatory']) && $field['isMandatory'] === 'y') {
                            $info = tr('Field "%0" is encrypted. Please enter the key before saving.', $key->get('name'));
                        } else {
                            $info = tr('Field "%0" is encrypted. Leave empty or enter the key first to fill it.', $key->get('name'));
                        }
                        $info .= ' ' . $key->manualEntry();
                        $context['disabled'] = true;
                    } else {
                        $currentValue = $handler->getValue();
                        if (! empty($currentValue)) {
                            $field['value'] = $key->decryptData($currentValue);
                            if ($field['value'] === false) {
                                unset($_SESSION['encryption_shared_keys'][$encryptionKeyId]);
                                $field['value'] = '';
                                $keyNotAccessible = true;
                                $info = tr('Decryption failed for field "%0": the entered key is incorrect.', $key->get('name'))
                                    . ' ' . $key->manualEntry();
                                $context['disabled'] = true;
                            }
                        }
                        if (! $keyNotAccessible) {
                            $info = tr('Field data is encrypted using key "%0".', $key->get('name'));
                        }
                    }
                } catch (\Tiki\Encryption\NotFoundException) {
                    return tr('Field is encrypted with a key that no longer exists!');
                } catch (\Tiki\Encryption\Exception $e) {
                    $field['value'] = '';
                    $keyNotAccessible = true;
                    $info = tr('Field data is encrypted using key "%0" but there was an error: %1', $key->get('name'), $e->getMessage());
                    $info .= ' ' . $key->manualEntry();
                    $context['disabled'] = true;
                }
                $handler = $trklib->get_field_handler($field, $item);
                $field = array_merge($field, $handler->getFieldData());
                $handler = $trklib->get_field_handler($field, $item);
                $infoClass = $keyNotAccessible
                    ? 'encryption-key-required-info description form-text'
                    : 'description form-text';
                $info = '<div class="' . $infoClass . '">' . $info . '</div>';
            }

            $desc = '';
            if (isset($params['showDescription']) && $params['showDescription'] == 'y' && $params['field']['type'] != 'S') {
                $desc = $params['field']['description'];
                if ($params['field']['descriptionIsParsed'] == 'y') {
                    $desc = \TikiLib::lib('parser')->parse_data($desc);
                } else {
                    $desc = htmlspecialchars($desc);
                }
                if (! empty($desc)) {
                    $desc = '<div class="description form-text">' . $desc . '</div>';
                }
            }

            $fieldHtml = $handler->renderInput($context, $params);

            if ($keyNotAccessible) {
                \TikiLib::lib('header')->add_jsfile(JS_ASSETS_PATH . '/jquery-tiki/tracker-field-unlock.js');
                $itemId = (int)($item['itemId'] ?? 0);
                return '<div class="encrypted-field-wrapper"'
                    . ' data-encryption-key-id="' . $encryptionKeyId . '"'
                    . ' data-field-id="' . (int)$field['fieldId'] . '"'
                    . ' data-item-id="' . $itemId . '"'
                    . ' data-network-error="' . htmlspecialchars(tr('A network error occurred. Please try entering the key again.'), ENT_QUOTES) . '">'
                    . $fieldHtml . $info
                    . '</div>' . $desc;
            }

            return $fieldHtml . $info . $desc;
        }
    }
}
