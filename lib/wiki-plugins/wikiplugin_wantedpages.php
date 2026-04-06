<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

use Tiki\Lib\Wiki\PluginsLib;

function wikiplugin_wantedpages_info()
{
    return [
        'name' => tra('Wanted Pages'),
        'documentation' => 'PluginWantedPages',
        'description' => tra('Show location of links to pages not yet created'),
        'prefs' => [ 'wikiplugin_wantedpages' ],
        'body' => tr('Custom level regex. A custom filter for wanted pages to be listed (only used when %0). Possible
            values: a valid regex-expression (PCRE).', '<code>level="custom"</code>'),
        'iconname' => 'search',
        'introduced' => 1,
        'tags' => [ 'basic' ],
        'params' => [
            'ignore' => [
                'required' => false,
                'name' => tra('Ignore'),
                'description' => tra('A wildcard pattern of originating pages to be ignored. (refer to PHP function
                    fnmatch() for details)'),
                'since' => '1',
                'accepted' => tra('a valid regex-expression (PCRE)'),
                'default' => '',
                'advanced' => true,
            ],
            'splitby' => [
                'required' => false,
                'name' => tra('Split By'),
                'description' => tra('The character by which ignored patterns are separated.'),
                'since' => '1',
                'default' => '+',
                'advanced' => true,
            ],
            'skipalias' => [
                'required' => false,
                'name' => tra('Skip Alias'),
                'description' => tra('Whether to skip wanted pages that have a defined alias (not skipped by default)'),
                'since' => '12.1',
                'default' => 1,
                'filter' => 'digits',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 1],
                    ['text' => tra('No'), 'value' => 0],
                ],
            ],
            'skipext' => [
                'required' => false,
                'name' => tra('Skip Extension'),
                'description' => tra('Whether to include external wikis in the list (not included by default)'),
                'since' => '1',
                'default' => 0,
                'filter' => 'digits',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 1],
                    ['text' => tra('No'), 'value' => 0],
                ],
            ],
            'collect' => [
                'required' => false,
                'name' => tra('Collect'),
                'description' => tra('Collect either originating (from) or wanted pages (to) in a cell and display them
                    in the second column.'),
                'since' => '1',
                'default' => 'from',
                'filter' => 'alpha',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('From'), 'value' => 'from'],
                    ['text' => tra('To'), 'value' => 'to'],
                ],
            ],
            'debug' => [
                'required' => false,
                'name' => tra('Debug'),
                'description' => tra('Switch on debug output with details about the items (debug not on by default)'),
                'since' => '1',
                'default' => 0,
                'filter' => 'digits',
                'advanced' => true,
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('No'), 'value' => 0],
                    ['text' => tra('Yes'), 'value' => 1],
                    ['text' => tra('Memory Saver'), 'value' => 2],
                    ],
            ],
            'table' => [
                'required' => false,
                'name' => tra('Table'),
                'description' => tra('Multiple collected items are separated in distinct table rows (default), or by
                    comma or line break in one cell.'),
                'since' => '1',
                'filter' => 'alpha',
                'default' => 'sep',
                'accepted' => 'sep, co, br',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Comma'), 'value' => 'co'],
                    ['text' => tra('Line break'), 'value' => 'br'],
                    ['text' => tra('Separate Row'), 'value' => 'sep'],
                ],
            ],
            'level' => [
                'required' => false,
                'name' => tra('Level'),
                'description' => tra('Filter the list of wanted pages according to page_regex or custom filter. The
                    default value is the site\'s __current__ page_regex.'),
                'since' => '1',
                'default' => '',
                'filter' => 'alpha',
                'advanced' => true,
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Custom'), 'value' => 'custom'],
                    ['text' => tra('Full'), 'value' => 'full'],
                    ['text' => tra('Strict'), 'value' => 'strict'],
                ],
            ],
        ],
    ];
}

