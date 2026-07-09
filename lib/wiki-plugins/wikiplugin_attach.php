<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

use Tiki\Sections;

function wikiplugin_attach_info()
{
    return [
        'searchable_by_default' => true,
        'name' => tra('Attachment'),
        'documentation' => 'PluginAttach',
        'description' => tra('Display an attachment or a list of them'),
        'prefs' => [ 'feature_wiki_attachments', 'wikiplugin_attach' ],
        'body' => tra("Comment"),
        'iconname' => 'attach',
        'introduced' => 1,
        'params' => [
            'name' => [
                'required' => false,
                'name' => tra('Name'),
                'description' => tra('File name of the attached file to link to. Either name, file, id or num can be
                    used to identify a single attachment'),
                'since' => '1',
                'filter' => 'text',
                'default' => '',
            ],
            'file' => [
                'required' => false,
                'name' => tra('File'),
                'description' => tra('Same as name'),
                'since' => '1',
                'filter' => 'text',
                'default' => '',
            ],
            'page' => [
                'required' => false,
                'name' => tra('Page'),
                'description' => tra('Name of the wiki page the file is attached to. If left empty when the plugin is
                    used on a wiki page, this defaults to that wiki page.'),
                'since' => '1',
                'filter' => 'pagename',
                'default' => '',
                'profile_reference' => 'wiki_page',
            ],
            'showdesc' => [
                'required' => false,
                'name' => tra('Show Description'),
                'description' => tra('Shows the description as the link text instead of the file name (not used by default)'),
                'since' => '1',
                'filter' => 'digits',
                'default' => 0,
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 1],
                    ['text' => tra('No'), 'value' => 0],
                ],
            ],
            'bullets' => [
                'required' => false,
                'name' => tra('Bullets'),
                'description' => tra('Makes the list of attachments a bulleted list (not set by default)'),
                'since' => '1',
                'filter' => 'digits',
                'default' => 0,
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 1],
                    ['text' => tra('No'), 'value' => 0],
                ],
            ],
            'image' => [
                'required' => false,
                'name' => tra('Image'),
                'description' => tr('Indicates that this file is an image, and should be displayed inline using the
                    %0 tag (not set by default)', '<code>img</code>'),
                'since' => '1',
                'filter' => 'digits',
                'default' => 0,
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 1],
                    ['text' => tra('No'), 'value' => 0],
                ],
            ],
            'inline' => [
                'required' => false,
                'name' => tra('Custom Label'),
                'description' => tr('Makes the text between the %0 tags the link text instead of the file name
                    or description. Only the first attachment will be listed.', '<code>{ATTACH}</code>'),
                'since' => '1',
                'filter' => 'digits',
                'default' => 0,
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 1],
                    ['text' => tra('No'), 'value' => 0],
                ],
            ],
            'all' => [
                'required' => false,
                'name' => tra('All'),
                'description' => tr('Lists links to all attachments for the entire tiki site together with pages they
                    are attached to when set to %0 (Yes)', '<code>1</code>'),
                'since' => '1',
                'filter' => 'digits',
                'default' => 0,
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 1],
                    ['text' => tra('No'), 'value' => 0],
                ],
            ],
            'num' => [
                'required' => false,
                'name' => tra('Order Number'),
                'description' => tra('Identifies the attachment to link to by the order of the attachment in the list
                    of attachments to a page instead of by file name or ID. Either name, file, id or num can be used to
                    identify a single attachment.'),
                'since' => '1',
                'default' => '',
            ],
            'id' => [
                'required' => false,
                'name' => tra('ID'),
                'description' => tra('Identifies the attachment to link to by id number instead of by file name or order
                    number. Either name, file, id or num can be used to identify a single attachment.'),
                'since' => '1',
                'accepted' => tra('Attachment name, file, id or num'),
                'filter' => 'text',
            ],
            'dls' => [
                'required' => false,
                'name' => tra('Downloads'),
                'description' => tr('The alt text that pops up on mouseover will include the number of downloads of the
                    attachment at the end when set to %0 (Yes)', '<code>1</code>'),
                'since' => '1',
                'filter' => 'digits',
                'default' => 0,
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 1],
                    ['text' => tra('No'), 'value' => 0],
                ],
            ],
            'icon' => [
                'required' => false,
                'name' => tra('File Type Icon'),
                'description' => tr('A file type icon is displayed in front of the attachment link when this is set to
                    %0 (Yes)', '<code>1</code>'),
                'since' => '1',
                'filter' => 'digits',
                'default' => 0,
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 1],
                    ['text' => tra('No'), 'value' => 0],
                ],
            ],

        ],
    ];
}

