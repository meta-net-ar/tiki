<?php

/**
 * @package tikiwiki
 */

use Tiki\Package\ComposerManager;

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

require_once("tiki-setup.php");

$composerManager = new ComposerManager(__DIR__);
if (! $composerManager->isInstalled('24eme/signaturepdf')) {
    Feedback::error("The SignaturePDF package is not installed. Please install it via the Composer Web Installer.", true);
    echo smarty_function_feedback([], $smarty->getEmptyInternalTemplate());
    exit;
}

require __DIR__ . '/vendor/24eme/signaturepdf/public/index.php';
