<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\FunctionHandler;

use Smarty\FunctionHandler\Base;
use Smarty\Template;
use SmartyTiki\TikiSmartyExtensionInterface;
use SmartyTiki\Traits\FunctionHandlerStaticFacadeTrait;

class TrackerInput extends Base implements TikiSmartyExtensionInterface
{
    use FunctionHandlerStaticFacadeTrait;

    public static function getSmartyName(): string
    {
        return 'trackerinput';
    }

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
            $encryptionState = 'plain';
            $encryptionKeyId = (int)($field['encryptionKeyId'] ?? 0);
            $key = null;
            if (! empty($field['encryptionKeyId'])) {
                try {
                    $key = new \Tiki\Encryption\Key($field['encryptionKeyId']);
                    if (! $key->isKeyAccessible()) {
                        $field['value'] = '';
                        $encryptionState = 'locked';
                        $context['disabled'] = true;
                    } else {
                        $currentValue = $handler->getValue();
                        if (! empty($currentValue)) {
                            $field['value'] = $key->decryptData($currentValue);
                            if ($field['value'] === false) {
                                unset($_SESSION['encryption_shared_keys'][$encryptionKeyId]);
                                $field['value'] = '';
                                $encryptionState = 'locked';
                                $context['disabled'] = true;
                            }
                        }
                        if ($encryptionState === 'plain') {
                            $encryptionState = 'unlocked';
                        }
                    }
                } catch (\Tiki\Encryption\NotFoundException) {
                    $encryptionState = 'forbidden';
                    $field['value'] = '';
                    $context['disabled'] = true;
                } catch (\Tiki\Encryption\Exception $e) {
                    $encryptionState = 'locked';
                    $field['value'] = '';
                    $context['disabled'] = true;
                }
                $handler = $trklib->get_field_handler($field, $item);
                $field = array_merge($field, $handler->getFieldData());
                $handler = $trklib->get_field_handler($field, $item);
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

            if ($encryptionState !== 'plain') {
                $header = \TikiLib::lib('header');
                $header->add_js_module("import '@vue-widgets/encrypted-field'");

                $fieldId = (int)$field['fieldId'];
                $keyName = $key ? htmlspecialchars($key->get('name'), ENT_QUOTES) : '';
                $itemId = (int)($item['itemId'] ?? 0);
                $fieldAttrs = ' data-field-id="' . $fieldId . '" data-item-id="' . $itemId . '"';

                if ($encryptionState === 'unlocked') {
                    return '<tiki-encrypted-field key-name="' . $keyName . '"' . $fieldAttrs . '></tiki-encrypted-field>'
                        . $fieldHtml
                        . $desc;
                }

                $header->add_js_module("import '@vue-widgets/enter-key-modal'");
                $header->add_js_module(
                    "import { handleEncryptedField } from '@tiki/ui-utils'; handleEncryptedField();"
                );

                if ($encryptionState === 'forbidden') {
                    return '<tiki-encrypted-field forbidden key-name="' . $keyName . '"' . $fieldAttrs . '></tiki-encrypted-field>'
                        . $fieldHtml . $desc;
                }

                return '<tiki-encrypted-field locked key-name="' . $keyName . '"' . $fieldAttrs . '></tiki-encrypted-field>'
                    . $fieldHtml
                    . '<tiki-enter-key-modal'
                    . ' data-field-id="' . $fieldId . '"'
                    . ' data-item-id="' . $itemId . '"'
                    . ' field-id="' . $fieldId . '"'
                    . ' key-name="' . $keyName . '"'
                    . ' encryption-key-id="' . $encryptionKeyId . '"'
                    . ' item-id="' . $itemId . '"'
                    . ' hidden></tiki-enter-key-modal>'
                    . $desc;
            }

            return $fieldHtml . $desc;
        }
    }
}
