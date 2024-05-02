<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

/**
 * Cache the page output (page as in http output, not wiki page) using memcache.
 * Important notes:
 * 1- This class will interrupt script processing on cache hit.  See dieAndOutputOrStore
 * 2- This cache uses Memcachelib directly, NOT Cachelib, which should probaly be changed... benoitg- 2024-02-16
 *
 */
class Tiki_PageCache
{
    /**
     * Not well named, this whole array (if not empty) is the cache key.
     *
     * If its null, the cache is currently disabled.
     */
    private $cacheDataKeys = [];
    /** This is the raw memcache key generated from cacheDataKeys */
    private $key;
    private $meta = null;
    private $headerLibCopy = null;

    public static function create()
    {
        return new self();
    }

    public function disableForRegistered()
    {
        global $user;

        if ($user) {
            $this->cacheDataKeys = null;
        }

        return $this;
    }

    public function onlyForGet()
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
    public function requiresPreference(string $preference): Tiki_PageCache
    {
        global $prefs;

        if ($prefs[$preference] != 'y') {
            $this->cacheDataKeys = null;
        }

        return $this;
    }

    public function addKeys($array, $keys)
    {
        if (is_array($this->cacheDataKeys)) {
            foreach ($keys as $k) {
                if (! isset($this->cacheDataKeys[$k])) {
                    $this->cacheDataKeys[$k] = isset($array[$k]) ? $array[$k] : null;
                }
            }
        }

        return $this;
    }

    public function addArray($array)
    {
        if (is_array($this->cacheDataKeys)) {
            $this->cacheDataKeys = array_merge($this->cacheDataKeys, $array);
        }

        return $this;
    }

    public function addValue($key, $value)
    {
        if (is_array($this->cacheDataKeys)) {
            $this->cacheDataKeys[$key] = $value;
        }

        return $this;
    }

    public function checkMeta($role, $data)
    {
        $this->meta = array_merge([ 'role' => $role ], $data);

        return $this;
    }

    /**
     * On a cache hit, this will completely end processing and return the
     * final http output directly to the browser (it calls php exit)
     * On cache miss, it will silently continue recording output,
     * and store it when the cache destructor is called.
     *
     * @return void
     */
    public function dieAndOutputOrStore(): Tiki_PageCache
    {
        if (is_array($this->cacheDataKeys)) {
            if (TikiLib::lib("memcache")->isFunctional()) {
                $memcachelib = TikiLib::lib("memcache");
                $this->key = $memcachelib->buildKey($this->cacheDataKeys);

                if ($this->meta) {
                    list($cachedOutput, $metaTime) = $memcachelib->getMulti(
                        [
                            $this->key,
                            $this->meta,
                        ]
                    );

                    if ($cachedOutput && $metaTime && $metaTime > $cachedOutput['timestamp']) {
                        $cachedOutput = null;
                    }
                } else {
                    $cachedOutput = $memcachelib->get($this->key);
                }
                if ($cachedOutput && $cachedOutput['output']) {
                    $headerlib = TikiLib::lib('header');
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
                    }


                    echo $cachedOutput['output'];
                    echo "\n<!-- memcache " . htmlspecialchars($this->key) . "-->";
                    exit;
                }

                // save state of headerlib
                $this->headerLibCopy = unserialize(serialize(TikiLib::lib('header')));

                // Start caching, automatically gather at destruction
                ob_start();
            }
        }

        return $this;
    }

    public function cleanUp()
    {
        if ($this->key) {
            $cachedOutput = [
                'timestamp' => time(),
                'output'    => ob_get_contents()
            ];

            //This is to avoid storing the entire output of headerlib before ob_start in memcache.  After calling (at worst the second time) headerlib will generate the same hash and return from cache if it has bundling activated.
            //But I still don't see why we care, we don't output anything from headerlib in dieAndOutputOrStore - benoitg - 2024-02-16
            if ($this->headerLibCopy) {
                $headerlib = TikiLib::lib('header');
                $cachedOutput['jsfiles']    = array_diff($headerlib->jsfiles, $this->headerLibCopy->jsfiles);
                $cachedOutput['skip_minify'] = array_diff($headerlib->skip_minify, $this->headerLibCopy->skip_minify);
                $cachedOutput['jq_onready'] = array_diff($headerlib->jq_onready, $this->headerLibCopy->jq_onready);
                $cachedOutput['js']         = array_diff($headerlib->js, $this->headerLibCopy->js);
                $cachedOutput['css']        = array_diff($headerlib->css, $this->headerLibCopy->css);
                $cachedOutput['cssfiles']   = array_diff($headerlib->cssfiles, $this->headerLibCopy->cssfiles);
            }

            if ($cachedOutput['output']) {
                TikiLib::lib("memcache")->set($this->key, $cachedOutput);
            }

            ob_end_flush();
        }

        $this->cacheDataKeys = [];
        $this->key = null;
        $this->meta = null;
    }

    public function invalidate()
    {
        if ($this->meta) {
            TikiLib::lib("memcache")->set($this->meta, time());
        }
    }

    public function __destruct()
    {
        $this->cleanUp();
    }
}
