<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * \brief Smarty plugin to use wiki page as a template resource parsing as little as with tpl on disk
 * -------------------------------------------------------------
 * File:     resource.tplwiki.php
 * Type:     resource
 * Name:     tplPage
 * Purpose:  Fetches a template from a wiki page but parsing as little as with tpl's on disk
 * -------------------------------------------------------------
 */
class Smarty_Resource_Tplwiki extends \Smarty\Resource\CustomPlugin
{
    protected function fetch($name, &$source, &$mtime)
    {
        /** @var \Smarty_Tiki $smarty */
        $smarty = TikiLib::lib('smarty');
        $info = $smarty->checkWikiPageTemplatePerms($name, $source);

        if ($info) {
            $source = $info['data'];
        }
    }

    protected function fetchTimestamp($name)
    {
        global $tikilib;

        $info = $tikilib->get_page_info($name);
        if (empty($info)) {
            return false;
        }

        if (
            preg_match('/\{([A-z-Z0-9_]+) */', $info['data']) ||
            preg_match('/\{\{.+\}\}/', $info['data'])
        ) { // there are some plugins - so it can be risky to cache the page
            return $tikilib->now;
        }

        return $info['lastModif'];
    }
}
