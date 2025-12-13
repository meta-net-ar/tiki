<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Cache;

//This happens really early in tiki init, autoloading doesn't seem to be available yet
require_once __DIR__ . '/KvpCacheInterface.php';

class NoCache implements KvpCacheInterface
{
    public function isFunctional(): bool
    {
        return true;
    }

    public function cacheItem($key, $data, $type = '')
    {
        return false;
    }

    public function isCached($key, $type = '')
    {
        return false;
    }

    public function getCached($key, $type = '', $lastModif = false)
    {
        return false;
    }

    public function invalidate($key, $type = '')
    {
        return false;
    }

    public function invalidateAll($type)
    {
        return false;
    }
}
