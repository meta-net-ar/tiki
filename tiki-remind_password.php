<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
require_once('tiki-setup.php');
$access->check_feature('forgotPass');
$smarty->assign('showmsg', 'n');
$smarty->assign('showfrm', 'y');
$isvalid = false;
if (isset($_REQUEST["user"])) {
    // this is a 'new password activation':
    if (isset($_REQUEST["actpass"])) {
        $oldPass = $userlib->activate_password($_REQUEST["user"], $_REQUEST["actpass"]);
        if ($oldPass) {
            header("location: tiki-change_password.php?user=" . urlencode($_REQUEST["user"]) . "&oldpass=" . $oldPass);
            die;
        }
        Feedback::errorAndDie(tra("Invalid username or activation code. Maybe this code has already been used."), \Laminas\Http\Response::STATUS_CODE_409);
    }
}
if (isset($_REQUEST["remind"])) {
    $emailResetMessage = tra("An email with a link to reset your password has been sent to the address on record if you have one, if you do not receive one shortly, please contact the administrator.");

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
        } else {
            $info = $userlib->get_user_info($_REQUEST["name"]);
            if (empty($info['email'])) { //only renew if i can mail the pass
                $showmsg = 'e';
                $smarty->assign('msg', $emailResetMessage);
            } elseif (! empty($info['valid']) && ($prefs['validateRegistration'] == 'y' || $prefs['validateUsers'] == 'y')) {
                $showmsg = 'e';
                $userlib->send_validation_email($_REQUEST["name"], $info['valid'], $info['email'], 'y');
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
        }
    } else {
        $showmsg = 'e';
        $smarty->assign('msg', tra('Please provide a username or email address.'));
    }
    if (isset($showmsg) && $showmsg == 'e') {
        $smarty->assign('showmsg', 'e');
    } else {
        include_once('lib/webmail/tikimaillib.php');
        $name = $_REQUEST['name'];

        $pass = $userlib->renew_user_password($name);

        $languageEmail = $tikilib->get_user_preference($name, "language", $prefs['site_language']);
        // Now check if the user should be notified by email
        $smarty->assign('mail_site', $_SERVER["SERVER_NAME"]);
        $smarty->assign('mail_user', $name);
        $smarty->assign('mail_apass', md5($pass));
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
// disallow robots to index page:
$smarty->assign('metatag_robots', 'NOINDEX, NOFOLLOW');
// Display the template
$smarty->assign('mid', 'tiki-remind_password.tpl');
$smarty->display("tiki.tpl");
