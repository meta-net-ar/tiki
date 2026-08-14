<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

use Ramsey\Uuid\Uuid;

// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace,PSR1.Methods.CamelCapsMethodName.NotCamelCaps,Squiz.Classes.ValidClassName.NotCamelCaps
class Services_IotApps_Controller
{
    public $table;
    private $tracker_utilities;
    public function setUp()
    {
        global $prefs;
        $this->tracker_utilities = new Services_Tracker_Utilities();
        if ($prefs['feature_trackers'] !== 'y' || $prefs['feature_internet_of_things'] !== 'y') {
            Feedback::error(tr("Additional features should be enabled for IoT APIs to work, please check the following:") . "feature_trackers,feature_internet_of_things.");
            exit(1);
        }
        $this->table = new TikiDb_Table(TikiDb::get(), 'tiki_iot_apps');
    }

    public function action_create_app($input)
    {
        global $access;
        $access->check_permission('tiki_p_manage_iot_apps');
        $payload = $input->payload->json();
        $app_uuid  = \Tiki\Utilities\Identifiers::generateUUID();
        $trackerId = intval($payload['tracker-id']);
        $name = $payload['app-name'];
        try {
            $this->table->insert([
                'app_uuid' => $app_uuid,
                'trackerId' => $trackerId,
                'name' => $name,
                'icon' => 'n',
                'dashboard_config' => '',
                'scenario_config' => '{}',
                'state_object' => '{}'
            ]);
            Feedback::success(tr("Successfully created the app: %0", $payload['app-name']));
        } catch (Exception $e) {
            Feedback::error(tr("Couldn't create app: %0", $e->getMessage()));
        }
        return true;
    }

    public function action_edit_app($input)
    {
        global $access;
        $access->check_permission('tiki_p_manage_iot_apps');
        $payload = $input->payload->json();
        $app_uuid  = $payload['app_uuid'];
        $trackerId = intval($payload['tracker-id']);
        $name = $payload['app-name'];
        try {
            $this->table->update(
                [
                'trackerId' => $trackerId,
                'name' => $name,
                'icon' => 'n',
                ],
                [
                "app_uuid" => $app_uuid
                ]
            );
            Feedback::success(tr("Successfully updated the app: %0", $payload['app-name']));
        } catch (Exception $e) {
            Feedback::error(tr("Couldn't update app: %0", $e->getMessage()));
        }
        return true;
    }

    public function action_save_flow_drawing($input)
    {
        global $access;
        $access->check_permission('tiki_p_manage_iot_apps');
        $payload = $input->payload->json();
        $app_uuid  = $payload['app_uuid'];
        $scenario_config = mb_convert_encoding($payload['scenario_config'], 'UTF8');
        try {
            $this->table->update(['scenario_config' => $scenario_config], ["app_uuid" => $app_uuid ]);
            Feedback::success(tr('Successfully saved the IoT app flow for app: %0', $payload['app_name']));
        } catch (Exception $e) {
            Feedback::error(tr("Couldn't update app: %0", $e->getMessage()));
        }
        return true;
    }

    public function action_delete_app($input)
    {
        global $access;
        $access->check_permission('tiki_p_manage_iot_apps');
        $app_uuid = $input->app_uuid->text();
        $app_name = $input->app_name->text();
        try {
            $this->table->delete(["app_uuid" => $app_uuid ]);
            $this->table = new TikiDb_Table(TikiDb::get(), 'tiki_iot_apps_actions_logs');
            $this->table->delete(["app_uuid" => $app_uuid ]);
            Feedback::success(tr('Successfully deleted the app %0 with all associated scenarios and dashboards', $app_name));
        } catch (Exception $e) {
            Feedback::error(tr("Couldn't delete app: %0", $e->getMessage()));
        }
        return true;
    }

    public function action_toggle_app_status($input)
    {
        global $access;
        $access->check_permission('tiki_p_manage_iot_apps');
        $state = $input->state->text();
        $app_uuid = $input->app_uuid->text();
        $app_name = $input->app_name->text();
        try {
            $this->table->update(['active' => $state], ["app_uuid" => $app_uuid]);
            if ($state == "y") {
                Feedback::success(tr("Successfully activated the %0 app", $app_name));
            } else {
                Feedback::success(tr("Successfully deactivated the %0 app, all associated services will not work including the app dashboard", $app_name));
            }
        } catch (Exception $e) {
            Feedback::error(tr("Couldn't toggle app status: %0", $e->getMessage()));
        }
        return true;
    }

