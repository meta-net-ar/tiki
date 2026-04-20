<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\CookieConsent;

use InvalidArgumentException;

class CookieConsentLib
{
    // Define constants for cookie categories
    public const BUILTIN_COOKIE_CATEGORY_ESSENTIAL = 'essential';
    public const BUILTIN_COOKIE_CATEGORY_ANALYTICS = 'analytics';
    public const BUILTIN_COOKIE_CATEGORY_MARKETING = 'marketing';
    public const BUILTIN_COOKIE_CATEGORY_FUNCTIONAL = 'functional';

    public const COOKIE_CONSENT_NAME = 'cookie_consent';

    private static $cookiePath = '';
    private static $cookieSecure = true; // Use HTTPS

    /**
     * Retrieve available cookie categories information with translations.
     *
     * @return array The available cookie categories, including translated names and descriptions.
     */
    public static function getCookieCategories(): array
    {
        return [
            self::BUILTIN_COOKIE_CATEGORY_ESSENTIAL => [
                'name' => tra('Essential'),
                'description' => tra('Necessary cookies for the website to function correctly.')
            ],
            self::BUILTIN_COOKIE_CATEGORY_ANALYTICS => [
                'name' => tra('Analytics'),
                'description' => tra('Cookies that track website usage and performance.')
            ],
            self::BUILTIN_COOKIE_CATEGORY_MARKETING => [
                'name' => tra('Marketing'),
                'description' => tra('Cookies used for targeted advertising and campaigns.')
            ],
            self::BUILTIN_COOKIE_CATEGORY_FUNCTIONAL => [
                'name' => tra('Functional'),
                'description' => tra('Cookies that enable additional functionality, such as personalization and enhanced user experience.')
            ]
        ];
    }

    private static function getDisabledCookieCategoryKeys(): array
    {
        $disabledCategoriesInfo = \TikiLib::lib('prefs')->getPreference('cookie_consent_disable_builtin_categories');
        return $disabledCategoriesInfo['value'];
    }

    /**
     * Stub function to minimize code churn.  In the future this will return all consent categories that were requested
     * by tiki code for the current request.
     *
     * Right now, it will return the categories not included in the temporary cookie_consent_disable_builtin_categories prefs
     *
     * @return array Cookie categories requested by tiki code in the current request.
     */
    public static function getRequestedCookieCategories(): array
    {

        $requestedCategories = self::getCookieCategories();

        $disabledCategories = self::getDisabledCookieCategoryKeys();
        if ($disabledCategories) {
            //Strip out the disabled categories
            foreach ($disabledCategories as $disabledCategoryKey) {
                if (isset($requestedCategories[$disabledCategoryKey])) {
                    unset($requestedCategories[$disabledCategoryKey]);
                } else {
                    \Feedback::error(tr("Category %0 from pref cookie_consent_disable_builtin_categories not found in: %1", $disabledCategoryKey, implode(', ', array_keys($requestedCategories))));
                }
            }
        }
        return $requestedCategories;
    }

