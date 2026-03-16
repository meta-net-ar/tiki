<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
use Tiki\Sitemap\Generator;

require_once 'tiki-setup.php';


if ($prefs['sitemap_enable'] !== 'y') {
    Feedback::errorAndDie(tra('Required features: sitemap_enable. If you do not have permission to activate these features, ask the site administrator.'), \Laminas\Http\Response::STATUS_CODE_401);
}

$siteMapFile = basename((string) ($_REQUEST['file'] ?? Generator::BASE_FILE_NAME . '-index.xml'));
$siteMapBase = realpath(Generator::getRelativePath());
$siteMapPath = $siteMapBase . DIRECTORY_SEPARATOR . $siteMapFile;

// filter valid file names
if (
    substr($siteMapFile, -4) !== '.xml'
    || strpbrk($siteMapFile, '/\\') !== false
    || dirname($siteMapPath) !== $siteMapBase
) {
    Feedback::errorAndDie(tra('The sitemap file is not available.'), \Laminas\Http\Response::STATUS_CODE_404);
}

// on fly sitemaps.
// TODO: add cache and should be flushed on item-type changed.
// and the auto Generate Must be skipped when cache is active.
if ($prefs['sitemap_method'] === 'auto') {
    $sitemap = new \Tiki\Sitemap\Generator();

    if ($sitemap->generateSitemap($base_url, $siteMapPath) === false) {
         Feedback::errorAndDie(tra('The sitemap file is not available.'), \Laminas\Http\Response::STATUS_CODE_404);
    }
}

// ensure file existence
if (file_exists($siteMapPath) === false) {
    Feedback::errorAndDie(tra('The sitemap file is not available. Please check <a href="tiki-admin_sitemap.php" class="alert-link"> sitemap administration </a> to configure it.'), \Laminas\Http\Response::STATUS_CODE_404);
}

// serve the manual built sitemap file.
header('Content-Type: application/xml; charset=utf-8');
readfile($siteMapPath);
