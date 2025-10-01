<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

class VueJsLib
{
    /**
     * @param string $str    body of the vue document
     * @param string $name   name of the component
     * @param bool   $app    whether to create the App
     * @param array  $data   values to expose to the Vue App
     * @param bool   $minify or not
     *
     * @return string
     * @throws Exception
     */

    public function processVue($str, $name = '', $app = false, $data = [], $minify = false)
    {
        $headerlib = TikiLib::lib('header');

        if (is_readable($str)) {
            $str = file_get_contents($str);
        }

        // process some shorthand syntax that doesn't work for us
        $str = preg_replace('/\s(@)(?=' . implode('|', $this->jsEvents()) . ')\b/', ' v-on:', $str);

        $dom = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $dom->loadHTML("<html lang=\"en\"><body>$str</body></html>");
        $errors = libxml_get_errors();
        foreach ($errors as $error) {
            // template and ui-predicate tags are expected, so ignore them...
            /* @var $error LibXMLError */
            if (
                ! in_array(
                    $error->message,
                    [
                    "Tag template invalid\n",
                    "Tag ui-predicate invalid\n",
                    "Tag trackerrules invalid\n",
                    "Tag durationpickeramount invalid\n",
                    "Tag durationpickermodal invalid\n",
                    ]
                )
            ) {
                trigger_error($error->message);
            }
        }
        libxml_clear_errors();

        if (! $name && $app) {
            $name = 'App';
        }

        $name = str_replace(' ', '', $name);

        // check if the string contains vuejs script or template tags
        if ($dom->getElementsByTagName('script')->length || $dom->getElementsByTagName('template')->length) {
            global $tikidomainslash;
            $tempDir = './temp/public/' . $tikidomainslash;
            $hash = $name ? $name : md5(serialize($str));

            $file = $tempDir . "vue_" . $hash . ".vue";
            if ($minify) {
                $minifier = new MatthiasMullie\Minify\JS($str);
                $minifier->minify($file);
            } else {
                file_put_contents($file, $str);
            }
            chmod($file, 0644);

            if ($app) {
                $data = json_encode($data);

                /**
                 * TEMPORARY FIX: Currently, `headerlib` native JS modules do not have isolated namespaces.
                 * Creating an import alias for the `mountApp` function to avoid the following error when multiple Vue apps are loaded:
                 * Error: Identifier 'mountApp' has already been declared.
                 */
                $importAlias = 'mountApp' . $name;

                $headerlib->add_js_module(<<<JS
                    import { mountApp as $importAlias } from '@tiki-vue-sfc-loader';
                    $importAlias('$name', '$file', $data);
                JS
                );

                return "<div id=\"$name\"></div>";
            }
        }

        return '';
    }

