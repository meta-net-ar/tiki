<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\FunctionHandler;

use Smarty\FunctionHandler\Base;
use Smarty\Template;

class Debugger extends Base
{
    public function handle($params, Template $template)
    {
        global $prefs;
        if ($prefs['feature_debug_console'] == 'y') {
            global $debugger;

            require_once 'lib/debug/debugger.php';

            // Get current URL
            $smarty->assign('console_father', $_SERVER['REQUEST_URI']);

            // Set default value
            $smarty->assign('result_type', NO_RESULT);

            // Exec user command in internal debugger
            if (isset($_REQUEST['command'])) {
                // Exec command in debugger
                $command_result = $debugger->execute($_REQUEST['command']);

                $smarty->assign('command', $_REQUEST['command']);
                $smarty->assign('result_type', $debugger->result_type());

                // If result need temlate then we have $command_result array...
                if ($debugger->result_type() == TPL_RESULT) {
                    $smarty->assign('result_tpl', $debugger->result_tpl());

                    $smarty->assign_by_ref('command_result', $command_result);
                } else {
                    $smarty->assign('command_result', $command_result);
                }
            } else {
                $smarty->assign('command', '');
            }

            // Draw tabs to array. Note that it MUST be AFTER exec command.
            // Bcouse 'exec' can change state of smth so tabs content should be changed...
            $tabs_list = $debugger->background_tabs_draw();
            // Add results tab which is always exists...
            $tabs_list['console'] = $smarty->fetch('debug/tiki-debug_console_tab.tpl');
            ksort($tabs_list);
            $tabs = [];

            // TODO: Use stupid dbl loop to generate links code and divs,
            //       but it is quite suitable for
            foreach ($tabs_list as $tname => $tcode) {
                // Generate href code for current button
                $href = '';

                foreach ($tabs_list as $tn => $t) {
                    $href .= (($tn == $tname) ? 'show' : 'hide') . "('" . md5($tn) . "');";
                }

                //
                $tabs[] = [
                    'button_caption' => $tname,
                    'tab_id' => md5($tname),
                    'button_href' => $href . 'return false;',
                    'tab_code' => $tcode
                ];
            }

            // Debug console open/close
            //require_once('lib/setup/cookies.php');
            $c = getCookie('debugconsole', 'menu');
            $smarty->assign('debugconsole_style', $c == 'o' ? 'display:block; opacity:0;' : 'display:none;');

            $smarty->assign_by_ref('tabs', $tabs);

            $js = '';
            $prepend_jq = "";
            $headerlib = \TikiLib::lib('header');
            $headerlib->add_jsfile('./vendor_bundled/vendor/npm-asset/interactjs/dist/interact.js');
            if (isset($_REQUEST['command']) && $_REQUEST['command'] != "help") {
                $prepend_jq .= "$('.selectable').css('cursor','text');";
                $headerlib->add_jq_onready($prepend_jq);
            }
            $headerlib->add_jsfile('./lib/jquery_tiki/function.debugger.js');
            $ret = $smarty->fetch('debug/function.debugger.tpl');
            return $ret;
        }
    }
}
