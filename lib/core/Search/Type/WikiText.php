<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Search_Type_WikiText implements Search_Type_Interface
{
    private $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    public static function stripTagsMaintainWords(string $html): string
    {
        /** Those html elements can sometimes be found in the middle of words */
        $inlineElementsNoSpace = [
            'b',
            'big',
            'em',
            'i',
            'small',
            'sub',
            'sup',
            'tt'
        ];

        /*
        //The following works fine, is very safe, but may be slow enough to be significant for indexing time.  Need to experiment rewriting with a negative lookahead regex (such as (?!ignoreme|ignoreme2|ignoremeN))
        static $purifier = null;
        if (! $purifier) {
            $config = HTMLPurifier_Config::createDefault();
            //These might be in the middle of words, and may not imply a word break
            $config->set('HTML.ForbiddenElements', $inlineElementsNoSpace);
            $purifier = new HTMLPurifier($config);
        }
        $htmlNoInline = $purifier->purify($html);*/

        //We all not in $inlineElementsNoSpace imply a word break (ex:  <td>word1</td><td>word2</td>), so we add a space

        //Regex doc: Replace an opening bracket by a space and a bracket, but not if it's followed by one of $inlineElementsNoSpace and e spece or a closing bracket.  This cannot be simplified further, otherwise the b to match some<b>thing</b> will also exclude <br/>
        $htmlSpaceBeforeTags = preg_replace("/<(?!(" . implode('|', $inlineElementsNoSpace) . ")(>|\s))/", ' <', $html);

        $plaintext = strip_tags($htmlSpaceBeforeTags);
        $plaintextNoDoubleSpace = str_replace('  ', ' ', $plaintext);
        return trim($plaintextNoDoubleSpace, " \n\r\t\v\x00");
    }

    public function getValue()
    {
        global $prefs, $pluginIncludeNumberOfInclusions;
        $pluginIncludeNumberOfInclusions = [];

        try {
            $out = TikiLib::lib('parser')->parse_data(
                $this->value,
                [
                    'parsetoc' => false,
                    'indexing' => true,
                    'exclude_plugins' => $prefs['unified_excluded_plugins'],
                    'include_plugins' => $prefs['unified_included_plugins'],
                ]
            );
        } catch (Throwable $e) {
            TikiLib::lib('errortracking')->captureException($e);
            $msg = "{$e->getMessage()} (Initially thrown in {$e->getFile()}:{$e->getLine()})";
            trigger_error($msg);
            $out = $e->getMessage();
        }

        return self::stripTagsMaintainWords($out);
    }

    public function filter(array $filters)
    {
        $value = $this->value;

        foreach ($filters as $f) {
            $value = $f->filter($value);
        }

        return new self($value);
    }
}
