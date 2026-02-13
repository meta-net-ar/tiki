<?php

/**
 * @package tikiwiki
 */

use Tiki\Lib\CookieConsent\CookieConsentLib;
use Tiki\Lib\Theme\ThemeLib;

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
$inputConfiguration = [
    [
        'staticKeyFilters'                => [
        'userId'                          => 'int',          //post
        'view_user'                       => 'int',          //post
        'generate'                        => 'bool',         //post
        'tfagenerate'                     => 'bool',         //post
        'new_info'                        => 'bool',         //post
        'realName'                        => 'striptags',    //post
        'gender'                          => 'striptags',    //post
        'country'                         => 'striptags',    //post
        'location'                        => 'striptags',    //post
        'homePage'                        => 'pagename',     //post
        'new_prefs'                       => 'bool',         //post
        'mytheme'                         => 'striptags',    //post
        'userbreadCrumb'                  => 'digits',       //post
        'language'                        => 'lang',         //post
        'languageAdmin'                   => 'lang',         //post
        'read_language'                   => 'lang',         //post
        'display_timezone'                => 'striptags',    //post
        'display_12hr_clock'              => 'bool',         //post
        'diff_versions'                   => 'bool',         //post
        'remember_closed_rboxes'          => 'bool',         //post
        'email_isPublic'                  => 'bool',         //post
        'mailCharset'                     => 'striptags',    //post
        'prefName'                        => 'striptags',    //post
        'mess_maxRecords'                 => 'digits',       //post
        'mess_archiveAfter'               => 'digits',       //post
        'mess_sendReadStatus'             => 'bool',         //post
        'minPrio'                         => 'digits',       //post
        'allowMsgs'                       => 'bool',         //post
        'mytiki_pages'                    => 'bool',         //post
        'mytiki_blogs'                    => 'bool',         //post
        'mytiki_msgs'                     => 'bool',         //post
        'mytiki_tasks'                    => 'bool',         //post
        'mytiki_forum_topics'             => 'bool',         //post
        'mytiki_forum_replies'            => 'bool',         //post
        'mytiki_items'                    => 'bool',         //post
        'mytiki_articles'                 => 'bool',         //post
        'tasks_maxRecords'                => 'digits',       //post
        'xmpp_jid'                        => 'striptags',    //post
        'xmpp_password'                   => 'password',     //post
        'xmpp_custom_server_http_bind'    => 'striptags',    //post
        'perspective_preferred'           => 'digits',      //post
        'webmonetization_payment_pointer' => 'striptags',    //post
        'webmonetization_paywall_text'    => 'striptags',    //post
        'notify_oneself'                  => 'bool',         //post
        'switch_user_notification'        => 'bool',         //post
        'cookie_consent_update'           => 'word',         //post
        'chgadmin'                        => 'bool',         //post
        'pass'                            => 'password',     //post
        'email'                           => 'email',        //post
        'pass1'                           => 'password',     //post
        'pass2'                           => 'password',     //post
        'twofactor'                       => 'bool',         //post
        'tfaSecret'                       => 'striptags',    //post
        'tfaPin'                          => 'digits',       //post
        'removetwofactor'                 => 'bool',         //post
        'deleteaccount'                   => 'bool',         //post
        ],
    ],
];

require_once('tiki-setup.php');
use Tiki\Sections;
$section = Sections::SECTION_MY_TIKI;
Sections::setCurrentSection($section);
$modlib = TikiLib::lib('mod');
$userprefslib = TikiLib::lib('userprefs');
$perspectivelib = TikiLib::lib('perspective');

// Get cookie consent user preferences
$cookieConsentPrefs = CookieConsentLib::getConsentPreferences();
// Get all the existing cookie categories
$cookieCategories = CookieConsentLib::getCookieCategories();

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use PragmaRX\Google2FA\Google2FA;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Tiki\Lib\TikiDate;
use Tiki\TwoFactorAuth\TwoFactorAuth;

// User preferences screen
if ($prefs['feature_userPreferences'] != 'y' && $prefs['change_password'] != 'y' && $tiki_p_admin_users != 'y') {
    Feedback::errorAndDie(tra("This feature is disabled") . ": feature_userPreferences", \Laminas\Http\Response::STATUS_CODE_403);
}
$access->check_user($user);

