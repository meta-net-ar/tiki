<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Cache;

use Exception;

//This happens really early in tiki init, autoloading doesn't seem to be available yet
require_once __DIR__ . '/KvpCacheInterface.php';

class FileSystem implements KvpCacheInterface
{
    public $folder;

    public function __construct()
    {
        global $tikidomain;
        $this->folder = realpath(TEMP_CACHE_PATH);
        if ($tikidomain) {
            $this->folder .= "/$tikidomain";
        }
        if (! is_dir($this->folder)) {
            mkdir($this->folder);
            chmod($this->folder, 0777);
            $resource = opendir($this->folder);
            if ($resource === false) {
                throw new Exception("Unable to create cache directory {$this->folder}");
            }
        }
    }

    public function isFunctional(): bool
    {
        return true;
    }

    public function cacheItem($key, $data, $type = '')
    {
        $key = $type . md5($key);
        @file_put_contents($this->folder . "/$key", $data);
        return true;
    }

    public function isCached($key, $type = '')
    {
        $key = $type . md5($key);
        return is_file($this->folder . "/$key");
    }

    public function getCached($key, $type = '', $lastModif = false)
    {
        $key = $type . md5($key);
        $file = $this->folder . "/$key";
        if (is_readable($file)) {
            // If a last date is given for cache validity, make sure the file is younger
            if ($lastModif !== false && filemtime($file) < $lastModif) {
                unlink($file);
                return false;
            }

            return @file_get_contents($file);
        } else {
            return false;
        }
    }

    public function invalidate($key, $type = '')
    {
        $key = $type . md5($key);
        if (is_file($this->folder . "/$key")) {
            unlink($this->folder . "/$key");
        }
    }

    public function invalidateAll($type)
    {
        $path = $this->folder;
        $all = opendir($path);
        if ($all === false) {
            throw new Exception("Unable to open cache directory {$this->folder}");
        }
        while ($file = readdir($all)) {
            if (str_starts_with($file, $type)) {
                unlink("$path/$file");
            }
        }
    }
}
