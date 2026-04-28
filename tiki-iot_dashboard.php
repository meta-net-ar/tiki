<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
require_once 'tiki-setup.php';
$access->check_feature(['feature_trackers','feature_internet_of_things','feature_realtime','auth_api_tokens']);
$access->check_permission('tiki_p_manage_iot_apps');

$headerlib = TikiLib::lib('header');
$trackerlib = TikiLib::Lib('trk');
$trackerUtilities = new Services_Tracker_Utilities();
$field_types = $trackerUtilities->getFieldTypes();

use Tiki\Lib\iot\DrawflowEditor;
$iot_apps = [];

global $jitRequest;

$mode = $jitRequest->mode->text();
$app_id = $jitRequest->app_id->text();

$fields = [
    'app_id' => 'id',
    'app_uuid',
    'trackerId',
    'app_name' => 'name',
    'app_icon' => 'icon',
    'app_active' => 'active',
    'scenario_config',
    'dashboard_config',
    'state_object',
    'iot_bridge_access_token',
    'iot_bridge_access_token_expire_at'

];

function addDependencies($mode = 'edit')
{
    global $headerlib;
    // TODO: import css files from js modules and configure vite to process and import them
    // As of now importing from the tiki-iot/* workspace does not work, vite does ignore them
    $headerlib->add_cssfile(NODE_PUBLIC_DIST_PATH . '/gridstack/dist/gridstack.min.css');
    $headerlib->add_cssfile(NODE_PUBLIC_DIST_PATH . '/gridstack/dist/gridstack-extra.min.css');
    $headerlib->add_jsfile(JS_ASSETS_PATH . '/jquery-tiki/bootstrap-toast-utility.js');
    $headerlib->add_cssfile('./lib/iot/theme/initial.css');
    if ($mode == "view") {
        $headerlib->add_js_module('import "@tiki-iot/tiki-iot-dashboard";'); //only has side effects
    } else {
        $headerlib->add_cssfile(NODE_PUBLIC_DIST_PATH . '/drawflow/dist/drawflow.min.css');
        $headerlib->add_cssfile('./lib/iot/theme/drawflow-default.css');
    }
}

