<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Config;

class Config implements \ArrayAccess, \IteratorAggregate, \Countable
{
    private array $data;
    private bool $allowModifications;
    private bool $readOnly = false;

    public function __construct(array $data = [], bool $allowModifications = false)
    {
        $this->data = $data;
        $this->allowModifications = $allowModifications;
    }

    public function toArray(): array
    {
        return $this->data;
    }

    public function setReadOnly(): void
    {
        $this->readOnly = true;
    }

    public function merge(self|array $with): void
    {
        $this->assertWritable();
        $array = $with instanceof self ? $with->toArray() : $with;
        $this->data = self::deepMerge($this->data, $array, true);
    }

    public function mergeAddOnly(self|array $with): void
    {
        $this->assertWritable();
        $array = $with instanceof self ? $with->toArray() : $with;
        $this->data = self::deepMerge($this->data, $array, false);
    }

    public function get(string $path, mixed $default = null): mixed
    {
        $ref = $this->data;
        foreach (explode('.', $path) as $seg) {
            if (! is_array($ref) || ! array_key_exists($seg, $ref)) {
                return $default;
            }
            $ref = $ref[$seg];
        }
        return $ref;
    }

    public function __get(string $name): self
    {
        $val = $this->data[$name] ?? [];
        return new self(is_array($val) ? $val : [], false);
    }

    public function __isset(string $name): bool
    {
        return isset($this->data[$name]);
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->data[$offset]);
    }
    public function offsetGet(mixed $offset): mixed
    {
        return $this->data[$offset] ?? null;
    }
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->assertWritable();
        $this->data[$offset] = $value;
    }
    public function offsetUnset(mixed $offset): void
    {
        $this->assertWritable();
        unset($this->data[$offset]);
    }
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->data);
    }
    public function count(): int
    {
        return count($this->data);
    }

    private function assertWritable(): void
    {
        if ($this->readOnly || ! $this->allowModifications) {
            throw new \RuntimeException('Config is read-only');
        }
    }

    private static function deepMerge(array $base, array $extra, bool $overwrite): array
    {
        foreach ($extra as $k => $v) {
            if (array_key_exists($k, $base)) {
                if (is_array($base[$k]) && is_array($v)) {
                    $base[$k] = self::deepMerge($base[$k], $v, $overwrite);
                } elseif ($overwrite) {
                    $base[$k] = $v;
                }
            } else {
                $base[$k] = $v;
            }
        }
        return $base;
    }
}
