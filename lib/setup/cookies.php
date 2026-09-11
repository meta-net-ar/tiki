<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
if (basename($_SERVER['SCRIPT_NAME']) === basename(__FILE__)) {
    die('This script may only be included.');
}

$headerlib = TikiLib::lib('header');
$smarty = TikiLib::lib('smarty');

if (isset($_SESSION['tiki_cookie_jar'])) {
    $cookielist = [];

    if (is_array($_SESSION['tiki_cookie_jar'])) {
        foreach ($_SESSION['tiki_cookie_jar'] as $nn => $vv) {
            $cookielist[] = "'" . \SmartyTiki\Modifier\Escape::apply($nn, 'javascript') . "': '" . \SmartyTiki\Modifier\Escape::apply($vv, 'javascript') . "'";
        }
    }

    if (count($cookielist)) {
        $headerlib->add_js('tiki_cookie_jar={' . implode(',', $cookielist) . '};');
    }
    $_COOKIE = array_merge($_SESSION['tiki_cookie_jar'], $_COOKIE);
} else {
    $headerlib->add_js('tiki_cookie_jar=new Object();');
}

$smarty->assign_by_ref('cookie', $_COOKIE);

/** This function is meant to replace PHPs low-level https://www.php.net/manual/en/function.setcookie.php function.  It only implements the 3 parameter version.
 *
 * the reason it exists is that:
 *
 * It is completely silly that setcookie is slightly different from the array returned by session_get_cookie_params.
 * It is also completely silly that it will not obey smaesite in session_set_cookie_params if you pass lifetime explicitly.  Maybe it disregards other parameters in some forms, I did not check.
 * benoitg -2025-05-12.
 *
 *
 */
function setcookie_obeySetCookieParams(string $name, string $value = "", array $options = []): bool
{
    $sessionParams = session_get_cookie_params();
    // Yes, it is completely silly that the options parameter of setcookie is slightly different from the array returned by session_get_cookie_params().
    $sessionParams['expires'] = $sessionParams['lifetime'];
    unset($sessionParams['lifetime']);
    $finalOptions = array_merge($sessionParams, $options);
    return setcookie($name, $value, $finalOptions);
}
/**
 * This seems to be the mirror function of CookieConsentLib::setCookieSection(), but
 * I am not 100% sure since CookieConsentLib has it's own setCookie() method - benoitg - 2026-03-26
 */
function getCookie($name, $section = null, $default = null)
{
    global $feature_no_cookie, $jitCookie;

    if (isset($_COOKIE[$name])) {
        $cookie = $_COOKIE[$name];
    } elseif (isset($jitCookie[$name])) {
        $cookie = $jitCookie[$name];
    }

    if (isset($cookie)) {
        // we need a reliable way to get cookies even if user has not accepted cookies
        // e.g. CSRF token in a cookie needs to be read as it is already set as a cookie
        return $cookie;
    }

    if ($feature_no_cookie || (empty($section) && ! isset($cookie) && isset($_SESSION['tiki_cookie_jar'][$name]))) {
        if (isset($_SESSION['tiki_cookie_jar'][$name])) {
            return $_SESSION['tiki_cookie_jar'][$name];
        } else {
            return $default;
        }
    } elseif ($section) {
        if (isset($_COOKIE[$section])) {
            if (preg_match("/@" . preg_quote($name, '/') . "\:([^@;]*)/", $_COOKIE[$section], $matches)) {
                return $matches[1];
            } else {
                return $default;
            }
        } else {
            return $default;
        }
    } else {
        if (isset($cookie)) {
            return $cookie;
        } else {
            return $default;
        }
    }
}
