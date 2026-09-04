<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

/**
 * Cache the page output (page as in http output, not wiki page) using memcache.
 * Important notes:
 * 1- This class will interrupt script processing on cache hit.  See dieAndOutputOrStore
 * 2- This cache uses \Tiki\Cache\MemcacheLib directly, NOT Cachelib, which should probably be changed... benoitg- 2024-02-16
 *
 */
class Tiki_PageCache
{
    /** Default expiration time, in seconds */
    private const DEFAULT_MAX_AGE = 600; //10 minutes

    /**
     * Not well named, this whole array (if not empty) is the cache key.
     *
     * If its null, the cache is currently disabled.
     */
    private ?array $cacheDataKeys = [];
    /** This is the raw memcache key generated from cacheDataKeys */
    private $key;
    /** This is the raw memcache key for the cache metadata */
    private $metaKey = null;
    private $headerLibCopy = null;
    private ?\Tiki\Cache\MemcacheLib $cache = null;

    private function __construct()
    {
        $this->cache = \Tiki\Cache\MemcacheLib::getInstance();
    }

    /** This is NOT a singleton */
    public static function create(): self
    {
        return new self();
    }

    public function disableForRegistered(): self
    {
        global $user;

        if ($user) {
            $this->cacheDataKeys = null;
        }

        return $this;
    }

    public function onlyForGet(): self
    {
        if ($_SERVER['REQUEST_METHOD'] != 'GET') {
            $this->cacheDataKeys = null;
        }

        return $this;
    }

    /**
     * Cache will only be active if the specified preference has value 'y'
     *
     * @param string $preference key, such as memcache_wiki_output
     * @return Tiki_PageCache for chaining
     */
    public function requiresPreference(string $preference): self
    {
        global $prefs;

        if ($prefs[$preference] != 'y') {
            $this->cacheDataKeys = null;
        }

        return $this;
    }

    public function addKeys($array, $keys): self
    {
        if (is_array($this->cacheDataKeys)) {
            foreach ($keys as $k) {
                if (! isset($this->cacheDataKeys[$k])) {
                    $this->cacheDataKeys[$k] = $array[$k] ?? null;
                }
            }
        }

        return $this;
    }

    public function addArray($array): self
    {
        if (is_array($this->cacheDataKeys)) {
            $this->cacheDataKeys = array_merge($this->cacheDataKeys, $array);
        }

        return $this;
    }

    /** Add to the data that will build the key that will store the cache */
    public function addValue($key, $value): self
    {
        if (is_array($this->cacheDataKeys)) {
            $this->cacheDataKeys[$key] = $value;
        }

        return $this;
    }

    /** Aparently this was to check named conditions with an expiration timestamp, but was never finished.  meta is never set in the cache - benoitg - 2024-04-16
     * string $role Identifier of the condition to check
     */
    public function checkMeta($role, $data): self
    {
        $this->metaKey = array_merge([ 'role' => $role ], $data);

        return $this;
    }

