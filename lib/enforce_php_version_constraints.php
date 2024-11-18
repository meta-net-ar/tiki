<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

require_once('php_version_constants.php');

// Check that PHP version is sufficient or if the PHP version is too recent, i.e. higher than the required version.
if (version_compare(PHP_VERSION, TIKI_MIN_PHP_VERSION, '<') || version_compare(PHP_VERSION, TIKI_TOO_RECENT_PHP_VERSION, '>=')) {
    $title = 'PHP >= ' . TIKI_MIN_PHP_VERSION . ' and < ' . TIKI_TOO_RECENT_PHP_VERSION . ' is required';
    if (PHP_SAPI == 'cli') {
        $content = "Wrong PHP version: " . PHP_VERSION . ". " . $title . "\n";
        echo "\033[31m" . $content . "\e[0m\n";
        exit(1);
    }
    $content = '<p>' . "Please contact your system administrator ( if you are not the one ;) ). Your version: " . PHP_VERSION . ' <br /> <br /> ' . '</p>';
    createPage($title, $content);
}

/**
 * creates the HTML page to be displayed.
 *
 * Tiki may not have been installed when we reach here, so we can't use our templating system yet.
 *
 * @param string $title   page Title
 * @param mixed  $content page Content
 */
function createPage($title, $content)
{
    echo <<<END
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta name="robots" content="noindex, nofollow">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <link type="text/css" rel="stylesheet" href="themes/base_files/css/tiki_base.css" />
        <link type="text/css" rel="stylesheet" href="themes/default/css/default.css" />
        <link type="text/css" rel="stylesheet" href="themes/base_files/css/tiki-install.css" />
        <link rel="icon" href="themes/base_files/favicons/favicon.ico" />
        <title>$title</title>
    </head>
    <body class="installer-body">
         <header class="header-main">
            <img alt="Site Logo" src="img/tiki/Tiki_WCG_light.png" class="logo-box" />
            <div class="text-box">
                <div class="heading-text">
                    <h2 class="main-text">$title</h2>
                </div>
            <div class="container">
                <div class="row mb-2">
                    <div class="col col-sm-8 offset-sm-2" id="col1">
                        <div class="mx-auto">
                            $content
                        </div>
                    </div>
                </div>
            </div>
            </div>
            <div style="position:fixed;bottom:1.5em;right:1.5em;z-index:1;">
                <a href="http://tiki.org" target="_blank" title="Powered by Tiki Wiki CMS Groupware"><img src="img/tiki/tikibutton.png" alt="Powered by Tiki Wiki CMS Groupware" /></a>
            </div>
        </div>
    </body>
</html>
END;

    die;
}
