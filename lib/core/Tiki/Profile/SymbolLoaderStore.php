<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Profile;

use TikiDb;
use TikiLib;

class SymbolLoaderStore
{
    public const KEY = 'profile_symbols_lookup';
    private $data = false;

    public function get($name, $filters)
    {
        $this->loadData();
        $profile = $filters['profile'];
        $domain = $filters['domain'];

        if (! isset($this->data[$domain][$profile][$name])) {
            $this->data[$domain][$profile][$name] = $this->fetch($name, $filters);

            // Storage should be done at most once per request, but this will only be possible
            // in 13 reliably
            $this->storeData();
        }

        return $this->data[$domain][$profile][$name];
    }

    public function fetch($name, $filters)
    {
        $filters = array_filter($filters);
        $filters['object'] = $name;

        $table = TikiDb::get()->table('tiki_profile_symbols');
        return $table->fetchOne('value', $filters, 'creation_date_desc');
    }

    private function loadData()
    {
        if ($this->data !== false) {
            return;
        }

        $cache = TikiLib::lib('cache');
        if (! $data = $cache->getSerialized(self::KEY)) {
            $data = [];
        }

        $this->data = $data;
    }

    private function storeData()
    {
        $cache = TikiLib::lib('cache');
        $cache->cacheItem(self::KEY, serialize($this->data));
    }
}
