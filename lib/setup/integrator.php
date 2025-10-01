<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
if (basename($_SERVER['SCRIPT_NAME']) === basename(__FILE__)) {
    die('This script may only be included.');
}

/*
 * Check location for Tiki Integrator script and setup aux CSS file if needed by repository
 */
include_once('lib/integrator/integrator.php');
if (! str_starts_with($_SERVER['REQUEST_URI'], 'tiki-integrator.php') && isset($_REQUEST['repID'])) {
    // Create instance of integrator
    $integrator = new TikiIntegrator();
    $headerlib->add_cssfile($integrator->get_rep_css($_REQUEST['repID']), 20);
}
