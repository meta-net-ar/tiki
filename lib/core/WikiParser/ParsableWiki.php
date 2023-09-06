<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class WikiParser_ParsableWiki extends ParserLib
{
    public function wikiParse($data, $noparsed = [])
    {
        global $prefs;

        if ($this->option['is_html'] && ! $this->option['parse_wiki']) {
            return $data;
        }

        // remove tiki comments first
        if ($this->option['wysiwyg']) {
            $data = preg_replace(';~tc~(.*?)~/tc~;s', '<tikicomment>$1</tikicomment>', $data);
        } else {
            $data = preg_replace(';(?<!~np~)~tc~(.*?)~/tc~(?!~/np~);s', '', $data);
        }

        // Handle ~pre~...~/pre~ sections
        $data = preg_replace(';~pre~(.*?)~/pre~;s', '<pre>$1</pre>', $data);

        // Strike-deleted text --text-- (but not in the context <!--[if IE]><--!> or <!--//--<!CDATA[//><!--
        // FIXME produces false positive for strings containing html comments. e.g: --some text<!-- comment -->
        $data = preg_replace("#(?<!<!|//)--([^\s>].+?)--#", "<strike>$1</strike>", $data);

        // Handle comments again in case parse_first method above returned wikiplugins with comments (e.g. PluginInclude a wiki page with comments)
        $data = preg_replace(';~tc~(.*?)~/tc~;s', '', $data);

        // Handle html comment sections
        $data = preg_replace(';~hc~(.*?)~/hc~;s', '<!-- $1 -->', $data);

        // Replace special characters
        // done after url catching because otherwise urls of dyn. sites will be modified // What? Chealer
        // must be done before color as we can have "~hs~~hs" (2 consecutive non-breaking spaces. The color syntax uses "~~".)
        // jb 9.0 html entity fix - excluded not $this->option['is_html'] pages
        if (! $this->option['is_html']) {
            $this->parse_htmlchar($data);
        }

        //needs to be before text color syntax because of use of htmlentities in lib/core/WikiParser/OutputLink.php
        $data = $this->parse_data_wikilinks($data, false, $this->option['wysiwyg']);

        // Replace colors ~~foreground[,background]:text~~
        // must be done before []as the description may contain color change
        $parse_color = 1;
        $temp = $data;
        while ($parse_color) { // handle nested colors, parse innermost first
            $temp = preg_replace_callback(
                "/~~([^~:,]+)(,([^~:]+))?:([^~]*)(?!~~[^~:,]+(?:,[^~:]+)?:[^~]*~~)~~/Ums",
                'ParserLib::colorAttrEscape',
                $temp,
                -1,
                $parse_color
            );

            if (! empty($temp)) {
                $data = $temp;
            }
        }

        // On large pages, the above preg rule can hit a BACKTRACE LIMIT
        // In case it does, use the simpler color replacement pattern.
        if (empty($temp)) {
            $data = preg_replace_callback(
                "/\~\~([^\:\,]+)(,([^\:]+))?:([^~]*)\~\~/Ums",
                'ParserLib::colorAttrEscape',
                $data
            );
        }

        // Extract [link] sections (to be re-inserted later)
        $noparsedlinks = [];

        // This section matches [...].
        // Added handling for [[foo] sections.  -rlpowell
        preg_match_all("/(?<!\[)(\[[^\[][^\]]+\])/", $data, $noparseurl);

        foreach (array_unique($noparseurl[1]) as $np) {
            $key = '§' . md5(TikiLib::genPass()) . '§';

            $aux["key"] = $key;
            $aux["data"] = $np;
            $noparsedlinks[] = $aux;
            $data = preg_replace('/(^|[^a-zA-Z0-9])' . preg_quote($np, '/') . '([^a-zA-Z0-9]|$)/', '\1' . $key . '\2', $data);
        }

        // BiDi markers
        $bidiCount = 0;
        $bidiCount = preg_match_all("/(\{l2r\})/", $data, $pages);
        $bidiCount += preg_match_all("/(\{r2l\})/", $data, $pages);

        $data = preg_replace("/\{l2r\}/", "<div dir='ltr'>", $data);
        $data = preg_replace("/\{r2l\}/", "<div dir='rtl'>", $data);
        $data = preg_replace("/\{lm\}/", "&lrm;", $data);
        $data = preg_replace("/\{rm\}/", "&rlm;", $data);

        // Replace boxes
        $delim = (isset($prefs['feature_simplebox_delim']) && $prefs['feature_simplebox_delim'] != "" ) ? preg_quote($prefs['feature_simplebox_delim']) : preg_quote("^");
        if ($this->option['markdown_conversion']) {
            $data = preg_replace("/{$delim}(.+?){$delim}/s", "{BOX(class='bg-light')}$1{BOX}", $data);
        } else {
            $data = preg_replace("/{$delim}(.+?){$delim}/s", "<div class=\"card bg-light\"><div class=\"card-body\">$1</div></div>", $data);
        }

        // Underlined text
        if ($this->option['markdown_conversion']) {
            $data = preg_replace("/===(.+?)===/", "{TAG(tag='u')}$1{TAG}", $data);
        } else {
            $data = preg_replace("/===(.+?)===/", "<u>$1</u>", $data);
        }

        // Center text
        if ($this->option['markdown_conversion']) {
            $replacement = "{DIV(style='text-align: center')}$1{DIV}";
        } else {
            $replacement = "<div style=\"text-align: center;\">$1</div>";
        }
        if ($prefs['feature_use_three_colon_centertag'] == 'y' || ($prefs['namespace_enabled'] == 'y' && $prefs['namespace_separator'] == '::')) {
            $data = preg_replace("/:::(.+?):::/", $replacement, $data);
        } else {
            $data = preg_replace("/::(.+?)::/", $replacement, $data);
        }

        // reinsert hash-replaced links into page
        foreach ($noparsedlinks as $np) {
            $data = str_replace($np["key"], $np["data"], $data);
        }

        if ($prefs['wiki_pagination'] != 'y') {
            $data = str_replace($prefs['wiki_page_separator'], $prefs['wiki_page_separator'] . ' <em>' . tr('Wiki page pagination has not been enabled.') . '</em>', $data);
        }

        $data = $this->parse_data_externallinks($data);

        $data = $this->parse_data_tables($data);

        /* parse_data_process_maketoc() calls parse_data_inline_syntax().

        It seems wrong to just call parse_data_inline_syntax() when the parsetoc option is disabled.
        Despite its name, parse_data_process_maketoc() does not just deal with TOC-s.

        I believe it would be better that parse_data_process_maketoc() check parsetoc, only to set $need_maketoc, so that the following calls parse_data_process_maketoc() unconditionally. Chealer 2018-01-02
        */
        if ($this->option['parsetoc']) {
            $this->parse_data_process_maketoc($data, $noparsed);
        } else {
            $data = $this->parse_data_inline_syntax($data);
        }

        // linebreaks using %%%
        $data = preg_replace("/\n?(?<![^%]\d)%%%/", "<br />", $data);

        // Close BiDi DIVs if any
        for ($i = 0; $i < $bidiCount; $i++) {
            $data .= "</div>";
        }

        return $data;
    }
}
