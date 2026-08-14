<?php

require_once('tiki-setup.php');

$access->check_feature('cryptpad_feature');

use Tiki\FileGallery\File as TikiFile;

header_remove('Set-Cookie');

function tiki_cp_origin_from_base(string $base): string
{
    $p = parse_url($base);
    if (! $p || empty($p['scheme']) || empty($p['host'])) {
        return '';
    }
    $origin = $p['scheme'] . '://' . $p['host'];
    if (! empty($p['port'])) {
        $origin .= ':' . $p['port'];
    }
    return $origin;
}

$cryptpadBase = trim($prefs['cryptpad_base_url'] ?? '');
$allowOrigin = tiki_cp_origin_from_base($cryptpadBase);
if (! empty($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    if ($allowOrigin) {
        header('Access-Control-Allow-Origin: ' . $allowOrigin);
        header('Vary: Origin');
        header('Access-Control-Allow-Methods: GET, OPTIONS');
        header('Access-Control-Allow-Headers: *');
    }
    http_response_code(204);
    exit;
}

$dataParam = isset($_GET['data']) ? (string) $_GET['data'] : '';
if ($dataParam === '') {
    $access->display_error('', tra('Invalid request'), 400);
}

$payload = Tiki_Security::get()->decode($dataParam);
if (! is_array($payload)) {
    $access->display_error('', tra('Invalid token'), 400);
}

$fileId = (int)($payload['fileId'] ?? 0);
$exp = (int)($payload['exp'] ?? 0);
if ($fileId <= 0 || $exp <= time()) {
    $access->display_error('', tra('Expired or invalid token'), 401);
}

$filegallib = TikiLib::lib('filegal');
$info = $filegallib->get_file($fileId);
if (! is_array($info)) {
    $access->display_error('', tra('File has been deleted'), 404);
}

if ($allowOrigin) {
    header('Access-Control-Allow-Origin: ' . $allowOrigin);
    header('Vary: Origin');
}

header('Cache-Control: private, no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: ' . gmdate('D, d M Y H:i:s', time()) . ' GMT');

$file = new TikiFile($info);
$wrapper = $file->getWrapper();
$filepath = method_exists($wrapper, 'isFileLocal') && $wrapper->isFileLocal() ? $wrapper->getReadableFile() : '';

$type = $info['filetype'] ?: 'application/octet-stream';
header('Content-Type: ' . $type);
$filename = basename($info['filename']);
header('Content-Disposition: filename="' . $filename . '"');

if ($filepath && is_file($filepath)) {
    header('Accept-Ranges: bytes');
    $size = filesize($filepath);
    if ($size !== false) {
        header('Content-Length: ' . $size);
    }
    readfile($filepath);
    exit;
}

$content = $wrapper->getContents();
if (function_exists('mb_strlen')) {
    header('Content-Length: ' . mb_strlen($content, '8bit'));
} else {
    header('Content-Length: ' . strlen($content));
}

echo $content;
