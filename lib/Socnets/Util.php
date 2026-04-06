<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// author : aris002@yahoo.co.uk

namespace Tiki\Lib\Socnets;

//require_once('lib/prefs/sochybrid.php');
/*
if (str_contains($_SERVER['SCRIPT_NAME'], basename(__FILE__))) {
    header('location: index.php');
    exit;
}
*/
use TikiLib;

//this is a universal helper/logger - do not put anything socnets specific
class Util
{
    public static string $logfile = 'tikihybrid3.log';
 // public static string $logfile = 'arilect-com_443.error_log';
    public static string $msgPreffix = 'aris002: ';


    public static function getLogFile()
    {
      //SHIT
      //return pathinfo(ini_get('error_log'),PATHINFO_DIRNAME) . self::$logfile;
      //return dirname(ini_get('error_log')) . self::$logfile;
      //return $custom_error_log_location . self::$logfile;
        return TIKI_PATH . '/temp/' . self::$logfile;
    }

  //TODO would this work with all things static?
    public static function setLogFile($logfile)
    {
        self::$logfile = $logfile;
    }



//should we make a param to exclude index.php and certain files?
    public static function getFileNamesPHP($path)
    {
        $fileNames = [];
        foreach (glob($path) as $file) {
            if (basename($file) === "index.php") {
                continue;
            }
            $fileNames [] = substr(basename($file), 0, -4);
          // or this way more universal? strtok( basename($file), '.' );
        }
        return $fileNames;
    }

//TODO does this work?
    public static function deletePrefsStarts($nameStarts = '')
    {
        $tikiLib = TikiLib::lib('tiki');
        global $prefs;

        $ret = [];
        foreach (array_keys($prefs) as $prefName) {
            if (str_starts_with($prefName, $nameStarts)) {
                $tikiLib->delete_preference($prefName);
                $prefs[$prefName] = null;
                $ret[] = $prefName;
            }
        }

        self::log2('deleted prXXXX: ', $ret);

        return $ret;
    }


    public static function logclear()
    {
        file_put_contents(self::getLogFile(), 'deleted from libs/socnets/Util' . PHP_EOL);
    }

    public static function log($msg)
    {
        $msg = self::$msgPreffix . $msg . PHP_EOL;

        file_put_contents(self::getLogFile(), $msg, FILE_APPEND);
    }

    public static function log2($msg, $msg1 = null)
    {
        if (isset($msg1)) {
            self::log($msg . PHP_EOL . var_export($msg1, true));
        } else {
            self::log($msg);
        }
    }
}
