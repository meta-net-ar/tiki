<?php

/**
 * Tiki's Installation script.
 *
 * Used to install a fresh Tiki instance, to upgrade an existing Tiki to a newer version and to test sendmail.
 *
 * @package TikiWiki
 * @copyright (c) Copyright by authors of the Tiki Wiki CMS Groupware Project. All Rights Reserved. See copyright.txt for details and a complete list of authors.
 * @licence Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
 */

use Tiki\TikiInit;

$in_installer = 1;
define('TIKI_IN_INSTALLER', 1);
if (! isset($title)) {
    $title = 'Tiki Installer';
}
if (! isset($dberror)) {
    $dberror = false;
}

// Show all errors
error_reporting(-1);
ini_set('display_errors', 1);

require_once('lib/init/initlib.php');

$tikipath = __DIR__ . '/';
TikiInit::appendIncludePath($tikipath);
define('TIKI_PATH', $tikipath);

require_once('db/tiki-db.php'); // to set up multitiki etc if there

$lockFile = 'db/' . $tikidomainslash . 'lock';
$authAttemptsFile = 'db/' . $tikidomainslash . 'installer_auth_attempts';


if (! isset($content)) {
    $content = tr('No content specified. Something went wrong.<br/>Please tell your administrator.<br/>If you are the administrator, you may want to check for / file a bug report.');
}

// if tiki installer is locked (probably after previous installation) display notice
if (file_exists($lockFile)) {
    $title = 'Tiki Installer Disabled';
    $td = empty($tikidomain) ? '' : '/' . $tikidomain;
    $content = '
                            <p class="under-text">' . tr("As a security precaution, the Tiki Installer has been disabled. To re-enable the installer:") . '</p>
                                <ol class="installer-ordered-list-style">
                                    <li class="installer-ordered-list"><p>' . tr('Use your file manager application to find the directory where you have unpacked your Tiki and remove the <span class="text-danger font-weight-bold">lock</span> file which was created in the <span class="text-danger font-weight-bold">db</span> folder') . '.</p></li>
                                    <li class="installer-ordered-list"><p>' . tr('Re-run') . ' <strong ><a class="text-yellow-inst" href="tiki-install.php' . (empty($tikidomain) ? '' : "?multi=$tikidomain") . '" title="Tiki Installer">tiki-install.php' . (empty($tikidomain) ? '' : "?multi=$tikidomain") . '</a></strong>.</p></li>
                                </ol>
                            ';
    createPage($title, $content);
}

if (! empty($db) && ! $db->getOne("SELECT COUNT(*) FROM `information_schema`.`character_sets` WHERE `character_set_name` = 'utf8mb4';")) {
    die(tr('Your database does not support the utf8mb4 character set required in Tiki19 and above. You need to upgrade your mysql or mariadb installation.'));
}

$tikiroot = str_replace('\\', '/', dirname($_SERVER['PHP_SELF']));
$session_params = session_get_cookie_params();
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
    $session_params['secure'] = true;
}
session_set_cookie_params($session_params['lifetime'], $tikiroot, $session_params['domain'], $session_params['secure'], $session_params['httponly']);
unset($session_params);
session_start();

$userCanAccessInstaller  = $_SESSION['accessible'] ?? false;
$userAuthenticatedFromDbCredentialsMd5 = $_SESSION['previousAuthMd5'] ?? null;
//Do NOT implicitely clear $_SESSION['accessible'] on a GET request.  A lot of code in tiki redirect in to the installer in some circumstances.  One such is that browsers will try to download /favicon.ico when using this file, which depending on timing will cause this file to be invisibly retrieved.

