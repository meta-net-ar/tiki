<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

// This plugin takes a block of Tiki content and sorts it line-wise.

function wikiplugin_sort_info()
{
    return [
        'name' => tra('Sort'),
        'documentation' => 'PluginSort',
        'description' => tra('Sort lines of text'),
        'prefs' => [ 'wikiplugin_sort' ],
        'body' => tra('Data to sort, one entry per line.'),
        'filter' => 'text',
        'iconname' => 'sort-desc',
        'introduced' => 1,
        'tags' => [ 'basic' ],
        'params' => [
            'sort' => [
                'required' => false,
                'name' => tra('Order'),
                'description' => tra('Set the sort order of lines of content (default is ascending)'),
                'since' => '1',
                'filter' => 'alpha',
                'default' => 'asc',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Ascending'), 'value' => 'asc'],
                    ['text' => tra('Descending'), 'value' => 'desc'],
                    ['text' => tra('Reverse'), 'value' => 'reverse'],
                    ['text' => tra('Shuffle'), 'value' => 'shuffle']
                ]
            ]
        ]
    ];
}



// Do a natural, locale aware and case-insenstitive sort of an array of strings.
//
// "Natural" means that numbers are sorted by their value, not by the digits they contain. For instance, "10" is
// ordered after "2".
//
// The "lang" argument is the language for which to order. Accented characters, like "ä", are ordered with their
// non-accented counterpart, like "a". In the non-locale aware natcasesort() function, accented characters get
// ordered at the end. That's not right for non-English languages.
//
// For the "lang" argument, "false" can be specified. In this case, the function investigates the language of the
// current user and uses that.
//
// For success, true is returned. In case of an error, false is returned. This are the return values of the
// collator_asort() function from the intl extension.

function mb_natcasesort(string $lang, array &$array): bool
{
    if ($lang == false) {
        $tikilib  = TikiLib::lib('tiki');
        $loginlib = TikiLib::lib('login');

        $user = $loginlib->getUser();
        $lang = $tikilib->get_language($user);
    }

    $coll = collator_create($lang);
    collator_set_attribute($coll, Collator::NUMERIC_COLLATION, Collator::ON);
    collator_set_attribute($coll, Collator::CASE_FIRST, Collator::LOWER_FIRST);
    collator_set_attribute($coll, Collator::ALTERNATE_HANDLING, Collator::SHIFTED);

    return collator_asort($coll, $array);
}



function wikiplugin_sort($data, $params)
{
    extract($params, EXTR_SKIP);

    $sort = (isset($sort)) ? $sort : "asc";
    $lines = preg_split("/\n+/", $data, -1, PREG_SPLIT_NO_EMPTY); // separate lines into array

    if ($sort == "asc") {
        // Sort ascending
        mb_natcasesort(false, $lines);
    } elseif ($sort == "desc") {
        // Sort descending
        mb_natcasesort(false, $lines);
        $lines = array_reverse($lines);
    } elseif ($sort == "reverse") {
        // Reverse the lines
        $lines = array_reverse($lines);
    } elseif ($sort == "shuffle") {
        // Shuffle the lines
        srand((float) microtime() * 1000000);
        shuffle($lines);
    }

    reset($lines);

    if (is_array($lines)) {
        $data = implode("\n", $lines);
    }

    $data = trim($data);
    return $data;
}
