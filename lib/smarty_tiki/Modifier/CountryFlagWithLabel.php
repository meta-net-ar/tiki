<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\Modifier;

use SmartyTiki\TikiSmartyExtensionInterface;

/**
 * Smarty modifier plugin to render a country flag emoji from a country name.
 *
 * - type:     modifier
 * - name:     countryflagwithlabel
 * - purpose:  Returns the emoji flag for a given Tiki country name (e.g. 'France', 'United_Kingdom'),
 *             together with the translated country name as an accessible label. The label is
 *             exposed through the title and aria-label attributes, it is not rendered as text.
 *             Use countryflagemoji instead when the surrounding markup already provides a label.
 *
 * Example: {$country|countryflagwithlabel}
 */
class CountryFlagWithLabel implements TikiSmartyExtensionInterface
{
    public static function getSmartyName(): string
    {
        return 'countryflagwithlabel';
    }

    public function handle(string $countryName): string
    {
        if (empty($countryName) || $countryName === 'None' || $countryName === 'Other') {
            return '';
        }
        $label = tra(strtr($countryName, '_', ' '));
        return \Tiki\CountryFlagHelper::toHtml($countryName, $label);
    }
}
