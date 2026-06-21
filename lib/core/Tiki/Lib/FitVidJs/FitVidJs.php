<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\FitVidJs;

class FitVidJs
{
    public static function getCustomSelector(): string
    {
        global $prefs;

        $domains = $prefs['jquery_fitvidjs_additional_domains'];

        if (empty($domains)) {
            return '';
        }

        $domains = explode("\n", $domains);
        $customSelectors = array_map(
            function ($domain) {
                $domain = trim($domain);
                return "iframe[src*='{$domain}']";
            },
            $domains
        );

        $customSelectors = implode(', ', $customSelectors);

        return '{players: "' . $customSelectors . '"}';
    }
}
