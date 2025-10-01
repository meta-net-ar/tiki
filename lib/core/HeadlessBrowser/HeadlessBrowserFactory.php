<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\HeadlessBrowser;

use Tiki\HeadlessBrowser\Exception\HeadlessException;

class HeadlessBrowserFactory
{
    public const CHROME = 'chrome';
    public const CASPERJS = 'casperjs';

    /**
     * Returns the type of headless browser configured.
     *
     * @return string
     */
    public static function getHeadlessBrowserType()
    {
        global $prefs;

        $defaultHeadlessBrowser = self::CHROME;

        $type = $prefs['headlessbrowser_integration_type'] ?? $defaultHeadlessBrowser;

        switch ($type) {  // check $type if one of te defined values or return the default value
            case self::CASPERJS:
                // fall to next
            case self::CHROME:
                return $type;
            default:
                return $defaultHeadlessBrowser;
        }
    }

    public static function getHeadlessBrowser()
    {
        return self::getHeadlessBrowserByType(self::getHeadlessBrowserType());
    }

    public static function getHeadlessBrowserByType($type)
    {
        $headlessBrowserType = ucfirst($type);
        $class = "\\Tiki\\HeadlessBrowser\\$headlessBrowserType";

        if (! class_exists($class)) {
            throw new HeadlessException(tr('Unsupported headless browser integration type: %0, Supported types are: %1, %2', $headlessBrowserType, self::CHROME, self::CASPERJS));
        }

        return new $class();
    }
}
