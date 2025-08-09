<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

require_once("tiki-setup.php");

$access->check_feature('feature_signaturepdf');
$access->check_user($user);

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

$headers = [];

curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($curl, $headerLine) use (&$headers) {
    $parts = explode(':', $headerLine, 2);
    if (count($parts) === 2) {
        $key = strtolower(trim($parts[0]));
        $value = trim($parts[1]);
        $headers[$key] = $value;
    }
    return strlen($headerLine);
});

$response = curl_exec($ch);
curl_close($ch);

if ($response === false) {
    Feedback::error("Error fetching the page: " . curl_error($ch));
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' || isAssetRequest()) {
    // For POST requests and asset requests, set the appropriate headers and output the response irectly
    foreach ($headers as $key => $value) {
        header("$key: $value");
    }
    echo $response;
    exit;
}

function isAssetRequest()
{
    $url = preg_replace('/\?.*/', '', $_SERVER['REQUEST_URI']);
    return preg_match('/\.(pdf|jpg|jpeg|png|gif|svg|css|js|mjs|woff2|ttf)$/', $url);
}

$out = preg_replace('#</?body[^>]*>|</?html[^>]*>|#', '', $response);

global $tikiroot;

$scheme = (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
$absoluteUrlBase = $scheme . "://" . $_SERVER['HTTP_HOST'] . $tikiroot;

// alter relative paths (href, src, etc.) to absolute paths
$out = preg_replace_callback(
    '#(href|src|action|.*\.workerSrc|url_font)\s*=\s*([\'"])([^\'"]+)\2#',
    function ($matches) use ($absoluteUrlBase) {
        $url = $matches[3];
        if (strpos($url, 'http') !== 0 && strpos($url, '#') !== 0) {
            $newUrl = $absoluteUrlBase . $url;
            return $matches[1] . '=' . $matches[2] . $newUrl . $matches[2];
        }
        return $matches[0];
    },
    $out
);
// css font url() paths
$out = preg_replace_callback(
    '#url\(\s*([\'"]?)([^\'")]+)\1\s*\)#',
    function ($matches) use ($absoluteUrlBase) {
        $url = $matches[2];
        if (strpos($url, 'http') !== 0 && strpos($url, '#') !== 0) {
            $newUrl = $absoluteUrlBase . $url;
            return 'url(' . $matches[1] . $newUrl . $matches[1] . ')';
        }
        return $matches[0];
    },
    $out
);

$smarty->assign('output_data', '<div class="inline-signaturepdf">' . $out . "</div>");

$smarty->assign('mid', 'tiki-signaturepdf.tpl');

$smarty->display('tiki.tpl');
