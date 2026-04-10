<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

use Tiki\Lib\Wiki\PluginsLibUtil;
use Tiki\Lib\Wiki\PluginsLib;

class WikiPluginBackLinks extends PluginsLib
{
    public $expanded_params = ["exclude", "info"];
    public function getDefaultArguments()
    {
        return ['exclude' => [],
                'include_self' => 0,
                'noheader' => 0,
                'page' => '[pagename]',
                'info' => false ];
    }

    public function getName()
    {
        return "BackLinks";
    }

    public function getDescription()
    {
        return wikiplugin_backlinks_info()['description'];
    }

    public function getVersion()
    {
        return preg_replace("/[Revision: $]/", '', "\$Revision: 1.17 $");
    }

    public function get_backlink_from_tracker_item($trackerObjectId)
    {
        $ids = explode(":", $trackerObjectId);
        $itemId = (int)$ids[0];
        $trackerId = (int)$ids[1];
        //Get item value from the ids
        $trackerlib = TikiLib::lib('trk');
        $fieldId = $this->get_item_field_id($itemId);
        $trackerItemPageName = ((int)($trackerlib->get_item_value($trackerId, $itemId, $fieldId))) ? "Item " .
            $itemId : $trackerlib->get_item_value($trackerId, $itemId, $fieldId);

        //Format the backlink using object link
        $smarty = TikiLib::lib('smarty');

        $backlink = smarty_function_object_link(
            [
                'type' => 'trackeritem',
                'id' => $itemId,
                'title' => $trackerItemPageName
            ],
            $smarty->getEmptyInternalTemplate()
        );

        return $backlink;
    }

    public function get_item_field_id($itemId)
    {
        global $tikilib;
        $query = "SELECT `fieldId` FROM `tiki_tracker_item_fields` WHERE `itemId` = ? ORDER BY `fieldId` ASC LIMIT 1";
        $res = $tikilib->query($query, [ $itemId ]);
        $fieldId = (int) $res->result[0]['fieldId'];
        return $fieldId;
    }

    public function list_backlinks_from_tracker_items($itemsLinks)
    {
        $head = '<ul>';
        $foot = '</ul>';
        $body = "";
        foreach ($itemsLinks as $itemlink) {
            $body .= '<li>' . $itemlink . '</li>';
        }
        return $head . $body . $foot;
    }

