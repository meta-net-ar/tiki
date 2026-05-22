<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
use Tiki\BruteForce\BruteForce;

$inputConfiguration = [
    [
        'staticKeyFilters' => [
            'user'          => 'striptags',
            'pass'          => 'raw',
            'passAgain'     => 'raw',
            'oldpass'       => 'raw',
            'actpass'       => 'raw',
            'change'        => 'word',
            'token'         => 'raw',
            'email'         => 'email',
            'newuser'       => 'alpha',
            'new_user_validation'     => 'alpha'
        ],
    ],
];
require_once('tiki-setup.php');

$access->check_feature('change_password');
$bruteForce = new BruteForce();

if (empty($_REQUEST['user']) || ! $userlib->user_exists($_REQUEST['user'])) {
    Feedback::errorAndDie(tra('Invalid username'), \Laminas\Http\Response::STATUS_CODE_400);
}

if (! isset($_REQUEST["oldpass"])) {
    $_REQUEST["oldpass"] = '';
}

$user = $_REQUEST["user"];
$secure_token = $_REQUEST["token"] ?? '';

if (isset($_REQUEST["newuser"]) && $_REQUEST["newuser"] == 'y') {
    $smarty->assign('new_user_validation', 'y');
}

$smarty->assign('userlogin', $_REQUEST["user"]);
$smarty->assign('oldpass', $_REQUEST["oldpass"]);
$smarty->assign('secure_token', $secure_token);

if (isset($_REQUEST["change"])) {
    $access->checkCsrf();
    if (($prefs['bruteforce_protection'] ?? 'n') === 'y') {
        if (! $bruteForce->isOperationAllowed('change_password', ['ip' => $tikilib->get_ip_address()])) {
            $nextAllowedTime = $bruteForce->getNextAllowedTime('change_password', ['ip' => $tikilib->get_ip_address()]);
            $waitTime = $nextAllowedTime - time();
            if ($waitTime > 60) {
                $waitMessage = sprintf(tra('Too many password change attempts. Please try again in %d minutes and %d seconds.'), floor($waitTime / 60), $waitTime % 60);
            } else {
                $waitMessage = sprintf(tra('Too many password change attempts. Please try again in %d seconds.'), $waitTime);
            }
            $smarty->assign('msg', $waitMessage);
            $smarty->display('error.tpl');
            die;
        }
    }

    // Verify authentication (oldpass, actpass, or token)
    $is_authenticated = false;
    $authenticated_oldpass = null;
    $can_change_password = false;
    $is_new_user_validation = isset($_REQUEST["new_user_validation"]) && $_REQUEST["new_user_validation"] === 'y';
    $pass_confirm = $userlib->getOne('select `pass_confirm` from `users_users` where binary `login`=?', [$user]);
    $must_change_password = ($pass_confirm === 0 || $pass_confirm === null);

    // Method 1: Activation code
    if (! empty($_REQUEST['actpass'])) {
        $authenticated_oldpass = $userlib->activate_password($_REQUEST['user'], $_REQUEST['actpass']);
        if (! empty($authenticated_oldpass)) {
            $is_authenticated = true;
            $can_change_password = true;
        } else {
            Feedback::error(tra('Invalid username or activation code. Maybe this code has already been used.'));
        }
    } elseif (! empty($secure_token)) {
        // Method 2: Reset token
        // Check if reset token is allowed for users who must change password
        if ($must_change_password) {
            Feedback::error(tra("Reset token cannot be used when password change is required. Please use your old password."));
        } else {
            // Reset token is allowed for regular password resets (not new user validation)
            $passwordResetLib = new \Tiki\Lib\Auth\PasswordResetLib();
            $token_info = $passwordResetLib->validatePasswordResetToken($user, $secure_token);
            if (! $token_info) {
                Feedback::error(tra("Invalid or expired reset token."));
            } else {
                // Token will be marked as used after successful password change
                $is_authenticated = true;
                $can_change_password = true;
            }
        }
    } elseif (! empty($_REQUEST['oldpass'])) {
        // Method 3: Old password
        list($isvalid, $validated_username, $error) = $userlib->validate_user($user, $_REQUEST["oldpass"]);
        if ($isvalid) {
            $is_authenticated = true;
            $can_change_password = true;
            $authenticated_oldpass = $_REQUEST['oldpass'];
        } else {
            Feedback::error(tra("Invalid old password"));
        }
    } elseif ($is_new_user_validation) {
        // Method 4: New user validation (no authentication required - legitimate exception)
        $can_change_password = true;
    } elseif ($must_change_password) {
        // Method 5: User must change password - old password is required
        Feedback::error(tra("Old password is required to change your password."));
    } else {
        // No authentication method was attempted
        Feedback::error(tra("Authentication required. Please provide your old password, activation code, or reset token."));
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
                $access->redirect($prefs['url_after_validation'], allowExternal: true);
            } else {
                $accesslib->redirect($homePageUrl);
            }
        }
    }
    // If authentication failed or validation failed, fall through to display the form
}

// Display password change form
global $prefs;
$prefs['language'] = $tikilib->get_user_preference($_REQUEST['user'], 'language', $prefs['site_language']);
$smarty->assign('email', $userlib->get_user_email($_REQUEST['user']));
$smarty->assign('metatag_robots', 'NOINDEX, NOFOLLOW');

$smarty->assign('mid', 'tiki-change_password.tpl');
$smarty->display("tiki.tpl");
