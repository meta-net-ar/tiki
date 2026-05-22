<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Tiki_Config_Ini extends Laminas\Config\Reader\Ini
{
    public const SECTION_SEPARATOR = ':';
    public const SECTION_EXTENDS_KEY = ';extends';

    protected $filterSection = null;

    public function setFilterSection($filter)
    {
        $this->filterSection = $filter;
    }

    /**
     * Process data from the parsed ini file.
     *
     * @param  array $data
     * @return array
     */
    protected function process(array $data): array
    {
        $data = $this->preProcessSectionInheritance($data);
        $config = parent::process($data);
        $config = $this->posProcessSectionInheritance($config);
        $config = $this->expandDotNotationKeys($config);

        if (! is_null($this->filterSection)) {
            if (array_key_exists($this->filterSection, $config)) {
                return $config[$this->filterSection];
            } else {
                return [];
            }
        }

        return $config;
    }

    protected function preProcessSectionInheritance(array $data)
    {
        $result = [];

        foreach ($data as $key => $value) {
            $tokens = explode(self::SECTION_SEPARATOR, $key);
            $section = trim($tokens[0]);
            if (count($tokens) == 2 && is_array($value)) {
                $value[self::SECTION_EXTENDS_KEY] = trim($tokens[1]);
            }
            $result[$section] = $value;
        }
        return $result;
    }

    protected function posProcessSectionInheritance(array $config)
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

    protected function resolveSectionInheritance($config, $section)
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

    /**
     * Expand dot-notation keys within each section into nested arrays.
     * Also handles top-level dot keys (outside any section), e.g.:
     *   preference.feature_wiki = y  =>  ['preference' => ['feature_wiki' => 'y']]
     *
     * @param  array $data
     * @return array
     */
    protected function expandDotNotationKeys(array $data): array
    {
        $result = [];
        foreach ($data as $section => $values) {
            if (! is_array($values)) {
                // This is a top-level flat key (no section), e.g. preference.feature_wiki = y
                $this->assignNestedKey($result, (string)$section, $values);
                continue;
            }
            // Section context: expand dot keys within the section's key-value pairs
            if (! isset($result[$section])) {
                $result[$section] = [];
            }
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
            $ref = &$array;
            while (count($keys) > 1) {
                $k = array_shift($keys);
                if (! isset($ref[$k]) || ! is_array($ref[$k])) {
                    $ref[$k] = [];
                }
                $ref = &$ref[$k];
            }
            $ref[array_shift($keys)] = $value;
        } else {
            $array[$key] = $value;
        }
    }
}
