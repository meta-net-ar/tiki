<?php

/**
 * This checks that composer was installed and otherwise displays a web-friendly error page
 *
 * @package Tiki
 * @copyright (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
 * @licence Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
 */

// this script may only be included - so its better to die if called directly.
// Don't call tiki-setup.php because it does the same test on composer's
// installation and displays a web-ugly error message // which only looks nice in
// command line mode
if (strpos($_SERVER["SCRIPT_NAME"], basename(__FILE__)) !== false) {
    header("location: index.php");
    exit;
}

require_once('lib/enforce_php_version_constraints.php');

if (! file_exists('vendor_bundled/vendor/autoload.php')) {
    $title = "Tiki Installer missing third party software files";
    $content = "<p>Your Tiki is not completely installed because Composer has not been run to fetch package dependencies.</p>";
    $content .= "<p>You need to run <b>sh setup.sh</b> from the command line.</p>";
    $content .= "<p>See <a href='https://doc.tiki.org/Composer' target='_blank' class='text-yellow-inst'>https://doc.tiki.org/Composer</a> for details.</p>";
    createPage($title, $content);
    exit;
}
