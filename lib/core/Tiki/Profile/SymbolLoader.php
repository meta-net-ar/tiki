<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Profile;

use ArrayAccess;

/**
 * Dynamically loads and caches profile symbols for usage in templates.
 *
 * The following provides the value for the most recent entry with such name
 * Smarty: {$symbols.some_name}
 * PHP: $symbols['some_name']
 *
 * Common names may appear in multiple profiles. It is possible to narrow them down:
 *
 * Smarty: {$symbols->Profile_Name_Here.some_name}
 * PHP: $symbols->Profile_Name_Here['some_name']
 */
class SymbolLoader implements ArrayAccess
{
    private $store;
    private $filters;
    private $nextFilters;

    public function __construct($store = null, ?array $filters = null, array $nextFilters = ['profile', 'domain'])
    {
        $this->store = $store ?: new SymbolLoaderStore();
        $this->nextFilters = $nextFilters;
        $this->filters = $filters ?: [
            'profile' => '',
            'domain' => '',
        ];
    }

    #[\ReturnTypeWillChange]
    public function offsetGet($name)
    {
        return $this->store->get($name, $this->filters);
    }

    public function offsetExists($name): bool
    {
        return true;
    }
    public function offsetSet($name, $value): void
    {
    }
    public function offsetUnset($name): void
    {
    }

    public function __get($name)
    {
        $nextFilters = $this->nextFilters;
        $next = array_shift($nextFilters);
        if ($next) {
            $filters = $this->filters;
            $filters[$next] = $name;
            return new self($this->store, $filters, $nextFilters);
        }
    }
}