$auto_query_args = ['userId', 'view_user'];

// Make sure user preferences uses https if SET
if (! $https_mode && isset($https_login) && $https_login == 'required') {
    header('Location: ' . $base_url_https . 'tiki-user_preferences.php');
    die;
}
if (! empty($_REQUEST['userId'])) {
    $userwatch = $tikilib->get_user_login($_REQUEST['userId']);
} elseif (! empty($_REQUEST["view_user"])) {
    $userwatch = $_REQUEST["view_user"];
} else {
    $userwatch = $user;
}

if ($userwatch != $user) {
    $access->check_permission('tiki_p_admin_users');
    if (empty($userwatch) || empty($userlib->user_exists($userwatch))) {
        Feedback::errorAndDie(tra("Unknown user"), \Laminas\Http\Response::STATUS_CODE_400);
    }
}

// Custom fields
$registrationlib = TikiLib::lib('registration');
$customfields = $registrationlib->get_customfields();
foreach ($customfields as $i => $c) {
    $customfields[$i]['value'] = $tikilib->get_user_preference($userwatch, $c['prefName']);
}
$smarty->assign_by_ref('customfields', $customfields);
$smarty->assign('userwatch', $userwatch);
$foo = parse_url($_SERVER["REQUEST_URI"]);
$foo1 = str_replace("tiki-user_preferences", "tiki-editpage", $foo["path"]);
$foo2 = str_replace("tiki-user_preferences", "tiki-index", $foo["path"]);
$smarty->assign('url_edit', $tikilib->httpPrefix() . $foo1);
$smarty->assign('url_visit', $tikilib->httpPrefix() . $foo2);


if ($prefs['feature_perspective'] === 'y') {
    $smarty->assign('perspectives', $perspectivelib->list_perspectives());
}

if ($prefs['cookie_consent_feature'] === "y") {
    $smarty->assign('cookieConsentPrefs', $cookieConsentPrefs);
    $smarty->assign('cookieCategories', $cookieCategories);
}

// form in first tab "Personal Information"
if ($prefs['feature_userPreferences'] == 'y' && isset($_POST["new_info"]) && $access->checkCsrf()) {
    if (isset($_POST["realName"]) && ($prefs['auth_ldap_nameattr'] == '' || $prefs['auth_method'] != 'ldap')) {
        $tikilib->set_user_preference($userwatch, 'realName', $_POST["realName"]);
        if ($prefs['user_show_realnames'] == 'y') {
            $cachelib = TikiLib::lib('cache');
            $cachelib->invalidate('userlink.' . $user . '0');
        }
    }

    if ($prefs['feature_community_gender'] == 'y') {
        if (isset($_POST["gender"])) {
            $tikilib->set_user_preference($userwatch, 'gender', $_POST["gender"]);
        }
    }

    $tikilib->set_user_preference($userwatch, 'country', $_POST["country"]);

    if (isset($_POST['location'])) {
        if ($coords = TikiLib::lib('geo')->parse_coordinates($_POST['location'])) {
            $tikilib->set_user_preference($userwatch, 'lat', $coords['lat']);
            $tikilib->set_user_preference($userwatch, 'lon', $coords['lon']);
            if (isset($coords['zoom'])) {
                $tikilib->set_user_preference($userwatch, 'zoom', $coords['zoom']);
            }
        }
    }

    if (isset($_POST["homePage"])) {
        $tikilib->set_user_preference($userwatch, 'homePage', $_POST["homePage"]);
    }
    TikiLib::events()->trigger(
        'tiki.user.update',
        [
            'type' => 'user',
            'object' => $userwatch,
            'user' => $GLOBALS['user'],
        ]
    );
}

