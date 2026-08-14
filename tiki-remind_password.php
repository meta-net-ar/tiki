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
$access->check_feature('forgotPass');
$smarty->assign('showmsg', 'n');
$smarty->assign('showfrm', 'y');
$isvalid = false;
$bruteForce = new BruteForce();
$bruteForceProperties = function ($requestedUser = null) use ($tikilib) {
    return ['user' => $requestedUser, 'ip' => $tikilib->get_ip_address()];
};
$bruteForceWaitMessage = function ($operation, array $properties, $minutesMessage, $secondsMessage) use ($bruteForce) {
    $waitTime = $bruteForce->getWaitTime($operation, $properties);
    if ($waitTime > 60) {
        return sprintf($minutesMessage, floor($waitTime / 60), $waitTime % 60);
    }
    return sprintf($secondsMessage, $waitTime);
};

if (isset($_REQUEST["user"])) {
    // this is a 'new password activation':
    if (isset($_REQUEST["actpass"])) {
        $resetAuthProperties = $bruteForceProperties($_REQUEST["user"]);
        if (
            ($prefs['bruteforce_protection'] ?? 'n') === 'y'
            && ! $bruteForce->isOperationAllowed('password_reset_auth', $resetAuthProperties, false)
        ) {
            Feedback::errorAndDie(
                $bruteForceWaitMessage(
                    'password_reset_auth',
                    $resetAuthProperties,
                    tra('Too many password reset authentication attempts. Please try again in %d minutes and %d seconds.'),
                    tra('Too many password reset authentication attempts. Please try again in %d seconds.')
                ),
                429
            );
        }

        $oldPass = $userlib->activate_password($_REQUEST["user"], $_REQUEST["actpass"]);
        if ($oldPass) {
            if (($prefs['bruteforce_protection'] ?? 'n') === 'y') {
                $bruteForce->success('password_reset_auth', $resetAuthProperties);
            }
            header("location: tiki-change_password.php?user=" . urlencode($_REQUEST["user"]) . "&oldpass=" . $oldPass);
            die;
        }
        if (($prefs['bruteforce_protection'] ?? 'n') === 'y') {
            $bruteForce->attempt('password_reset_auth', $resetAuthProperties);
        }
        Feedback::errorAndDie(tra("Invalid username or activation code. Maybe this code has already been used."), \Laminas\Http\Response::STATUS_CODE_409);
    }
}

