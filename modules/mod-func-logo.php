<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * @return array
 */
function module_logo_info()
{
    return [
        'name' => tra('Logo'),
        'description' => tra('Site logo, title and subtitle.'),
        'prefs' => ['feature_sitelogo'],
        'params' => [
            'src' => [
                'name' => tra('Logo Image URL'),
                'description' => tra('Source URL for the site logo image file. Defaults to sitelogo_src preference (set on Look & Feel admin).'),
                'filter' => 'url',
            ],
            'title_attr' => [               // seems module params called title disappear?
                'name' => tra('HTML Image Title'),
                'description' => tra('Image title attribute. Defaults to sitelogo_title preference.'),
                'filter' => 'text',
            ],
            'alt_attr' => [
                'name' => tra('HTML Alt Attribute'),
                'description' => tra('Image alt attribute for screen readers, etc. Defaults to sitelogo_alt preference.'),
                'filter' => 'text',
            ],
            'link' => [
                'name' => tra('Link'),
                'description' => tra('URL for the image and titles link. Defaults to "./" (site homepage).'),
                'filter' => 'url',
            ],
            'sitetitle' => [
                'name' => tra('Site Title'),
                'description' => tra('Large text to identify the website. Defaults to sitetitle preference.'),
                'filter' => 'text',
            ],
            'sitesubtitle' => [
                'name' => tra('Site Subtitle'),
                'description' => tra('Smaller text for a short description, etc. Defaults to sitesubtitle preference.'),
                'filter' => 'text',
            ],
            'class_image' => [
                'name' => tra('Logo Class'),
                'description' => tra('CSS class for the logo image. Defaults to sitelogo.'),
                'filter' => 'text',
            ],
            'class_titles' => [
                'name' => tra('Site Titles Class'),
                'description' => tra('CSS class for the title text container div. Defaults to sitetitles.'),
                'filter' => 'text',
            ],
            'max_height' => [
                'name' => tra('Logo maximum height'),
                'description' => tra('Maximum height in pixels for the logo image. Leave empty for automatic sizing based on navbar content.'),
                'filter' => 'digits',
                'default' => '',
                'units' => 'px',
            ],
        ],
    ];
}

/**
 * @param $mod_reference
 * @param $module_params
 */
function module_logo($mod_reference, &$module_params)
{
    global $prefs;

    $module_params = array_merge(
        [
            'src'          => $prefs['sitelogo_src'],
            'title_attr'   => $prefs['sitelogo_title'],
            'alt_attr'     => $prefs['sitelogo_alt'],
            'link'         => './',
            'sitetitle'    => $prefs['sitetitle'],
            'sitesubtitle' => $prefs['sitesubtitle'],
            'class_image'  => 'sitelogo',
            'class_titles' => 'sitetitles',
            'max_height'   => '',
        ],
        $module_params
    );
}
