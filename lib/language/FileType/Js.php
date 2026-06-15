<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Lib\Language\FileType;

/**
 * @package   Tiki
 * @subpackage    Language
 * Define properties to collect translatable
 * strings from JavaScript and Vue files.
 */
class Js extends \Language_FileType
{
    protected $regexes = [
        'singleQuoted' => '|\Wtra?\s*\(\s*\'(.+?)\'\s*[\),]|s', // strings encapsulated with single quotes
        'doubleQuoted' => '|\Wtra?\s*\(\s*"(.+?)"\s*[\),]|s', // strings encapsulated with double quotes
        'backtickQuoted' => '|\Wtra?\s*\(\s*`(.+?)`\s*[\),]|s' // template literals (backticks)
    ];

    protected $extensions = ['.js', '.vue'];

    protected $cleanupRegexes = [
        "!/\*.*?\*/!s" => '',  // Block comments
        "!^\s*//.*$!m" => '', // Line comments
        '/\Wtra?\s*\((["\'\`])\1\)/' => '', // remove empty calls to tra()
    ];

    /**
     * Post process method for regex that collect single quoted strings.
     *
     * @param array $strings
     * @return array
     */
    public function singleQuoted(array $strings): array
    {
        return str_replace("\'", "'", $strings);
    }

    /**
     * Post process method for regex that collect double quoted strings.
     *
     * @param array $strings
     * @return array
     */
    public function doubleQuoted(array $strings): array
    {
        foreach ($strings as $key => $string) {
            $strings[$key] = \Language::removePhpSlashes($string);
        }

        return $strings;
    }

    /**
     * Post process method for regex that collect backtick quoted strings.
     *
     * @param array $strings
     * @return array
     */
    public function backtickQuoted(array $strings): array
    {
        return str_replace("\`", "`", $strings);
    }
}
