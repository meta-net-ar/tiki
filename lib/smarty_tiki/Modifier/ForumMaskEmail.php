<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace SmartyTiki\Modifier;

use SmartyTiki\TikiSmartyExtensionInterface;
use SmartyTiki\Traits\ModifierStaticFacadeTrait;

/**
 * Smarty modifier forummaskemail
 * -------------------------------------------------------------
 * Purpose:  mask email addresses
 * -------------------------------------------------------------
 */
class ForumMaskEmail implements TikiSmartyExtensionInterface
{
    use ModifierStaticFacadeTrait;

    public static function getSmartyName(): string
    {
        return 'forummaskemail';
    }

    /**
     * Handle the modifier
     *
     * @param string|null $text The text to process. Can be null, in which case it will be returned as is.
     * @return string
     */
    public function handle($text)
    {
        global $prefs;

        if (! $text) {
            return $text;
        }

        if (($prefs['forum_mask_emails'] ?? 'n') === 'n') {
            return $text;
        }

        preg_match_all('/([a-zA-Z0-9._-]+@[a-zA-Z0-9._-]+\.[a-zA-Z0-9_-]+)/', $text, $match);
        $emailsFound = $match[0] ?? [];

        foreach ($emailsFound as $email) {
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $emailMasked = preg_replace('/(?<=^.{2})[^@]*|(?<=@.{2}).*/', '...', $email);
                $text = str_replace($email, $emailMasked, $text);
            }
        }

        return $text;
    }
}
