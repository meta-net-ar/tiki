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

        $field = $params['field'];
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
            if (! empty($field['encryptionKeyId'])) {
                try {
                    $key = new \Tiki\Encryption\Key($field['encryptionKeyId']);
                    $field['value'] = $key->decryptData($handler->getValue());
                    $info = tr('Field data is encrypted using key "%0".', $key->get('name'));
                } catch (\Tiki\Encryption\NotFoundException $e) {
                    return tr('Field is encrypted with a key that no longer exists!');
                } catch (\Tiki\Encryption\Exception $e) {
                    $field['value'] = '';
                    $info = tr('Field data is encrypted using key "%0" but where was an error decrypting the data: %1', $key->get('name'), $e->getMessage());
                    $info .= ' ' . $key->manualEntry();
                }
                $handler = $trklib->get_field_handler($field, $item);
                $field = array_merge($field, $handler->getFieldData());
                $handler = $trklib->get_field_handler($field, $item);
                $info = '<div class="description form-text">' . $info . '</div>';
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

            return $handler->renderInput($context, $params) . $info . $desc;
        }
    }
}