    public function run($data, $params)
    {
        //To be able to read prefs
        global $prefs;
        $wikilib = TikiLib::lib('wiki');
        $exclude = $params['exclude'] ?? [];
        $params = $this->getParams($params, true);
        $aInfoPreset = array_keys($this->aInfoPresetNames);
        extract($params, EXTR_SKIP);

        if (is_null($page)) {
            // get current page name
            global $page;
        }

        /////////////////////////////////
        // Create a valid list for $info
        /////////////////////////////////
        //
        if (! is_null($info)) {
            $info_temp = [];
            foreach ($info as $sInfo) {
                if (in_array(trim($sInfo), $aInfoPreset)) {
                    $info_temp[] = trim($sInfo);
                }
                $info = $info_temp ? $info_temp :
                    false;
            }
        }
        $sOutput = "";
        // Verify if the page exists
        if (! $wikilib->page_exists($page)) {
            return $this->error(tra("Page cannot be found") . " : <b>$page</b>");
        }
        //
        /////////////////////////////////
        // Process backlinks
        /////////////////////////////////
        //

        $aBackRequest = [];
        //To get and count all the backlinks
        //from trackers
        $tBackRequest = [];
        $counttbi = 0;
        $aBackLinks = $wikilib->get_backlinks($page);
        $aPages = [];
        foreach ($aBackLinks as $backlink) {
            if (
                $backlink['type'] == 'wiki page'
                || $backlink['type'] == 'trackeritemfield'
                && ! in_array($backlink["objectId"], $exclude)
            ) {
                if ($backlink['type'] == 'trackeritemfield') {
                    $tBackRequest[] = $this->get_backlink_from_tracker_item($backlink["objectId"]);
                    $counttbi += 1;
                } else {
                    //Case they are wiki pages
                    $aBackRequest[] = $backlink["objectId"];
                }
            }
        }
        if ($include_self) {
            $aBackRequest[] = $page;
        }
        if (! $aBackRequest) {
            $sOutput .= tra("No pages link to") . " (($page))";
        } else {
            //Sorting backlinks by page list pref
            $sort_mode = $prefs['wiki_list_sortorder'];
            $sort_mode .= '_';
            $sort_mode .= $prefs['wiki_list_sortdirection'];
            $aPages = $this->list_pages(0, -1, $sort_mode, $aBackRequest);
        }
        //
        /////////////////////////////////
        // Start of Output
        /////////////////////////////////
        //
        if (! $noheader) {
            // Create header
            if (! empty($aPages["count"])) {
                $count = $aPages["count"];
            } else {
                $count = 0;
            }
            if (! $count == 0) {
                if ($count == 1) {
                    $sOutput .= tra("One page links to") . " (($page))";
                } else {
                    $sOutput = "$count " . tra("pages link to") . " (($page))";
                }
            }
            $sOutput .= "\n";
        }
        if (! empty($aPages)) {
            $sOutput .= PluginsLibUtil::createTable($aPages["data"], $info);
        }
        //If any backlink in a tracker item field
        $sOutput .= "\n";
        if ($counttbi > 0) {
            // Create header for tracker items
            if ($counttbi == 1) {
                $sOutput .= tra("One tracker item links to") . " (($page))";
            } else {
                $sOutput .= "$counttbi " . tra("tracker items link to") . " (($page))";
            }
            $sOutput .= "\n";
            $sOutput .= $this->list_backlinks_from_tracker_items($tBackRequest);
        }
        return $sOutput;
    }
}

function wikiplugin_backlinks_info()
{
    return [
        'name' => tra('Backlinks'),
        'documentation' => 'PluginBacklinks',
        'description' => tra('List all pages and tracker items that link to a particular page'),
        'prefs' => [ 'feature_wiki', 'wikiplugin_backlinks' ],
        'iconname' => 'backlink',
        'introduced' => 1,
        'params' => [
            'page' => [
                'required' => false,
                'name' => tra('Page'),
                'description' => tra('The page links will point to. Default value is the current page.'),
                'since' => '1',
                'advanced' => true,
                'filter' => 'pagename',
                'profile_reference' => 'wiki_page',
            ],
            'info' => [
                'required' => false,
                'name' => tra('Displayed Information'),
                'description' => tra('Pipe separated list of fields to display. ex: hits|user'),
                'since' => '1',
                'advanced' => true,
                'separator' => '|',
                'filter' => 'text',
            ],
            'exclude' => [
                'required' => false,
                'name' => tra('Excluded pages'),
                'description' => tr('Pipe-separated list of pages to be excluded from the listing, for example:
                    %0HomePage|Sandbox%1', '<code>', '</code>'),
                'since' => '1',
                'advanced' => true,
                'separator' => '|',
                'filter' => 'pagename',
                'profile_reference' => 'wiki_page',
            ],
            'include_self' => [
                'required' => false,
                'name' => tra('Include Self'),
                'description' => tra('With or without self-link (default is without)'),
                'since' => '1',
                'advanced' => true,
                'filter' => 'digits',
                'default' => 0,
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => '1'],
                    ['text' => tra('No'), 'value' => '0'],
                ],
            ],
            'noheader' => [
                'required' => false,
                'name' => tra('Header'),
                'description' => tra('With or without header (default is with header)'),
                'since' => '1',
                'filter' => 'digits',
                'default' => 0,
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('With header'), 'value' => '0'],
                    ['text' => tra('Without header'), 'value' => '1'],
                ],
            ],
        ],
    ];
}

function wikiplugin_backlinks($data, $params)
{
    $plugin = new WikiPluginBackLinks();
    $data = $plugin->run($data, $params);
    if (TikiLib::lib('parser')->option['is_markdown']) {
        $data = "\n" . $data;
    }
    return $data;
}