class WikiPluginWantedPages extends PluginsLib
{
    public function getDefaultArguments()
    {
        return [    'ignore' => '', // originating pages to be ignored
                        'splitby' => '+', // split ignored pages by this character
                        'skipalias' => 1, // true, skip a page alias as a wanted page
                        'skipext' => 0, // false, display external wiki links
                        'collect' => 'from', // display (and sort) wanted pages in the first column,
                        // collect originating pages in the second column (and separate them by table parameter)
                        'table' => 'sep', // show each line of output in a separate table row
                        'level' => '', // use current page_regex to filter output
                        'debug' => 0]; // false, no debug output; a value of 2
                            // tries to allocate as little memory as possible.
    }

    public function getName()
    {
        return 'WantedPages';
    }

    public function getDescription()
    {
        return wikiplugin_wantedpages_info()['description'];
    }

    public function getVersion()
    {
        return preg_replace("/[Revision: $]/", '', "\$Revision: 1.7 $");
    }

    /**
     * Check if a link is escaped (wrapped in ~np~ tags) in the source page
     * @param string $fromPageContent The content of the page containing the link
     * @param string $toPage The target page of the link
     * @return bool True if the link is escaped, false otherwise
     */
    private function isEscapedLink($fromPageContent, $toPage)
    {
        if (empty($fromPageContent)) {
            return false;
        }

        // Check if link is wrapped in ~np~ tags (exact or partial matches within np blocks)
        // Pattern: ~np~...((PageName))...~/np~ and ~np~...(...(PageName))...~/np~
        $quotedPage = preg_quote($toPage, '/');
        $escapedPattern = '/~np~[^~]*?\((?:[a-zA-Z0-9_-]+)?\(' . $quotedPage . '\)\).*?~\/np~/s';

        return preg_match($escapedPattern, $fromPageContent) === 1;
    }

