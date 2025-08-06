<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

require_once("tiki-setup.php");

$serverEndpoint = $_SERVER['HTTP_HOST'] . str_replace('/tiki-signaturepdf.php', '/tiki-signaturepdf_handler.php', $_SERVER['REQUEST_URI']);
$ch = curl_init($serverEndpoint);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    curl_setopt($ch, CURLOPT_POST, true);
    if (! empty($_FILES)) {
        $files = [];
        foreach ($_FILES as $key => $file) {
            if (is_array($file['name'])) {
                foreach ($file['name'] as $index => $name) {
                    $files[$key . '[' . $index . ']'] = new CURLFile($file['tmp_name'][$index], $file['type'][$index], $name);
                }
            } else {
                $files[$key] = new CURLFile($file['tmp_name'], $file['type'], $file['name']);
            }
        }
        $postData = array_merge($_POST, $files);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    } else {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $_POST);
    }
}

$response = curl_exec($ch);
curl_close($ch);

if ($response === false) {
    Feedback::error("Error fetching the page: " . curl_error($ch));
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: '. curl_getinfo($ch, CURLINFO_CONTENT_TYPE));
    echo $response;
    exit;
}

// if the request is for an asset, set the appropriate content type and output the content
$url = preg_replace('/\?.*/', '', $_SERVER['REQUEST_URI']);
if (preg_match('/\.(pdf|jpg|jpeg|png|gif|css|js|mjs|woff2)$/', $url)) {
    $ext = pathinfo($url, PATHINFO_EXTENSION);
    switch ($ext) {
        case 'pdf':
            header('Content-Type: application/pdf');
            break;
        case 'jpg':
        case 'jpeg':
            header('Content-Type: image/jpeg');
            break;
        case 'png':
            header('Content-Type: image/png');
            break;
        case 'gif':
            header('Content-Type: image/gif');
            break;
        case 'css':
            header('Content-Type: text/css');
            break;
        case 'js':
        case 'mjs':
            header('Content-Type: application/javascript');
            break;
        case 'woff2':
            header('Content-Type: font/woff2');
            break;
        default:
            header('Content-Type: text/plain');
            break;
    }
    echo $response;
    exit;
}

$out = preg_replace('#</?body[^>]*>|</?html[^>]*>|#', '', $response);

global $tikiroot;

// alter relative paths (href, src, etc.) to absolute paths
$out = preg_replace_callback(
    '#(href|src|action|.*\.workerSrc)\s*=\s*([\'"])([^\'"]+)\2#',
    function ($matches) use ($tikiroot) {
        $url = $matches[3];
        if (strpos($url, 'http') !== 0 && strpos($url, '#') !== 0) {
            $scheme = (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
            $host = $_SERVER['HTTP_HOST'];
            $origin = $scheme . "://" . $host . $tikiroot;
            $newUrl = $origin . $url;
            return $matches[1] . '=' . $matches[2] . $newUrl . $matches[2];
        }
        return $matches[0];
    },
    $out
);

$smarty->assign('output_data', '<div class="inline-signaturepdf">' . $out . "</div>");

$smarty->assign('mid', 'tiki-signaturepdf.tpl');

$smarty->display('tiki.tpl');
