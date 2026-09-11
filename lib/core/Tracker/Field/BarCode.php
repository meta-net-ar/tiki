<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Com\Tecnick\Barcode\Barcode;

// phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps,PSR1.Classes.ClassDeclaration.MissingNamespace
class Tracker_Field_BarCode extends Tracker_Field_Text
{
    public static function getManagedTypesInfo(): array
    {
        return [
            'barcode' => [
                'name' => tr('BarCode'),
                'description' => tr('BarCode field'),
                'help' => 'BarCode-Field',
                'prefs' => ['trackerfield_barcode'],
                'tags' => ['basic'],
                'default' => 'y',
                'params' => [
                    'output_format' => [
                        'name' => tr("Bar code output format"),
                        'description' => tr("Choose in which way the barcode will be displayed"),
                        'filter' => 'text',
                        'default' => 'QR_CODE',
                        'options' => [
                            'QR_CODE' => tr('QR code'),
                            'EAN_13' => tr('EAN-13'),
                            'CODE_128' => tr('Code 128'),
                            'UPC' => tr('UPC'),
                            'Auto' => tr('Auto')
                        ],
                    ]
                ],
            ],
        ];
    }

    public function getFieldData(array $requestData = []): array
    {
        $ins_id = $this->getInsertId();

        return [
            'value' => (isset($requestData[$ins_id]))
                ? $requestData[$ins_id]
                : $this->getValue(),
        ];
    }

    public function renderInput($context = [])
    {
        $rawdata = $this->getValue();
        $data = $this->getValueAndFormat($rawdata);
        return $this->renderTemplate('trackerinput/barcode.tpl', $context, $data);
    }

    public function renderOutput($context = []) // probably similar to renderInnerOutput methode
    {
        $data = $this->getValueAndFormat($this->getValue());
        if ($context['list_mode'] === 'csv') {
            return $data['value'];
        } else {
            $barcode_type = $data['format'];
            $value = $data['value'];
            $data['image_output'] = "";
            $output_bacode_type = array_keys(self::getManagedTypesInfo()['barcode']['params']['output_format']['options']);
            if (in_array($barcode_type, $output_bacode_type)) {
                if ($barcode_type == "QR_CODE") {
                    $data['image_output'] = $this->generateQrCode($value);
                }
                if ($barcode_type == "CODE_128") {
                    $data['image_output'] = $this->generate1DCode("C128", intval($value));
                }
                if ($barcode_type == "EAN_13") {
                    $data['image_output'] = $this->generate1DCode("EAN13", intval($value));
                }
                if ($barcode_type == "UPC") {
                    $data['image_output'] = $this->generate1DCode("UPCA", intval($value));
                }
            }
            return $this->renderTemplate('trackeroutput/barcode.tpl', $context, $data);
        }
    }

    private function generate1DCode(string $type, int $data): string
    {
        $barcode = new Barcode();
        $bobj = $barcode->getBarcodeObj(
            $type . ',H',
            $data,
            -2,
            -50
        )->setBackgroundColor('white');
        return $bobj->getSvgCode();
    }
    private function generateQrCode(string $data): string
    {
        if (! extension_loaded("imagick")) {
            Feedback::error(tra("Missing Imagick extension, it is required to display back Barcodes. Please contact site administrator if you are not one."));
            return "___ERROR___"; // use a string that is less likely to be used in text
        }
        $renderer = new ImageRenderer(
            new RendererStyle(250),
            new SvgImageBackEnd()
        );
        $writer = new Writer($renderer);
        return $writer->writeString($data);
    }

    private function getValueAndFormat(string $compound_value): array
    {
        $compound_value_obj = json_decode($compound_value);
        $value = '-';
        $format = null; // format should come from scan result value not actual field setting because it can change over time

        if (is_object($compound_value_obj) && property_exists($compound_value_obj, 'value')) {
            $value = $compound_value_obj->value;
        }
        if (is_object($compound_value_obj) && property_exists($compound_value_obj, 'format')) {
            $format = $compound_value_obj->format;
        }
        return ['value' => $value, 'format' => $format];
    }

    public function isValid($ins_fields_data)
    {
        $ins_id = $this->getFieldId();
        $isMandatory = $ins_fields_data[$ins_id]['isMandatory'] == 'y';
        $data = $this->getValueAndFormat($ins_fields_data[$ins_id]['value']);
        if (($data['value'] == '-' || $data['format'] == null || $data['value'] == "") && $isMandatory) {
            return tr("request malformed");
        }

        $currentField = array_values(self::getManagedTypesInfo());
        $accepted_barcode_type_options = array_keys($currentField[0]['params']['output_format']['options']);
        unset($accepted_barcode_type_options[count($accepted_barcode_type_options) - 1]); //unset the auto type, it shouldnt be submitted as value, from the frontend auto is replaced by a real accepted type
        if (! in_array($data['format'], $accepted_barcode_type_options)) {
            $accepted_barcode_type_options_slice = array_slice($accepted_barcode_type_options, 0, -1);
            $last_option = array_slice($accepted_barcode_type_options, -1);
            $last_option = $last_option[0];
            return tr("the %0 barcode is not yet supported, please scan a supported BarCode. Supported BarCodes: %1 and %2.", $data['format'], implode(", ", $accepted_barcode_type_options_slice), $last_option);
        }
        $format = $data['format'];
        $value = $data['value'];
        $validatorslib = TikiLib::lib('validators');
        $validatorslib->setInput($value);

        if ($format == "EAN_13") {
            return $validatorslib->validateInput("eanOneThreeCode");
        }
        if ($format == "CODE_128") {
            return $validatorslib->validateInput("barcode128");
        }
        if ($format == "UPC") {
            return $validatorslib->validateInput("upc");
        }
        if ($format == "QR_CODE") {
            return strlen($value) > 1 || tra("QR Code very short"); //at least 2characters
        }
        return true;
    }

    /**
    * Return a plain text representation of the barcode
    *
    * @param array $context
    * @return string
    */
    public function renderText($context = [])
    {
        $data = $this->getValueAndFormat($this->getValue());

        return isset($data['value']) ? (string) $data['value'] : '';
    }
}
