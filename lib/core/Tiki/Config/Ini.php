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

    public function fromFile(string $filename, ?string $section = null): array
    {
        if (! is_readable($filename)) {
            throw new \RuntimeException("File '$filename' is not readable");
        }

        $data = parse_ini_file($filename, true, INI_SCANNER_TYPED);

        if ($data === false) {
            throw new \RuntimeException("Failed to parse ini file '$filename'");
        }

        $data = $this->preProcessSectionInheritance($data);
        $data = $this->expandDotNotationKeys($data);
        $data = $this->posProcessSectionInheritance($data);

        if ($section !== null) {
            if (array_key_exists($section, $data)) {
                return $data[$section];
            } else {
                return [];
            }
        }

        return $data;
    }

    public function fromString(string $content, ?string $section = null): array
    {
        $data = parse_ini_string($content, true, INI_SCANNER_TYPED);

        if ($data === false) {
            throw new \RuntimeException("Failed to parse ini string");
        }

        $data = $this->preProcessSectionInheritance($data);
        $data = $this->expandDotNotationKeys($data);
        $data = $this->posProcessSectionInheritance($data);

        if ($section !== null) {
            if (array_key_exists($section, $data)) {
                return $data[$section];
            } else {
                return [];
            }
        }

        return $data;
    }

    private function preProcessSectionInheritance(array $data): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            $tokens = explode(self::SECTION_SEPARATOR, (string)$key);
            $section = trim($tokens[0]);
            if (count($tokens) == 2 && is_array($value)) {
                $value[self::SECTION_EXTENDS_KEY] = trim($tokens[1]);
            }
            $result[$section] = $value;
        }
        return $result;
    }

    private function posProcessSectionInheritance(array $config): array
    {
        $result = [];

        foreach ($config as $key => $value) {
            if (is_array($value) && array_key_exists(self::SECTION_EXTENDS_KEY, $value)) {
                $value = $this->resolveSectionInheritance($config, (string)$key);
            }
            $result[$key] = $value;
        }
        return $result;
    }

    private function resolveSectionInheritance(array $config, string $section): array
    {
        $result = [];

        if (array_key_exists(self::SECTION_EXTENDS_KEY, $config[$section])) {
            $parentSection = $config[$section][self::SECTION_EXTENDS_KEY];
            unset($config[$section][self::SECTION_EXTENDS_KEY]);
            $result = $this->resolveSectionInheritance($config, $parentSection);
        }
        $result = array_replace_recursive($result, $config[$section]);

        return $result;
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
        if (str_contains($key, '.')) {
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
        } else {
            $array[$key] = $value;
        }
    }
}