    /**
     * generates a predicate ui vue.js based rules gui for a tracker field
     *
     * @param array $params
     *
     * @return string
     * @throws Exception
     */
    public function getFieldRules($params)
    {
        if (empty($params['fieldId'])) {
            Feedback::error(tr('No fieldId for Field Rules'));
        }

        // remove empties (?)
        $targetFields = array_values(array_filter($params['targetFields']));

        foreach ($targetFields as & $field) {
            $this->setFieldType($field);
        }

        // remove auto-inc and other non-compatible field types
        $params['targetFields'] = array_values(array_filter($targetFields));

        if (is_string($params['rules'])) {
            $params['rules'] = json_decode(html_entity_decode($params['rules']));
        }

        if (! is_object($params['rules']) || empty($params['rules'])) {
            $params['rules'] = [
                'conditions' => null,
                'actions'    => null,
                'else'       => null,
            ];
        }

        $params['definition'] = \Tracker\Rule\Definition::get();

        $singleSpaAppName = "@vue-mf/tracker-rules-" . \Tiki\Utilities\Identifiers::getHttpRequestId();
        $singleSpaDomId = "single-spa-application:{$singleSpaAppName}";

        TikiLib::lib('header')->add_js_module('
            import "@vue-mf/root-config";
            import "@vue-mf/tracker-rules";
        ');
            // language=JavaScript
            TikiLib::lib('header')->add_jq_onready('
            window.registerApplication({
                name: "' . $singleSpaAppName . '",
                app: () => importShim("@vue-mf/tracker-rules"),
                activeWhen: (location) => {
                    return true;
                },
                customProps: {
                    trackerRulesObject: ' . json_encode($params) . ',
                },
            });
            onDOMElementRemoved("' . $singleSpaDomId . '", function () {
                window.unregisterApplication("' . $singleSpaAppName . '");
            });
        ');

        return  '<div id="' . $singleSpaDomId . '" class="tracker-rules"></div>';
    }

    public function generateTrackerRulesJS($fields, $parentSelector = '.tracker-field-group:first', $isInEditContext = false)
    {

        $js = '';

        TikiLib::lib('header')->add_jsfile('lib/jquery_tiki/tiki-tracker-rules.js');

        foreach (array_filter($fields) as $field) {
            if (! empty($field['rules']) && $field['rules'] !== '{"conditions":null,"actions":null,"else":null}') {
                $rules = Tiki\Lib\core\Tracker\Rule\Rules::fromData($field['fieldId'], $field['rules']);
                $js .= $rules->getJavaScript($parentSelector, $field, $isInEditContext);
            }
        }

        return $js;
    }

    /**
     * @return array
     */
    private function jsEvents(): array
    {
        return [
            'abort',
            'afterprint',
            'animationcancel',
            'animationend',
            'animationiteration',
            'animationstart',
            'audioprocess',
            'auxclick',
            'beforeprint',
            'beforeunload',
            'blur',
            'canplay',
            'canplaythrough',
            'change',
            'click',
            'close',
            'complete',
            'compositionend',
            'compositionstart',
            'compositionupdate',
            'contextmenu',
            'copy',
            'cut',
            'dblclick',
            'drag',
            'dragend',
            'dragenter',
            'dragleave',
            'dragover',
            'dragstart',
            'drop',
            'durationchange',
            'emptied',
            'ended',
            'error',
            'focus',
            'fullscreenchange',
            'fullscreenerror',
            'keydown',
            'keypress',
            'keyup',
            'load',
            'load',
            'loadeddata',
            'loadedmetadata',
            'loadend',
            'loadstart',
            'message',
            'mousedown',
            'mouseenter',
            'mouseleave',
            'mousemove',
            'mouseout',
            'mouseover',
            'mouseup',
            'offline',
            'online',
            'open',
            'pagehide',
            'pageshow',
            'paste',
            'pause',
            'play',
            'playing',
            'pointerlockchange',
            'pointerlockerror',
            'popstate',
            'progress',
            'ratechange',
            'reset',
            'resize',
            'scroll',
            'seeked',
            'seeking',
            'select',
            'stalled',
            'submit',
            'suspend',
            'timeout',
            'timeupdate',
            'transitioncancel',
            'transitionend',
            'transitionrun',
            'transitionstart',
            'unload',
            'volumechange',
            'waiting',
            'wheel',
            // seems to be a custom vue or ui-predicate event?
            'initialize',
        ];
    }

    /**
     * @param array  $field (by reference)
     * @param string $insPrefix
     */
    private function setFieldType(array &$field, string $insPrefix = 'ins_'): void
    {
        global $prefs;

        switch ($field['type']) {
            case 'f':    // datetime
            case 'j':    // datepicker
            case 'CAL':  // calendar item
                $field['argumentType'] = 'DateTime';
                break;
            case 'n':    // number
            case 'b':    // currency
                $field['argumentType'] = 'Number';
                break;
            case 'c':    // checkbox
                $field['argumentType'] = 'Boolean';
                break;
            case 'e':    // Category
            case 'M':    // Multiselect
                $field['argumentType'] = 'Collection';
                break;
            case 'q':    // auto increment (not used client-side)
                $field = [];
                return;
            case 'status':    // item status
                $field['argumentType'] = 'Status';
                break;
            default:
                $field['argumentType'] = 'Text';
                break;
        }
        if ($field['type'] !== 'status') {
            $field['ins_id'] = $insPrefix . $field['fieldId'];
        } else {
            $field['ins_id'] = $field['fieldId'];   // it's just status
        }

        // check for optional "collections" (multiple)
        if (
            $field['type'] === 'r' && $field['options_map']['selectMultipleValues']    // ItemLink
            ||
            $field['type'] === 'w' && $field['options_map']['selectMultipleValues']    // DynamicItemsList
        ) {
            $field['argumentType'] = 'Collection';
        }

        if ($field['type'] === 'u' && $field['options_map']['multiple']) {  // UserSelector
            // check if it will be using an autocomplete input
            $groups = array_filter($field['options_map']['groupIds']);
            $count = TikiLib::lib('user')->count_users_by_groupIds($groups);

            if ($prefs['user_selector_threshold'] > $count) {
                $field['argumentType'] = 'Collection';
            }
        }
        if ($field['argumentType'] === 'Collection') {
            $field['ins_id'] = $field['ins_id'] . '[]';
        }
    }
}