if ($prefs['feature_userPreferences'] == 'y' && isset($_POST["new_prefs"]) && $access->checkCsrf()) {
    // setting preferences
    if ($prefs['change_theme'] == 'y' && empty($group_theme)) {
        if (isset($_POST['mytheme'])) {
            $themeandoption = ThemeLib::extractThemeAndOptionFromString($_POST['mytheme']);
            $theme = $themeandoption[0];
            $themeOption = $themeandoption[1];
            $tikilib->set_user_preference($userwatch, 'theme', $theme);
            if (isset($themeOption)) {
                $tikilib->set_user_preference($userwatch, 'theme_option', empty($themeOption) ? '' : $themeOption);
            }
            //Something is needed for the theme change to be displayed without additional manual page refresh. Problem: when modifying another user's settings (not my user's) using any of the below ways the refreshed screen will show my user's preference screen instead of staying on the edited user's preference screen
            //header("location: tiki-user_preferences.php?view_user=$userwatch");
            //$access->redirect($_SERVER['REQUEST_URI'], '', 200);
        }
    }
    if (isset($_POST["userbreadCrumb"])) {
        $tikilib->set_user_preference($userwatch, 'userbreadCrumb', $_POST["userbreadCrumb"]);
    }
    if (isset($_POST["notify_oneself"])) {
        $tikilib->set_user_preference($userwatch, 'notify_oneself', $_POST["notify_oneself"]);
    }

    if (isset($_POST["cookie_consent_update"])) {
        // Initialize the preferences array using the consent action from POST data.
        $preferences = [
            'consentGiven' => true,
            'action' => $_POST['cookie_consent_update'],
            'categories' => []
        ];
        // Loop through all defined cookie categories and set their statuses.
        foreach ($cookieCategories as $category => $description) {
            if ($_POST['cookie_consent_update'] === 'declineNonEssential') {
                // When the user declines unnecessary cookies, disable all non-essential categories.
                $preferences['categories'][$category] = ($category === 'essential');
            } else {
                // For "acceptAll" or "customized" actions, enable the category only if the corresponding POST flag is set.
                // Always ensure the essential category is true.
                $preferences['categories'][$category] = ($category === 'essential') ? true : (isset($_POST["cookie_consent_$category"]) && $_POST["cookie_consent_$category"] === "on");
            }
        }
        // Store the complete consent preferences as a single JSON cookie via CookieConsentLib.
        CookieConsentLib::setConsentPreferences($preferences);

        // Save the preferences in the user's settings as a JSON string.
        $tikilib->set_user_preference($userwatch, 'cookie_consent_user_pref', json_encode($preferences));
    }

    if (isset($_POST["switch_user_notification"])) {
        $tikilib->set_user_preference($userwatch, 'switch_user_notification', $_POST["switch_user_notification"]);
    }

    $langLib = TikiLib::lib('language');
    if (isset($_POST["language"]) && $langLib->is_valid_language($_POST['language'])) {
        if ($tiki_p_admin || $prefs['change_language'] == 'y') {
            $tikilib->set_user_preference($userwatch, 'language', $_POST["language"]);
        }
        if ($userwatch == $user) {
            include('lang/' . $_POST["language"] . '/language.php');
        }
    } else {
        $tikilib->set_user_preference($userwatch, 'language', '');
    }
    $smarty->assign('tiki_p_admin', $tiki_p_admin);
    if ($tiki_p_admin && isset($_POST['languageAdmin']) && $langLib->is_valid_language($_POST['languageAdmin'])) {
        $tikilib->set_user_preference($userwatch, 'language_admin', $_POST['languageAdmin']);
    } else {
        $tikilib->set_user_preference($userwatch, 'language_admin', '');
    }
    if (isset($_POST['read_language'])) {
        $list = [];
        $tok = strtok($_POST['read_language'], ' ');
        while (false !== $tok) {
            $list[] = $tok;
            $tok = strtok(' ');
        }
        $list = array_unique($list);
        $langLib = TikiLib::lib('language');
        $list = array_filter($list, [$langLib, 'is_valid_language']);
        $list = implode(' ', $list);
        $tikilib->set_user_preference($userwatch, 'read_language', $list);
    }
    if (isset($_POST['display_timezone'])) {
        $tikilib->set_user_preference($userwatch, 'display_timezone', $_POST['display_timezone']);
    }

    if (isset($_POST['display_12hr_clock']) && $_POST['display_12hr_clock'] == 'on') {
        $tikilib->set_user_preference($userwatch, 'display_12hr_clock', 'y');
        $smarty->assign('display_12hr_clock', 'y');
    } else {
        $tikilib->set_user_preference($userwatch, 'display_12hr_clock', 'n');
        $smarty->assign('display_12hr_clock', 'n');
    }
    if (isset($_POST['diff_versions']) && $_POST['diff_versions'] == 'on') {
        $tikilib->set_user_preference($userwatch, 'diff_versions', 'y');
        $smarty->assign('diff_versions', 'y');
    } else {
        $tikilib->set_user_preference($userwatch, 'diff_versions', 'n');
        $smarty->assign('diff_versions', 'n');
    }

    $tikilib->set_user_preference($userwatch, 'remember_closed_rboxes', empty($_POST['remember_closed_rboxes']) ? 'n' : 'y');

    $email_isPublic = $_POST['email_isPublic'] ?? 'n';
    $tikilib->set_user_preference($userwatch, 'email is public', $email_isPublic);
    $tikilib->set_user_preference($userwatch, 'mailCharset', $_POST['mailCharset']);
    // Custom fields
    foreach ($customfields as $custpref => $prefvalue) {
        $prefName = $prefvalue['prefName'];
        if (isset($_POST[$prefName])) {
            $tikilib->set_user_preference($userwatch, $prefName, $_POST[$prefName]);
        }
    }

    if (isset($_POST['mess_maxRecords'])) {
        $tikilib->set_user_preference($userwatch, 'mess_maxRecords', $_POST['mess_maxRecords']);
    }
    if (isset($_POST['mess_archiveAfter'])) {
        $tikilib->set_user_preference($userwatch, 'mess_archiveAfter', $_POST['mess_archiveAfter']);
    }
    if (isset($_POST['mess_sendReadStatus']) && $_POST['mess_sendReadStatus'] == 'on') {
        $tikilib->set_user_preference($userwatch, 'mess_sendReadStatus', 'y');
    } else {
        $tikilib->set_user_preference($userwatch, 'mess_sendReadStatus', 'n');
    }
    if (isset($_POST['minPrio'])) {
        $tikilib->set_user_preference($userwatch, 'minPrio', $_POST['minPrio']);
    }
    if ($prefs['allowmsg_is_optional'] == 'y') {
        if (isset($_POST['allowMsgs']) && $_POST['allowMsgs'] == 'on') {
            $tikilib->set_user_preference($userwatch, 'allowMsgs', 'y');
        } else {
            $tikilib->set_user_preference($userwatch, 'allowMsgs', 'n');
        }
    }
    if (isset($_POST['mytiki_pages']) && $_POST['mytiki_pages'] == 'on') {
        $tikilib->set_user_preference($userwatch, 'mytiki_pages', 'y');
    } else {
        $tikilib->set_user_preference($userwatch, 'mytiki_pages', 'n');
    }
    if (isset($_POST['mytiki_blogs']) && $_POST['mytiki_blogs'] == 'on') {
        $tikilib->set_user_preference($userwatch, 'mytiki_blogs', 'y');
    } else {
        $tikilib->set_user_preference($userwatch, 'mytiki_blogs', 'n');
    }
    if (isset($_POST['mytiki_msgs']) && $_POST['mytiki_msgs'] == 'on') {
        $tikilib->set_user_preference($userwatch, 'mytiki_msgs', 'y');
    } else {
        $tikilib->set_user_preference($userwatch, 'mytiki_msgs', 'n');
    }
    if (isset($_POST['mytiki_tasks']) && $_POST['mytiki_tasks'] == 'on') {
        $tikilib->set_user_preference($userwatch, 'mytiki_tasks', 'y');
    } else {
        $tikilib->set_user_preference($userwatch, 'mytiki_tasks', 'n');
    }
    if (isset($_POST['mytiki_forum_topics']) && $_POST['mytiki_forum_topics'] == 'on') {
        $tikilib->set_user_preference($userwatch, 'mytiki_forum_topics', 'y');
    } else {
        $tikilib->set_user_preference($userwatch, 'mytiki_forum_topics', 'n');
    }
    if (isset($_POST['mytiki_forum_replies']) && $_POST['mytiki_forum_replies'] == 'on') {
        $tikilib->set_user_preference($userwatch, 'mytiki_forum_replies', 'y');
    } else {
        $tikilib->set_user_preference($userwatch, 'mytiki_forum_replies', 'n');
    }
    if (isset($_POST['mytiki_items']) && $_POST['mytiki_items'] == 'on') {
        $tikilib->set_user_preference($userwatch, 'mytiki_items', 'y');
    } else {
        $tikilib->set_user_preference($userwatch, 'mytiki_items', 'n');
    }
    if (isset($_POST['mytiki_articles']) && $_POST['mytiki_articles'] == 'on') {
        $tikilib->set_user_preference($userwatch, 'mytiki_articles', 'y');
    } else {
        $tikilib->set_user_preference($userwatch, 'mytiki_articles', 'n');
    }
    if (isset($_POST['tasks_maxRecords'])) {
        $tikilib->set_user_preference($userwatch, 'tasks_maxRecords', $_POST['tasks_maxRecords']);
    }
    if ($prefs['feature_intertiki'] == 'y' && ! empty($prefs['feature_intertiki_mymaster']) && $prefs['feature_intertiki_import_preferences'] == 'y') { //send to the master
        $userlib->interSendUserInfo($prefs['interlist'][$prefs['feature_intertiki_mymaster']], $userwatch);
    }

    if (isset($_POST['xmpp_jid'])) {
        $tikilib->set_user_preference($userwatch, 'xmpp_jid', $_POST['xmpp_jid']);
    }
    if (isset($_POST['xmpp_password'])) {
        $tikilib->set_user_preference($userwatch, 'xmpp_password', $_POST['xmpp_password']);
    }
    if (isset($_POST['xmpp_custom_server_http_bind'])) {
        $tikilib->set_user_preference($userwatch, 'xmpp_custom_server_http_bind', $_POST['xmpp_custom_server_http_bind']);
    }

    if (isset($_POST['perspective_preferred']) &&  $perspectivelib->perspective_exists($_POST['perspective_preferred'])) {
        $tikilib->set_user_preference($userwatch, 'perspective_preferred', $_POST['perspective_preferred']);
    } else {
        $tikilib->set_user_preference($userwatch, 'perspective_preferred', null);
    }

    if (isset($_POST['webmonetization_payment_pointer'])) {
        $tikilib->set_user_preference($userwatch, 'webmonetization_payment_pointer', $_POST['webmonetization_payment_pointer']);
    }
    if (isset($_POST['webmonetization_paywall_text'])) {
        $tikilib->set_user_preference($userwatch, 'webmonetization_paywall_text', $_POST['webmonetization_paywall_text']);
    }

    TikiLib::events()->trigger(
        'tiki.user.update',
        [
            'type' => 'user',
            'object' => $userwatch,
            'user' => $GLOBALS['user'],
        ]
    );
}
if ($prefs['auth_method'] == 'ldap' && $user == 'admin' && $prefs['ldap_skip_admin'] == 'y') {
    $change_password = 'y';
    $smarty->assign('change_password', $change_password);
}
if ($prefs['feature_intertiki'] == 'y' && $prefs['feature_intertiki_server'] == 'n') {
    $prefs['change_password'] = 'n';
    $warning = "New password fields are hidden on Intertiki clients.\nTo update your password, please log in directly to the Intertiki server.";
    $smarty->assign('intertiki_no_password_change_warning', $warning);
}

