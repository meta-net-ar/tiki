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
     * @param string $prefName The name of the preference that contains the list of users/emails to parse
     *
     * @return array An array with valid users/emails to notify
     * @throws Exception
     */
    public static function getSchedulerNotificationUsers($prefName)
    {

        global $tikilib;

        $notificationUsers = $tikilib->get_preference($prefName);

        $usersLib = TikiLib::lib('user');
        $logsLib = TikiLib::lib('logs');

        $users = [];
        $invalid = [];

        if (empty($notificationUsers)) {
            return $usersLib->get_group_users('Admins', 0, -1, '*');
        }

        $parts = explode(',', $notificationUsers);

        foreach ($parts as $target) {
            $target = trim($target);

            if ($usersLib->user_exists($target)) {
                $user = $usersLib->get_user_info($target);
                $users[] = $user;
                continue;
            }

            if ($usersLib->user_exists_by_email($target)) {
                $userLogin = $usersLib->get_user_by_email($target);
                $user = $usersLib->get_user_info($userLogin);
                $users[] = $user;
                continue;
            }

            if (filter_var($target, FILTER_VALIDATE_EMAIL)) {
                $users[] = [
                    'email' => $target
                ];
                continue;
            };

            $invalid[] = $target;
        }

        if (! empty($invalid)) {
            $error_message = tr("Found invalid user(s)/email(s) to send notification on preference %0. Invalid users/emails: %1", $prefName, implode(', ', $invalid));
            $logsLib->add_log('Scheduler error', $error_message);
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
