<?php

/**
 * PHP built-in server router for dev only.
 * Usage: php -S localhost:8000 doc/devtools/router.php
 */

if (PHP_SAPI !== 'cli-server') {
    header('HTTP/1.0 403 Forbidden');
    echo 'This router is for development use only.';
    exit;
}

$tikiroot = dirname(dirname(__DIR__));

$uri  = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = ltrim($uri, '/');

// Serve static files
if ($path && file_exists($tikiroot . '/' . $path) && is_file($tikiroot . '/' . $path)) {
    return false;
}

// Fix $_SERVER so route.php computes $path and $base correctly.
$_SERVER['SCRIPT_URL'] = '/' . $path;
$_SERVER['PHP_SELF']   = '/route.php';

require $tikiroot . '/route.php';
