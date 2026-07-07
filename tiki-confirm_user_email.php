<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
use Tiki\BruteForce\BruteForce;

require_once('tiki-setup.php');

if (getenv('REQUEST_METHOD') == 'HEAD') {
    // It seems Outlook sends a HEAD request before the GET request.
    // This ensures the one-time secret string is not invalidated before users actually access the page
    Feedback::errorPage(tr('Invalid request.'));
    die();
}

global $tiki_p_admin_users;
$bruteForce = new BruteForce();
$bruteForceProperties = function ($requestedUser = null) use ($tikilib) {
    return ['user' => $requestedUser, 'ip' => $tikilib->get_ip_address()];
};
$accountValidationWaitMessage = function (array $properties) use ($bruteForce) {
    $waitTime = $bruteForce->getWaitTime('account_validation', $properties);
    if ($waitTime > 60) {
        return sprintf(tra('Too many account validation attempts. Please try again in %d minutes and %d seconds.'), floor($waitTime / 60), $waitTime % 60);
    }
    return sprintf(tra('Too many account validation attempts. Please try again in %d seconds.'), $waitTime);
};

// Admins can validate users even if preference is not active.
if ($tiki_p_admin_users !== 'y' && (isset($prefs['email_due']) && $prefs['email_due'] < 0 ) && $prefs['validateUsers'] != 'y') {
    Feedback::errorPage(tr('This feature is disabled') . ': validateUsers');
}

if (isset($_REQUEST['user']) && isset($_REQUEST['pass']) && $access->checkCsrf()) {
    $accountValidationProperties = $bruteForceProperties($_REQUEST['user']);
    if (
        ($prefs['bruteforce_protection'] ?? 'n') === 'y'
        && ! $bruteForce->isOperationAllowed('account_validation', $accountValidationProperties, false)
    ) {
        Feedback::errorAndDie($accountValidationWaitMessage($accountValidationProperties), 429);
    }

    if ($userlib->confirm_email($_REQUEST['user'], $_REQUEST['pass'])) {
        if (($prefs['bruteforce_protection'] ?? 'n') === 'y') {
            $bruteForce->success('account_validation', $accountValidationProperties);
        }
        if (empty($user)) {
            $_SESSION["$user_cookie_site"] = $user = $_REQUEST['user'];
        }
        $msg = tr('User %0 validated by the admin', htmlspecialchars($_REQUEST['user']));
        Feedback::success($msg);
        $redirect = '';
        if (! empty($_SERVER['HTTP_REFERER'])) {
            $referer = parse_url($_SERVER['HTTP_REFERER']);
            if (! empty($referer['path'])) {
                $redirect = isset($referer['query']) ? $referer['path'] . '?' . $referer['query'] : $referer['path'];
            }
        }
        if (empty($redirect)) {
            $redirect = 'tiki-information.php?msg=' . urlencode($msg);
        }
        $access->redirect($redirect);
        die;
    }
    if (($prefs['bruteforce_protection'] ?? 'n') === 'y') {
        $bruteForce->attempt('account_validation', $accountValidationProperties);
    }
}

Feedback::errorPage(tr('Problem. Try to log in again to receive new confirmation instructions.'));