if (isset($_REQUEST["remind"])) {
    // Duration before the password reset link becomes invalid
    $resetTime = $prefs['resetpasswordlink_expiry'];
    if ($resetTime < 60) {
        $resetTime = $resetTime . ' ' . tr($resetTime == 1 ? 'minute' : 'minutes');
    } else {
        $hours = round($resetTime / 60, 1);
        $resetTime = $hours . ' ' . tr($hours == 1 ? 'hour' : 'hours');
    }

    $emailResetMessage = tr("An email with a link to reset your password has been sent to the address on record if you have one, if you do not receive one shortly, please contact the administrator. Be sure to check your inbox and follow the link within the next %0.", $resetTime);

    $forgotPasswordProperties = $bruteForceProperties($_REQUEST['name'] ?? null);
    if (
        ($prefs['bruteforce_protection'] ?? 'n') === 'y'
        && ! $bruteForce->isOperationAllowed('forgot_password', $forgotPasswordProperties, false)
    ) {
        http_response_code(429);
        $showmsg = 'e';
        $smarty->assign('showmsg', 'y');
        $smarty->assign('showfrm', 'n');
        $smarty->assign(
            'msg',
            $bruteForceWaitMessage(
                'forgot_password',
                $forgotPasswordProperties,
                tra('Too many forgot password attempts. Please try again in %d minutes and %d seconds.'),
                tra('Too many forgot password attempts. Please try again in %d seconds.')
            )
        );
    } else {
        // validate captcha
        $captchalib = TikiLib::lib('captcha');
        if ($prefs['feature_antibot'] == 'y' && (! $captchalib->validate())) {
            $showmsg = 'e';
            $smarty->assign('msg', $captchalib->getErrors());
        } elseif (! empty($_REQUEST['name'])) {
            if (! $userlib->user_exists($_REQUEST['name'])) {
                $showmsg = 'e';


                $smarty->assign('showmsg', 'y');
                $smarty->assign('showfrm', 'n');

                $smarty->assign('msg', $emailResetMessage);
                if (($prefs['bruteforce_protection'] ?? 'n') === 'y') {
                    $bruteForce->attempt('forgot_password', $forgotPasswordProperties);
                }
            } else {
                $info = $userlib->get_user_info($_REQUEST["name"]);
                if (empty($info['email'])) { //only renew if i can mail the pass
                    $showmsg = 'e';
                    $smarty->assign('msg', $emailResetMessage);
                    if (($prefs['bruteforce_protection'] ?? 'n') === 'y') {
                        $bruteForce->attempt('forgot_password', $forgotPasswordProperties);
                    }
                } elseif (! empty($info['valid']) && ($prefs['validateRegistration'] == 'y' || $prefs['validateUsers'] == 'y')) {
                    $showmsg = 'e';
                    $userlib->send_validation_email($_REQUEST["name"], $info['valid'], $info['email'], 'y');
                    if (($prefs['bruteforce_protection'] ?? 'n') === 'y') {
                        $bruteForce->attempt('forgot_password', $forgotPasswordProperties);
                    }
                } else {
                    $_REQUEST['email'] = $info['email'];
                }
            }
        } elseif (! empty($_REQUEST['email'])) {
            if (! ($_REQUEST['name'] = $userlib->get_user_by_email($_REQUEST['email']))) {
                $showmsg = 'e';

                $smarty->assign('showmsg', 'y');
                $smarty->assign('showfrm', 'n');

                $smarty->assign('msg', $emailResetMessage);
                if (($prefs['bruteforce_protection'] ?? 'n') === 'y') {
                    $bruteForce->attempt('forgot_password', $forgotPasswordProperties);
                }
            }
        } else {
            $showmsg = 'e';
            $smarty->assign('msg', tra('Please provide a username or email address.'));
        }
    }
    if (isset($showmsg) && $showmsg == 'e') {
        $smarty->assign('showmsg', 'e');
    } else {
        include_once('lib/webmail/tikimaillib.php');
        $name = $_REQUEST['name'];
        $forgotPasswordProperties = $bruteForceProperties($name);

        if (
            ($prefs['bruteforce_protection'] ?? 'n') === 'y'
            && ! $bruteForce->isOperationAllowed('forgot_password', $forgotPasswordProperties, false)
        ) {
            http_response_code(429);
            $smarty->assign('showmsg', 'e');
            $smarty->assign('showfrm', 'n');
            $smarty->assign(
                'msg',
                $bruteForceWaitMessage(
                    'forgot_password',
                    $forgotPasswordProperties,
                    tra('Too many forgot password attempts. Please try again in %d minutes and %d seconds.'),
                    tra('Too many forgot password attempts. Please try again in %d seconds.')
                )
            );
        } else {
            if (($prefs['bruteforce_protection'] ?? 'n') === 'y') {
                $bruteForce->attempt('forgot_password', $forgotPasswordProperties);
            }

            // Generate a secure password reset token instead of the insecure hash
            $passwordResetLib = new \Tiki\Lib\Auth\PasswordResetLib();
            $token_info = $passwordResetLib->generateSecurePasswordResetToken($name);

            if (! $token_info) {
                Feedback::errorAndDie(tra("Failed to generate password reset token. Please try again."), \Laminas\Http\Response::STATUS_CODE_500);
            }

            // Generate actpass for backward compatibility
            $actpass = md5($userlib->renew_user_password($name));

            // Format expiry time for display
            $expiry_time_formatted = $token_info['expiry_time'];
            // Convert seconds to minutes for display
            $expiry_minutes = round($expiry_time_formatted / 60, 1);
            if ($expiry_minutes < 1) {
                $expiry_time_formatted = $expiry_time_formatted . ' ' . tr($expiry_time_formatted == 1 ? 'second' : 'seconds');
            } else {
                $expiry_time_formatted = $expiry_minutes . ' ' . tr($expiry_minutes == 1 ? 'minute' : 'minutes');
            }

            $languageEmail = $tikilib->get_user_preference($name, "language", $prefs['site_language']);
            // Now check if the user should be notified by email
            $smarty->assign('mail_site', $_SERVER["SERVER_NAME"]);
            $smarty->assign('mail_user', $name);
            $smarty->assign('mail_token', $token_info['token']);
            $smarty->assign('mail_expires', $token_info['expires']);
            $smarty->assign('mail_expiry_time', $token_info['expiry_time']);
            $smarty->assign('mail_expiry_time_formatted', $expiry_time_formatted);
            $smarty->assign('mail_apass', $actpass);
            $smarty->assign('mail_ip', $tikilib->get_ip_address());
            $mail_data = sprintf($smarty->fetchLang($languageEmail, 'mail/password_reminder_subject.tpl'), $_SERVER["SERVER_NAME"]);
            $mail = new TikiMail($name);
            $mail->setSubject($mail_data);
            $mail->setText(stripslashes($smarty->fetchLang($languageEmail, 'mail/password_reminder.tpl')));

            // grab remote IP through forwarded-for header when served by cache
            $mail->setHeader('X-Password-Reset-From', $tikilib->get_ip_address());

            if (! $mail->send([$_REQUEST['email']])) {
                Feedback::errorAndDie(tra("The mail can't be sent. Contact the administrator"), \Laminas\Http\Response::STATUS_CODE_500);
            }
            // Just show "success" message and no form
            $smarty->assign('showmsg', 'y');
            $smarty->assign('showfrm', 'n');

            $smarty->assign('msg', $emailResetMessage);
        }
    }
}
// disallow robots to index page:
$smarty->assign('metatag_robots', 'NOINDEX, NOFOLLOW');
// Display the template
$smarty->assign('mid', 'tiki-remind_password.tpl');
$smarty->display("tiki.tpl");
