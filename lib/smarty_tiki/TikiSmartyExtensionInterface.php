<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace SmartyTiki;

/**
 * Interface for Smarty extensions that declare their Smarty tag/modifier name.
 *
 * Implementing classes must return the name used in templates, e.g. 'icon', 'escape', 'self_link'.
 * This is used by SmartyExtensionMapper to auto-generate the extension mapping.
 */
interface TikiSmartyExtensionInterface
{
    /**
     * Returns the Smarty tag or modifier name as used in templates.
     */
    public static function getSmartyName(): string;
}
