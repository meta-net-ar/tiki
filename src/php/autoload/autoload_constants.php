<?php

/** This file is included from composer.json in autoload/files section */

if (strpos($_SERVER['SCRIPT_NAME'], basename(__FILE__)) !== false) {
    return;
}

require_once(__DIR__ . '/../../../path_constants.php');
require_once(__DIR__ . '/../../../php_version_constants.php');