$tfaSecret = $userlib->get_2_factor_secret($userwatch);
if (isset($_POST['chgadmin']) && $access->checkCsrf()) {
    if (isset($_POST['pass'])) {
        $pass = $_POST['pass'];
    } else {
        $pass = '';
    }
    // check user's password, admin doesn't need it to change other user's info
    if ($tiki_p_admin != 'y' || $user == $userwatch) {
        if ($prefs['feature_intertiki'] == 'y' && ! empty($prefs['feature_intertiki_mymaster'])) {
            if ($ok = $userlib->intervalidate($prefs['interlist'][$prefs['feature_intertiki_mymaster']], $userwatch, $pass)) {
                if ($ok->faultCode()) {
                    $ok = false;
                }
            }
        } else {
            list($ok, $userwatch, $error) = $userlib->validate_user($userwatch, $pass, false, null, false);
        }
        if (! $ok) {
            Feedback::errorAndDie(tra("Invalid password. Your current password is required to change administrative information"), \Laminas\Http\Response::STATUS_CODE_409);
        }
    }
    if (! empty($_POST['email']) && ($prefs['login_is_email'] != 'y' || $user == 'admin') && $_POST['email'] != $userlib->get_user_email($userwatch)) {
        if (validate_email($_POST['email'])) {
            $userlib->change_user_email($userwatch, $_POST['email'], $pass);
            Feedback::success(sprintf(tra('Email is set to %s'), $_POST['email']));
            if ($prefs['feature_intertiki'] == 'y' && ! empty($prefs['feature_intertiki_mymaster']) && $prefs['feature_intertiki_import_preferences'] == 'y') { //send to the master
                $userlib->interSendUserInfo($prefs['interlist'][$prefs['feature_intertiki_mymaster']], $userwatch);
            }

            TikiLib::events()->trigger(
                'tiki.user.update',
                [
                    'type' => 'user',
                    'object' => $userwatch,
                    'user' => $GLOBALS['user'],
                ]
            );
        } else {
            Feedback::error(tr('Invalid email address "%0"', $_POST['email']));
        }
    }
    // If user has provided new password, let's try to change
    if (! empty($_POST["pass1"])) {
        if ($_POST["pass1"] != $_POST["pass2"]) {
            Feedback::errorAndDie(tra("The passwords did not match"), \Laminas\Http\Response::STATUS_CODE_400);
        }
        $polerr = $userlib->check_password_policy($_POST["pass1"]);
        if (strlen($polerr) > 0) {
            Feedback::errorAndDie($polerr, \Laminas\Http\Response::STATUS_CODE_400);
        }
        $userlib->change_user_password($userwatch, $_POST["pass1"]);
        $userlib->update_lastlogin($userwatch);
        if ($prefs['feature_user_encryption'] === 'y') {
            // Notify CryptLib about the login
            $cryptlib = TikiLib::lib('crypt');
            $cryptlib->onChangeUserPassword($_POST["pass"], $_POST["pass1"]);
        }
        Feedback::success(tra('Password has been changed'));
    }
}

