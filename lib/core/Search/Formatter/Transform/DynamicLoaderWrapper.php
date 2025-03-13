<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Search\Formatter\Transform;

class DynamicLoaderWrapper extends \ArrayObject
{
    private $source;
    private $loaded = [];

    public function __construct($entry, $source)
    {
        parent::__construct($entry);
        $this->source = $source;
        $this->loaded['ignored_fields'] = true;
        if (! empty($entry['ignored_fields'])) {
            foreach ($entry['ignored_fields'] as $field) {
                $this->loaded[$field] = true;
            }
        }
    }

    public function offsetGet($name): mixed
    {
        $this->load($name);
        if (isset($this[$name])) {
            return parent::offsetGet($name);
        }
        return null;
    }

    public function offsetExists($name): bool
    {
        return parent::offsetExists($name);
    }

    private function load($name)
    {
        if (isset($this->loaded[$name])) {
            return;
        }

        $this->loaded[$name] = true;
        $data = $this->source->getData($this->getArrayCopy(), $name);

        foreach ($data as $key => $name) {
            $this[$key] = $name;
        }
    }
}
