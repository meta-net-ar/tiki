<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Paths;

class Customization
{
    private static function getCurrentSitePathImpl(bool $wantPublicPath, ?string $pathFragment = null)
    {
        $suffixFragment = $pathFragment ? '/' . $pathFragment : '';
        global $tikidomain;
        if ($tikidomain) {
            $path = ($wantPublicPath ? TIKI_CUSTOMIZATIONS_MULTITIKI_SITES_PUBLIC_PATH : TIKI_CUSTOMIZATIONS_MULTITIKI_SITES_PATH) . '/' . $tikidomain . $suffixFragment;
            //This will check both files and directories
            if (file_exists($path)) {
                return $path;
            }
        }
        $path = ($wantPublicPath ? TIKI_CUSTOMIZATIONS_MULTITIKI_DEFAULT_SITE_PUBLIC_PATH : TIKI_CUSTOMIZATIONS_MULTITIKI_DEFAULT_SITE_PATH) . $suffixFragment;
        if (file_exists($path)) {
            return $path;
        }
        return null;
    }


    public static function getCurrentSitePrivatePath(?string $pathFragment = null): ?string
    {
        return self::getCurrentSitePathImpl(false, $pathFragment);
    }

    public static function getCurrentSitePublicPath(?string $pathFragment = null): ?string
    {
        return self::getCurrentSitePathImpl(true, $pathFragment);
    }

    private static function getSharedPathImpl(bool $wantPublicPath, ?string $pathFragment = null)
    {
        $suffixFragment = $pathFragment ? '/' . $pathFragment : '';
        $path = ($wantPublicPath ? TIKI_CUSTOMIZATIONS_SHARED_PUBLIC_PATH : TIKI_CUSTOMIZATIONS_SHARED_PATH) . $suffixFragment;
        if (file_exists($path)) {
            return $path;
        }
        return null;
    }
    public static function getSharedPrivatePath(?string $pathFragment = null): ?string
    {
        return self::getSharedPathImpl(false, $pathFragment);
    }
    public static function getSharedPublicPath(?string $pathFragment = null): ?string
    {
        return self::getSharedPathImpl(true, $pathFragment);
    }

    public static function getCustomLangFragment()
    {
        global $prefs;
        $language = $prefs['language'];
        return "lang/$language/custom.js";
    }
}
