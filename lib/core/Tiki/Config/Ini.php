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

    protected $filterSection = null;

    public function setFilterSection($filter)
    {
        $this->filterSection = $filter;
    }

    /**
     * Load configuration from an INI file.
     *
     * @param  string $file
     * @return array
    */
    public function fromFile(string $file): array
    {
        $content = file_get_contents($file);

        // Check if PHP tags are at the top, remove them safely, fail if PHP code found elsewhere
        if (preg_match('/^<\?php\s.*?\?>/s', $content, $matches)) {
            $content = substr($content, strlen($matches[0]));
            if (strpos($content, '<?php') !== false || strpos($content, '?>') !== false) {
                throw new \RuntimeException("The INI file contains unexpected PHP code outside the protection block: {$file}");
            }
        }

        $data = parse_ini_string($content, true, INI_SCANNER_TYPED);
        if ($data === false) {
            throw new \RuntimeException("Failed to parse INI file: {$file}");
        }

        return $this->process($data);
    }

    /**
     * Load configuration from a string containing INI content.
     *
     * @param  string $iniContent
     * @return array
     */
    public function fromString(string $iniContent): array
    {
        $data = parse_ini_string($iniContent, true, INI_SCANNER_TYPED);
        return $this->process($data);
    }

    /**
     * Process data from the parsed ini file.
     *
     * @param  array $data
     * @return array
     */
    protected function process(array $data)
    {
        $data = $this->preProcessSectionInheritance($data);
        $config = $data;
        $config = $this->posProcessSectionInheritance($config);

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
}
