<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

$inputConfiguration = [
    [ 'staticKeyFilters' => [
        'user' => 'text',
        'username' => 'text',
        'pass' => 'none',
        'passAgain' => 'none',
        'oldpass' => 'none',
    ] ]
];
require_once('tiki-setup.php');

if (Language::isRTL()) {
    TikiLib::lib('header')->add_cssfile('vendor_bundled/vendor/hesammousavi/bootstrap-v4-rtl/bootstrap-rtl.min.css', 99); // 99 is high rank order as it should load after all other css files
}

$access->check_feature('change_password');

if (empty($_REQUEST['user']) || ! $userlib->user_exists($_REQUEST['user'])) {
    $smarty->assign('msg', tra('Invalid username'));
    $smarty->assign('errortype', 'login');
    $smarty->display("error.tpl");
    die;
}

if (! isset($_REQUEST["oldpass"])) {
    $_REQUEST["oldpass"] = '';
}

if (isset($_REQUEST["newuser"]) && $_REQUEST["newuser"] == 'y') {
    $smarty->assign('new_user_validation', 'y');
}

$smarty->assign('userlogin', $_REQUEST["user"]);
$smarty->assign('oldpass', $_REQUEST["oldpass"]);

if (isset($_REQUEST["change"])) {
    check_ticket('change-password');
    // Check that pass and passAgain match, otherwise display error and exit
    if ($_REQUEST["pass"] != $_REQUEST["passAgain"]) {
        $smarty->assign('msg', tra("The passwords do not match"));
        $smarty->assign('errortype', 'no_redirect_login');
        $smarty->display("error.tpl");
        die;
    }

    // Check that new password is different from old password, otherwise display error and exit
    if ($_REQUEST["pass"] == $_REQUEST["oldpass"]) {
        $smarty->assign('msg', tra("You can not use the same password again"));
        $smarty->assign('errortype', 'no_redirect_login');
        $smarty->display("error.tpl");
        die;
    }

    $polerr = $userlib->check_password_policy($_REQUEST["pass"]);
    if (strlen($polerr) > 0) {
        $smarty->assign('msg', $polerr);
        $smarty->assign('errortype', 'no_redirect_login');
        $smarty->display("error.tpl");
        die;
    }

    if (empty($_REQUEST['oldpass']) && ! empty($_REQUEST['actpass'])) {
        $_REQUEST['oldpass'] = $userlib->activate_password($_REQUEST['user'], $_REQUEST['actpass']);
        if (empty($_REQUEST['oldpass'])) {
            $smarty->assign('msg', tra('Invalid username or activation code. Maybe this code has already been used.'));
            $smarty->assign('errortype', 'no_redirect_login');
            $smarty->display('error.tpl');
            die;
        }
    }
    // Check that provided user name could log in with old password, otherwise display error and exit
    list($isvalid, $_REQUEST["user"], $error) = $userlib->validate_user($_REQUEST["user"], $_REQUEST["oldpass"]);
    if (! $isvalid) {
        $smarty->assign('msg', tra("Invalid old password"));
        $smarty->assign('errortype', 'no_redirect_login');
        $smarty->display("error.tpl");
        die;
    }
    if (isset($_REQUEST['email'])) {
        if (empty($_REQUEST['email']) || ! validate_email($_REQUEST['email'], $prefs['validateEmail'])) {
            $smarty->assign('msg', tra('Your email could not be validated; make sure you email is correct'));
            $smarty->assign('errortype', 'no_redirect_login');
            $smarty->display("error.tpl");
            die;
        }
        $userlib->change_user_email_only($_REQUEST['user'], $_REQUEST['email']);
    }

    $userlib->change_user_password($_REQUEST["user"], $_REQUEST["pass"]);
    // Login the user and display Home page
    $_SESSION["$user_cookie_site"] = $_REQUEST["user"];
    $user = $_REQUEST["user"];
    $logslib->add_log('login', 'logged from change_password', $_REQUEST['user'], '', '', $tikilib->now);

    if ($prefs['feature_user_encryption'] === 'y') {
        // Notify CryptLib about the password change
        $cryptlib = TikiLib::lib('crypt');
        $cryptlib->onChangeUserPassword($_REQUEST["oldpass"], $_REQUEST["pass"]);
    }

    // re-evaluate homepage since we just login the user
    include TIKI_PATH . '/lib/setup/default_homepage.php';
    $homePageUrl = $prefs['tikiIndex']; // set up in lib/setup/default_homepage.php

    // Check if a wizard should be run.
    // If a wizard is run, it will return to the $url location when it has completed. Thus no code after $wizardlib->onLogin will be executed
    $wizardlib = TikiLib::lib('wizard');
    $force = $_REQUEST["user"] == 'admin';
    $wizardlib->onLogin($user, $homePageUrl, $force);

    // Go to homepage or url_after_validation
    $accesslib = TikiLib::lib('access');
    if (! empty($prefs['url_after_validation']) && ! empty($_REQUEST['new_user_validation'])) {
        $access->redirect($prefs['url_after_validation']);
    } else {
        $accesslib->redirect($homePageUrl);
    }

    // Only proceed if password change is allowed
    if ($can_change_password) {
        // Validate password change operation
        $validation_errors = false;
        if ($_REQUEST["pass"] != $_REQUEST["passAgain"]) {
            Feedback::error(tra("The passwords do not match"));
            $validation_errors = true;
        }
        // Check password policy
        $polerr = $userlib->check_password_policy($_REQUEST["pass"]);
        if (strlen($polerr) > 0) {
            Feedback::error($polerr);
            $validation_errors = true;
        }
        // Also check if new password matches current password hash
        $current_hash = $userlib->getOne('select `hash` from `users_users` where binary `login`=?', [$user]);
        if (! empty($current_hash) && password_verify($_REQUEST["pass"], $current_hash)) {
            Feedback::error(tra("You can not use the same password again"));
            $validation_errors = true;
        }
        // Validate email if provided
        if (isset($_REQUEST['email'])) {
            if (empty($_REQUEST['email']) || ! validate_email($_REQUEST['email'], $prefs['validateEmail'])) {
                Feedback::error(tra('Your email could not be validated; make sure your email is correct'));
                $validation_errors = true;
            }
        }

        // Only proceed with password change if validation passed
        if (! $validation_errors) {
            // Perform password change operation
            if (isset($_REQUEST['email']) && ! empty($_REQUEST['email'])) {
                $userlib->change_user_email_only($user, $_REQUEST['email']);
            }
            $res = $userlib->change_user_password($user, $_REQUEST["pass"]);
            if ($res && $prefs['pass_history_management'] === 'y') {
                $userlib->addPasswordHistory($user, $_REQUEST["pass"]);
            }

            // Mark reset token as used only after successful password change
            if (! empty($secure_token) && ! $is_new_user_validation && ! $must_change_password) {
                $passwordResetLib = new \Tiki\Lib\Auth\PasswordResetLib();
                $passwordResetLib->markPasswordResetTokenUsed($user, $secure_token);
            }

            // Handle encryption if enabled
            if ($prefs['feature_user_encryption'] === 'y' && ! empty($authenticated_oldpass)) {
                $cryptlib = TikiLib::lib('crypt');
                $cryptlib->onChangeUserPassword($authenticated_oldpass, $_REQUEST["pass"]);
            }

            // Login user as part of the change operation
            $userlib->update_expired_groups();
            $loginlib = TikiLib::lib('login');
            $loginlib->activateSession($user);
            $logslib->add_log('login', 'logged from change_password', $user, '', '', $tikilib->now);
            if ($jitRequest->oldpass->text() !== 'admin') {
                include TIKI_PATH . '/lib/setup/default_homepage.php';
            }
            $homePageUrl = $prefs['tikiIndex'];
            $wizardlib = TikiLib::lib('wizard');
            $force = $user == 'admin';
            $wizardlib->onLogin($user, $homePageUrl, $force);
            $accesslib = TikiLib::lib('access');
            if (! empty($prefs['url_after_validation']) && ! empty($_REQUEST['new_user_validation'])) {
                $access->redirect($prefs['url_after_validation']);
            } else {
                $accesslib->redirect($homePageUrl);
            }
        }
    }
    // If authentication failed or validation failed, fall through to display the form
}

// Display the template
global $prefs;
$prefs['language'] = $tikilib->get_user_preference($_REQUEST['user'], 'language', $prefs['site_language']);
$smarty->assign('email', $userlib->get_user_email($_REQUEST['user']));

// disallow robots to index page:
$smarty->assign('metatag_robots', 'NOINDEX, NOFOLLOW');

$smarty->assign('mid', 'tiki-change_password.tpl');
$smarty->display("tiki.tpl");
