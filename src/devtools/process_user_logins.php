#!/usr/bin/php
<?php

/**
 * Change user logins from email to a more normal username
 * this is a potentially damaging process and should be tested significantly offline first.
 *
 * THERE IS NO UNDO (and you'll need to reindex afterwards)
 * You will need to set some variables below before it will run to avoid accidental usage, then do:
 *
 * `php src/devtools/process_user_logins.php` from the root of your tiki
 *
 * Good luck!
 */

// best to do this while the site is closed!
$bypass_siteclose_check = true;
global $user;

require_once('tiki-setup.php');
$user = TikiLib::lib('user')->getDefaultAdminLogin();

// thanks Kelvin J on http://www.php.net/manual/en/function.in-array.php
function in_arrayi($needle, $haystack)
{
    return in_array(strtolower($needle), array_map('strtolower', $haystack));
}

function processUsers(): void
{
    // ******* SET VARIABLES HERE *****
    // set this to control how many users to process on each run
    $max = 0;
    // minimum username length
    $minUsernameLength = 4;

    // set these to a user tracker id and the login field if you want to use the file (from $profilePictureFieldId) as the user's avatar
    $trackerId = 0;
    $fieldId = 0;
    $profilePictureFieldId = '0';

    if (! $max) {
        die("You need to edit this script and set the \$max var to the number of users to process each time.\n" .
            "The script takes quite a long time to run and there is no undo, so best to review this script and understand how it works before you try it.\n");
    }

    $datenow = date('c');
    echo "{$datenow} Processing user emails to usernames\n";

    /** @var TikiDb_Table $userTable */
    $userTable = TikiDb::get()->table('users_users');

    /** @var TikiLib $tikilib */
    $tikilib = TikiLib::lib('tiki');
    /** @var UsersLib $userlib */
    $userlib = TikiLib::lib('user');
    /** @var TrackerLib $userlib */
    $trklib = TikiLib::lib('trk');
    /** @var UserPrefsLib $userprefslib */
    $userprefslib = TikiLib::lib('userprefs');
    /** @var AvatarLib $avatarlib */
    $avatarlib = TikiLib::lib('avatar');

    $allUsers = $userTable->fetchAll(
        ['userId', 'email', 'login'],
        ['default_admin' => $userTable->not(true)]
    );

    $emailRegex = '/(.*?)@(.*?).([^.]+)$/';

    // keep new logins to check for duplicates
    $newLogins = [$tikilib->getDefaultAdminLogin()];

    // grab all the non-email login users here as some will have registered since the initial processing

    foreach ($allUsers as $aUser) {
        $oldLogin = $aUser['login'];

        $rc = preg_match($emailRegex, $oldLogin, $loginMatches);

        if (! $rc) {
            $newLogins[] = $oldLogin;
        }
    }

    foreach ($allUsers as $aUser) {
        $oldLogin = $aUser['login'];

        $rc = preg_match($emailRegex, $oldLogin, $loginMatches);

        if ($rc) {
            $login = strtolower($loginMatches[1]);

            if (in_arrayi($login, $newLogins) || strlen($login) < $minUsernameLength) {
                $login .= '.' . $loginMatches[2];
            }
            if (in_arrayi($login, $newLogins) || strlen($login) < $minUsernameLength) {
                $login .= '.' . $loginMatches[3];
            }
            if (in_arrayi($login, $newLogins)) {
                $login .= '.' . $aUser['userId'];
            }
            echo "Changing user {$oldLogin} to $login...";

            // use the userlib function to change it to what we want
            $userlib->change_login($oldLogin, $login);

            $newLogins[] = $login;

            echo " done\n";

            if (count($newLogins) > $max) {
                echo "Only processing the first $max username as it takes too long\n";
                break;
            }
        } else {
            $login = $oldLogin;
            if (! in_arrayi($login, $newLogins)) {
                $newLogins[] = $login;
            }
            //echo "Not done {$oldLogin}\n";
        }
        ob_flush();

        if (count($newLogins) < $max && $trackerId && $fieldId) {
            // use the user's tracker item photo for the user avatar
            $avatar = $userprefslib->get_user_avatar_img($login);
            if (empty($avatar['avatarData'])) {
                $item = $trklib->get_item($trackerId, $fieldId, $login);
                if (! empty($item[$profilePictureFieldId])) {  // has a photo
                    $fileIds = explode(',', $item[$profilePictureFieldId]);
                    if ($fileIds) {
                        echo "setting up user avatar for $login...";
                        $file = \Tiki\FileGallery\File::id($fileIds[0]);
                        $wrapper = $file->getWrapper();

                        $name = $file->getParam('name');
                        if (strlen($name) > 80) {
                            $name = substr($name, 0, 80);
                        }
                        $avatarlib->setAvatarFromFile($wrapper->getReadableFile(), $login, $name);

                        echo " done\n";
                    }
                }
            }
        }
    }

    $tikilib->set_preference('login_is_email', 'n');
    $tikilib->set_preference('login_allow_email', 'y');
    $tikilib->set_preference('min_username_length', $minUsernameLength);

    echo "All done\n";
}

processUsers();
