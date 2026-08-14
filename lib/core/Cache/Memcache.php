<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Cache;

use TikiLib;

//This happens really early in tiki init, autoloading doesn't seem to be available yet
require_once __DIR__ . '/KvpCacheInterface.php';

class Memcache implements KvpCacheInterface
{
    private function getKey($key, $type)
    {
        return $type . md5($key);
    }

    public function isFunctional(): bool
    {
        return TikiLib::lib("memcache")->isFunctional();
    }

    public function cacheItem($key, $data, $type = '')
    {
        TikiLib::lib("memcache")->set($this->getKey($key, $type), $data);
        return true;
    }

    public function isCached($key, $type = '')
    {
        return false;
    }

    public function getCached($key, $type = '', $lastModif = false)
    {
        return TikiLib::lib("memcache")->get($this->getKey($key, $type));
    }

    public function invalidate($key, $type = '')
    {
        return TikiLib::lib("memcache")->delete($this->getKey($key, $type));
    }

    public function invalidateAll($type)
    {
        return TikiLib::lib("memcache")->flush();
    }
}
