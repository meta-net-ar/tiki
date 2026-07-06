<?php

/**
 * @package tikiwiki
 */

use Tiki\Package\ComposerManager;
use Tiki\SignaturePDF\SignaturePDF;

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

require_once("tiki-setup.php");

$access->check_feature('feature_signaturepdf');
$access->check_user($user);

$composerManager = new ComposerManager(__DIR__);
if (! $composerManager->isInstalled('24eme/signaturepdf')) {
    Feedback::errorAndDie(tr("The SignaturePDF package is not installed. Please install it via the Composer Web Installer."), 400);
}

global $prefs, $tikiroot;

$ch = curl_init(SignaturePDF::getHandlerUrl());
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

if ($response === false) {
    Feedback::error("Error fetching the page: " . curl_error($ch));
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' || isAssetRequest()) {
    // For POST requests and asset requests, set the appropriate headers and output the response irectly
    $skipHeaders = ['content-length', 'transfer-encoding', 'content-encoding'];
    foreach ($headers as $key => $value) {
        if (! in_array($key, $skipHeaders)) {
            header("$key: $value");
        }
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

$defaultGalleryId = $prefs['home_file_gallery'];
$canBrowseFileGalleries = Perms::get(['type' => 'file gallery', 'object' => $defaultGalleryId])->view_file_gallery;

if ($prefs['fgal_elfinder_feature'] == 'y') {
    include_once 'lib/jquery_tiki/elfinder/tikiElFinder.php';
    tikiElFinder::loadJSCSS();
}

TikiLib::lib('header')->add_js_module('import "@jquery-tiki/signaturepdf";');

if ($canBrowseFileGalleries && $prefs['feature_file_galleries'] == 'y') {
    TikiLib::lib('header')->add_js_module(<<<JS
    import { handlePickFromFileGalleries, handleSaveToFileGalleries } from "@jquery-tiki/signaturepdf";
    handlePickFromFileGalleries({
        defaultGalleryId: $defaultGalleryId
    });
    handleSaveToFileGalleries();
    JS
    );
}

$smarty->assign('output_data', '<div class="inline-signaturepdf">' . $out . "</div>");

$smarty->assign('mid', 'tiki-signaturepdf.tpl');

$smarty->display('tiki.tpl');
