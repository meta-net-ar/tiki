<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * @return array
 */
function module_user_bookmarks_info()
{
    return [
        'name' => tra('My Bookmarks'),
        'description' => tra('Lightweight interface to user bookmarks, enabling to view them concisely, do some manipulations and bookmark the page being viewed'),
        'prefs' => ["feature_user_bookmarks"],
        'params' => []
    ];
}

/**
 * @param $mod_reference
 * @param $module_params
 */
function module_user_bookmarks($mod_reference, $module_params)
{
    $tikilib = TikiLib::lib('tiki');
    $smarty = TikiLib::lib('smarty');

    global $user, $prefs, $tiki_p_create_bookmarks;
    global $bookmarklib;
    include_once('lib/bookmarks/bookmarklib.php');

    $setup_parsed_uri = parse_url($_SERVER["REQUEST_URI"]);

    if (isset($setup_parsed_uri["query"])) {
        parse_str($setup_parsed_uri["query"], $setup_query_data);
    } else {
        $setup_query_data = [];
    }

    if ($user && $tiki_p_create_bookmarks == 'y') {
        // check the session to get the directory or create directory =0
        $smarty->assign('ownurl', $tikilib->httpPrefix() . $_SERVER["REQUEST_URI"]);

        if (isset($_REQUEST["bookmarks_directory"])) {
            $_SESSION["bookmarks_directory"] = $_REQUEST["bookmarks_directory"];
        } elseif (! isset($_SESSION["bookmarks_directory"])) {
            $_SESSION["bookmarks_directory"] = 0;
        }

        $ownurl = $tikilib->httpPrefix() . $_SERVER["REQUEST_URI"];

        // Now build urls
        if (str_contains($ownurl, '?')) {
            $modb_sep = '&amp;';
        } else {
            $modb_sep = '?';
        }

        $smarty->assign('modb_sep', $modb_sep);

        if (isset($_REQUEST["bookmark_removeurl"])) {
            $bookmarklib->remove_url($_REQUEST["bookmark_removeurl"], $user);
        }

        if (isset($_REQUEST["bookmark_create_folder"])) {
            $bookmarklib->add_folder($_SESSION["bookmarks_directory"], $_REQUEST['modb_name'], $user);
        }

        if (isset($_REQUEST["bookmark_mark"])) {
            $name = $_REQUEST["modb_name"];
            if (empty($name)) {
                // Check if we are bookmarking a wiki-page
                if (str_contains($_SERVER["REQUEST_URI"], 'tiki-index')) {
                    // Get the page
                    if (isset($setup_query_data["page"])) {
                        $name = $setup_query_data["page"];
                    } else {
                        $name = $prefs['wikiHomePage'];
                    }
                }

                // Check if we are bookmarking an article
                if (str_contains($_SERVER["REQUEST_URI"], 'tiki-read_article')) {
                    $artlib = TikiLib::lib('art');
                    $info = $artlib->get_article($setup_query_data["articleId"]);

                    $name = $info["title"];
                }

                // Check if we are bookmarking a file gallery
                if (str_contains($_SERVER["REQUEST_URI"], 'tiki-list_file_gallery')) {
                    $filegallib = TikiLib::lib('filegal');
                    $info = $filegallib->get_file_gallery($setup_query_data["galleryId"]);

                    $name = $info["name"];
                }

                // Check if we are bookmarking a forum
                if (str_contains($_SERVER["REQUEST_URI"], 'tiki-view_forum')) {
                    $info = TikiLib::lib('comments')->get_forum($setup_query_data["forumId"]);
                    $name = $info["name"];
                }

                // Check if we are bookmarking a faq
                if (str_contains($_SERVER["REQUEST_URI"], 'tiki-view_faq')) {
                    $info = TikiLib::lib('faq')->get_faq($setup_query_data["faqId"]);
                    $name = $info["title"];
                }

                // Check if we are bookmarking a weblog
                if (str_contains($_SERVER["REQUEST_URI"], 'tiki-view_blog')) {
                    $bloglib = TikiLib::lib('blog');
                    $info = $bloglib->get_blog($setup_query_data["blogId"]);

                    $name = $info["title"];
                }
            }

            if (! empty($name)) {
                $bookmarklib->replace_url(0, $_SESSION["bookmarks_directory"], $name, $ownurl, $user);
            }
        }

        $modb_p_info = $bookmarklib->get_folder($_SESSION["bookmarks_directory"], $user);
        if (is_array($modb_p_info) && isset($modb_p_info["parentId"])) {
            $modb_father = $modb_p_info["parentId"];
        } else {
            $modb_father = 0;
        }

        $modb_urls = $bookmarklib->list_folder($_SESSION["bookmarks_directory"], 0, -1, 'name_asc', '', $user);
        $smarty->assign('modb_urls', $modb_urls["data"]);
        // get folders
        $modb_folders = $bookmarklib->get_child_folders($_SESSION["bookmarks_directory"], $user);
        $modb_pf = [
            "name" => "..",
            "folderId" => $modb_father,
            "parentId" => 0,
            "user" => $user
        ];

        if ($_SESSION["bookmarks_directory"]) {
            array_unshift($modb_folders, $modb_pf);
        }

        $smarty->assign('modb_folders', $modb_folders);
        $smarty->clear_assign('tpl_module_title');
    }
}