if (
    isset($_POST['twofactor'])
    && isset($_POST['tfaSecret'])
    && isset($_POST['tfaPin'])
    && $access->checkCsrf()
) {
    if ($userlib->validate_two_factor($_POST['tfaSecret'], $_POST['tfaPin'], $user)) {
        $tfaSecret = $userlib->update_2_factor_secret(
            $user,
            $_POST['tfaSecret']
        );
    } else {
        if (empty($_POST['tfaPin'])) {
            Feedback::error(tr('Field 2FA Code is required.'));
        } else {
            Feedback::error(tr('Invalid 2FA Code.'));
        }
        header('Location: ' . basename(__FILE__) . '?tfagenerate=true');
        die;
    }
}

if (isset($_POST['twofactor']) && $access->checkCsrf() && $prefs['twoFactorAuthType'] == TwoFactorAuth::EMAIL_2FA) {
    $tfaSecret = $userlib->update_2_factor_secret($user, 'y');
}

if (
    isset($_POST['removetwofactor'])
    && $access->checkCsrf()
) {
    $tfaSecret = $userlib->update_2_factor_secret($user, '');
    unset($_REQUEST['generate']);
    unset($_SESSION['tfaSecret']);
}

$twoFactorSecret = $userlib->get_2_factor_secret($userwatch);
$force2FA = $userlib->forceTwoFactorAuth($userwatch);
$smarty->assign('force2FA', $force2FA);
$smarty->assign('twoFactorSecret', $twoFactorSecret);

