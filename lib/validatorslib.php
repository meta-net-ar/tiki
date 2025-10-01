<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Validators
{
    public $available;
    private $input;

    public function __construct()
    {
        global $prefs;
        $this->available = $this->get_all_validators();
    }

    public function setInput($input)
    {
        $this->input = $input;
        return true;
    }

    public function getInput()
    {
        if (isset($this->input)) {
            return $this->input;
        } else {
            return false;
        }
    }

    public function validateInput($validator, $parameter = '', $message = '')
    {
        include_once('lib/validators/validator_' . $validator . '.php');
        if (! function_exists("validator_$validator") || ! isset($this->input)) {
            return false;
        }
        $func_name = "validator_$validator";
        $result = $func_name($this->input, $parameter, $message);
        return $result;
    }

    private function get_all_validators()
    {
        $validators = [];
        foreach (glob('lib/validators/validator_*.php') as $file) {
            $base = basename($file);
            $validator = substr($base, 10, -4);
            $validators[] = $validator;
        }
        return $validators;
    }

    public function generateTrackerValidateJS($tracker_definition, $custom_rules = '', $custom_messages = '', $custom_handlers = '', $requiredFields = [])
    {
        global $prefs;
        $fields_data = $tracker_definition->getFields();
        $factory = new Tracker_Field_Factory($tracker_definition);

        $validationjs = 'rules: { ';
        foreach ($fields_data as $field_value) {
            $handler = $factory->getHandler($field_value);
            if ($handler === null) {
                $name = $field_value['name'];
                $permName = $field_value['permName'];
                trigger_error('Handler for field named ' . $name . ' with permName ' . $permName . ' is null. To resolve this problem, please activate the required fields in this tracker: In the concerned tracker, click on the Fields button then Click to Enable', E_USER_WARNING);
                continue;
            }
            $field_name = $handler->getHTMLFieldName();

            if ($field_value['type'] == 'b') {
                $validationjs .= $field_name . '_currency: {required:
                    function(element){
                        return $("#' . $field_name . '").val()!="";
                    },},';
            }

            $isMandatory = self::isFieldMandatory($field_value, $requiredFields);
            $hasValidation = ! empty($field_value['validation']);

            if ($hasValidation ||  $isMandatory) {
                if ($field_value['isMultilingual'] == 'y') {
                    $validationjs .= '"' . $field_name . "[" . end($prefs["available_languages"]) . "]\"" . " : { ";
                } else {
                    $validationjs .= '"' . $field_name . '"' . ': { ';
                }
                if ($isMandatory && $handler->isClientSideMandatory()) {
                    if ($field_value['type'] == 'D') {
                        $validationjs .= 'required_in_group: [1, ".group_' . $field_name . '", "other"], ';
                    } elseif ($field_value['type'] == 'A') {
                        $validationjs .= 'required_tracker_file: [1, ".file_' . $field_name . '"], ';
                    } elseif ($field_value['type'] == 'f') {    // old style date picker - jq validator rules have to apply to an element name or id
                                                                // so we have to add a required_in_group for each of the date selects in turn
                        $validationjs .= 'required: false },';  // dummy for the "group"
                        $date_ins_num = $field_value['options_array'][0] === 'dt' ? 5 : 3;
                        $validationjs .= $field_name . 'Month: {required_in_group: [' . $date_ins_num . ', "select[name^=\'' . $field_name . '\']"]}, ' .
                            $field_name . 'Day: {required_in_group: [' . $date_ins_num . ', "select[name^=\'' . $field_name . '\']"]}, ' .
                            $field_name . 'Year: {required_in_group: [' . $date_ins_num . ', "select[name^=\'' . $field_name . '\']"], ';
                        if ($field_value['options_array'][0] === 'dt') {
                            $validationjs = rtrim($validationjs, ', ');
                            $validationjs .= '},';
                            $validationjs .= $field_name . 'Hour: {required_in_group: [' . $date_ins_num . ', "select[name^=\'' . $field_name . '\']"]}, ' .
                                $field_name . 'Minute: {required_in_group: [' . $date_ins_num . ', "select[name^=\'' . $field_name . '\']"], ';
                        }
                    } else {
                        if ($field_value['isMultilingual'] == 'y') {
                            $required_script = "required: function(e) { ";
                            $condition = "";
                            foreach ($prefs["available_languages"] as $index => $lang) {
                                if ($index == 0) {
                                    $condition .= "\"\" == $(\"[name='" . $field_name . "[" . $lang . "]'" . "]\").val()";
                                } else {
                                    $condition .= " && \"\" == $(\"[name='" . $field_name . "[" . $lang . "]'" . "]\").val()";
                                }
                            }
                            $required_script .= "return " . $condition . " }";
                            $validationjs .= $required_script;
                        } else {
                            $validationjs .= 'required: true, ';
                        }
                    }
                }
                if ($field_value['validation']) {
                    $validationjs .= 'remote: { ';
                    $validationjs .= 'url: "validate-ajax.php", ';
                    $validationjs .= 'type: "post", ';
                    $validationjs .= 'data: { ';
                    $validationjs .= 'validator: "' . $field_value['validation'] . '", ';
                    global $jitRequest;
                    if ($jitRequest->itemId->int()) {
                        $current_id = $jitRequest->itemId->int();
                    } else {
                        $current_id = 0;
                    }
                    if ($field_value['validation'] == 'distinct' && empty($field_value['validationParam'])) {
                        $validationjs .= 'parameter: "trackerId=' . $field_value['trackerId'] . '&fieldId=' . $field_value['fieldId'] . '&itemId=' . $current_id . '", ';
                    } elseif ($field_value['validation'] == 'remotelock') {
                        $validationjs .= 'parameter: "trackerId=' . $field_value['trackerId'] . '&itemId=' . $current_id . '", ';
                    } else {
                        $validationjs .= 'parameter: "' . addslashes($field_value['validationParam']) . '", ';
                    }
                    $validationjs .= 'message: "' . tra($field_value['validationMessage']) . '", ';
                    $validationjs .= 'input: function() {
                        const input = $("[name=\'' . $field_name . '\']");
                        if(input.is(":checkbox")) {
                            return $("[name=\'' . $field_name . '\']:checked").map(function() { return this.value; }).get().join(",");
                        }
                        return $("[name=\'' . $field_name . '\']").val();
                    }';
                    $validationjs .= '';
                    $validationjs .= '} } ';
                } else {
                    // remove last comma (not supported in IE7)
                    $validationjs = rtrim($validationjs, ' ,');
                }
                $validationjs .= '}, ';
            }
        }
        $validationjs .= $custom_rules;
        // remove last comma (not supported in IE7)
        $validationjs = rtrim($validationjs, ' ,');
        $validationjs .= '}, ';
        $validationjs .= 'messages: { ';
        foreach ($fields_data as $field_value) {
            if ($field_value['type'] == 'b') {
                if ($field_value['validationMessage']) {
                    $validationjs .= $field_name . '_currency: "' . tra($field_value['validationMessage']) . '",';
                } else {
                    $validationjs .= $field_name . '_currency: "' . tra('This field is required') . '",';
                }
            }

            $isMandatory = self::isFieldMandatory($field_value, $requiredFields);
            $hasValidationMessage = ! empty($field_value['validationMessage']);

            if ($isMandatory && $hasValidationMessage) {
                $validationjs .= '"' . $field_name . '" : { ';
                $validationjs .= 'required: "' . self::getValidationMessage($field_value) . '" ';
                $validationjs .= '}, ';
            } elseif ($isMandatory) {
                if ($field_value['isMultilingual'] == 'y') {
                    $validationjs .= '"' . $field_name . "[" . end($prefs["available_languages"]) . "]\"" . " : { ";
                    $validationjs .= 'required: "' . tra('The mandatory field ' . $field_value['name'] . ' must contain at least one language value') . '" ';
                } else {
                    $validationjs .= '"' . $field_name . '" : { ';
                    $validationjs .= 'required: "' . tra('This field is required') . '" ';
                }
                $validationjs .= '}, ';
            }
        }
        $validationjs .= $custom_messages;
        // remove last comma (not supported in IE7)
        $validationjs = rtrim($validationjs, ' ,');
        $validationjs .= '}, ';
        // Add an invalidHandler to scroll the first error into view
        // works in both modal and full page modes and leaves the focus on the error input
        $validationjs .= '
