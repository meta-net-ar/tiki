<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

use Tiki\TwoFactorAuth\Exception\TwoFactorAuthException;
use Tiki\TwoFactorAuth\TwoFactorAuth;

$section_class = 'tiki_login';  // This will be body class instead of $section
$inputConfiguration = [
    [
        'staticKeyFilters'     => [
        'showTwoFactorForm'        => 'string',         //post
        'clearmenucache'       => 'bool',           //post
        'user'                 => 'username',       //post
        'create2FaCodeNormalLogin' => 'alpha',      //get
        'tiki_username'        => 'username',       //get
        ],
    ],
];
include_once("tiki-setup.php");

// Emit 401 when reached via an auth challenge (via ?challenge=1 param or session loginfrom).
if (! $user && (! empty($_GET['challenge']) || ! empty($_SESSION['loginfrom']))) {
    http_response_code(401);
}

// Setup Two-Factor Auth form state
$showTwoFactorForm = $prefs['twoFactorAuth'];
if (isset($_REQUEST["showTwoFactorForm"])) {
    $showTwoFactorForm = 'y';
}
$smarty->assign('showTwoFactorForm', $showTwoFactorForm);

$create2FaCodeNormalLogin = isset($_REQUEST["create2FaCodeNormalLogin"]) ? 'y' : 'n';
try {
    if ($prefs['twoFactorAuth'] === 'y' && $create2FaCodeNormalLogin === 'y' && ! empty($_REQUEST['tiki_username'])) {
        $tikiUserName = urldecode($_REQUEST['tiki_username']);
        $twoFactorAuth = TwoFactorAuth::getTwoFactorAuth();
        $isTokenGenerated = $twoFactorAuth->generateCode($tikiUserName);
        if ($prefs['twoFactorAuthType'] === TwoFactorAuth::EMAIL_2FA && ! empty($isTokenGenerated)) {
            $message = tr("An email containing your authentication code has been sent. Please enter the code to access the website.");
            Feedback::success($message);
        }
    }
} catch (TwoFactorAuthException $e) {
    $message = tr($e->getMessage());
    Feedback::error($message);
}
$smarty->assign('create2FaCodeNormalLogin', $create2FaCodeNormalLogin);

if ($prefs['login_autologin'] == 'y' && $prefs['login_autologin_redirectlogin'] == 'y' && ! empty($prefs['login_autologin_redirectlogin_url'])) {
    $access->redirect($prefs['login_autologin_redirectlogin_url'], allowExternal: true);
}

if (isset($_REQUEST['clearmenucache'])) {
    TikiLib::lib('menu')->empty_menu_cache();
}
if (isset($_REQUEST['user'])) {
    if (TikiLib::lib('user')->user_has_permission($_REQUEST['user'], 'tiki_p_admin') && (! isset($_SESSION["groups_are_emulated"]) || $_SESSION["groups_are_emulated"] != "y")) {
        $smarty->assign('showloginboxes', 'y');
        $smarty->assign('adminuser', $_REQUEST['user']);
    } else {
        $smarty->assign('loginuser', $_REQUEST['user']);
    }
}
if (($prefs['useGroupHome'] != 'y' || $prefs['limitedGoGroupHome'] == 'y') && ! isset($_SESSION['loginfrom'])) {
    if (isset($_SERVER['HTTP_REFERER']) && str_starts_with($_SERVER['HTTP_REFERER'], $url_scheme . '://' . $url_host)) {
        $_SESSION['loginfrom'] = $_SERVER['HTTP_REFERER'];
    } else {
        $_SESSION['loginfrom'] = $prefs['tikiIndex'];
    }
}

$headerlib->add_js('$(function() {
    $("#login-user").trigger("focus").trigger("select");
})');

// disallow robots to index page:
$smarty->assign('metatag_robots', 'NOINDEX, NOFOLLOW');
$smarty->assign('headtitle', tra('Log In'));
$smarty->assign('mid', 'tiki-login.tpl');

$smarty->display("tiki.tpl");
