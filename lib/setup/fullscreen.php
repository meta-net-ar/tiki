<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
//this script may only be included - so its better to die if called directly.
if (str_contains($_SERVER['SCRIPT_NAME'], basename(__FILE__))) {
    header('location: index.php');
    exit;
}

if (isset($_GET['fullscreen'])) {
    if ($_GET['fullscreen'] == 'y') {
        $_SESSION['fullscreen'] = 'y';
    } else {
        $_SESSION['fullscreen'] = 'n';
    }
}
if (! isset($_SESSION['fullscreen'])) {
    $_SESSION['fullscreen'] = 'n';
}
