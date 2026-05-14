<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\Modifier;

/**
 * Smarty modifier plugin to render a country flag emoji from a country name.
 *
 * - type:     modifier
 * - name:     countryflagname
 * - purpose:  Returns the emoji flag for a given Tiki country name (e.g. 'France', 'United_Kingdom')
 *
 * Example: {$country|countryflagname}
 */
class CountryFlagName
{
    public function handle(string $countryName): string
    {
        if (empty($countryName) || $countryName === 'None' || $countryName === 'Other') {
            return '';
        }
        $label = tra(strtr($countryName, '_', ' '));
        return \Tiki\CountryFlagHelper::toHtml($countryName, $label);
    }
}
