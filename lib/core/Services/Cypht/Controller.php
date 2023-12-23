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

        /* process the request */
        $dispatcher = new Hm_Dispatch($config);

        if (! empty($_SESSION[$session_prefix]['user_data']['debug_mode_setting'])) {
            $msgs = Hm_Debug::get();
            foreach ($msgs as $msg) {
                $logslib->add_log('cypht', $msg);
            }
        }

        // either html or already json encoded, so skip broker/accesslib output and do it here
        echo $dispatcher->session->dedup_page_links($dispatcher->output);
        exit;
    }
}
