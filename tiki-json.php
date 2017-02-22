<?php 

require_once('tiki-setup.php');
$method = strtolower($_SERVER['REQUEST_METHOD']) ?: 'get';

// sanitize lib parameter
if (!empty($_REQUEST['lib']) 
    && preg_match('/^\w[\w_-]{2,36}$/', $_REQUEST['lib'])) {
    $lib = $_REQUEST['lib'];
} else {
    header("HTTP/1.0 400 Bad Request");
    header("Content-Type: text/plain; charset=UTF-8");
    die('Error: "lib" parameter is mandatory');
}


// check if lib exists
if ( file_exists("lib/{$lib}/{$lib}lib.php") ) {
    include("lib/{$lib}/{$lib}lib.php");
} else if ( file_exists("lib/{$lib}lib.php") ) {
    include("lib/{$lib}lib.php");
} else {
    header("HTTP/1.0 404 Not Found");
    header("Content-Type: text/plain; charset=UTF-8");
    die('Error: "'.$lib.'" not found');
}


// check if lib implements an output
if( class_exists('TikiJsonResponse') ) {
    $obj = new TikiJsonResponse();
    if ( method_exists($obj, $method) ) {
        ini_set('html_errors', false);
        header('Content-Type: text/plain; charset=UTF-8');
        $response = call_user_func(array($obj, $method));
        echo $response;
        die;
    }
}

header("HTTP/1.0 501 Not Implemented");
header("Content-Type: text/plain; charset=UTF-8");
die('Error: json response for "'.$lib.'" is not implemented');