    public function run($data, $params)
    {
        global $prefs, $page_regex;

        // Grab and handle our Tiki parameters...
        extract($params, EXTR_SKIP);
        // for regexes and external wiki details, see tikilib.php
        if ($level == 'strict') {
            $level_reg = '([A-Za-z0-9_])([\.: A-Za-z0-9_\-])*([A-Za-z0-9_])';
        } elseif ($level == 'full') {
            $level_reg = '([A-Za-z0-9_]|[\x80-\xFF])([\.: A-Za-z0-9_\-]|[\x80-\xFF])*([A-Za-z0-9_]|[\x80-\xFF])';
        } elseif ($level == 'complete') {
            $level_reg = '([^|\(\)])([^|\(\)](?!\)\)))*?([^|\(\)])';
        } elseif (($level == 'custom') && ($data != '')) {
            if (preg_ispreg($data)) { // custom regular expression
                $level_reg = $data;
            } elseif ($debug == 2) {
                echo tr('%0: non-valid custom regex', $data) . '<br />';
            }
        } else { // default
            $level_reg = $page_regex;
        }

        // define the separator
        if ($table == 'br') {
            $break = '<br />';
        } elseif ($table == 'co') {
            $break = tra(', ');
        } else {
            $break = 'sep';
        }

        // get array of fromPages to be ignored
        $ignorepages = explode($splitby, $ignore);

        // Currently we only look in wiki pages.
        // Wiki links in articles, blogs, etc are ignored.
        $query = 'SELECT DISTINCT tl.`toPage`, tl.`fromPage`, ' .
            'CASE ' .
                'WHEN tl.`fromPage` LIKE \'objectlink:trackeritemfield:%\' THEN ttif.`value` ' .
                'ELSE tpf.`data` ' .
            'END AS fromPageContent ' .
            'FROM `tiki_links` tl ' .
            'LEFT JOIN `tiki_pages` tp ON (tl.`toPage` = tp.`pageName`) ' .
            'LEFT JOIN `tiki_pages` tpf ON (tl.`fromPage` = tpf.`pageName`) ' .
            'LEFT JOIN `tiki_tracker_item_fields` ttif ON (' .
                'SUBSTRING_INDEX(SUBSTRING_INDEX(tl.`fromPage`, \':\', 3), \':\', -1) = ttif.`itemId` ' .
                'AND SUBSTRING_INDEX(tl.`fromPage`, \':\', -1) = ttif.`fieldId` ' .
                'AND tl.`fromPage` LIKE \'objectlink:trackeritemfield:%\' ' .
            ')';
        if ($skipalias) {
            $query .= ' LEFT JOIN `tiki_object_relations` tor ON (tl.`toPage` = tor.`target_itemId`)';
        }

        $categories = $this->get_jail();
        if ($categories) {
            $query .= ' INNER JOIN `tiki_objects` AS tob ON (tob.`itemId` = tl.`fromPage` AND tob.`type` = ?) INNER JOIN `tiki_category_objects` AS tc ON (tc.`catObjectId` = tob.`objectId` AND tc.`categId` IN (' . implode(', ', array_fill(0, count($categories), '?')) . '))';
        }
        $query .= ' WHERE tp.`pageName` IS NULL';
        if ($skipalias) {
            $query .= ' AND (tor.`relation` IS NULL OR tor.`relation` != \'tiki.link.alias\')';
        }
        $result = $this->query($query, $categories ? array_merge(['wiki page'], $categories) : []);
        $tmp = [];

        while ($row = $result->fetchRow()) {
            // For object links, we now fetch the content to check for escape tags.
            if ($this->isEscapedLink($row['fromPageContent'] ?? '', $row['toPage'])) {
                if ($debug == 2) {
                    echo tr('%0 [from: %1]: escaped with ~np~ tags', $row['toPage'], $row['fromPage']) . '<br />';
                } elseif ($debug) {
                    $tmp[] = [$row['toPage'], $row['fromPage'], 'escaped with ~np~ tags'];
                }
                continue;
            }

            foreach ($ignorepages as $ipage) {
                // test whether a substring ignores this page, ignore case
                if (fnmatch(mb_strtolower($ipage), mb_strtolower($row['fromPage'])) === true) {
                    if ($debug == 2) { // the "hardcore case"
                        echo tr('%0 [from: %1]: ignored', $row['toPage'], $row['fromPage']) . '<br />';
                    } elseif ($debug) { // add this page to the table
                        $tmp[] = [$row['toPage'], $row['fromPage'], 'ignored'];
                    }
                    continue 2; // no need to test other ignorepages or toPages
                }
            } // foreach ignorepage

            // if toPage contains colon, and exloding yields two parts => external Wiki
            if (($skipext) && (str_contains($row['toPage'], ':'))) {
                $parts = explode(':', $row['toPage']);
                if (count($parts) == 2) {
                    if ($debug == 2) {
                        echo tr('%0 [from: %1]: External Wiki', $row['toPage'], $row['fromPage']) . '<br />';
                    } elseif ($debug) {
                        $tmp[] = [$row['toPage'], $row['fromPage'], 'External Wiki'];
                    }
                    continue;
                }
            } // $skipext

            $dashWikiWord = preg_match("/^(?<=[ \n\t\r\,\;]|^)([A-Z][a-z0-9_\-\x80-\xFF]+[A-Z][a-z0-9_\-\x80-\xFF]+[A-Za-z0-9\-_\x80-\xFF]*)(?=$|[ \n\t\r\,\;\.])$/", $row['toPage']);
            $WikiWord = preg_match("/^(?<=[ \n\t\r\,\;]|^)([A-Z][a-z0-9\x80-\xFF]+[A-Z][a-z0-9\x80-\xFF]+[A-Za-z0-9\x80-\xFF]*)(?=$|[ \n\t\r\,\;\.])$/", $row['toPage']);
            // test whether toPage is a valid wiki page under current syntax
            if ($dashWikiWord && ! $WikiWord) { // a Dashed-WikiWord, can we allow this?
                if (($prefs['feature_wikiwords'] != 'y') || ($prefs['feature_wikiwords_usedash'] != 'y')) {
                    $debugResult = debug_print($row, $debug, tra('dash-WikiWord'));
                    if (isset($debugResult)) {
                        $tmp = $debugResult;
                    }
                    continue;
                }
            } elseif ($WikiWord) { // a WikiWord, can we allow this?
                if ($prefs['feature_wikiwords'] != 'y') {
                    $debugResult = debug_print($row, $debug, tra('WikiWord'));
                    if (isset($debugResult)) {
                        $tmp = $debugResult;
                    }
                    continue;
                }
            } else { // no WikiWord, we can now filter with the level parameter
                if (! preg_match("/^($level_reg)$/", $row['toPage'])) {
                    $debugResult = debug_print($row, $debug, tra('not in level'));
                    if (isset($debugResult)) {
                        $tmp = $debugResult;
                    }
                    continue;
                }
            } // dashWikiWord, WikiWord, normal link

            if (! $debug) { // a normal, valid WantedPage:
                $tmp[] = [$row['toPage'], $row['fromPage']];
            } elseif ($debug == 2) {
                debug_print($row, $debug, tra('valid'));
            } // in simple debug mode, valid links are ignored
        } // while (each entry in tiki_links is handled)
        unset($result); // free memory

        if ($debug == 2) {
            return(tra('End of debug output.'));
        }

        $out = [];
        $linkin  = (! $debug) ? '((' : '~np~'; // this is how toPages are handled
        $linkout = (! $debug) ? '))' : '~/np~';
        if (is_array($tmp)) {
            foreach ($tmp as $row) { // row[toPage, fromPage, reason]
                if ($debug) { // modified rejected toPages with reason
                    $row[0] = tr('<em>%0</em>: %1', $row[2], $row[0]);
                }
                $row[0] = is_object_link($row[0]) ? parse_object_link($row[0]) : ($linkin . $row[0] . $linkout); // toPages
                $row[1] = is_object_link($row[1]) ? parse_object_link($row[1]) : '((' . $row[1] . '))'; // fromPages

                // two identical keys may exist, they can either be displayed
                // each in its own table row, or be collected in one cell, separated by
                // either comma or <br />
                if ($collect == 'from') {
                    if ($break == 'sep') {
                        // toPages separated in each row, there might be duplicates!!!
                        $out[] = [$row[0], $row[1]];
                    } elseif (! array_key_exists($row[0], $out)) {
                        // multiple fromPages (for one toPage) might be in one row, this is the first
                        $out[$row[0]] = $row[1];
                    } else {
                        // multiple fromPages might be in one row, this is a follow-up
                        $out[$row[0]] = $out[$row[0]] . $break . $row[1];
                    }
                } else { // $collect == to
                    if ($break == 'sep') {
                        // fromPages separated in each row, there might be duplicates!!!
                        $out[] = [$row[1], $row[0]];
                    } elseif (! array_key_exists($row[1], $out)) {
                        // multiple toPages (for one fromPage) might be in one row, this is the first
                        $out[$row[1]] = $row[0];
                    } else { // multiple toPages might be in one row, this is a follow-up
                        $out[$row[1]] = $out[$row[1]] . $break . $row[0];
                    }
                }
            } // foreach (received row) is handled
            unset($tmp); // free memory
        }

        // sort the entries
        if ($break == 'sep') {
            sort($out);
        } else {
            ksort($out);
        }

        $headerwant = tra('Wanted Page');
        $headerref = tra('Referenced By Page');
        $rowbreak = "\n";
        $endtable = '||';
        if ($prefs['feature_wiki_tables'] != 'new') {
            $rowbreak = ' || ';
            $endtable = '';
        }

        $sOutput = '||' . '__';
        if ($collect == 'from') {
            $sOutput .= $headerwant . '__|__' . $headerref . '__' . $rowbreak;
            if ($break == 'sep') {
                foreach ($out as $link) {
                    $sOutput .= $link[0] . ' | ' . $link[1] . $rowbreak;
                }
            } else {
                foreach ($out as $to => $from) {
                    $sOutput .= $to . ' | ' . $from . $rowbreak;
                }
            }
        } else { // $collect == 'to'
            $sOutput .= $headerref . '__|__' . $headerwant . '__' . $rowbreak;
            if ($break == 'sep') {
                foreach ($out as $link) {
                    $sOutput .= $link[0] . ' | ' . $link[1] . $rowbreak;
                }
            } else {
                foreach ($out as $from => $to) {
                    $sOutput .= $from . ' | ' . $to . $rowbreak;
                }
            }
        }
        $sOutput .= $endtable;
        if (TikiLib::lib('parser')->option['is_markdown']) {
            $sOutput = TikiLib::lib('parser')->parse_data($sOutput, ['is_markdown' => false, 'is_html' => true]);
        }

        return $sOutput;
    }
}

