<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function validator_uniqueemail($input, $parameter = '', $message = '')
{
    global $prefs;
    $userlib = TikiLib::lib('user');
    include_once __DIR__ . '/../../lib/ban/banlib.php';

    $ip = $_SERVER['REMOTE_ADDR'];

    if (! empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (! empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } elseif ($ip == '::1') {
        $ip = gethostbyname(getHostName());
    }

    $ban_message = tra('You are not allow to do such operation, please contact the administrator.');

    $list = $banlib->getValidateEmailRule($ip);

    $date_parse = date_parse($list['date_to']);
    $expire_date = $banlib->make_time($date_parse['hour'], $date_parse['minute'], $date_parse['second'], $date_parse['month'], $date_parse['day'], $date_parse['year']);
    $attempt = $list['attempts'];
    $banId = $list['banId'];

    if ($prefs['feature_banning'] === 'y') {
        $prefs_attempt = (int)$prefs['feature_banning_attempts'];
        if ($attempt >= $prefs_attempt) {
            if ($banlib->now > $expire_date) {
                $banlib->remove_rule($banId);
            } else {
                return $ban_message;
            }
        } else {
            $attempt = (int)$attempt + 1 ;
            list($ip1, $ip2, $ip3, $ip4) = explode('.', $ip);
            $date_from = $banlib->now;

            $duration = ! empty($prefs['feature_banning_duration']) && ((int)$prefs['feature_banning_duration']) > 0 ? (int)$prefs['feature_banning_duration'] : 1;

            $date_to = ($banlib->now + ($duration * 60));
            $sections = ['validateEmail'];
            $msg = "attempt to validate email more time as unexpected, max number trial is $prefs_attempt times";
            $banlib->replace_rule($banId, 'ip', 'validating unique email', $ip1, $ip2, $ip3, $ip4, 'user', $date_from, $date_to, 'n', $msg, $sections, $attempt);
        }
    }

    $userlib->get_user_by_email($input);
    if ($prefs['user_unique_email_validation'] === 'y' && $userlib->get_user_by_email($input)) {
        return tra("Email already in use");
    }
    return true;
}
