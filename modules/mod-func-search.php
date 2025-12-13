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
function module_search_info()
{
    return [
        'name' => tra('Search'),
        'description' => tra('Multi-purpose search module (go or edit page by name and/or search site)'),
        'prefs' => [],
        'params' => [
            'legacy_mode' => [
                'name' => tra('Legacy Mode'),
                'description' => tra('Setting to emulate previous behaviour.') . ' ' . tra('Default:') . ' ""' . ' ("search"=search_box, "page"=search_wiki_page, "quick"=quick_edit)'
            ],
            'tiki_search' => [
                'name' => tra('Tiki'),
                'description' => tra('If set to "y" the search performed is a "Tiki search".') . ' ' . tra('Default:') . ' "n"' . tra(' (full text search)')
            ],
            'show_object_filter' => [
                'name' => tra('Show Object Type Filter'),
                'description' => tra('If set to "y" shows a dropdown of sections to search.') . ' ' . tra('Default:') . ' "n"' . tra(' (no object filter)')
            ],
            'use_autocomplete' => [
                'name' => tra('Use autocomplete'),
                'description' => tra('If set to "y" input uses autocomplete for pagenames if applicable.') . ' ' . tra('Default:') . ' "y"' . tra(' (use autocomplete)')
            ],
            'advanced_search' => [
                'name' => tra('Advanced search'),
                'description' => tra('Use advanced (boolean) search (full text search only).') . ' ' . tra('Default:') . ' "y"' . tra(' (use advanced search)'),
            ],
            'show_search_button' => [
                'name' => tra('Show Search Button'),
                'description' => tra('Show search button.') . ' ' . tra('Default:') . ' "y"' . tra(' (do show search button)'),
            ],
            'show_go_button' => [
                'name' => tra('Show Go Button'),
                'description' => tra('Show go to page button.') . ' ' . tra('Default:') . ' "y"' . tra(' (do show go button)'),
            ],
            'show_edit_button' => [
                'name' => tra('Show Edit Button'),
                'description' => tra('Show edit button.') . ' ' . tra('Default:') . ' "y"' . tra(' (do show edit button)'),
            ],
            'default_button' => [
                'name' => tra('Default Button'),
                'description' => tra('Action to perform on entering <return>.') . ' ' . tra('Default:') . ' "search"' . tra(' (search|go|edit)'),
            ],
            // initially from quick-edit
            'search_action' => [
                'name' => tra('Search Form Action'),
                'description' => tra("If set, send the form to the given location (relative to Tiki's root) for processing.") . ' ' . tra('Default:') . tra(' tiki-searchindex.php (for Tiki search)'),
            ],
            'search_submit' => [
                'name' => tra('Search Submit Label'),
                'description' => tra('The label on the button to submit the form.') . ' ' . tra('Default:') . ' ' . tra('Search'),
            ],
            'go_action' => [
                'name' => tra('Go Form Action'),
                'description' => tra("If set, send the form to the given location (relative to Tiki's root) for processing.") . ' ' . tra('Default:') . ' tiki-listpages.php'
            ],
            'go_submit' => [
                'name' => tra('Go Submit Label'),
                'description' => tra('The label on the button to submit the form.') . ' ' . tra('Default:') . ' ' . tra('Go')
            ],
            'edit_action' => [
                'name' => tra('Edit Form Action'),
                'description' => tra("If set, send the form to the given location (relative to Tiki's root) for processing.") . ' ' . tra('Default:') . ' tiki-editpage.php'
            ],
            'edit_submit' => [
                'name' => tra('Edit Submit Label'),
                'description' => tra('The label on the button to submit the form.') . ' ' . tra('Default:') . ' ' . tra('Edit')
            ],
            'input_size' => [
                'name' => tra('Input field width'),
                'description' => tra('Width of the text input field (in characters).') . ' ' . tra('Example value:') . ' 15.' . ' ' . tra('Default:') . tra(' 0 (leave automatic width)'),
                'filter' => 'int'
            ],
            'select_size' => [
                'name' => tra('Select size'),
                'description' => tra('Size of the Search Filter dropdown list.') . ' ' . tra('Default:') . ' 10',
                'filter' => 'int'
            ],
            'search_heading' => [
                'name' => tra('Heading'),
                'description' => tra("Optional heading to display at the top of the module's content.")
            ],
            'templateId' => [
                'name' => tra('Edit Template identifier'),
                'description' => tra('If set to a template identifier, the specified template is used for creating new Wiki pages.') . ' ' . tra('Not set by default.')
            ],
            'categId' => [
                'name' => tra('Category identifier'),
                'description' => tra('If set to a category identifier, pages created through the module are automatically categorized in the specified category.') . ' ' . tra('Not set by default.')
            ],
            'compact' => [
                'name' => tra('Compact mode'),
                'description' => tra('Makes the three buttons only appear on mouse-over.') . ' ' . tra('Default:') . ' "n"'
            ],
            'additional_filters' => [
                'name' => tr('Additional filters'),
                'description' => tr('Filters to be applied to the search results, as a URL-encoded string. Ex.: catgories=1+AND+2&prefix~title=Test'),
            ],
        ]
    ];
}

/**
 * @param $mod_reference
 * @param $smod_params
 */