function wikiplugin_wantedpages($data, $params)
{
    $plugin = new WikiPluginWantedPages();
    return $plugin->run($data, $params);
}

// fnmatch() is not defined on windows or PHP < 4.3.0!!
// From php help "fnmatch", http://www.php.net/manual/de/function.fnmatch.php
// comment by "soywiz at gmail dot com 26-Jul-2005 07:07" (as of Jan. 21 2006)
if (! function_exists('fnmatch')) {
    function fnmatch($pattern, $string)
    {
        for ($op = 0, $npattern = '', $n = 0, $l = strlen($pattern); $n < $l; $n++) {
            switch ($c = $pattern[$n]) {
                case '\\':
                    $npattern .= '\\' . @$pattern[++$n];
                    break;
                case '.':
                case '+':
                case '^':
                case '$':
                case '(':
                case ')':
                case '{':
                case '}':
                case '=':
                case '!':
                case '<':
                case '>':
                case '|':
                                                                $npattern .= '\\' . $c;
                    break;
                case '?':
                case '*':
                    $npattern .= '.' . $c;
                    break;
                case '[':
                case ']':
                default:
                        $npattern .= $c;
                    if ($c == '[') {
                        $op++;
                    } elseif ($c == ']') {
                        if ($op == 0) {
                            return false;
                        }
                        $op--;
                    }
                    break;
            }
        }
        if ($op != 0) {
            return false;
        }
        return preg_match('/' . $npattern . '/i', $string);
    }
}

