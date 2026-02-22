<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
$inputConfiguration = [
    [
        'staticKeyFilters'                => [
            'add_type'                    => 'bool',                //post
            'new_type'                    => 'string',              //post
            'remove_type'                 => 'word',                //get
            'update_type'                 => 'bool',                //post
            'att_remove'                  => 'int',                 //post
            'att_type'                    => 'text',                //get
        ],
        'staticKeyFiltersForArrays'                => [
            'type_array'                  => 'string',              //post
            'use_ratings'                 => 'string',                //post
            'show_pre_publ'               => 'string',                //post
            'heading_only'                => 'string',                //post
            'allow_comments'              => 'string',                //post
            'comment_can_rate_article'    => 'string',                //post
            'show_image'                  => 'string',                //post
            'show_avatar'                 => 'string',                //post
            'show_author'                 => 'string',                //post
            'show_pubdate'                => 'string',                //post
            'show_reads'                  => 'string',                //post
            'show_size'                   => 'string',                //post
            'show_topline'                => 'string',                //post
            'show_subtitle'               => 'string',                //post
            'show_image_caption'          => 'string',                //post
            'show_linkto'                 => 'string',                //post
            'creator_edit'                => 'string',                //post
            'new_attribute'               => 'string',              //post
        ],
    ]
];
require_once('tiki-setup.php');
use Tiki\Sections;
$section = Sections::SECTION_CMS;
Sections::setCurrentSection($section);
$artlib = TikiLib::lib('art');
$access->check_feature('feature_articles');

// PERMISSIONS: NEEDS p_admin or tiki_p_articles_admin_types
$access->check_permission(['tiki_p_articles_admin_types']);

if (isset($_REQUEST["add_type"])) {
    if (empty($_REQUEST["new_type"])) {
        Feedback::error(tra('The article type field can not be empty'));
    } else {
        $artlib->add_type($_REQUEST["new_type"]);
        Feedback::success(tra('Article type saved successfully'));
    }
} elseif (isset($_REQUEST["remove_type"])) {
    $access->checkCsrf();
    $artlib->remove_type($_REQUEST["remove_type"]);
} elseif (isset($_REQUEST["update_type"])) {
    foreach (array_keys($_REQUEST["type_array"]) as $this_type) {
        $defaultFields = [
            "use_ratings",
            "show_pre_publ",
            "heading_only",
            "allow_comments",
            "comment_can_rate_article",
            "show_image",
            "show_avatar",
            "show_author",
            "show_pubdate",
            "show_reads",
            "show_size",
            "show_topline",
            "show_subtitle",
            "show_image_caption",
            "show_linkto",
            "creator_edit"
        ];

        // Initialize fields with 'n' if not defined
        foreach ($defaultFields as $field) {
            $_REQUEST[$field] = is_array($_REQUEST[$field] ?? null) ? $_REQUEST[$field] : [];
            if (! isset($_REQUEST[$field][$this_type])) {
                $_REQUEST[$field][$this_type] = 'n';
            }
        }
        // Edit type of article
        $artlib->edit_type(
            $this_type,
            $_REQUEST["use_ratings"][$this_type],
            $_REQUEST["show_pre_publ"][$this_type],
            $_REQUEST["heading_only"][$this_type],
            $_REQUEST["allow_comments"][$this_type],
            $_REQUEST["comment_can_rate_article"][$this_type],
            $_REQUEST["show_image"][$this_type],
            $_REQUEST["show_avatar"][$this_type],
            $_REQUEST["show_author"][$this_type],
            $_REQUEST["show_pubdate"][$this_type],
            $_REQUEST["show_reads"][$this_type],
            $_REQUEST["show_size"][$this_type],
            $_REQUEST["show_topline"][$this_type],
            $_REQUEST["show_subtitle"][$this_type],
            $_REQUEST["show_linkto"][$this_type],
            $_REQUEST["show_image_caption"][$this_type],
            $_REQUEST["creator_edit"][$this_type]
        );

        // Adding custom attributes
        if ($prefs["article_custom_attributes"] == 'y' && ! empty($_REQUEST["new_attribute"][$this_type])) {
            $ok = $artlib->add_article_type_attribute($this_type, $_REQUEST["new_attribute"][$this_type]);
            if (! $ok) {
                Feedback::errorAndDie(tra("Failed to add attribute"), \Laminas\Http\Response::STATUS_CODE_409);
            } else {
                Feedback::success(tra("Article type attribute updated successfully"));
            }
        }
    }
}

$types = $artlib->list_types();

if ($prefs["article_custom_attributes"] == 'y') {
    if (isset($_REQUEST["att_type"]) && isset($_REQUEST["att_remove"])) {
        $artlib->delete_article_type_attribute($_REQUEST["att_type"], $_REQUEST["att_remove"]);
    }
    foreach ($types as &$t) {
        $t["attributes"] = $artlib->get_article_type_attributes($t["type"], 'relationId ASC');
    }
}

$smarty->assign('types', $types);

include_once('tiki-section_options.php');

// the strings below are used to localize the article types in the template file
//get_strings tr('Article') tr('Review') tr('Event') tr('Classified')
$smarty->assign('mid', 'tiki-article_types.tpl');
$smarty->display("tiki.tpl");
