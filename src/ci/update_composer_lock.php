<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

/**
 * This tool allows updating vendor_bundled/composer.lock when vendor_bundled/composer.json is updated, and is also used by the CI to warn about dependency issues.
 */

if (isset($_SERVER['REQUEST_METHOD'])) {
    die('Only available through command-line.');
}

$dir = __DIR__;
require __DIR__ . '/vcscommons.php';

$vendorBundledDir = $dir . '/../../vendor_bundled';
$composerLockFile = $dir . '/../../vendor_bundled/composer.lock';
$composerPharFile = $dir . '/../../temp/composer.phar';

if (! is_dir($vendorBundledDir)) {
    error('vendor_bundled folder does not exits');
}

if (! file_exists($composerLockFile)) {
    error('file vendor_bundled/composer.lock not found');
}

if (! file_exists($composerPharFile)) {
    error('file temp/composer.phar not found');
}

$composerLockBefore = file_get_contents($composerLockFile);
$composerOutput = null;
$composerRetval = null;
exec('cd ' . $vendorBundledDir . ' && ../temp/composer.phar update --lock  --no-progress', $composerOutput, $composerRetval);
if ($composerRetval !== 0) {
    error("composer update failed with exit code $composerRetval.  Unable to update and compare composer.lock, see the output above.", $composerRetval);
}

$composerLockAfter = file_get_contents($composerLockFile);

if ($composerLockBefore != $composerLockAfter) {
    important('composer.lock was updated by composer update.  Most likely there were updated packages compatible with the versions specified in vendor_bundled/composer.json, and you likely want to commit vendor_bundled/composer.lock now.');
    exit(1);
} else {
    important('composer.lock is up to date');
    exit(0);
}