    /**
     * Initialize the consent preferences based on existing cookies.
     *
     * @return array The initialized consent preferences.
     */
    public static function initializeConsentPreferences()
    {
        global $tikilib, $user, $prefs;

        $defaultConsentPreferences = [
            'action' => 'customized',
            'consentGiven' => false, // Helps determine if the user has given consent or not
            'categories' => array_map(fn() => false, array_keys(self::getCookieCategories())) // Default to false for all categories
        ];
        $consentPreferences = null;
        /* FIXME:  This is incomplete.  There are at least the following cases to deal with:
            1- The browser cookie is absent, user not logged in.
                * Right now we ask consent systematically (can't improve until we collect the functions that request consent)
                * The consent form is modal, and you can't do anything unless you clear it.  Which you can't. This is stupid, and I can't remember another website that does this.
            2- The browser cookie is present, user not logged in.  No need to re-ask consent, unless new categories requested (which isn't working now, but since we ask for all of them, and it's a bit unlikely to have new categories added beyond the BUILTIN ones, it may be tolerable for the time being)
            3- The browser cookie is absent, user logs in.  Consent needs to be written back.
                * This scenario is impossible now (the consent interface being modal) But it looks like it would work with the 2026-03-24 fixes..
            4- The browser cookie is present, user logs in and had no prefs set.  Consent needs to be migrated over.  This does not work now as far as I can tell
            5- The browser cookie is present, user logs in and had inconsistent prefs set.  This isn't dealt with at all.
            6- User is or isn't logged in, consent is updated manually.  This wasn't working if the user was logged-in.  Fixed with the 2026-03-24 fixes for logged in users.
                * I cannot find where an anonymous user is supposed to do this.

            benoitg - 2026-03-24
        */

        // First, if the user is logged in, try to load their stored preference
        $consentPreferences = self::getUserPreference();
        if ($consentPreferences) {
            // Sync the cookie with the user preference
            self::setConsentPreferences($consentPreferences);
        }

        // Second, try to read the consent cookie from browser cookie
        if (! $consentPreferences) {
            $rawConsentCookie = self::getCookie(self::COOKIE_CONSENT_NAME);
            $consentCookie = $rawConsentCookie !== null ? urldecode($rawConsentCookie) : null;

            if ($consentCookie) {
                $consentPreferences = json_decode(urldecode($consentCookie), true);
                return $consentPreferences;
            }
        }

        // Third, fallback to defaults
        if (! $consentPreferences) {
            $consentPreferences = $defaultConsentPreferences;
        }

        return $consentPreferences;
    }

    private static function getUserPreference(): ?array
    {
        global $tikilib, $user;

        if ($user) {
            $userPreferences = $tikilib->get_user_preference($user, 'cookie_consent_user_pref', '');
            if ($userPreferences) {
                $consentPreferences = json_decode($userPreferences, true);
                if (is_array($consentPreferences)) {
                    return $consentPreferences;
                }
            }
        }
        return null;
    }

    /**
     * Get the current user consent preferences.
     *
     * @param string|null $category The category to check (e.g., 'analytics'). If null, returns the main consent preferences.
     * @return array|null User preferences for the specified category, or the main consent preferences if no category is specified. Returns null if no preferences are set.
     */
    public static function getConsentPreferences(?string $category = null)
    {
        $preferences = self::initializeConsentPreferences();
        return $category ? ($preferences['categories'][$category] ?? $preferences) : $preferences;
    }

    /**
     * Set or update the user consent preferences.
     *
     * @param array $preferences An associative array with consent preferences, e.g.:
     *                           [
     *                               'consentGiven' => 'true',
     *                               'action' => 'acceptAll',
     *                               'categories' => [
     *                                   'essential' => true,
     *                                   'analytics' => true,
     *                                   'marketing' => false
     *                               ]
     *                           ]
     * @return void
     */
    public static function setConsentPreferences(array $preferences)
    {
        global $prefs, $tikilib, $user;

        // Validate each category
        foreach ($preferences['categories'] as $category => $status) {
            if (! array_key_exists($category, self::getCookieCategories()) || ! is_bool($status)) {
                throw new \InvalidArgumentException('Invalid category or status.');
            }
        }

        $preferences['consentGiven'] = true; // in case trying to update but consentGiven is not set
        // Store the preferences in the cookie
        self::setCookieSection(
            self::COOKIE_CONSENT_NAME,
            json_encode($preferences),
            '',
            time() + (86400 * $prefs['cookie_consent_expires']),
            self::$cookiePath,
            '',
            self::$cookieSecure
        );

        // Save the preferences in the user's prefs if logged in
        if ($user) {
            $tikilib->set_user_preference($user, 'cookie_consent_user_pref', json_encode($preferences));
        }
    }