// A small function to determine whether a string is a [valid] preg expression.
// From php help "Regular Expression Functions (Perl-Compatible)", http://www.php.net/pcre/
// comment by "alexbodn at 012 dot n@t dot il 09-Jan-2006 11:45" (as of Jan. 21 2006)
if (! function_exists('preg_ispreg')) {
    function preg_ispreg($str)
    {
        $prefix = '';
        $sufix = '';
        if ($str[0] != '^') {
            $prefix = '^';
        }
        if ($str[strlen($str) - 1] != '$') {
            $sufix = '$';
        }
        $estr = preg_replace("'^/'", "\\/", preg_replace("'([^/])/'", "\\1\\/", $str));
        if (@preg_match("/" . $prefix . $estr . $sufix . "/", $str, $matches)) {
            return strcmp($str, $matches[0]) != 0;
        }
        return true;
    }
}

if (! function_exists('debug_print')) {
    function debug_print($row, $debug, $message)
    {
        if ($debug == 2) {
            echo tr('%0 [from: %1]: %2', $row['toPage'], $row['fromPage'], $message) . '<br />';
            return;
        } elseif ($debug) {
            $tmp[] = [$row['toPage'], $row['fromPage'], $message];
            return $tmp;
        }
    }
}

//test whether fromPage or toPage is unparsed object link.
//The Pattern of unparsed object link is objectlink:objecttype:itemId:fieldId
if (! function_exists('is_object_link')) {
    function is_object_link($link)
    {
        $parts = explode(':', $link);
        if (str_contains($link, 'objectlink:') && count($parts) >= 3) {
            return true;
        }
        return false;
    }
}

if (! function_exists('parse_object_link')) {
    function parse_object_link($link)
    {
        $smarty = TikiLib::lib('smarty');
        $parts = explode(':', $link);
        $data = smarty_function_object_link(
            [
                'type' => $parts[1],
                'id' => $parts[2],
            ],
            $smarty->getEmptyInternalTemplate()
        );
        return $data;
    }
}
