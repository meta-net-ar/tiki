<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function prefs_home_list($partial = false)
{

    return [
        'home_blog' => [
            'name' => tra('Home blog (main blog)'),
            'description' => tra('Select the main blog used as a homepage option and for default blog links.'),
            'type' => 'list',
            'options' => $partial ? [] : listblog_pref(),
            'default' => 0,
            'help' => 'Blog-Config#Home_Blog_main_blog_',
            'profile_reference' => 'blog',
        ],
        'home_forum' => [
            'name' => tra('Home forum (main forum)'),
            'description' => tra('Select the main forum used as a homepage option and for default forum links.'),
            'type' => 'text',
            'default' => 0,
            'help' => 'Forum-Settings',
            'profile_reference' => 'forum',
        ],
        'home_file_gallery' => [
            'name' => tra('Home file gallery (main file gallery)'),
            'description' => tra('Select the default file gallery'),
            'type' => 'list',
            'options' => $partial ? [] : TikiLib::lib('filegal')->getFileGalleryList(),
            'default' => 1,
            'help' => 'File-Gallery-General-Settings',
            'profile_reference' => 'file_gallery',
        ],
    ];
}

/**
 * listblog_pref: retrieve the list of blogs for the home_blog preference
 *
 * @access public
 * @return array: blogId => title(truncated)
 */
function listblog_pref()
{
    $bloglib = TikiLib::lib('blog');

    $allblogs = $bloglib->list_blogs(0, -1, 'created_desc', '');
    $listblogs = ['' => 'None'];

    if ($allblogs['count'] > 0) {
        foreach ($allblogs['data'] as $blog) {
            $listblogs[ $blog['blogId'] ] = substr($blog['title'], 0, 30);
        }
    } else {
        $listblogs[''] = tra('No blog available (create one first)');
    }

    return $listblogs;
}
