<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Cache;

use TikiLib;

//This happens really early in tiki init, autoloading doesn't seem to be available yet
require_once __DIR__ . '/KvpCacheInterface.php';
/**
 * This is just a thin wrapper around Tiki\Cache\MemcacheLib which for historical reasons has a different API from KvpCacheInterface
 */
class Memcache implements KvpCacheInterface
{
    private function getKey($key, $type)
    {
        return $type . md5($key);
    }

    public function isFunctional(): bool
    {
        return \Tiki\Cache\MemcacheLib::getInstance()->isFunctional();
    }

    public function cacheItem($key, $data, $type = '')
    {
        \Tiki\Cache\MemcacheLib::getInstance()->set($this->getKey($key, $type), $data);
        return true;
    }

    public function isCached($key, $type = '')
    {
        return false;
    }

    public function getCached($key, $type = '', $lastModif = false)
    {
        return \Tiki\Cache\MemcacheLib::getInstance()->get($this->getKey($key, $type));
    }

    public function invalidate($key, $type = '')
    {
        return \Tiki\Cache\MemcacheLib::getInstance()->delete($this->getKey($key, $type));
    }

    public function invalidateAll($type)
    {
        return \Tiki\Cache\MemcacheLib::getInstance()->flush();
    }
}
