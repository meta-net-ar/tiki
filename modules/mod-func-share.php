<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
//this script may only be included - so its better to die if called directly.
if (str_contains($_SERVER['SCRIPT_NAME'], basename(__FILE__))) {
    header('location: index.php');
    exit;
}

/**
 * @return array
 */
function module_share_info()
{
    return [
        'name' => tra('Share'),
        'description' => tra('Links for sharing, reporting etc.'),
        'params' => [
            'report' => [
                'name' => tra('Report'),
                'description' => tra('Report to Webmaster'),
                'filter' => 'alpha',
                'options' => [
                    ['text' => tra('Default: Yes'), 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n'],
                ],
            ],
            'share' => [
                'name' => tra('Share'),
                'description' => tra('Share this page'),
                'filter' => 'alpha',
                'options' => [
                    ['text' => tra('Default: Yes'), 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n'],
                ],
            ],
            'email' => [
                'name' => tra('Email'),
                'description' => tra('Email this page'),
                'filter' => 'alpha',
                'options' => [
                    ['text' => tra('Default: Yes'), 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n'],
                ],
            ],
            'icons' => [
                'name' => tra('Icons'),
                'description' => tra('Use icons for report, share and email links'),
                'filter' => 'alpha',
                'options' => [
                    ['text' => tra('Default: No'), 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n'],
                ],
            ],
            'facebook' => [
                'name' => tra('Facebook'),
                'description' => tra('Show Facebook "Like" button on the current page'),
                'filter' => 'alpha',
                'options' => [
                    ['text' => tra('Default: No'), 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n'],
                ],
            ],
            'facebook_href' => [
                'name' => tra('Facebook: URL'),
                'description' => tra('URL to "Like" (leave blank for current URL)'),
                'filter' => 'url',
            ],
            'facebook_send' => [
                'name' => tra('Facebook: Send'),
                'description' => tra('Show Facebook "Send" button'),
                'filter' => 'alpha',
                'options' => [
                    ['text' => tra('Default: Yes'), 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n'],
                ],
            ],
            'facebook_layout' => [
                'name' => tra('Facebook: Layout'),
                'description' => tra('Size, layout and amount of social context'),
                'filter' => 'text',
                'options' => [
                    ['text' => tra('Default: Standard'), 'value' => ''],
                    ['text' => tra('Standard'), 'value' => 'standard'],
                    ['text' => tra('Button count'), 'value' => 'button_count'],
                    ['text' => tra('Box count'), 'value' => 'box_count'],
                ],
            ],
            'facebook_width' => [
                'name' => tra('Facebook: Width'),
                'description' => tra('Width in pixels') . ' (450)',
                'filter' => 'digits',
            ],
            'facebook_height' => [
                'name' => tra('Facebook: Height'),
                'description' => tra('Container height in CSS units (e.g. "120px" or "2em")'),
                'filter' => 'text',
            ],
            'facebook_show_faces' => [
                'name' => tra('Facebook: Show Faces'),
                'description' => tra('Show pictures of others who like this'),
                'filter' => 'alpha',
                'options' => [
                    ['text' => tra('Default: Yes'), 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n'],
                ],
            ],
            'facebook_verb' => [
                'name' => tra('Facebook: Verb'),
                'description' => tra('Verb to display in button'),
                'filter' => 'word',
                'options' => [
                    ['text' => tra('Default: Like'), 'value' => ''],
                    ['text' => tra('Like'), 'value' => 'like'],
                    ['text' => tra('Recommend'), 'value' => 'recommend'],
                ],
            ],
            'facebook_colorscheme' => [
                'name' => tra('Facebook: Colors'),
                'description' => tra('Color scheme'),
                'filter' => 'word',
                'options' => [
                    ['text' => tra('Default: Light'), 'value' => ''],
                    ['text' => tra('Light'), 'value' => 'light'],
                    ['text' => tra('Dark'), 'value' => 'dark'],
                ],
            ],
            'facebook_font' => [
                'name' => tra('Facebook: Font'),
                'description' => tra('Font to display'),
                'filter' => 'text',
                'options' => [
                    ['text' => tra('Default: Lucida Grande'), 'value' => ''],
                    ['text' => 'Lucida Grande', 'value' => 'lucida grande'],
                    ['text' => 'Arial', 'value' => 'arial'],
                    ['text' => 'Segoe UI', 'value' => 'segoe ui'],
                    ['text' => 'Tahoma', 'value' => 'tahoma'],
                    ['text' => 'Trebuchet MS', 'value' => 'trebuchet ms'],
                    ['text' => 'Verdana', 'value' => 'verdana'],
                ],
            ],
            'facebook_locale' => [
                'name' => tra('Facebook: Locale'),
                'description' => tra('Locale in the format ll_CC (default "en_US")'),
                'filter' => 'text',
            ],
            'facebook_ref' => [
                'name' => tra('Facebook: Referrals'),
                'description' => tra('Label for tracking referrals (optional)'),
                'filter' => 'text',
            ],
            'facebook_appId' => [
                'name' => tra('Facebook: App Id'),
                'description' => tra('ID of your Facebook app (optional)'),
                'filter' => 'digits',
            ],
            'twitter' => [
                'name' => tra('Twitter'),
                'description' => tra('Show Twitter Follow Button on the current page'),
                'filter' => 'alpha',
                'options' => [
                    ['text' => tra('Default: No'), 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n'],
                ],
            ],
            'twitter_username' => [
                'name' => tra('Twitter: User Name'),
                'description' => tra('Twitter user name to quote as "via"'),
                'filter' => 'text',
            ],
            'twitter_label' => [
                'name' => tra('Twitter: Label'),
                'description' => tra('Text to display. Default "Tweet"'),
                'filter' => 'text',
            ],
            'twitter_url' => [
                'name' => tra('Twitter: URL'),
                'description' => tra('URL to "Tweet" (leave blank for current URL)'),
                'filter' => 'url',
            ],
            'twitter_show_count' => [
                'name' => tra('Twitter: Show Count'),
                'description' => tra('Position of Tweet count'),
                'options' => [
                    ['text' => tra('Default: Horizontal'), 'value' => ''],
                    ['text' => tra('Horizontal'), 'value' => 'horizontal'],
                    ['text' => tra('Vertical'), 'value' => 'vertical'],
                    ['text' => tra('None'), 'value' => 'none'],
                ],
                'filter' => 'alpha',
            ],
            'twitter_language' => [
                'name' => tra('Twitter: Language'),
                'description' => tra('Two letter language code'),
                'options' => [
                    ['text' => tra('Default: English'), 'value' => ''],
                    ['text' => tra('English'), 'value' => 'en'],
                    ['text' => tra('German'), 'value' => 'de'],
                    ['text' => tra('Spanish'), 'value' => 'es'],
                    ['text' => tra('French'), 'value' => 'fr'],
                    ['text' => tra('Indonesian'), 'value' => 'id'],
                    ['text' => tra('Italian'), 'value' => 'it'],
                    ['text' => tra('Korean'), 'value' => 'ko'],
                    ['text' => tra('Japanese'), 'value' => 'ja'],
                    ['text' => tra('Dutch'), 'value' => 'nl'],
                    ['text' => tra('Portuguese'), 'value' => 'pt'],
                    ['text' => tra('Reunionese'), 'value' => 're'],
                    ['text' => tra('Turkish'), 'value' => 'tr'],
                ],
                'filter' => 'word',
            ],
            'twitter_width' => [
                'name' => tra('Twitter: Width'),
                'description' => tra('Width in pixels or percentage (e.g. 300px)'),
                'filter' => 'text',
            ],
            'twitter_height' => [
                'name' => tra('Twitter: Height'),
                'description' => tra('Container height in CSS units (e.g. "120px" or "2em")'),
                'filter' => 'text',
            ],
            'twitter_text' => [
                'name' => tra('Twitter: Text'),
                'description' => tra('Tweet text (leave empty to use page title'),
                'filter' => 'word',
            ],
            'linkedin' => [
                'name' => tra('LinkedIn'),
                'description' => tra('Linked in share button'),
                'options' => [
                    ['text' => tra('Default: No'), 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n'],
                ],
                'filter' => 'alpha',
            ],
            'linkedin_url' => [
                'name' => tra('LinkedIn: URL'),
                'description' => tra('URL to share (leave blank for current URL)'),
                'filter' => 'url',
            ],
            'linkedin_mode' => [
                'name' => tra('LinkedIn: Count Mode'),
                'description' => tra('Position of count'),
                'options' => [
                    ['text' => tra('Default: None'), 'value' => ''],
                    ['text' => tra('None'), 'value' => 'none'],
                    ['text' => tra('Top'), 'value' => 'top'],
                    ['text' => tra('Right'), 'value' => 'right'],
                ],
                'filter' => 'word',
            ],
            'google' => [
                'name' => tra('Google +1'),
                'description' => tra('Google +1 button'),
                'options' => [
                    ['text' => tra('Default: No'), 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n'],
                ],
                'filter' => 'alpha',
            ],
            'google_size' => [
                'name' => tra('Google: Size'),
                'description' => tra('Google button size'),
                'options' => [
                    ['text' => tra('Default: Standard'), 'value' => ''],
                    ['text' => tra('Standard'), 'value' => 'standard'],
                    ['text' => tra('Small'), 'value' => 'small'],
                    ['text' => tra('Medium'), 'value' => 'medium'],
                    ['text' => tra('Tall'), 'value' => 'tall'],
                ],
                'filter' => 'word',
            ],
            'google_annotation' => [
                'name' => tra('Google: Annotation'),
                'description' => tra('Google annotation'),
                'options' => [
                    ['text' => tra('Default: Bubble'), 'value' => ''],
                    ['text' => tra('Bubble'), 'value' => 'bubble'],
                    ['text' => tra('Inline'), 'value' => 'inline'],
                    ['text' => tra('None'), 'value' => 'none'],
                ],
                'filter' => 'word',
            ],
            'google_language' => [
                'name' => tra('Google: Language'),
                'description' => tra('Google language') . ' (en-US|fr|ca|de|en-UK|...)',
                'filter' => 'text',
            ],
            'google_href' => [
                'name' => tra('Google: URL'),
                'description' => tra('URL to share (leave blank for current URL)'),
                'filter' => 'url',
            ],
        ],
    ];
}

/**
 * @param $mod_reference
 * @param $module_params
 */
function module_share($mod_reference, $module_params)
{
    static $share_mod_usage_counter = 0;
    $smarty = TikiLib::lib('smarty');
    $smarty->assign('share_mod_usage_counter', ++$share_mod_usage_counter);

    $smarty->assign('share_icons', ! empty($module_params['icons']) && $module_params['icons'] === 'y');

    // facebook like

    $fbData = '';
    $fbDivAttr = '';

    if (! empty($module_params['facebook_height'])) {
        $fbDivAttr .= ' height:' . $module_params['facebook_height'] . ';';
    }
    if (empty($module_params['facebook_send']) || $module_params['facebook_send'] === 'y') {
        $fbData .= ' data-send="true"';
    } else {
        $fbData .= ' data-send="false"';
    }
    if (! empty($module_params['facebook_layout']) && $module_params['facebook_layout'] !== 'standard') {
        $fbData .= ' data-layout="' . $module_params['facebook_layout'] . '"';
    }
    if (! empty($module_params['facebook_width'])) {
        $fbData .= ' data-width="' . $module_params['facebook_width'] . '"';
        $fbDivAttr .= ' width:' . $module_params['facebook_width'] . 'px;';
    }
    if (empty($module_params['facebook_show_faces']) || $module_params['facebook_layout'] === 'y') {
        $fbData .= ' data-show-faces="true"';
    } else {
        $fbData .= ' data-show-faces="false"';
    }
    if (! empty($module_params['facebook_verb']) && $module_params['facebook_verb'] !== 'like') {
        $fbData .= ' data-action="recommend"';
    }
    if (! empty($module_params['facebook_colorscheme']) && $module_params['facebook_colorscheme'] !== 'light') {
        $fbData .= ' data-colorscheme="dark"';
    }
    if (! empty($module_params['facebook_font']) && $module_params['facebook_font'] !== 'lucida grande') {
        $fbData .= ' data-font="' . $module_params['facebook_font'] . '"';
    }
    if (! empty($module_params['facebook_ref'])) {
        $fbData .= ' data-ref="' . htmlspecialchars($module_params['facebook_ref']) . '"';
    }
    if (! empty($module_params['facebook_href'])) {
        $fbData .= ' data-href="' . $module_params['facebook_href'] . '"';
    }
    $smarty->assign('fb_data_attributes', $fbData);

    if (! empty($module_params['facebook_appId'])) {
        $smarty->assign('fb_app_id_param', '&appId=' . $module_params['facebook_appId']);
    } else {
        $smarty->assign('fb_app_id_param', '');
    };
    if (! empty($fbDivAttr)) {
        $fbDivAttr = ' style="' . $fbDivAttr . '"';
    }
    $smarty->assign('fb_div_attributes', $fbDivAttr);

    // TODO find a way of matching up tiki lang with Facebook Locales

    if (! empty($module_params['facebook_locale'])) {
        $smarty->assign('fb_locale', $module_params['facebook_locale']);
    } else {
        $smarty->assign('fb_locale', 'en_US');
    };

    // twitter button

    $twData = '';
    $twDivAttr = '';

    if (! empty($module_params['twitter_height'])) {
        $twDivAttr .= ' height:' . $module_params['twitter_height'] . ';';
    }
    if (! empty($module_params['twitter_width'])) {
        $twDivAttr .= ' width:' . $module_params['twitter_width'] . ';';
    }
    if (empty($module_params['twitter_show_count']) || $module_params['twitter_show_count'] === 'horizontal') {
        $twData .= ' data-count="horizontal"';
    } else {
        $twData .= ' data-count="' . $module_params['twitter_show_count'] . '"';
    }
    if (! empty($module_params['twitter_username'])) {
        $twData .= ' data-via="' . $module_params['twitter_username'] . '"';
    }
    if (! empty($module_params['twitter_language'])) {
        $twData .= ' data-lang="' . $module_params['twitter_language'] . '"';
    }
    if (! empty($module_params['twitter_text'])) {
        $twData .= ' data-text="' . htmlspecialchars($module_params['twitter_text']) . '"';
    }
    if (! empty($module_params['twitter_url'])) {
        $twData .= ' data-url="' . $module_params['twitter_url'] . '"';
    }

    $smarty->assign('tw_data_attributes', $twData);

    if (! empty($twDivAttr)) {
        $twDivAttr = 'style="' . $twDivAttr . '"';
    }
    $smarty->assign('tw_div_attributes', $twDivAttr);

    // linkedin

    $liData = '';
    if (! empty($module_params['linkedin_url'])) {
        $liData .= ' data-url="' . $module_params['linkedin_url'] . '"';
    }
    if (! empty($module_params['linkedin_mode'])) {
        $liData .= ' data-counter="' . $module_params['linkedin_mode'] . '"';
    }
    $smarty->assign('li_data_attributes', $liData);

    // linkedin

    $glData = '';
    if (! empty($module_params['google_size']) && $module_params['google_size'] !== 'standard') {
        $glData .= ' data-size="' . $module_params['google_size'] . '"';
    }
    if (! empty($module_params['google_annotation']) && $module_params['google_annotation'] !== 'bubble') {
        $glData .= ' data-annotation="' . $module_params['google_annotation'] . '"';
    }
    if (! empty($module_params['google_href'])) {
        $glData .= ' data-href="' . $module_params['google_href'] . '"';
    }
    $smarty->assign('gl_data_attributes', $glData);

    if (! empty($module_params['google_language']) && $module_params['google_language'] !== 'en-US') {
        $smarty->assign('gl_script_addition', "  window.___gcfg = {lang: '{$module_params['google_language']}'};\n");
    }
}
