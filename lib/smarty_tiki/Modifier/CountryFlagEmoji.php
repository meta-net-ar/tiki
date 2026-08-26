<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\Modifier;

use SmartyTiki\TikiSmartyExtensionInterface;

/**
 * Smarty modifier plugin to render a bare emoji flag from a country name.
 *
 * - type:     modifier
 * - name:     countryflagemoji
 * - purpose:  Returns the raw emoji character(s) for a given Tiki country name.
 *             Use this inside <option> elements where HTML tags cannot be rendered.
 *
 * Example: {$country|countryflagemoji}
 */
class CountryFlagEmoji implements TikiSmartyExtensionInterface
{
    public static function getSmartyName(): string
    {
        return 'countryflagemoji';
    }

    public function handle(string $countryName): string
    {
        return \Tiki\CountryFlagHelper::toEmoji($countryName);
    }
}
