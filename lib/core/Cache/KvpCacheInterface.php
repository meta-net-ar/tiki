<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Cache;

interface KvpCacheInterface
{
    /** Is this cache system enabled and functional */
    public function isFunctional(): bool;

    public function cacheItem($key, $data, string $type = '');

    /**
     * @deprecated It does not make sense to do this rather than getCached()
     *
     * @param [type] $key
     * @param string $type A namespace for the value
     * @return boolean
     */
    public function isCached($key, string $type = '');

    public function getCached($key, string $type = '', $lastModif = false);

    public function invalidate($key, string $type = '');

    /**
     * Invalidate all keys of the given type.
     * It is not garanteed that all implementation can restrict the
     * invalidation to only this namespace.
     *
     * @param $type
     * @return void
     */
    public function invalidateAll(string $type);
}