if ($mode == "view" && ! empty($app_id)) {
    try {
        $table = new TikiDb_Table(TikiDb::get(), 'tiki_iot_apps');
        $iot_apps = $table->fetchAll($fields, ["app_uuid" => $app_id]);
        if (empty($iot_apps)) {
            $access->display_error(null, tr('Could not access IoT app with ID %0', $app_id), 404);
            die;
        }
    } catch (Exception $e) {
        Feedback::error($e->getMessage());
    }
    addDependencies($mode);
    if ($iot_apps[0]['app_active'] != 'y') {
        Feedback::warning(tr("You can't view the dashboard UI if the app is not activated; please activate it from the main IoT dashboard"));
    }
    $iot_apps[0]['hardware_io'] = json_decode($iot_apps[0]['state_object'], true);
    $iot_apps[0]['session_id'] = session_id();
    $smarty->assign("app", $iot_apps[0]);
    $smarty->assign('mid', 'tiki-iot_ui_dashboard.tpl');
} else {
    try {
        $table = new \TikiDb_Table(\TikiDb::get(), 'tiki_iot_apps');
        $iot_apps = $table->fetchAll($fields);
    } catch (Exception $e) {
        Feedback::error($e->getMessage());
    }
    addDependencies();
    $edit_iot_app_forms = [];
    $onReadyScripts = [];
    $trackerIdsList = $trackerlib->list_trackers();
    foreach ($iot_apps as $key => $iot_app) {
        $app_id = trim(str_replace([' ','-'], '_', $iot_app['app_uuid']));
        $editor = new DrawflowEditor($app_id, $iot_app['app_uuid'], $iot_app['app_name']);
        $editors[] = $editor;
        $trkfields = $trackerlib->list_tracker_fields($iot_app['trackerId']);
        $iot_app_config = ['tracker_fields' => []];
        if ($trkfields && isset($trkfields['data']) && ! empty($trkfields['data'])) {
            foreach ($trkfields['data'] as $fieldinfo) {
                $iot_app_config['tracker_fields'][] = ['label' => $fieldinfo['name'] . ' [' . $field_types[$fieldinfo['type']]['name'] . ']','value' => $fieldinfo['permName']];
            }
        } else {
            Feedback::error(tr("The tracker attached to the app %0 does not have any field! your IoT app might not be functional", $iot_app['app_name']));
        }
        $iot_apps[$key]['app_editor'] = $editor->getHtmlLayout($iot_app_config);
        $iot_apps[$key]['editor_id'] = $editor->editor_id;
        $iot_apps[$key]['iot_bridge_access_token'] = $iot_app['iot_bridge_access_token'];
        $iot_apps[$key]['iot_bridge_access_token_expire_at'] = $iot_app['iot_bridge_access_token_expire_at'];
        $iot_apps[$key]['app_raw_info'] = $iot_app;
        $iot_apps[$key]['hardware_io'] = json_decode($iot_app['state_object'], true);
        $iot_apps[$key]['tracker_fields'] = $trkfields;
        $edit_iot_app_forms[$iot_app['app_uuid']] = $smarty->fetch("iot/edit_iot_app_form.tpl", ['trackerIdsList' => $trackerIdsList['data'], 'app_data' => $iot_app]);
        unset($iot_apps[$key]['app_raw_info']['scenario_config']);
        $onReadyScripts[] = $editor->getEditorScript($iot_app['scenario_config']);
        $smarty->assign("iconset", TikiLib::lib('iconset')->getIconsetForTheme($prefs['theme_iconset'], "")->icons());
    }
    $create_iot_app_form = $smarty->fetch("iot/create_iot_app_form.tpl", ['trackerIdsList' => $trackerIdsList['data']]);
    $sensor_input_widget = $smarty->fetch("iot/dashboard_widgets/sensor-input.tpl");
    $led_widget = $smarty->fetch("iot/dashboard_widgets/led.tpl");
    $gauge_widget = $smarty->fetch("iot/dashboard_widgets/gauge-full.tpl");
    $half_gauge_widget = $smarty->fetch("iot/dashboard_widgets/gauge-full.tpl");
    $generic_widget = $smarty->fetch("iot/dashboard_widgets/generic.tpl");
    $switch_button_widget = $smarty->fetch("iot/dashboard_widgets/switch-button.tpl");
    $widget_function_script = <<<JS
    var createIotAppFormTemplate = `{$create_iot_app_form}`;
    window.getWidgetMarkup = ({ widget, dataSource, widgetIcon, bgColor, textColor, borderColor, widgetLabel, dataUnit })=>{
         switch (widget) {
             case "sensor-input":
                 return `{$sensor_input_widget}`;
             case "led":
                 return `{$led_widget}`;
             case "switch-button":
                 return `{$switch_button_widget}`;
             case "gauge-half":
                 return `{$half_gauge_widget}`;
             case "gauge":
                 return `{$gauge_widget}`;
             default:
                 return `{$generic_widget}`;
         }
    };
    JS;
    $headerlib->add_js($widget_function_script);
    $edit_iot_app_forms_js = json_encode($edit_iot_app_forms);
    $headerlib->add_js("var editIotAppForms = {$edit_iot_app_forms_js};");
    $onReadyScriptsFullCode = implode("\n", $onReadyScripts);
    $jsModule = <<<JS
    import {
        zoomIn,
        zoomOut,
        zoomReset,
        clearEditor,
        drawflowInstances,
        Drawflow,
        saveDrawing,
        drawflowImports,
        DrawflowInteractiveZone,
        initFlowIfFirstTime,
        revoqueAccessToken,
        showAccessTokenForm,
        generateToken,
        saveDashboardUi,
        toggleFloat,
        compact
    } from "@tiki-iot/tiki-iot-dashboard-all";
    $onReadyScriptsFullCode
    $(document).ready(function(){
        $('[drawflow-editor-action]').on('click', function(){
            let el = $(this);
            let action = el.attr('drawflow-editor-action');
            let editorId, appUuid, appName, selector;
            switch(action){
               case 'zoomIn':
                   editorId = el.parent().data('editor-id');
                   zoomIn(editorId);
                   break;
               case 'zoomOut':
                   editorId = el.parent().data('editor-id');
                   zoomOut(editorId);
                   break;
               case 'zoomReset':
                   editorId = el.parent().data('editor-id');
                   zoomReset(editorId);
                   break;
               case 'clearEditor':
                   editorId = el.parent().data('editor-id');
                   clearEditor(editorId);
                   break;
               case 'saveDrawing':
                   editorId = el.data('editor-id');
                   appUuid = el.data('app-uuid');
                   appName = el.data('app-name');
                   saveDrawing(el,editorId, appUuid, appName);
                   break;
               case 'initFlowIfFirstTime':
                   editorId = el.data('editor-id');
                   initFlowIfFirstTime(editorId);
                   break;
               case 'revoqueAccessToken':
                   appUuid = el.data('app-uuid');
                   appName = el.data('app-name');
                   revoqueAccessToken(el, appUuid, appName);
                   break;
               case 'showAccessTokenForm':
                   selector = el.data('selector');
                   showAccessTokenForm(selector);
                   break;
               case 'generateToken':
                   generateToken(el);
                   break;
               case 'saveDashboardUi':
                   appUuid = el.data('app-uuid');
                   appName = el.data('app-name');
                   saveDashboardUi(el, appName, appUuid);
                   break;
               case 'toggleFloat':
                   toggleFloat(el);
                   break;
               case 'compact':
                   compact(el);
                   break;
           }
        });
        $('[drawflow-editor-form-action]').on('change', function(){
            let el = $(this);
            let action = el.attr('drawflow-editor-form-action');
            switch(action){
               case 'toggleFloat':
                   toggleFloat(el);
                   break;
           }
        });
    });
    JS;
    $headerlib->add_js_module($jsModule);
    $smarty->assign("trackerIdsList", $trackerIdsList['data']);
    $smarty->assign("iot_apps", $iot_apps);

    $smarty->assign('mid', 'tiki-iot_dashboard.tpl');
}
$smarty->display("tiki.tpl");