// Were database details defined before? If so, load them
if (file_exists('db/' . $tikidomainslash . 'local.php')) {
    include 'db/' . $tikidomainslash . 'local.php';
    // In case of replication, ignore it during installer.
    unset($shadow_dbs, $shadow_user, $shadow_pass, $shadow_host);

    // check for provided login details and check against the old, saved details that they're correct
    if (isset($_POST['dbuser'], $_POST['dbpass'])) {
        if (($_POST['dbuser'] == $user_tiki) && ($_POST['dbpass'] == $pass_tiki)) {
            $userCanAccessInstaller = true;
            $userAuthenticatedFromDbCredentialsMd5 = password_hash($user_tiki . $pass_tiki . $dbs_tiki, PASSWORD_DEFAULT);
            unset($_POST['dbuser']);
            unset($_POST['dbpass']);

            if (file_exists($authAttemptsFile)) {
                unlink($authAttemptsFile);
            }
        } else {
            $userCanAccessInstaller = false;
            $userAuthenticatedFromDbCredentialsMd5 = null;
            $attempts = (int)@file_get_contents($authAttemptsFile);

            if (++$attempts >= 10) {
                //Lock the installer
                touch($lockFile);
                unlink($authAttemptsFile);
            } else {
                file_put_contents($authAttemptsFile, $attempts);
            }
        }
    } elseif (isset($_POST['multi']) && $_POST['multi'] === $tikidomain) {
        $userAuthenticatedFromDbCredentialsMd5 =
            password_hash($user_tiki . $pass_tiki . $dbs_tiki, PASSWORD_DEFAULT);
    }

    if ($userAuthenticatedFromDbCredentialsMd5) {
        if (! password_verify($user_tiki . $pass_tiki . $dbs_tiki, $userAuthenticatedFromDbCredentialsMd5)) {
            //The local.php file has changed, or the user and password used to verify the user is otherwise obsolete
            //Note that this will NOT run when the installer started without a local.php file (the user didn't have to authenticate)
            $userCanAccessInstaller = false;
            $userAuthenticatedFromDbCredentialsMd5 = null;
        }
    }
    // Here we continue with whatever was in the session for $userCanAccessInstaller, so the code above and below needs to be correct...
} else {
    // No database configuration found, so it's a first-install and thus installer is accessible to all.  Note that this will persist until the session expires, someone enters a wrong password above or the database configuration changes if it was not created by the installer.
    // In practice this will get cleared at step 9 of the installer when we clear the caches, but it's not ideal.
    $userCanAccessInstaller = true;
    $userAuthenticatedFromDbCredentialsMd5 = null;
}

$_SESSION['accessible'] = $userCanAccessInstaller;
$_SESSION['previousAuthMd5'] = $userAuthenticatedFromDbCredentialsMd5;
if ($userCanAccessInstaller === true) {
    // allowed to access installer, include it
    $logged = true;
    $admin_acc = 'y';
    include_once 'installer/tiki-installer.php';
} else {
    // Installer knows db details but no login details were received for this script.
    // Thus, display a form.
    $title = tr('Tiki Installer Security Precaution');
    $content = '<p class="text-light mt-lg-3 mx-3">' . tr('You are attempting to run the Tiki Installer. For your protection, this installer can be used only by a site administrator.To verify that you are a site administrator, enter your <strong><em>database</em></strong> credentials (database username and password) here.') . '</p>
                <p class="text-light mx-3">' . tr('If you have forgotten your database credentials, find the directory where you have unpacked your Tiki and have a look inside the <strong class="text-yellow-inst">db</strong> folder into the <strong class="text-yellow-inst">local.php</strong> file.') . '</p>
                <form method="post" action="tiki-install.php" class="text-center">
                    <p class="col-6 offset-3"><label for="dbuser" class="sr-only text-white">' . tr("Database username") . '</label> <input type="text" id="dbuser" name="dbuser" class="form-control text-center" placeholder="' . tr('Database username') . '"/></p>
                    <p class="col-6 offset-3"><label for="dbpass" class="sr-only text-white">' . tr("Database password") . '</label> <input type="password" id="dbpass" name="dbpass" class="form-control text-center" placeholder="' . tr('Database password') . '"/></p>
                    <p class="col-6 offset-3"><input type="submit" class="btn btn-primary" value=" ' . tr("Validate and Continue ") . '" /></p>
                </form>
                <p>&nbsp;</p>';

    createPage($title, $content);
}