$userinfo = $userlib->get_user_info($userwatch);
$generate = isset($_REQUEST['tfagenerate']) || empty($tfaSecret);
if ($prefs['twoFactorAuth'] == 'y' && $generate && $prefs['twoFactorAuthType'] == TwoFactorAuth::TOTP_2FA) {
    $google2fa = new Google2FA();
    if (empty($_SESSION['tfaSecret']) || $_SESSION['tfaSecret'] == $tfaSecret) {
        $_SESSION['tfaSecret'] = $google2fa->generateSecretKey();
    }
    $tfaSecret = $_SESSION['tfaSecret'];
    $smarty->assign('tfaSecret', $tfaSecret);
    $g2faUrl = $google2fa->getQRCodeUrl(
        $tikilib->get_preference('browsertitle', "Tiki Wiki"),
        $userinfo['email'],
        $tfaSecret
    );

    if (extension_loaded('imagick')) {
        $imageBackEnd = new ImagickImageBackEnd();
        $imageType = 'png';
    } else {
        $imageBackEnd = new SvgImageBackEnd();
        $imageType = 'svg+xml';
    }

    $writer = new Writer(
        new ImageRenderer(
            new RendererStyle(350),
            $imageBackEnd
        )
    );
    $tfaSecretQR = base64_encode($writer->writeString($g2faUrl));
    $smarty->assign('tfaSecretQR', $tfaSecretQR);
    $smarty->assign('imageType', $imageType);
}