    /**
     * Check if a specific category is allowed based on current user cookie consent preferences.
     *
     * @param string $category The category to check (e.g., 'analytics').
     * @return bool True if the category is explicitly allowed (true), false otherwise.
     */
    public static function isCategoryAllowed(string $category): bool
    {
        if (! array_key_exists($category, self::getCookieCategories())) {
            throw new \InvalidArgumentException('Invalid category.');
        }
        // throw an error when category not provided
        if (empty($category)) {
            throw new \InvalidArgumentException('Category not provided.');
        }
        $disabledCategories = self::getDisabledCookieCategoryKeys();
        if (isset($disabledCategories[$category])) {
            //The category is disabled, act as if the user refused consent.
            return false;
        }

        //Uncomment to debug effective cookies consent.  The js variable in jqueryTiki isn't really readable from page source.
        //echo "Cookies consent before first check: \n";var_dump(self::getConsentPreferences()); die;

        // Retrieve the stored consent for the given category.
        // getConsentPreferences($category) should return
        // true (allowed), false (refused) or null (not answered).
        $preference = self::getConsentPreferences()['categories'][$category];

        return $preference ?? false;
    }

    /**
     * Set a cookie at runtime with the specified category, if allowed.
     *
     * TODO:  Write a simple setCookie() function to mirror getCookie().  This function's signature is what it is only for legacy reasons - benoitg - 2026-03-25
     *
     * @return bool Returns true if the cookie was set, false if consent was not given.
     */
    public static function tikiSetCookie(
        string $name,
        string $value,
        string $category,
        int $expire = 0,
        string $section = '',
        string $path = '',
        string $domain = '',
        bool $secure = false,
        bool $httpOnly = false
    ): bool {
        // Check if the category is allowed

        if (self::isCategoryAllowed($category)) {
            self::setCookieSection(
                $name,
                $value,
                $section,
                $expire,
                $path,
                $domain,
                $secure
            );
            return true; // Consent was given, cookie was set
        }
        return false; // Consent was NOT given, cookie was NOT set
    }

    private static function setCookieSection($name, $value, $section = '', $expire = 0, $path = '', $domain = '', $secure = '')
    {
        global $feature_no_cookie;  //This global is undocumented, but I can only find one place that assigns it, and it's not a global there!  benoitg - 2026-06-25

        if (TIKI_API) {
            return;
        }

        if ($section) {
            $valSection = self::getCookie($section);
            $name2 = '@' . $name . ':';
            if ($valSection) {
                if (preg_match('/' . preg_quote($name2) . '/', $valSection)) {
                    $valSection  = preg_replace('/' . preg_quote($name2) . '[^@;]*/', $name2 . $value, $valSection);
                } else {
                    $valSection = $valSection . $name2 . $value;
                }
                self::setCookieSection($section, $valSection, '', $expire, $path, $domain, $secure);
            } else {
                $valSection = $name2 . $value;
                self::setCookieSection($section, $valSection, '', $expire, $path, $domain, $secure);
            }
        } else {
            if ($feature_no_cookie) {
                // See note at the begining of fucntion.  As far as I can tell, this is unreachable code - benoitg - 2026-06-25
                $_SESSION['tiki_cookie_jar'][$name] = $value;
            } else {
                setcookie($name, $value, $expire, $path, $domain, $secure);
            }
        }
    }

    public static function getCookie(string $cockie_name): ?string
    {
        $cokie_stored = array_values(array_filter(explode('; ', $_SERVER["HTTP_COOKIE"] ?? ''), function ($cookie) use ($cockie_name) {
            return strpos($cookie, $cockie_name) !== false;
        }));
        return $cokie_stored ? str_replace($cockie_name . '=', '', $cokie_stored[0]) : null;
    }

    /**
     * Revoke all consent by clearing the cookie and user cookie consent pref.
     */
    public static function revokeConsent()
    {
        global $tikilib, $user;
        self::setCookieSection(self::COOKIE_CONSENT_NAME, '', '', time() - 3600, self::$cookiePath, '', self::$cookieSecure);
        if ($user) {
            $tikilib->set_user_preference($user, 'cookie_consent_user_pref', '');
        }
    }
}
