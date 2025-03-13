<?php

namespace Tiki\Lib\iot;

use TikiDb;
use Tiki\Realtime\IotDashboardNotifier;

class EventListener
{
    public function trackerItemCreate($args): void
    {
        $trackerId = $args['trackerId'];
        $values = $args['values_by_permname'];
        $table = new \TikiDb_Table(TikiDb::get(), 'tiki_iot_apps');
        $linked_app = $table->fetchAll(
            [
                'app_id' => 'id',
                'app_uuid',
                'trackerId',
                'app_name' => 'name',
                'app_icon' => 'icon',
                'app_active' => 'active',
                'scenario_config',
                'dashboard_config',
                'state_object'
            ],
            ['trackerId' => $trackerId,'active' => 'y']
        );
        foreach ($linked_app as $app) {
            $scenario_config = $app['scenario_config'];
            $processor = new DrawflowProcessor($scenario_config, $values, $app['app_uuid']);
            $processor->getAdjacentList()->getRootNodes()->checkBadRouting()->buildQueue()->traverseGraph();
            $values['app_flow_logs'] = DrawflowProcessor::$app_flow_logs;
            $hardware_io_state = TikiDb::get()->fetchAll("SELECT state_object FROM tiki_iot_apps WHERE app_uuid=?", [$app['app_uuid']], -1, -1, 'exception');
            if ($hardware_io_state) {
                $values['state_object'] = $hardware_io_state[0]['state_object'];
            }
            IotDashboardNotifier::broadCast($app['app_uuid'], $values);
        }
        return;
    }
}
