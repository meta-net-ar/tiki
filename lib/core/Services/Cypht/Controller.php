<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

class Services_Cypht_Controller
{
    public function action_ajax($input)
    {
        global $tikipath, $tikiroot, $logslib;

        $session_prefix = $input->hm_session_prefix->text();
        if (empty($session_prefix)) {
            $session_prefix = 'cypht';
        }

        require_once $tikipath . '/lib/cypht/integration/classes.php';

        // all ajax cypht requests work with closed session, so they can run concurrently
        // handle reopening upon write in the integration class
        session_write_close();

        /* get configuration */
        $config = new Tiki_Hm_Site_Config_File([], $session_prefix, @$_SESSION[$session_prefix]['settings_per_page']);
        if ($input->page->text()) {
            $config->set('append_url_query', 'page=' . urlencode($input->page->text()));
        }
        $environment->define_default_constants($config);

        /* process the request */
        $dispatcher = new Hm_Dispatch($config);

        if (! empty($_SESSION[$session_prefix]['user_data']['debug_mode_setting'])) {
            $msgs = Hm_Debug::get();
            foreach ($msgs as $msg) {
                $logslib->add_log('cypht', $msg);
            }
        }

        Feedback::sendHeaders();

        // either html or already json encoded, so skip broker/accesslib output and do it here
        echo $dispatcher->output;
        exit;
    }

    public function actionGetRequestKey($input)
    {
        global $tikipath;
        require_once $tikipath . '/lib/cypht/integration/classes.php';

        $config = new Tiki_Hm_Site_Config_File([], 'cypht');
        $session = (new Hm_Session_Setup($config))->setup_session();
        $module_exec = new Hm_Module_Exec($config);
        $request = new Hm_Request($module_exec->filters, $config);

        Hm_Request_Key::load($session, $request, false);

        return Hm_Request_Key::generate();
    }
}