if (isset($_POST['deleteaccount']) && $tiki_p_delete_account == 'y' && $access->checkCsrf(true)) {
    $userlib->remove_user($userwatch);

    $unifiedsearchlib = TikiLib::lib('unifiedsearch');
    $unifiedsearchlib->invalidateObject('user', $userwatch);

    if ($user == $userwatch) {
        header('Location: tiki-logout.php');
    } elseif ($tiki_p_admin_users == 'y') {
        header('Location: tiki-adminusers.php');
    } else {
        header("Location: $base_url");
    }
    die();
}

if (! empty($_POST)) {
    // This avoids accident form ressubmission
    header('Location:' . basename(__FILE__) . '?userId=' . $_REQUEST['userId']);
    return;
}


/**
 * Don't do any write operation from here
 */


$location = [
    'lat' => (float) $tikilib->get_user_preference($userwatch, 'lat', ''),
    'lon' => (float) $tikilib->get_user_preference($userwatch, 'lon', ''),
    'zoom' => (int) $tikilib->get_user_preference($userwatch, 'zoom', ''),
];

$location = TikiLib::lib('geo')->build_location_string($location);

$smarty->assign('location', $location);

$tikilib->get_user_preference($userwatch, 'mytiki_pages', 'y');
$tikilib->get_user_preference($userwatch, 'mytiki_blogs', 'y');
$tikilib->get_user_preference($userwatch, 'mytiki_items', 'y');
$tikilib->get_user_preference($userwatch, 'mytiki_msgs', 'y');
$tikilib->get_user_preference($userwatch, 'mytiki_tasks', 'y');
$tikilib->get_user_preference($userwatch, 'mylevel', '1');
$tikilib->get_user_preference($userwatch, 'tasks_maxRecords');
$tikilib->get_user_preference($userwatch, 'mess_maxRecords', 20);
$tikilib->get_user_preference($userwatch, 'mess_archiveAfter', 0);
$tikilib->get_user_preference($userwatch, 'mess_sendReadStatus', 0);
$tikilib->get_user_preference($userwatch, 'allowMsgs', 'y');
$tikilib->get_user_preference($userwatch, 'minPrio', 3);
$tikilib->get_user_preference($userwatch, 'theme', '');
$tikilib->get_user_preference($userwatch, 'language', $prefs['language']);
$tikilib->get_user_preference($userwatch, 'realName', '');
if ($prefs['feature_community_gender'] == 'y') {
    $tikilib->get_user_preference($userwatch, 'gender', 'Hidden');
}
$tikilib->get_user_preference($userwatch, 'country', 'Other');
$tikilib->get_user_preference($userwatch, 'userbreadCrumb', $prefs['site_userbreadCrumb']);
$tikilib->get_user_preference($userwatch, 'homePage', '');
$tikilib->get_user_preference($userwatch, 'email is public', 'n');
if (isset($user_preferences[$userwatch]['email is public'])) {
    $user_preferences[$userwatch]['email_isPublic'] = $user_preferences[$userwatch]['email is public'];
}
$tikilib->get_user_preference($userwatch, 'mailCharset', $prefs['default_mail_charset']);
$tikilib->get_user_preference($userwatch, 'display_12hr_clock', 'n');
$smarty->assign_by_ref('userinfo', $userinfo);
//user theme
$themelib = TikiLib::lib('theme');
$available_themesandoptions = $themelib->get_available_themesandoptions();
$smarty->assign_by_ref('available_themesandoptions', $available_themesandoptions);
$userwatch_theme = $tikilib->get_user_preference($userwatch, 'theme', null);
$userwatch_themeOption = $tikilib->get_user_preference($userwatch, 'theme_option', null);
$smarty->assign_by_ref('userwatch_theme', $userwatch_theme);
$smarty->assign_by_ref('userwatch_themeOption', $userwatch_themeOption);
//user language
$languages = [];
$langLib = TikiLib::lib('language');
$languages = $langLib->list_languages();
$smarty->assign_by_ref('languages', $languages);

