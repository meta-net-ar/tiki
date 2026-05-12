<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function prefs_site_list()
{
    global $prefs;

    $available_layouts = TikiLib::lib('theme')::listUserSelectableLayouts($prefs['site_theme'] ?? ($prefs['theme'] ?? ''), $prefs['theme_option'] ?? '');
    $available_admin_layouts = TikiLib::lib('theme')::listUserSelectableLayouts($prefs['theme_admin'] ?? ($prefs['theme'] ?? ''), $prefs['theme_option_admin'] ?? '');
    $listGroups = TikiLib::lib('user')->get_groups();
    $groups[''] = tr('All');
    foreach ($listGroups['data'] as $group) {
        $groups[$group['groupName']] = $group['groupName'];
    }
    unset($groups['Anonymous']);

    $siteAccessHelp = 'Site-Access';
    $miscellaneousHelp = 'Miscellaneous';
    $generalPreferencesHelp = 'General-Preferences';
    $generalSettingsHelp = 'General-Settings';
    $googleAnalyticsHelp = 'Google-Analytics';
    $mauticHelp = 'Mautic';
    $siteLayoutHelp = 'Site-Layout';
    $matomoHelp = 'Matomo';
    $generalSecurityHelp = 'General-Security';

    $preferences = [
        'site_closed' => [
            'name' => tra('Close site'),
            'description' => tra("Use this setting to \"close\" the Tiki site (such as for maintenance). Users attempting to access the site will see only a log-in form. Only users with specific permission will be allowed to log in. Use the Message to display to specify the message that visitors will see when attempting to access your site."),
            'type' => 'flag',
            'help' => 'Site-Access#Close_site',
            'perspective' => false,
            'tags' => ['basic'],
            'default' => 'n',
        ],
        'site_closed_title' => [
            'name' => tra('Title'),
            'description' => tra('Heading shown to visitors when the site is closed.'),
            'type' => 'text',
            'perspective' => false,
            'help' => $siteAccessHelp,
            'dependencies' => [
                'site_closed',
            ],
            'default' => tra('Coming soon'),
            'tags' => ['basic'],
        ],
        'site_closed_msg' => [
            'name' => tra('Message'),
            'description' => tra('Message shown to visitors when the site is closed.'),
            'type' => 'text',
            'perspective' => false,
            'help' => $siteAccessHelp,
            'dependencies' => [
                'site_closed',
            ],
            'default' => tra('Site is closed for maintenance; please come back later.'),
            'tags' => ['basic'],
        ],
        'site_busy_title' => [
            'name' => tra('Site Busy Title'),
            'description' => tra('Heading shown when the site is closed because server load exceeds the threshold.'),
            'type' => 'text',
            'perspective' => false,
            'help' => $siteAccessHelp,
            'dependencies' => [
                'use_load_threshold',
            ],
            'default' => tra('Server too busy'),
        ],
        'site_busy_msg' => [
            'name' => tra('Site Busy Message'),
            'description' => tra('Message shown when the site is closed because server load exceeds the threshold.'),
            'type' => 'text',
            'perspective' => false,
            'help' => $siteAccessHelp,
            'dependencies' => [
                'use_load_threshold',
            ],
            'default' => tra('Server is currently too busy; please come back later.'),
        ],
        'site_crumb_seper' => [
            'name' => tra('Locations (breadcrumbs)'),
            'description' => tra('Separator displayed between items in breadcrumb navigation paths.'),
            'type' => 'text',
            'hint' => tr('Examples:  » / >  : -> →'),
            'size' => '5',
            'default' => '»',
            'help' => $miscellaneousHelp,
        ],
        'site_nav_seper' => [
            'name' => tra('Choices'),
            'description' => tra('Separator between the site browser title and the current page title in the window title and meta tags.'),
            'type' => 'text',
            'hint' => tr('Examples: | / ¦  :'),
            'size' => '5',
            'default' => '|',
            'help' => $miscellaneousHelp,
        ],
        'site_title_location' => [
            'name' => tra('Browser title position'),
            'description' => tra('Position of the browser title in the full browser bar relative to the current page\'s descriptor.'),
            'type' => 'list',
            'options' => [
                'after' => tra('After current page\'s descriptor'),
                'before' => tra('Before current page\'s descriptor'),
                'none' => tra('No browser title, only current page\'s descriptor'),
                'only' => tra('Only browser title, no current page\'s descriptor'),
            ],
            'tags' => ['basic'],
            'default' => 'after',
            'help' => $generalPreferencesHelp,
        ],
        'site_title_breadcrumb' => [
            'name' => tra('Browser title display mode'),
            'description' => tra('When breadcrumbs are used, method to display the browser title.'),
            'type' => 'list',
            'options' => [
                'invertfull' => tra('Most-specific first'),
                'fulltrail' => tra('Least-specific first (site)'),
                'pagetitle' => tra('Current only'),
                'desc' => tra('Description'),
            ],
            'tags' => ['advanced'],
            'default' => 'invertfull',
            'help' => $generalPreferencesHelp,
        ],
        'site_favicon_enable' => [
            'name' => tr('Favicons'),
            'description' => tra('Custom favicon image files can be put in the /themes/(themename)/favicons directory, or the default Tiki favicons can be used.'),
            'type' => 'flag',
            'default' => 'y',
            'help' => 'Favicon',
        ],
        'site_terminal_active' => [
            'name' => tra('Site terminal'),
            'description' => tra('Allows users to be directed to a specific perspective depending on the origin IP address. Can be used inside intranets to use different configurations for users depending on their departements or discriminate people in web contexts. Unspecified IPs will fall back to default behavior, including multi-domain handling. Manually selected perspectives take precedence over this.'),
            'type' => 'flag',
            'dependencies' => [
                'feature_perspective',
            ],
            'default' => 'n',
            'help' => $generalSettingsHelp,
        ],
        'site_terminal_config' => [
            'name' => tra('Site terminal configuration'),
            'description' => tra('Provides the mapping from subnets to perspective.'),
            'type' => 'textarea',
            'perspective' => false,
            'size' => 10,
            'hint' => tra('One per line. Network prefix in CIDR notation (address/mask size), separated by comma with the perspective ID.') . ' ' . tra('Example:') . ' 192.168.12.0/24,12',
            'default' => '',
            'help' => $generalSettingsHelp,
        ],
        'site_google_analytics_account' => [
            'name' => tr('Google Analytics account number'),
            'description' => tra('The account number for the site. The account number from Google is something like UA-XXXXXXX-YY or G-XXXXXXXXXX.'),
            'type' => 'text',
            'size' => 15,
            'default' => '',
            'hint' => tr(' Enter the account number with prefix (prefix eg: "G-" or "UA-")'),
            'dependencies' => [
                'wikiplugin_googleanalytics',
            ],
            'help' => $generalSettingsHelp,
        ],
        'site_google_analytics_group_option' => [
            'name' => tr('Google Analytics Groups Option'),
            'description' => tr('Define option for Google Analytics groups'),
            'type' => 'list',
            'tags' => ['advanced'],
            'help' => $googleAnalyticsHelp,
            'options' => [
                '' => tr('None'),
                'included' => tr('Included'),
                'excluded' => tr('Excluded'),
            ],
            'default' => '',
            'dependencies' => [
                'wikiplugin_googleanalytics',
            ],
        ],
        'site_google_analytics_groups' => [
            'name' => tra('Google Analytics Available Groups'),
            'description' => tr('User groups for which Google Analytics will be available'),
            'type' => 'multilist',
            'tags' => ['advanced'],
            'options' => $groups,
            'help' => $googleAnalyticsHelp,
            'default' => [''],
            'dependencies' => [
                'site_google_analytics_group_option',
                'wikiplugin_googleanalytics',
            ],
        ],
        'site_google_analytics_site_ownership' => [
            'name' => tr('Google Analytics site ownership'),
            'description' => tra('Verification process of proving that you own the site or app that you claim to own'),
            'type' => 'text',
            'default' => '',
            'help' => $googleAnalyticsHelp,
        ],
        'site_google_analytics_gtag' => [
            'name' => tr('Google Global Site Tag Mode'),
            'description' => tra('Use the newer Google Global Site Tag (gtag.js) as opposed to the previous ga.js.'),
            'type' => 'flag',
            'default' => 'y',
            'dependencies' => [
                'site_google_analytics_account',
            ],
            'help' => $googleAnalyticsHelp,
        ],
        'site_google_credentials' => [
            'name' => tra('Google authentication credentials file'),
            'description' => tr('Path to the Google Service Account credentials JSON file.'),
            'type' => 'text',
            'size' => 30,
            'default' => '',
            'warning' => 'Must be kept private and not accessible on the internet directly',
            'help' => $googleAnalyticsHelp,
        ], // TODO: update the google analytics documentation
        'site_mautic_enable' => [
            'name' => tra('Mautic Integration'),
            'description' => tra('Enable the feature here but configure it elsewhere'),
            'type' => 'flag',
            'keywords' => 'mautic integration analytics',
            'default' => 'n',
            'admin' => 'mautic',
            'help' => $mauticHelp,
        ],
        'site_mautic_url' => [
            'name' => tra('Mautic URL'),
            'description' => tra('Base URL of your Mautic instance, used for tracking scripts, embedded forms, and API calls.'),
            'type' => 'text',
            'filter' => 'text',
            'dependencies' => 'site_mautic_enable',
            'default' => '',
            'tags' => ['basic'],
            'help' => $mauticHelp,
        ],
        'site_mautic_tracking_script_location' => [
            'name' => tra('Tracking Script Location'),
            'description' => tra('Where to inject the site-wide Mautic pageview tracking script: in the page head, in the footer before </body>, or not at all (tracking only where the Mautic plugin is used).'),
            'type' => 'radio',
            'options' => [
                'head' => tra('Added in the <head> section of tiki pages.'),
                'embed' => tra('Embed it within the footer are before </body> tag.'),
                'visitor' => tra('Visitor will not be tracked when rendering the page.'),
            ],
            'help' => $mauticHelp,
            'default' => 'embed',
        ],
        'site_mautic_username' => [
            'name' => tra('Mautic Username'),
            'description' => tra('Mautic API username for Basic authentication when loading contacts through the Mautic plugin.'),
            'type' => 'text',
            'dependencies' => ['site_mautic_enable'],
            'perspective' => false,
            'default' => '',
            'help' => $mauticHelp,
        ],
        'site_mautic_password' => [
            'name' => tra('Mautic Password'),
            'description' => tra('Mautic API password for Basic authentication when loading contacts through the Mautic plugin.'),
            'type' => 'password',
            'dependencies' => ['site_mautic_enable'],
            'perspective' => false,
            'default' => '',
            'help' => $mauticHelp,
        ],
        'site_layout' => [
            'name' => tr('Site layout'),
            'description' => tr('Changes the template for the overall site layout'),
            'type' => 'list',
            'default' => SMARTY_DEFAULT_LAYOUT,
            'help' => $siteLayoutHelp,
            'hint' => tra('Important: when using the Classic Bootstrap (fixed top navbar) layout, be sure to set the fixed-top navbar height, below, to prevent content overlap.'),
            'tags' => ['advanced'],
            'options' => $available_layouts,
        ],
        'site_layout_per_object' => [
            'name' => tr('Enable layout per page, etc.'),
            'description' => tr('Specify an alternate layout for a particular wiki page, etc.'),
            'tags' => ['experimental'],
            'type' => 'flag',
            'default' => 'n',
            'help' => 'General-Layout-Options',
        ],
        'site_matomo_analytics_server_url' => [
            'name' => tr('Matomo server URL'),
            'description' => tr('The URL to the Matomo server of this site') . '<br />'
                    . tr('In Matomo, the selected site (Site Id) must have view permission set for anonymous, or a token authentication parameter can be inserted in the Matomo server URL.'),
            'type' => 'text',
            'filter' => 'url',
            'size' => 30,
            'default' => '',
            'hint' => 'http(s)://yourMatomo.tld/index.php(?token_auth=yourtokencode)',
            'help' => $matomoHelp,
        ],
        'site_matomo_site_id' => [
            'name' => tra('Site Id'),
            'description' => tr('The ID of this website in Matomo'),
            'type' => 'text',
            'size' => '5',
            'default' => '',
            'dependencies' => [
                'site_matomo_analytics_server_url',
            ],
            'help' => $matomoHelp,
        ],
        'site_matomo_code' => [
            'name' => tra('Matomo JavaScript tracking code'),
            'description' => tra("Code to be placed on every page of this website just before the </body> tag"),
            'type' => 'textarea',
            'size' => '6',
            'filter' => 'rawhtml_unsafe',
            'default' => '',
            'dependencies' => [
                'site_matomo_analytics_server_url',
                'wikiplugin_matomo',
            ],
            'help' => $matomoHelp,
        ],
        'site_matomo_group_option' => [
            'name' => tr('Matomo Groups Option'),
            'description' => tr('Define option for Matomo groups'),
            'type' => 'list',
            'tags' => ['advanced'],
            'options' => [
                '' => tr('None'),
                'included' => tr('Included'),
                'excluded' => tr('Excluded'),
            ],
            'default' => '',
            'dependencies' => [
                'site_matomo_code',
                'wikiplugin_matomo',
            ],
            'help' => $matomoHelp,
        ],
        'site_matomo_groups' => [
            'name' => tr('Matomo Available Groups'),
            'description' => tr('User groups for which Matomo will be available'),
            'type' => 'multilist',
            'tags' => ['advanced'],
            'options' => $groups,
            'default' => [''],
            'dependencies' => [
                'site_matomo_group_option',
                'wikiplugin_matomo',
            ],
            'help' => $matomoHelp,
        ],
        'site_short_lived_csrf_tokens' => [
            'name' => tra('Use short lived CSRF tokens'),
            'description' => tra("CSRF tokens generated will be valid for one use only and will have a limited life span"),
            'warning' => tra('Changing the CSRF tokens to be short lived may lead to an increase of errors on submitting information when the users take a long time to finish an operation or the session is lost.'),
            'type' => 'flag',
            'default' => 'n',
            'tags' => ['advanced'],
            'help' => $generalSecurityHelp,
        ],
        'site_security_timeout' => [
            'name' => tra('Security timeout'),
            'description' => tr('Sets the expiration of CSRF tickets and related forms. The %0session_lifetime%1
                preference is used for the default, if set, otherwise the %0session.gc_maxlifetime%1 %0php.ini%1 setting
                is used, subject to a default maximum of four hours in any case.', '<code>', '</code>'),
            'type' => 'text',
            'filter' => 'digits',
            'warning' => tra('Minimum value is 30 seconds to avoid blocking everyone from being able to make any changes, including to this setting'),
            'units' => tra('seconds'),
            'constraints' => [
                'min' => 30
            ],
            'tags' => ['advanced'],
            'default' => TikiLib::lib('access')->getDefaultTimeout(),
            'dependencies' => [
                'site_short_lived_csrf_tokens',
            ],
            'help' => $generalSecurityHelp,
        ],
    ];

    // Only include the admin layout preference if UAB is disabled
    if (isset($prefs['theme_unified_admin_backend']) && $prefs['theme_unified_admin_backend'] !== 'y') {
        $preferences['site_layout_admin'] = [
            'name' => tr('Admin layout'),
            'description' => tr('Specify which layout template to use for admin pages.'),
            'type' => 'list',
            'default' => SMARTY_DEFAULT_LAYOUT,
            'help' => $siteLayoutHelp,
            'hint' => tra('Note: this does not affect the Unified Admin Backend. Only the legacy admin pages when UAB is disabled. An admin theme must be selected first.'),
            'tags' => ['advanced'],
            'options' => $available_admin_layouts,
            'mandatory_dependencies' => [
                'theme_admin',
            ],
        ];
    }

    return $preferences;
}
