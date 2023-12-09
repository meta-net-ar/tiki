<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\FunctionHandler;

use Smarty\FunctionHandler\Base;
use Smarty\Template;

class NotificationLink extends Base
{
    public function handle($params, Template $template)
    {
        global $user, $prefs;

        if ($prefs['monitor_enabled'] != 'y') {
            return;
        }

        if (! $user) {
            return '';
        }

        $servicelib = \TikiLib::lib('service');

        $smarty = \TikiLib::lib('smarty');
        $smarty->assign('monitor_link', $params);
        return $smarty->fetch('monitor/notification_link.tpl');
    }
}
