<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

// API wrapper for testing - handles $_GET setup from QUERY_STRING

// Suppress warnings and notices during API tests to prevent them from breaking JSON output
// Only errors and above will be reported
error_reporting(E_ERROR | E_PARSE);

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
        } elseif (strpos($contentType, 'multipart/form-data') !== false) {
            if (preg_match('/boundary=(.*)$/', $contentType, $matches)) {
                $boundary = trim($matches[1]);
                $parts = explode('--' . $boundary, $input);

                foreach ($parts as $part) {
                    if (empty(trim($part)) || $part === '--') {
                        continue;
                    }

                    if (preg_match('/Content-Disposition:.*?name=\"([^\"]+)\"(?:; filename=\"([^\"]+)\")?/', $part, $dispMatch)) {
                        $name = $dispMatch[1];
                        $filename = $dispMatch[2] ?? null;
                        
                        // Split on \r\n\r\n to separate headers from content
                        $partPieces = preg_split('/\r\n\r\n/', $part, 2);
                        $value = $partPieces[1] ?? '';

                        if ($filename) {
                            $_POST[$name] = base64_encode($value);
                            $_POST['name'] = $_POST['filename'] = $filename;
                            $_POST['size'] = strlen($value);
                            $_POST['type'] = 'application/octet-stream';
                        } else {
                            // For regular form fields, trim the value
                            $_POST[$name] = trim($value);
                        }
                    }
                }
            }
        }
    }
}

require_once('tiki-api.php');
