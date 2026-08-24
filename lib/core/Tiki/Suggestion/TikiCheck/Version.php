<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Suggestion\TikiCheck;

use Tiki\Suggestion\SuggestionRulesInterface as SuggestionRules;

class Version implements SuggestionRules
{
    public function parser(): array
    {
        include_once(__DIR__ . '/../../../../setup/twversion.class.php');
        $TWV = new \TWVersion();
        $versionUtils = new \Tiki_Version_Utils();
        // Suggestions are displayed as plain feedback messages, so only keep the text
        return $versionUtils->getUpgradeMessages($TWV->version);
    }
}
