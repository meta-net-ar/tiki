<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Scheduler_Task_UserLockMailerCommandTask extends Scheduler_Task_CommandTask
{
    public function execute($params = null)
    {
        if (empty($params['user_login'])) {
            $this->errorMessage = tra('Missing user login to lock or unlock.');
            return false;
        }

        $user_to_lock = $params['user_login'];
        $lock_status = $params['lock_status'];
        // Current tiki site
        $this_site = $_SERVER['HTTP_HOST'];
        $this->logger->debug(sprintf(tra('Updating lock status for %s'), $user_to_lock));

        try {
            $userlib = TikiLib::lib('user');
            $userlib->send_lock_status_email($user_to_lock, $lock_status, $this_site);
        } catch (\Exception $e) {
            $this->errorMessage = $e->getMessage();
            return false;
        }
        return true;
    }

    public function getParams()
    {
        return [
            'user_login' => [
                'name' => tra('User login'),
                'type' => 'text',
                'required' => true,
            ],
            'lock_status' => [
                'name' => tra('Lock status'),
                'type' => 'text',
                'required' => true,
            ]
        ];
    }
}
