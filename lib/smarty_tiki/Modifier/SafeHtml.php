<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\Modifier;

use SmartyTiki\TikiSmartyExtensionInterface;

class SafeHtml implements TikiSmartyExtensionInterface
{
    public static function getSmartyName(): string
    {
        return 'safe_html';
    }

    /**
     * @param string|null $string The HTML string to sanitize.
     * @return string The sanitized, safe HTML.
     */
    public function handle(?string $string): string
    {
        if (empty($string)) {
            return '';
        }

        // Use a static instance to ensure the expensive purifier setup runs only once per request.
        static $purifier = null;
        if ($purifier === null) {
            require_once('lib/htmlpurifier_tiki/HTMLPurifier.tiki.php');
            $config = \HTMLPurifier_HTML5Config::inherit(\getHTMLPurifierTikiConfig());

            // Define a strict security policy based on a whitelist of allowed elements and attributes.
            $config->set('HTML.Allowed', 'a[href|title],b,strong,i,em,br,p,u');
            $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true]);

            // Add defense-in-depth attributes for all links.
            $config->set('HTML.TargetBlank', true);
            $config->set('HTML.Nofollow', true);

            $purifier = new \HTMLPurifier($config);
        }

        return $purifier->purify($string);
    }
}