function module_search($mod_reference, $smod_params)    // modifies $smod_params so uses & reference
{
    $smarty = TikiLib::lib('smarty');
    global $prefs;
    static $search_mod_usage_counter = 0;
    $smarty->assign('search_mod_usage_counter', ++$search_mod_usage_counter);

    $smarty->assign('module_error', '');
    $smarty->assign_by_ref('smod_params', $smod_params);

    if ($prefs['feature_search'] == 'n') {
        $smod_params['tiki_search'] = 'none';
        $smarty->assign('module_error', tra('feature_search is disabled.'));
        return;
    }

    if (isset($smod_params['go_action']) && $smod_params['go_action'] == 'ti') {    // temporary fix for 5.0 in case params were truncated in the db
        unset($smod_params['go_action']);
    }

    if (isset($smod_params['additional_filters'])) {
        parse_str($smod_params['additional_filters'], $out);
        $smod_params['additional_filters'] = $out ?: [];
    } else {
        $smod_params['additional_filters'] = [];
    }

    // set up other param defaults
    $defaults = [
        'legacy_mode' => '',
        'show_object_filter' => 'n',
        'use_autocomplete' => 'y',
        'tiki_search' => 'y',
        'advanced_search' => 'y',
        'show_search_button' => 'y',
        'show_go_button' => 'y',
        'show_edit_button' => 'y',
        'default_button' => 'search',
        'input_size' => 0,
        'select_size' => 10,
        'search_action' => 'tiki-searchindex.php',
        'search_submit' => tra('Search'),
        'go_action' => 'tiki-listpages.php',
        'go_submit' => tra('Go'),
        'edit_action' => 'tiki-editpage.php',
        'edit_submit' => tra('Edit'),
        'search_heading' => '',
        'templateId' => '',
        'categId' => '',
        'compact' => 'n',
        'title' => tra('Search'),
    ];

    $smod_params = array_merge($defaults, $smod_params);

    if (isset($smod_params['tiki_search']) && $smod_params['tiki_search'] == 'y') {
        $smod_params['advanced_search'] = 'n';
    }

    switch ($smod_params['legacy_mode']) {
        case 'quick':       // params from old quick_edit module
            $smod_params['show_search_button'] = 'n';
            $smod_params['show_go_button'] = 'n';
            $smod_params['show_edit_button'] = 'y';
            $smod_params['edit_submit'] = $smod_params['submit'] ?? tra("Create/Edit");
            $smod_params['default_button'] = 'edit';
            $smod_params['edit_action'] = $smod_params['action'] ?? 'tiki-editpage.php';
            $smod_params['input_size'] = $smod_params['size'] ?? 15;
            $smod_params['search_heading'] = $smod_params['mod_quickedit_heading'] ?? $smod_params['search_heading'];
            $smod_params['title'] = tra('Quick Edit a Wiki Page');
            break;

        case 'search':      // params from old search_box module
            $smod_params['tiki_search'] = $smod_params['tiki'] ?? 'n';
            $smod_params['show_search_button'] = 'y';
            $smod_params['show_go_button'] = 'n';
            $smod_params['show_edit_button'] = 'n';
            $smod_params['advanced_search'] = 'y';
            $smod_params['search_submit'] = tra('Go');
            $smod_params['default_button'] = 'search';
            break;

        case 'page':        // params from old search_wiki_page module
            $smod_params['show_search_button'] = 'n';
            $smod_params['show_go_button'] = 'y';
            $smod_params['show_edit_button'] = 'n';
            $smod_params['go_submit'] = tra('Go');
            $smod_params['default_button'] = 'go';
            $smod_params['title'] = tra('Search for Wiki Page');
            break;

        case '':
        default:
            break;
    }

    switch ($smod_params['default_button']) {
        case 'edit':
            $smod_params['default_action'] = $smod_params['edit_action'];
            break;

        case 'go':
            $smod_params['default_action'] = $smod_params['go_action'];
            break;

        case 'search':
        default:
            $smod_params['default_action'] = $smod_params['search_action'];
            break;
    }

    if (
        ($smod_params['show_search_button'] == 'y' || $smod_params['default_action'] == $smod_params['search_action'])
            && $smod_params['show_edit_button'] == 'n' && $smod_params['show_go_button'] == 'n'
    ) {
        $smod_params['use_autocomplete'] = 'n';
    }

    if (! empty($_REQUEST['highlight'])) {
        $smod_params['input_value'] = $_REQUEST['highlight'];
    } elseif (! empty($_REQUEST['words'])) {
        $smod_params['input_value'] = $_REQUEST['words'];
    } elseif (! empty($_REQUEST['find'])) {
        $smod_params['input_value'] = $_REQUEST['find'];
    } elseif (! empty($_REQUEST['filter']['content']) && is_array($_REQUEST['filter'])) {
        $smod_params['input_value'] = $_REQUEST['filter']['content'];
    } else {
        $smod_params['input_value'] = '';
    }
    if (! empty($_REQUEST['where'])) {
        $smod_params['where'] = $_REQUEST['where'];
    } elseif (! empty($_REQUEST['filter']['type'])) {
        $smod_params['where'] = $_REQUEST['filter']['type'];
    } else {
        $smod_params['where'] = '';
    }
    if (! empty($_REQUEST['boolean_last'])) {
        if (! empty($_REQUEST['boolean'])) {
            $smod_params['advanced_search'] = 'y';
        } else {
            $smod_params['advanced_search'] = 'n';
        }
    }
}
