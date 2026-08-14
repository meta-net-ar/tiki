<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
//this script may only be included - so its better to die if called directly.

namespace Tiki\Lib\Language;

if (str_contains($_SERVER['SCRIPT_NAME'], basename(__FILE__))) {
    header('location: index.php');
    exit;
}
final class LangStringEscaper
{
    public static function addPhpSlashes(string $string): string
    {
        $addPHPslashes = [
            "\n" => '\n',
            "\r" => '\r',
            "\t" => '\t',
            '\\' => '\\\\',
            '$'  => '\$',
            '"'  => '\"'
        ];
        return strtr($string, $addPHPslashes);
    }
}