    /**
     * On a cache hit, this will completely end processing and return the
     * final http output directly to the browser (it calls php exit)
     * On cache miss, it will silently continue recording output,
     * and store it when the cache destructor is called.
     */
    public function dieAndOutputOrStore(): self
    {
        $debug = false;
        if (is_array($this->cacheDataKeys) && $this->cache->isFunctional()) {
                $this->key = $this->cache->buildKey($this->cacheDataKeys);

            if ($this->metaKey) {
                list($cachedOutput, $maxAge) = $this->cache->getMulti(
                    [
                    $this->key,
                    $this->metaKey,
                    ]
                );
            } else {
                $cachedOutput = $this->cache->get($this->key);
            }
            if ($debug) {
                var_dump("TikiPageCache: " . $this->key, $cachedOutput, $maxAge); //@phpstan-ignore disallowedFunctions.varDump (this is gated debug code)
            }
                $maxAge = $maxAge ?? self::DEFAULT_MAX_AGE;
            if ($cachedOutput) {
                $remainingTime = $maxAge - (time() - $cachedOutput['timestamp']);
                if ($remainingTime <= 0) {
                    if ($debug) {
                        var_dump("TikiPageCache:  Stale cache, $remainingTime seconds remaining"); //@phpstan-ignore disallowedFunctions.varDump (this is gated debug code)
                    }
                    $cachedOutput = null;
                } else {
                    if ($debug) {
                        var_dump("TikiPageCache:  Cache hit, $remainingTime seconds remaining"); //@phpstan-ignore disallowedFunctions.varDump (this is gated debug code)
                    }
                }
            } else {
                if ($debug) {
                    var_dump("TikiPageCache:  Cache miss at " . time()); //@phpstan-ignore disallowedFunctions.varDump (this is gated debug code)
                }
            }

            if ($cachedOutput) {
                /*$headerlib = TikiLib::lib('header');
                if (is_array($cachedOutput['jsfiles'])) {
                foreach ($cachedOutput['jsfiles'] as $rank => $files) {
                    foreach ($files as $file) {
                        $skip_minify = isset($cachedOutput['skip_minify']) ? true : false;
                        $headerlib->add_jsfile_by_rank($file, $rank, $skip_minify);
                    }
                }
                }
                if (is_array($cachedOutput['js'])) {
                foreach ($cachedOutput['js'] as $rank => $js) {
                    foreach ($js as $j) {
                        $headerlib->add_js($j, $rank);
                    }
                }
                }
                if (is_array($cachedOutput['jq_onready'])) {
                foreach ($cachedOutput['jq_onready'] as $rank => $js) {
                    foreach ($js as $j) {
                        $headerlib->add_jq_onready($j, $rank);
                    }
                }
                }
                if (is_array($cachedOutput['css'])) {
                foreach ($cachedOutput['css'] as $rank => $css) {
                    foreach ($css as $c) {
                        $headerlib->add_css($c, $rank);
                    }
                }
                }
                if (is_array($cachedOutput['cssfile'])) {
                foreach ($cachedOutput['cssfile'] as $rank => $css) {
                    foreach ($css as $c) {
                        $headerlib->add_cssfile($c, $rank);
                    }
                }
                }*/

                echo $cachedOutput['output'];
                echo "\n<!-- memcache " . htmlspecialchars($this->key) . "-->";
                exit;
            }

                // save state of headerlib
                //$this->headerLibCopy = unserialize(serialize(TikiLib::lib('header')));

                // Start caching, gather at flush
                ob_start();
        } else {
            if ($debug) {
                    var_dump("TikiPageCache:  Cache disabled"); //@phpstan-ignore disallowedFunctions.varDump (this is gated debug code)
            }
        }
        return $this;
    }

    public function cleanUp(): self
    {
        if ($this->key) {
            $cachedOutput = [
                'timestamp' => time(),
                'output'    => ob_get_contents()
            ];

            //This is to avoid storing the entire output of headerlib before ob_start in memcache.  After calling (at worst the second time) headerlib will generate the same hash and return from cache if it has bundling activated.
            //But I still don't see why we care, we don't output anything from headerlib in dieAndOutputOrStore - benoitg - 2024-02-16
            /*if ($this->headerLibCopy) {
                $headerlib = TikiLib::lib('header');
                $cachedOutput['jsfiles']    = array_diff($headerlib->jsfiles, $this->headerLibCopy->jsfiles);
                $cachedOutput['skip_minify'] = array_diff($headerlib->skip_minify, $this->headerLibCopy->skip_minify);
                $cachedOutput['jq_onready'] = array_diff($headerlib->jq_onready, $this->headerLibCopy->jq_onready);
                $cachedOutput['js']         = array_diff($headerlib->js, $this->headerLibCopy->js);
                $cachedOutput['css']        = array_diff($headerlib->css, $this->headerLibCopy->css);
                $cachedOutput['cssfiles']   = array_diff($headerlib->cssfiles, $this->headerLibCopy->cssfiles);
            }*/

            if ($cachedOutput['output']) {
                $this->cache->set($this->key, $cachedOutput);
                $this->cache->set($this->metaKey, self::DEFAULT_MAX_AGE);
            }
            ob_end_flush();
            return $this;
        }

        $this->cacheDataKeys = [];
        $this->key = null;
        $this->metaKey = null;
        return $this;
    }

    public function invalidate(): self
    {
        if ($this->metaKey) {
            $this->cache->set($this->metaKey, 0);
        }
        return $this;
    }

    public function flush(): self
    {
        return $this->cleanUp();
    }
}
