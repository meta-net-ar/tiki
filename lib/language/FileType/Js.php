<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

/**
 * @package   Tiki
 * @subpackage    Language
 * Define properties to collect translatable
 * strings from JavaScript and Vue files.
 */
class Language_FileType_Js extends Language_FileType
{
    protected $regexes = [
        'singleQuoted' => '|\Wtra?\s*\(\s*\'(.+?)\'\s*[\),]|s', // strings encapsulated with single quotes
        'doubleQuoted' => '|\Wtra?\s*\(\s*"(.+?)"\s*[\),]|s', // strings encapsulated with double quotes
        'backtickQuoted' => '|\Wtra?\s*\(\s*`(.+?)`\s*[\),]|s' // template literals (backticks)
    ];

    protected $extensions = ['.js', '.vue'];

    protected $cleanupRegexes = [
        "!/\*.*?\*/!s" => '',  // Block comments
        "!^\s*//.*\$!m" => '', // Line comments
        '/\Wtra?\s*\((["\'`])\1\)/' => '', // remove empty calls to tra()
    ];

    /**
     * Post process method for regex that collect single quoted strings.
     */
    public function singleQuoted(array $strings)
    {
        return str_replace("\'", "'", $strings);
    }

    /**
     * Post process method for regex that collect double quoted strings.
     */
    public function doubleQuoted(array $strings)
    {
        foreach ($strings as $key => $string) {
            $strings[$key] = Language::removePhpSlashes($string);
        }

        return $strings;
    }

    /**
     * Post process method for regex that collect backtick quoted strings.
     */
    public function backtickQuoted(array $strings)
    {
        return str_replace("\`", "`", $strings);
    }
}
