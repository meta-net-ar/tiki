<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\FunctionHandler;

use Smarty\FunctionHandler\Base;
use Smarty\Template;
use Tiki\TaskQueue\QueuedTaskBanner;
use TikiLib;

class QueuedTasksBanner extends Base
{
    public function handle($params, Template $template)
    {
        global $prefs;

        if ($prefs['feature_queued_tasks'] === 'y') {
            $smarty = TikiLib::lib('smarty');
            $result = QueuedTaskBanner::get();
            if (empty($result)) {
                return '';
            }
            $smarty->assign('queuedInfo', $result);
            return $smarty->fetch('queuedtasks/alert.tpl');
        } else {
            return '';
        }
    }
}
