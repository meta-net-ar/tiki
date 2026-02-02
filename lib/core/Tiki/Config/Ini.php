<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Config;

class Ini
{
    public const SECTION_SEPARATOR = ':';
    public const SECTION_EXTENDS_KEY = ';extends';

    protected ?string $filterSection = null;

    public function setFilterSection(?string $filter): void
    {
        $this->filterSection = $filter;
    }

    public function fromFile(string $file, ?string $identifier = null): array
    {
        $raw = file_get_contents($file);
        $raw = preg_replace('#^\xEF\xBB\xBF#', '', $raw);
        $raw = preg_replace('#^<\?php.*?\?>#s', '', $raw);
        $raw = ltrim($raw);

        $parsed = parse_ini_string($raw, true, INI_SCANNER_RAW);
        if ($parsed === false) {
            throw new \RuntimeException("Failed to parse INI: $file");
        }

        if ($identifier && isset($parsed[$identifier])) {
            $parsed = $parsed[$identifier];
        } elseif (count($parsed) === 1) {
            $first = array_key_first($parsed);
            if (is_array($parsed[$first])) {
                $parsed = $parsed[$first];
            }
        }

        $tree = $this->expandDotNotationKeys(['__root__' => is_array($parsed) ? $parsed : []]);

        $prev = $this->filterSection;
        $this->filterSection = '__root__';
        try {
            return $this->process($tree);
        } finally {
            $this->filterSection = $prev;
        }
    }

    public function fromString(string $iniContent): array
    {
        $data = parse_ini_string($iniContent, true, INI_SCANNER_RAW);
        if ($data === false) {
            throw new \RuntimeException("Failed to parse INI string");
        }
        $data = $this->expandDotNotationKeys($data);
        return $this->process($data);
    }

    protected function process(array $data): array
    {
        $data = $this->preProcessSectionInheritance($data);
        $config = $this->postProcessSectionInheritance($data);

        if ($this->filterSection !== null) {
            return $config[$this->filterSection] ?? [];
        }
        return $config;
    }

    protected function preProcessSectionInheritance(array $data): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            $tokens = explode(self::SECTION_SEPARATOR, (string) $key);
            $section = trim($tokens[0]);
            if (count($tokens) === 2 && is_array($value)) {
                $value[self::SECTION_EXTENDS_KEY] = trim($tokens[1]);
            }
            $result[$section] = $value;
        }
        return $result;
    }

    protected function postProcessSectionInheritance(array $config): array
    {
        $result = [];
        foreach ($config as $key => $value) {
            if (is_array($value) && array_key_exists(self::SECTION_EXTENDS_KEY, $value)) {
                $value = $this->resolveSectionInheritance($config, $key);
            }
            $result[$key] = $value;
        }
        return $result;
    }

    protected function resolveSectionInheritance(array $config, string $section): array
    {
        $result = [];
        if (isset($config[$section][self::SECTION_EXTENDS_KEY])) {
            $parent = $config[$section][self::SECTION_EXTENDS_KEY];
            unset($config[$section][self::SECTION_EXTENDS_KEY]);
            $result = $this->resolveSectionInheritance($config, $parent);
        }
        return array_replace_recursive($result, $config[$section] ?? []);
    }

    private function expandDotNotationKeys(array $data): array
    {
        $result = [];
        foreach ($data as $section => $values) {
            if (! is_array($values)) {
                $result[$section] = $values;
                continue;
            }
            $result[$section] ??= [];
            foreach ($values as $key => $value) {
                $this->assignNestedKey($result[$section], (string)$key, $value);
            }
        }
        return $result;
    }

    private function assignNestedKey(array &$array, string $key, mixed $value): void
    {
        if (strpos($key, '.') === false) {
            $array[$key] = $value;
            return;
        }
        $keys = explode('.', $key);
        $ref =& $array;
        while (count($keys) > 1) {
            $k = array_shift($keys);
            if (! isset($ref[$k]) || ! is_array($ref[$k])) {
                $ref[$k] = [];
            }
            $ref =& $ref[$k];
        }
        $ref[array_shift($keys)] = $value;
    }
}