function wikiplugin_attach($data, $params)
{
    global $atts;
    global $user, $section_class;

    $wikilib = TikiLib::lib('wiki');
    $tikilib = TikiLib::lib('tiki');
    $params = WikiPlugin_Helper::applyParamsDefaults($params, wikiplugin_attach_info());

    extract($params, EXTR_SKIP);

    $loop = [];
    if (! isset($atts)) {
        $atts = [];
    }

    if (! is_array($atts) || ! array_key_exists("data", $atts) || ! is_array($atts['data']) || count($atts["data"]) < 1) {
        // We're being called from a preview or something; try to build the atts ourselves.

        // See if we're being called from a tracker page.
        if (Sections::isCurrentSection(Sections::SECTION_TRACKERS)) {
            $trklib = TikiLib::lib('trk');
            $atts_item_name = $_REQUEST["itemId"];

            // First get the tracker ID for this item
            $trackerId = $trklib->get_tracker_for_item($atts_item_name);
            $tracker_info = $trklib->get_tracker($trackerId);
            $tracker_options = $trklib->get_tracker_options($trackerId);

            if (! is_array($tracker_info) || ! is_array($tracker_options)) {
                throw new Exception(tr('No tracker found matching id %0', $atts_item_name));
            }

            $atts = $trklib->list_item_attachments($atts_item_name, 0, -1, 'comment_asc', '');
        }

        // See if we're being called from a wiki page.
        if ($section_class && str_contains($section_class, Sections::SECTION_WIKI_PAGE)) {
            $atts_item_name = $_REQUEST["page"];
            $atts = $wikilib->list_wiki_attachments($atts_item_name, 0, -1, 'created_desc', '');
        }
    }

    // Save for restoration before this script ends
    $old_atts = $atts;
    $url = '';

    if ($all) {
        $atts = $wikilib->list_all_attachments(0, -1, 'page_asc', '');
    } elseif (! empty($page)) {
        if (! $tikilib->page_exists($page)) {
            return "''" . tr('Page "%0" does not exist', $page) . "''";
        }
        if ($tikilib->user_has_perm_on_object($user, $page, 'wiki page', 'tiki_p_wiki_view_attachments')) {
            $atts = $wikilib->list_wiki_attachments($page, 0, -1, 'created_desc', '');
            $url = "&amp;page=$page";
        }
    }

    if (! array_key_exists("count", $atts)) {
        if (array_key_exists("data", $atts)) {
            $atts['count'] = count($atts["data"]);
        } else {
            $atts['count'] = 0;
            $atts["data"] = "";
        }
    }

    if (empty($num)) {
        $num = 0;
    }
    if (empty($id)) {
        $id = 0;
    } else {
        $num = 0;
    }

    if (! empty($file)) {
        $name = $file;
    }

    if (! empty($name)) {
        $id = 0;
        $num = 0;
    } else {
        $name = '';
    }

    if (! $atts['count']) {
        return "''" . tra('No such attachment on this page') . "''";
    } elseif ($num > 0 and $num < ($atts['count'] + 1)) {
        $loop[] = $num;
    } else {
        $loop = range(1, $atts['count']);
    }

    $out = [];
    if ($data) {
        $out[] = $data;
    }

    foreach ($loop as $n) {
        $n--;
        $attachment = $atts['data'][$n];
        if ((! $name and ! $id) or $id == $attachment['attId'] or $name == $attachment['filename']) {
            $link = "";
            if ($bullets) {
                $link .= "<li>";
            }
            $description = (! empty($showdesc) && ! empty($attachment['comment']))
                ? $attachment['comment']
                : $attachment['filename'];

            if (! empty($dls)) {
                $description .= ' ' . $attachment['hits'];
            }

            if ($image) {
                $link .= '<img src="tiki-download_wiki_attachment.php?attId=' . $attachment['attId'] . $url . '" class="wiki"';
                $link .= ' alt="' . $description . '"/>';
            } else {
                $link .= '<a href="tiki-download_wiki_attachment.php?attId=' . $attachment['attId'] . $url . '&amp;download=y" class="wiki"';
                $link .= ' title="' . $description . '">';
                if (! empty($icon)) {
                    $iconhtml = smarty_modifier_iconify($attachment['filename']);
                    $link .= $iconhtml . '&nbsp';
                }

                if (! empty($showdesc) && ! empty($attachment['comment'])) {
                    $link .= strip_tags($attachment['comment']);
                } elseif (! empty($inline) && ! empty($data)) {
                    $link .= $data;
                } else {
                    $link .= strip_tags($attachment['filename']);
                }

                $link .= '</a>';

                $pageall = strip_tags($attachment['page']);
                if ($all) {
                    $link .= " attached to " . '<a title="' . $pageall . '" href="' . $pageall . '" class="wiki">' . $pageall . '</a>';
                }
            }

            if ($bullets) {
                $link .= "</li>";
            }

            $out[] = $link;
        }
    }

    if ($bullets) {
        $separator = "\n";
    } else {
        $separator = "<br />\n";
    }

    if (! empty($inline) && ! empty($data)) {
        if (array_key_exists(1, $out)) {
            $data = $out[1];
        } else {
            $data = "";
        }
    } else {
        $data = implode($separator, $out);
    }

    if ($bullets) {
        $data = "<ul>" . $data . "</ul>";
    }

    if (strlen($data) == 0) {
        $data = "<strong>" . tra('No such attachment on this page') . "</strong>";
    }

    $atts = $old_atts;

    return '~np~' . $data . '~/np~';
}