    public function action_save_dashboard($input)
    {
        global $access;
        $access->check_permission('tiki_p_manage_iot_apps');
        $payload = $input->payload->json();
        $app_uuid  = $payload['app_uuid'];
        $dashboard_config = mb_convert_encoding($payload['grid_data'], 'UTF8');
        $dashboard_config = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', "", $dashboard_config);
        try {
            $this->table->update(['dashboard_config' => $dashboard_config], ["app_uuid" => $app_uuid]);
            Feedback::success(tr('Successfully saved the IoT app dashboard UI %0', $payload['app_name']));
        } catch (Exception $e) {
            Feedback::error(tr("Couldn't update app: %0", $e->getMessage()));
        }
        return true;
    }

    public function action_change_io_state($input)
    {
        global $access;
        $access->check_permission('tiki_p_manage_iot_apps');
        $payload = $input->payload->json();
        $app_uuid  = $payload['app_uuid'];
        $state = $payload['state'];
        $pin = $payload['pin'];
        $current_state = $this->table->fetchOne("state_object", ["app_uuid" => $app_uuid]);
        try {
            if ($current_state) {
                $current_state_array = json_decode($current_state, true);
                if (empty($current_state_array[$pin])) {
                    $current_state_array[$pin] = [];
                }
                $current_state_array[$pin]['state'] = $state;
                $current_state_array[$pin]['hardware_sync_done'] = false;
                $this->table->update(['state_object' => json_encode($current_state_array)], ["app_uuid" => $app_uuid]);
            }
            return tr('OK');
        } catch (Exception $e) {
            http_response_code(400);
            return(tr("Couldn't update app: %0", $e->getMessage()));
        }
        return true;
    }

    public function action_save_access_token($input)
    {
        global $access;
        $access->check_permission('tiki_p_manage_iot_apps');
        $payload = $input->payload->json();
        $app_uuid  = $payload['app_uuid'];
        //return $payload;
        $iot_bridge_access_token  = $payload['access_token'];
        $iot_bridge_access_token_expire_at  = ! empty($payload['token_expire_at']) ? $payload['token_expire_at'] : null; //null for no expiration
        try {
            $this->table->update(['iot_bridge_access_token' => $iot_bridge_access_token,"iot_bridge_access_token_expire_at" => $iot_bridge_access_token_expire_at], ["app_uuid" => $app_uuid]);
            Feedback::success(tr('Successfully created the Access token for IoT app %0', $payload['app_name']));
        } catch (Exception $e) {
            Feedback::error(tr("Couldn't create Access token for IoT app: %0", $e->getMessage()));
        }
        return true;
    }

    public function action_revoque_access_token($input)
    {
        global $access;
        $access->check_permission('tiki_p_manage_iot_apps');
        $payload = $input->payload->json();
        $app_uuid  = $payload['app_uuid'];
        try {
            $this->table->update(['iot_bridge_access_token' => "","iot_bridge_access_token_expire_at" => null], ["app_uuid" => $app_uuid]);
            Feedback::success(tr('Successfully revoked the Access token for IoT app %0', $payload['app_name']));
        } catch (Exception $e) {
            Feedback::error(tr("Couldn't revoke Access token for IoT app: %0", $e->getMessage()));
        }
        return true;
    }

