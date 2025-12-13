<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * @return array
 */
function module_last_podcasts_info()
{
    return [
        'name' => tra('Newest Podcasts'),
        'description' => tra('Displays Podcasts.'),
        'prefs' => ['feature_file_galleries'],
        'common_params' => ['nonums', 'rows'],
        'params' => [
            'galleryId' => [
                'required' => false,
                'name' => tra('File galleries IDs'),
                'description' => tra('List of IDs of file galleries of type "Podcast (Audio)". Identifiers are separated by a colon (":"). If none, all file galleries will be scanned.') . ' ' . tra('Example value:') . ' 1:3. ',
                'filter' => 'int',
                'separator' => ':',
                'profile_reference' => 'file_gallery',
            ],
            'link_url' => [
                'required' => false,
                'name' => tra('Bottom Link URL'),
                'description' => tra('URL for a link at bottom of module.'),
            ],
            'link_text' => [
                'required' => false,
                'name' => tra('Bottom Link URL Text'),
                'description' => tra('Text for link if Bottom Link URL is set.'),
                'default' => tra('More Podcasts'),
                'filter' => 'striptags',
            ],
            'verbose' => [
                'required' => false,
                'name' => tra('Verbose'),
                'description' => 'y|n ' . tra('Display description of podcast below player if "y", and on title mouseover if "n".'),
                'default' => 'y',
                'filter' => 'striptags',
            ]
        ]
    ];
}

/**
 * @param $mod_reference
 * @param $module_params
 */
function module_last_podcasts($mod_reference, $module_params)
{
    $smarty = TikiLib::lib('smarty');

    $filegallib = TikiLib::lib('filegal');

    if (isset($module_params['galleryId'])) {
        if (is_string($module_params['galleryId'])) {
            $module_params['galleryId'] = explode(':', $module_params['galleryId']);
        }
        $mediafiles = $filegallib->get_files(0, $mod_reference["rows"], 'created_desc', '', $module_params['galleryId']);
    } else {
        $mediafiles = $filegallib->list_files(0, $mod_reference["rows"], 'created_desc', '');
    }

    $mediaplayer = (isset($module_params['mediaplayer']) && is_readable($module_params['mediaplayer'])) ? $module_params['mediaplayer'] : '';

    $smarty->assign('modLastFiles', $mediafiles['data']);
    $smarty->assign('nonums', $module_params['nonums'] ?? 'n');
    $smarty->assign('verbose', $module_params['verbose'] ?? 'y');
    $smarty->assign('link_url', $module_params['link_url'] ?? '');
    $smarty->assign('link_text', $module_params['link_text'] ?? 'More Podcasts');
    $smarty->assign('module_rows', $mod_reference["rows"]);
}