focusInvalid: false,
invalidHandler: function(event, validator) {
    var errors = validator.numberOfInvalids();
    if (errors) {
        var $container = $scroller = $(this).parents(".modal"),
            offset = 0;

        if (!$container.length) {
            $container = $("html");
            $scroller = $("body");
            offset = $(".fixed-top").outerHeight() || 0;
        }
        var containerScrollTop = $scroller.scrollTop(),
            $firstError = $(validator.errorList[0].element),
            $scrollElement = $firstError.parents(".tracker-field-group");

        if (! $scrollElement.length) {
            $scrollElement = $firstError;
        }

        if ($firstError.parents(".tab-content").length > 0) {
            $tab = $firstError.parents(".tab-pane");
            $(\'a[href="#\' + $tab.attr("id") + \'"]\').tab("show");
        }

        $container.animate({
            scrollTop: containerScrollTop + $scrollElement.offset().top - offset - ($(window).height() / 2)
        }, 1000, function () {
            $firstError.trigger("focus");
        });
    }
}
';
        if ($custom_handlers) {
            $validationjs .= ",\n$custom_handlers";
        }
        return $validationjs;
    }

    public function isFieldMandatory($field_value, $requiredFields)
    {
        return ($field_value['isMandatory'] == 'y')
            || (isset($requiredFields) && in_array($field_value['fieldId'], $requiredFields));
    }

    public function getValidationMessage($field_value)
    {
        if (! empty($field_value['validationMessage'])) {
            return tra($field_value['validationMessage']);
        }
        return tra('This field is required');
    }
}