$user_pages = $tikilib->get_user_pages($userwatch, -1);
$smarty->assign_by_ref('user_pages', $user_pages);
$bloglib = TikiLib::lib('blog');
$user_blogs = $bloglib->list_user_blogs($userwatch, false);
$smarty->assign_by_ref('user_blogs', $user_blogs);
$user_items = TikiLib::lib('trk')->get_user_items($userwatch);
$smarty->assign_by_ref('user_items', $user_items);
$flags = $tikilib->get_flags('', '', '', true);
$smarty->assign_by_ref('flags', $flags);
$avatar = $tikilib->get_user_avatar($userwatch);
$smarty->assign_by_ref('avatar', $avatar);
$mailCharsets = ['utf-8', 'iso-8859-1'];
$smarty->assign_by_ref('mailCharsets', $mailCharsets);
$smarty->assign_by_ref('user_prefs', $user_preferences[$userwatch]);
$tikilib->get_user_preference($userwatch, 'diff_versions', 'n');
$usertrackerId = false;
$useritemId = false;
if ($prefs['userTracker'] == 'y') {
    $re = $userlib->get_usertracker($userinfo["userId"]);
    if (isset($re['usersTrackerId']) && $re['usersTrackerId'] && Tracker_Definition::get($re['usersTrackerId'])) {
        $trklib = TikiLib::lib('trk');
        $info = $trklib->get_item_id($re['usersTrackerId'], $re['usersFieldId'], $userwatch);
        $usertrackerId = $re['usersTrackerId'];
        $useritemId = $info;
    }
}
$smarty->assign('usertrackerId', $usertrackerId);
$smarty->assign('useritemId', $useritemId);
// Custom fields
foreach ($customfields as $custpref => $prefvalue) {
    $prefvalue['value'] = $tikilib->get_user_preference($userwatch, $prefvalue['prefName'], $prefvalue['value']);
    $smarty->assign($prefvalue['prefName'], $prefvalue['value']);
}
if ($prefs['feature_messages'] == 'y' && $tiki_p_messages == 'y') {
    $unread = $tikilib->user_unread_messages($userwatch);
    $smarty->assign('unread', $unread);
}
$smarty->assign('timezones', TikiDate::getTimeZoneList());

$tikilib->set_display_timezone($user);

if (isset($prefs['display_timezone'])) {
    $smarty->assign('display_timezone', $prefs['display_timezone']);
}

if ($prefs['users_prefs_display_timezone'] == 'Site') {
    $smarty->assign('warning_site_timezone_set', 'y');
} else {
    $smarty->assign('warning_site_timezone_set', 'n');
}

$smarty->assign('userPageExists', 'n');
if ($prefs['feature_wiki'] == 'y' and $prefs['feature_wiki_userpage'] == 'y') {
    if ($tikilib->page_exists($prefs['feature_wiki_userpage_prefix'] . $user)) {
        $smarty->assign('userPageExists', 'y');
    }
}
include_once('tiki-section_options.php');
$smarty->assign('mid', 'tiki-user_preferences.tpl');
$smarty->display("tiki.tpl");
