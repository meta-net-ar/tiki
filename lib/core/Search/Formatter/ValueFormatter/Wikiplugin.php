<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Search_Formatter_ValueFormatter_Wikiplugin extends Search_Formatter_ValueFormatter_Abstract
{
    private $arguments;

    public function __construct($arguments)
    {
        $this->arguments = $arguments;
    }

    public function render($name, $value, array $entry)
    {
        if (substr($name, 0, 11) !== 'wikiplugin_') {
            return $value;
        } else {
            $name = substr($name, 11);
        }

        if (isset($this->arguments['content'])) {
            $content = $this->replaceFieldNamesWithValues($this->arguments['content'], $entry);
            unset($this->arguments['content']);
        } else {
            $content = '';
        }
        $defaults = [];
        if (isset($this->arguments['default'])) {
            parse_str($this->arguments['default'], $defaults);
        }

        $params = [];
        foreach ($this->arguments as $key => $val) {
            if (isset($entry[$val])) {
                $params[$key] = $entry[$val];
            } elseif (isset($defaults[$key])) {
                $params[$key] = $defaults[$key];
            } elseif ($key !== 'default') {
                $params[$key] = $this->replaceFieldNamesWithValues($val, $entry);
            }
        }

        $parserlib = TikiLib::lib('parser');
        $out = $parserlib->pluginExecute(
            $name,
            $content,
            $params,
            0,
            false,
            [
                'context_format' => 'html',
                'wysiwyg' => false,
                'is_html' => 'y'
            ]
        );

        return '~np~' . $out . '~/np~';
    }

    /**
     * Replaces placeholders surrounded by % chars with the value of that field in the current result
     * e.g. in `[item%object_id%]` %object_id% would be replaced by the current item id
     *
     * @param string $string
     * @param array  $entry
     *
     * @return array
     */
    private function replaceFieldNamesWithValues(string $string, array $entry): string
    {
        $matches = [];

        // look for %field_name% in the content and replace with $entry['field_name']
        if ($rc = preg_match_all('/%(\w+)%/', $string, $matches)) {
            for ($i = 0; $i < $rc; $i++) {
                if (isset($entry[$matches[1][$i]])) {
                    $string = str_replace($matches[0][$i], $entry[$matches[1][$i]], $string);
                }
            }
        }
        return $string;
    }
}
