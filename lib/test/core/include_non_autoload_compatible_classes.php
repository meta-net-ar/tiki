<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/*********************************************************
 * In lib/test/core/bootstrap.php, we define an _autoload()
 * function which, given a class named A_B_C, will automatically
 * look for it in A/B/C.php.
 *
 * But there are many classes in PHP and in Tiki that do not
 * follow that naming convention. So, we need to include them
 * explicitly in this file.
 *********************************************************/

if (strpos($_SERVER['SCRIPT_NAME'], basename(__FILE__)) !== false) {
    header('location: index.php');
    exit;
}


 include_once('lib/diff/difflib.php');
