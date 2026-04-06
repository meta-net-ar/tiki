<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Wizard\Pages;

use Wizard;

/**
 * The Wizard's language handler
 */
class ChangesWizardNewIn30 extends Wizard
{
    public function pageTitle()
    {
        return tra('New in Tiki 30');
    }

    public function isEditable()
    {
        return true;
    }

    public function onSetupPage($homepageUrl)
    {
        // Run the parent first
        parent::onSetupPage($homepageUrl);

        // Show if any more specification is needed
        return true;
    }

    public function getTemplate()
    {
        return 'wizard/changes_new_in_30.tpl';
    }

    public function onContinue($homepageUrl)
    {
        // Run the parent first
        parent::onContinue($homepageUrl);
    }
}
