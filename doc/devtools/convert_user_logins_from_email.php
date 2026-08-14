#!/usr/bin/php
<?php

/**
 * change all user logins from email to a more normal username
 */

die('Edit this file and remove this "die" line as it will make irreversible changes to your database so should be used with caution!');

$datenow = date('c');
echo "{$datenow} Processing user emails to usernames ";

$minUsernameLength = 4;

// best to do this while the site is closed!
$bypass_siteclose_check = true;

require_once('tiki-setup.php');

/** @var TikiDb_Table $userTable */
$userTable = TikiDb::get()->table('users_users');

/** @var TikiLib $tikilib */
$tikilib = TikiLib::lib('tiki');
/** @var UsersLib $userlib */
$userlib = TikiLib::lib('user');

$allUsers = $userTable->fetchAll(
    ['userId', 'email', 'login'],
    ['login' => $userTable->not('admin')]
);

// keep new logins to check for duplicates
$newLogins = ['admin'];
/** @var Tiki\TikiDb\PdoResult $ret */
$ret = null;

foreach ($allUsers as $aUser) {
    $oldLogin = $aUser['login'];

    $rc = preg_match('/(.*?)@(.*?).([^.]+)$/', $oldLogin, $loginMatches);

    if ($rc) {
        $login = strtolower($loginMatches[1]);

        if (in_array($login, $newLogins) || strlen($login) < $minUsernameLength) {
            $login .= '.' . $loginMatches[2];
        }
        if (in_array($login, $newLogins) || strlen($login) < $minUsernameLength) {
            $login .= '.' . $loginMatches[3];
        }
        if (in_array($login, $newLogins)) {
            $login .= '.' . $aUser['userId'];
        }
        //echo "Changing user {$oldLogin} to $login...";

        // use the userlib function to change it to what we want
        $userlib->change_login($oldLogin, $login);

        $newLogins[] = $login;

        //echo " done\n";
    } else {
        echo "Not done {$oldLogin}\n";
    }
    ob_flush();
}

$tikilib->set_preference('login_is_email', 'n');
$tikilib->set_preference('login_allow_email', 'y');
$tikilib->set_preference('min_username_length', $minUsernameLength);

echo "All done\n";
