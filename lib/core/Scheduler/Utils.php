<?php

class Scheduler_Utils
{
    /**
     * Validate a cron time string
     *
     * @param $cron string A cron time expression (ex.: 0 0 * * *)
     * @return bool true if valid, false otherwise
     */
    public static function validate_cron_time_format($cron)
    {
        return Cron\CronExpression::isValidExpression($cron);
    }

    /**
     * Parse users/emails to send notifications
     *
     * @return array An array with valid users to notify
     * @throws Exception
     */
    public static function getSchedulerNotificationUsers()
    {
        global $tikilib;

        $notificationUsers = $tikilib->get_preference('scheduler_users_to_notify_on_stalled');
        $notifyAdmins = $tikilib->get_preference('scheduler_notify_admins', 'y') === 'y';

        $usersLib = TikiLib::lib('user');
        $logsLib = TikiLib::lib('logs');

        $users = [];

        // Get admin users if needed
        if ($notifyAdmins) {
            $adminUsers = $usersLib->get_group_users('Admins', 0, -1, '*');
            foreach ($adminUsers as $user) {
                if (! empty($user['email']) && ! in_array($user['email'], array_column($users, 'email'))) {
                    $users[] = $user;
                }
            }
        }

        // Add specific notification users/emails
        if (! empty($notificationUsers)) {
            $parts = explode(',', $notificationUsers);
            foreach ($parts as $target) {
                $target = trim($target);

                if (empty($target)) {
                    continue;
                }

                if ($usersLib->user_exists($target)) {
                    $user = $usersLib->get_user_info($target);
                    if (! empty($user['email']) && ! in_array($user['email'], array_column($users, 'email'))) {
                        $users[] = $user;
                    }
                    continue;
                }

                if ($usersLib->user_exists_by_email($target)) {
                    if (! in_array($target, array_column($users, 'email'))) {
                        $userLogin = $usersLib->get_user_by_email($target);
                        $user = $usersLib->get_user_info($userLogin);
                        $users[] = $user;
                    }
                    continue;
                }

                if (filter_var($target, FILTER_VALIDATE_EMAIL)) {
                    if (! in_array($target, array_column($users, 'email'))) {
                        $users[] = [
                            'login' => $target,
                            'email' => $target
                        ];
                    }
                    continue;
                }

                $logsLib->add_log('Scheduler error', tr("Invalid user/email to send notification: %0", $target));
            }
        }

        return $users;
    }

    /**
     * Check if schedulers are configured
     *
     * @return bool
     */
    public function isSchedulerRunConfigured()
    {
        $tikilib = TikiLib::lib('tiki');

        $lastRunWarningMinutes = $tikilib->get_preference('scheduler_last_run_warning_minutes', 60);
        $lastRunTimestamp = $tikilib->get_preference('scheduler_last_run_timestamp');
        $lastRunThreshold = ! empty($lastRunTimestamp) ? strtotime('+ ' . $lastRunWarningMinutes . ' minutes', $lastRunTimestamp) : 0;

        if ($lastRunWarningMinutes > 0 && time() > $lastRunThreshold) {
            return false;
        }

        return true;
    }
}
