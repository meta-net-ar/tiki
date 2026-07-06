<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\SignaturePDF;

class SignaturePDF
{
    public static function getHandlerUrl(): string
    {
        if (self::isDevMode()) {
            return self::getDevUrl();
        } else {
            return self::getProdUrl();
        }
    }

    public static function sanitizeRequestUri(string $requestUri): string
    {
        // Remove the tikiroot from the request URI
        global $tikiroot;
        $requestUri = preg_replace('#^' . preg_quote($tikiroot, '#') . '#', '', $requestUri);

        // Remove the tiki-signaturepdf.php from the request URI
        $requestUri = str_replace('tiki-signaturepdf.php', '', $requestUri);

        return $requestUri;
    }

    private static function isDevMode(): bool
    {
        global $prefs;
        return $prefs['signaturepdf_dev_mode'] == 'y';
    }

    private static function getDevUrl(): string
    {
        global $prefs;
        $devPort = $prefs['signaturepdf_dev_port'];
        return "http://localhost:$devPort/tiki-base" . self::sanitizeRequestUriDev($_SERVER['REQUEST_URI']);
    }

    private static function sanitizeRequestUriDev(string $requestUri): string
    {
        // Remove the tikiroot from the request URI
        global $tikiroot;
        $requestUri = preg_replace('#^' . preg_quote($tikiroot, '#') . '#', '', $requestUri);

        // Remove the tiki-signaturepdf.php from the request URI
        $requestUri = str_replace('tiki-signaturepdf.php', '', $requestUri);

        return $requestUri;
    }

    private static function getProdUrl(): string
    {
        return $_SERVER['HTTP_HOST'] . str_replace('/tiki-signaturepdf.php', '/tiki-signaturepdf_handler.php', $_SERVER['REQUEST_URI']);
    }
}
