<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

// API wrapper for testing - handles $_GET setup from QUERY_STRING

// Parse QUERY_STRING into $_GET if set
if (! empty($_SERVER['QUERY_STRING'])) {
    parse_str($_SERVER['QUERY_STRING'], $_GET);
}

// Parse POST data from stdin for POST/PUT/PATCH requests
if (in_array($_SERVER['REQUEST_METHOD'] ?? '', ['POST', 'PUT', 'PATCH'])) {
    $input = file_get_contents('php://stdin');
    if (! empty($input)) {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? 'application/json';
        if (strpos($contentType, 'application/json') !== false) {
            $data = json_decode($input, true);
            if ($data) {
                $_POST = array_merge($_POST, $data);
            }
        } elseif (strpos($contentType, 'application/x-www-form-urlencoded') !== false) {
            parse_str($input, $postData);
            $_POST = array_merge($_POST, $postData);
        }
    }
}

require_once('tiki-api.php');