    /**
     * This route functions as middleware for IoT hardware to access Tiki APIs and IoT features.
     * It simplifies the communication process for microcontrollers like the ESP8266 with Tiki.
     * This route handles authentication for devices with limited resources (e.g., storage constraints, headlessness, low RAM)
     * that struggle to process long strings like JWTs. Device authentication is managed using an access token (specific to Tiki IoT)
     * combined with the app's unique identifier.
    */
    public function action_iot_api_middleware($input)
    {
        global $prefs;
        global $access;

        $raw_post_json = file_get_contents("php://input");
        if (empty($raw_post_json)) {
            $access->display_error(null, tr('Could not access Tiki IoT APIs, missing request body'), 400); // requesting device should consider success only when response code is 200
        }
        try {
            $request_data = json_decode($raw_post_json, true);
            if (empty($request_data)) {
                throw new Error(tr("Invalid request"));
            }
        } catch (Exception $error) {
            $access->display_error(null, tr('Could not access Tiki IoT APIs, invalid request body'), 400); // requesting device should consider success only when response code is 200
        }
        $api_action = $request_data['api_action']; //can be get_io_state or record_data
        $headers = array_change_key_case(getallheaders(), CASE_UPPER);
        $token = $headers["X-AUTH-TOKEN"];
        $app_uuid = $headers["X-APP-UUID"];

        if (! isset($app_uuid)) {
            $access->display_error(null, tr('Could not access Tiki IoT APIs, missing app id'), 400); // requesting device should consider success only when response code is 200
        }

        if (! in_array($api_action, ['get_io_state', 'set_io_state', 'record_data'])) {
            $access->display_error(null, tr('Could not access Tiki IoT APIs, invalid API action, api_action can only be record_data, set_io_state or get_io_state'), 400); // requesting device should consider success only when response code is 200
            return;
        }
        $fields = [
            'app_uuid',
            'trackerId',
            'app_name' => 'name',
            'app_active' => 'active',
            'state_object',
            'iot_bridge_access_token',
            'iot_bridge_access_token_expire_at',
        ];

        $iot_apps = $this->table->fetchAll($fields, ["app_uuid" => $app_uuid]);

        if (empty($iot_apps)) {
            $error = tr('Could not access Tiki IoT APIs, invalid app id');
            $access->display_error(null, $error, 401);
        }
        $iot_apps = $iot_apps[0];

        if ($iot_apps['app_active'] !== 'y') {
            $error = tr('Could not access Tiki IoT APIs, the IoT app is not activated, please activate it first');
            $access->display_error(null, $error, 500);
        }

        if (empty($iot_apps['iot_bridge_access_token'])) {
            $error = tr('Could not access Tiki IoT APIs, no access token currently exist, please contact your admin');
            $access->display_error(null, $error, 500);
        }

        if ($iot_apps['iot_bridge_access_token'] !== $token) {
            $error = tr('Could not access Tiki IoT APIs, invalid access token');
            $access->display_error(null, $error, 401);
        }
            $expirationDate = new DateTime($iot_apps['iot_bridge_access_token_expire_at']);
            $currentDate = new DateTime();

        if ($iot_apps['iot_bridge_access_token_expire_at'] && ($currentDate > $expirationDate)) { //if no expiration date, skip
            $error = tr('Could not access Tiki IoT APIs, token has expired');
            $access->display_error(null, $error, 401);
        }
        if ($api_action == 'set_io_state' || $api_action == 'get_io_state') {
            if (! is_array($request_data['io_ids'])) {
                $error = tr('Could not access Tiki IoT APIs, please specify IO ids to you want to get or set state for');
                $access->display_error(null, $error, 400);
            }
        }
        if ($api_action == "get_io_state") {
            $state_object = json_decode($iot_apps['state_object'], true);
            $states = [];
            foreach ($request_data['io_ids'] as $io_id) {
                if (in_array($io_id, array_keys($state_object))) {
                    $states[$io_id] = $state_object[$io_id]['state'];
                    $state_object[$io_id]['hardware_sync_done'] = true; //notify the backend that this state was sent to hardware
                } else {
                    $states[$io_id] = null;
                }
            }
            $this->table->update(['state_object' => json_encode($state_object)], ["app_uuid" => $app_uuid]);
            // TODO : Notify the real time dashboard, probably doing what is done here lib/iot_tiki/php/EventListener.php without trackers changes
            return $states;
        }
        if ($api_action == 'set_io_state') {
            $state_object = json_decode($iot_apps['state_object'], true);
            foreach ($request_data['io_ids'] as $io_id => $state) {
                if ($state) { //skip if no state
                    $state_object[$io_id] = $state_object[$io_id] ?? [];
                    $state_object[$io_id]['state'] = $state;
                    $state_object[$io_id]['hardware_sync_done'] = true; // we assume this state is sent to the dashboard when it is already set on the hardware
                }
            }
            $this->table->update(['state_object' => json_encode($state_object)], ["app_uuid" => $app_uuid]);
            // TODO : Notify the real time dashboard, probably doing what is done here lib/iot_tiki/php/EventListener.php without trackers changes
            return $state_object;
        }
        if ($api_action == 'record_data') {
            /*
            "payload": {"fieldPermName": value,...}
            */
            $fields = $request_data['payload'];
            $trackerId = $iot_apps['trackerId'];
            $definition = Tracker_Definition::get($trackerId);

            if (! $definition) {
                $error = tr('Could not find the tracker specified for this IoT app');
                $access->display_error(null, $error, 400);
            }
            $itemId = $this->tracker_utilities->insertItem(
                $definition,
                [
                    'status' => 'o',
                    'fields' => $fields,
                ]
            );

            if ($itemId === false) {
                $error = tr('Tracker item could not be created.');
                $access->display_error(null, $error, 400);
            }

            return ['success' => true, 'trackerId' => $trackerId, 'itemId' => $itemId];
        }
        return false;
    }
}
