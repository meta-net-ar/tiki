<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * \brief Smarty plugin to use wiki page as a template resource
 * -------------------------------------------------------------
 * File:     resource.wiki.php
 * Type:     resource
 * Name:     wiki
 * Purpose:  Fetches a template from a wiki page
 * -------------------------------------------------------------
 */
class Smarty_Resource_Wiki extends \Smarty\Resource\CustomPlugin
{
    protected function fetch($name, &$source, &$mtime): void
    {
        /** @var \Smarty_Tiki $smarty */
        $smarty = TikiLib::lib('smarty');
        $info = $smarty->checkWikiPageTemplatePerms($name, $source);

        if ($info) {
            $source = TikiLib::lib('parser')->parse_data($info['data'], ['is_html' => $info['is_html'], 'print' => 'y', 'inside_pretty' => true]);
            if (preg_match('/\{\w+.*?}/', $source) && ! str_contains($source, '{literal}')) {
                // when used as the output template in a list plugin tags like `{display name="title"}` upset smarty
                $source = "{literal}{$source}{/literal}";
            }
        }
    }

    protected function fetchTimestamp($name): ?int
    {
        global $tikilib;
        $info = $tikilib->get_page_info($name);
        if (empty($info)) {
            return false;
        }
        if (
            preg_match('/\{([A-z-Z0-9_]+) */', $info['data'])
            || preg_match('/\{\{.+\}\}/', $info['data'])
        ) { // there are some plugins - so it can be risky to cache the page
            return $tikilib->now + 100; // future needed in case consecutive run of template;
        }

        return $info['lastModif'];
    }
}
