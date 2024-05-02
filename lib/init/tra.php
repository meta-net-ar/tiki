<?php

/**
 * Tiki translation functions
 *
 * @package TikiWiki
 * @subpackage lib\init
 * @copyright (c) Copyright by authors of the Tiki Wiki CMS Groupware Project. All Rights Reserved. See copyright.txt for details and a complete list of authors.
 * @licence Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
 */

 require_once('lib/language/LanguageTranslator.php');

/**
 * Translate an English string.  First parameter is the string.  Every other parameter are parameters to be substituted for the other arguments of tr.
 * tr("Processing %0 of %1 things", 4, 10) will return "Processing 4 of 10 things" translated in the current language
 * @param string $content English string to be translated.  Can contain parameter (%0, %1, etc.) for which the other arguments of tr will be substituted.
 * @return string
 */
function tr($content): string
{
    $args = func_get_args();
    return tra($content, '', false, array_slice($args, 1));
}

/**
 * translate an English string
 * ex:  tra("Processing %0 of %1 things", 'fr', null, [4,10]) will return "Processing 4 of 10 things" translated in french
 * @param string $content English string to be translated.  Can contain parameter (%0, %1, etc.) for which the content of the args array will be substituted.
 * @param string $lg language - if not specified = global current language
 * @param bool   $unused Legacy positional parameter, seems to have been used for interactive translation at some point in the past
 * @param array  $args
 *
 * @return string
 */
function tra($content, ?string $lg = null, $unused = false, array $args = []): string
{
    global $prefs;

    if (empty($lg)) {
        if (! empty($prefs['language'])) {
            $lang = $prefs['language'];
        } elseif (! empty($prefs['site_language'])) {
            $lang = $prefs['site_language'];
        } else {
            $lang = 'en';
        }
    } else {
        $lang = $lg;
    }

    $translator = \I18n\LanguageTranslator::getInstance($lang);
    $out = $translator->translate($content, $args);

    return $out;
}
