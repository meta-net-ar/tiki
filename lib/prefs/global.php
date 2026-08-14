<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
use Tiki\Package\VendorHelper;

function prefs_global_list($partial = false)
{
    $dateAndTimeDateAndTimeFormatsHelp = 'Date-and-Time#Date_and_Time_Formats';
    $loginGeneralPreferencesHelp = 'Login-General-Preferences';
    $wikiGeneralPreferencesHelp = 'Wiki-General-Preferences';
    $generalLayoutOptionsHelp = 'General-Layout-Options';
    $generalPreferencesHelp = 'General-Preferences';
    $wikiSyntaxLinksHelp = 'Wiki-Syntax-Links';
    $navigationHelp = 'Navigation';
    $copyrightHelp = 'Copyright';
    $groupsHelp = 'Groups';
    $mapsHelp = 'Maps';
    $moduleSettingsParametersHelp = 'Module-Settings-Parameters';
    $ldapAuthenticationHelp = 'LDAP-authentication';

    return [
        'browsertitle' => [
            'name' => tra('Browser title'),
            'description' => tra('Visible label in the browser\'s title bar on all pages. Also appears in search engine results.'),
            'type' => 'text',
            'default' => '',
            'help' => $generalPreferencesHelp,
            'tags' => ['basic'],
            'public' => true,
            'translatable' => true,
        ],
        'fallbackBaseUrl' => [
            'name' => tra('Fallback for tiki base URL'),
            'description' => tra('The full URL to the Tiki base URL including protocol, domain and path (example: https://example.org/tiki/), used when the current URL can not be determined, example, when executing from the command line.'),
            'type' => 'text',
            'default' => '',
            'help' => $generalPreferencesHelp,
            'tags' => ['basic'],
            'public' => true,
        ],
        'validateUsers' => [
            'name' => tra('Validate new user registrations by email'),
            'description' => tra('Tiki will send an email message to the user. The message contains a link that must be clicked to validate the registration. After clicking the link, the user will be validated. You can use this option to limit false registrations or fake email addresses.'),
            'type' => 'flag',
            'help' => $loginGeneralPreferencesHelp,
            'dependencies' => [
                'sender_email',
            ],
            'default' => 'y',
            'tags' => ['basic'],
        ],
        'wikiHomePage' => [
            'name' => tra('Wiki homepage'),
            'description' => tra('The default home page of the wiki when no other page is specified. The page will be created if it does not already exist.'),
            'keywords' => 'homepage',
            'type' => 'text',
            'help' => $wikiGeneralPreferencesHelp,
            'size' => 20,
            'default' => 'HomePage',
            'tags' => ['basic'],
            'profile_reference' => 'wiki_page',
        ],
        'useGroupHome' => [
            'name' => tra('Use group homepages'),
            'description' => tra('Users can be directed to different pages upon logging in, depending on their default group.'),
            'type' => 'flag',
            'help' => $groupsHelp,
            'keywords' => 'group home page pages',
            'default' => 'n',
        ],
        'limitedGoGroupHome' => [
            'name' => tra('Go to the group homepage only if logging in from the default homepage'),
            'type' => 'flag',
            'help' => $wikiGeneralPreferencesHelp,
            'dependencies' => [
                'useGroupHome',
            ],
            'keywords' => 'group home page pages',
            'default' => 'n',
        ],
        'cachepages' => [
            'name' => tra('Cache external pages'),
            'type' => 'flag',
            'help' => $wikiSyntaxLinksHelp,
            'default' => 'n',
        ],
        'cacheimages' => [
            'name' => tra('Cache external images'),
            'type' => 'flag',
            'help' => 'Cache-External-Images',
            'default' => 'n',
        ],
        'tmpDir' => [
            'name' => tra('Temporary directory'),
            'description' => tra('Directory on your server, relative to your Tiki installation, for storing temporary files. Tiki must have full read and write access to this directory.'),
            'keywords' => 'tmp temp path',
            'type' => 'text',
            'help' => 'General-Settings',
            'size' => 30,
            'default' => sys_get_temp_dir(),  // note: this gets overridden in lib/setup/prefs.php
            'perspective' => false,
        ],
        'helpurl' => [
            'name' => tra('Help URL'),
            'description' => tra('The default help system may not be complete. You can contribute to the Tiki documentation, which is a community-edited wiki.'),
            'help' => 'Welcome-Authors',
            'type' => 'text',
            'size' => '50',
            'dependencies' => [
                'feature_help',
            ],
            'default' => "http://doc.tiki.org/",
            'public' => true,
        ],
        'popupLinks' => [
            'name' => tra('Open external links in new window'),
            'type' => 'flag',
            'description' => tr('Open links to external sites in a new browser tab or window.'),
            'default' => 'y',
            'help' => $wikiSyntaxLinksHelp,
            'tags' => ['basic'],
        ],
        'allowImageLazyLoad' => [
            'name' => tra('Allow image lazy loading'),
            'type' => 'flag',
            'help' => 'UI-Effects',
            'description' => tr('Allow that images are loaded in a lazy way'),
            'default' => 'n',
            'tags' => ['advanced'],
        ],
        'wikiLicensePage' => [
            'name' => tra('License page'),
            'description' => tra('The wiki page where the license information is written.'),
            'type' => 'text',
            'help' => $copyrightHelp,
            'size' => '30',
            'default' => '',
        ],
        'wikiSubmitNotice' => [
            'name' => tra('Submit notice'),
            'description' => tra('Text to appear when content is being submitted'),
            'type' => 'text',
            'help' => $copyrightHelp,
            'size' => '30',
            'default' => '',
        ],
        'gdaltindex' => [
            'name' => tra('Full path to gdaltindex'),
            'description' => tra('Full filesystem path to the GDAL gdaltindex utility, used to build raster tile indexes for map imagery.'),
            'type' => 'text',
            'size' => '50',
            'help' => $mapsHelp,
            'perspective' => false,
            'default' => '',
        ],
        'ogr2ogr' => [
            'name' => tra('Full path to ogr2ogr'),
            'description' => tra('Full filesystem path to the GDAL ogr2ogr utility, used to convert between vector geospatial data formats.'),
            'type' => 'text',
            'size' => '50',
            'help' => $mapsHelp,
            'perspective' => false,
            'default' => '',
        ],
        'mapzone' => [
            'name' => tra('Map Zone'),
            'description' => tra('Longitude range used for map coordinates: -180 to 180, or 0 to 360.'),
            'type' => 'list',
            'help' => $mapsHelp,
            'options' => [
                '180' => '[-180 180]',
                '360' => '[0 360]',
            ],
            'default' => '180',
        ],
        'modallgroups' => [
            'name' => tra('Always display modules to all groups'),
            'type' => 'flag',
            'description' => tr('Any setting for the Groups parameter will be ignored and the module will be displayed to all users.'),
            'default' => 'n',
            'help' => $moduleSettingsParametersHelp,
        ],
        'modseparateanon' => [
            'name' => tra('Hide anonymous-only modules from registered users'),
            'type' => 'flag',
            'description' => tr('If an individual module is assigned to the Anonymous group, the module will be displayed only to anonymous visitors. Registered users will not see the module.'),
            'default' => 'n',
            'help' => $moduleSettingsParametersHelp,
        ],
        'modhideanonadmin' => [
            'name' => tra('Hide anonymous-only modules from Admins'),
            'description' => tra('Hide modules assigned only to the Anonymous group from administrators, so admins see the site as registered users would.'),
            'type' => 'flag',
            'default' => 'n',
            'help' => $moduleSettingsParametersHelp,
        ],
        'maxArticles' => [
            'name' => tra('Maximum number of articles on the articles homepage'),
            'type' => 'text',
            'description' => tr('The number of articles to show on each page of the Articles homepage.'),
            'size' => '5',
            'help' => 'Articles-General-Settings',
            'filter' => 'digits',
            'units' => tra('articles'),
            'default' => 10,
        ],
        'sitead' => [
            'name' => tra('Site Ads and Banners Content'),
            'description' => tra('Wiki-formatted content for site-wide ads and banners, typically using the banner plugin to display banner zones.'),
            'hint' => tra('Example:') . ' ' . "{banner zone='" . tra('Test') . "'}",
            'type' => 'textarea',
            'size' => '5',
            'default' => '',
            'help' => 'Site-Ads-and-Banners',
        ],
        'urlOnUsername' => [
            'name' => tra('URL to go to when clicking on a username'),
            'type' => 'text',
            'description' => tr('URL to go to when clicking on a username. Default: %0 Use %user% for login name and %userId% for userId) %1', ': tiki-user_information.php?userId=%userId% <em>(', ')</em>'),
            'default' => '',
            'help' => $navigationHelp,
        ],
        'forgotPass' => [
            'name' => tra('Forgot password'),
            'description' => tra('Users can request a password reset. They will receive a link by email.'),
            'type' => 'flag',
            'detail' => tra("Since passwords are stored securely, it's not possible to tell the user what the password is. It's only possible to change it."),
            'default' => 'y',
            'help' => $loginGeneralPreferencesHelp,
            'tags' => ['basic'],
        ],
        'twoFactorAuth' => [
            'name' => tra('Allow users to use 2FA'),
            'description' => tra('Allow users to enable Two-factor Authentication.'),
            'type' => 'flag',
            'help' => 'Two-factor-authentication',
            'default' => 'n',
        ],
        'twoFactorAuthType' => [
            'name' => tra('2FA Type'),
            'description' => tra('Type of 2FA to be used.'),
            'type' => 'list',
            'help' => $loginGeneralPreferencesHelp,
            'options' => [
                \Tiki\TwoFactorAuth\TwoFactorAuth::TOTP_2FA => tra('Authenticator App (TOTP)'),
                \Tiki\TwoFactorAuth\TwoFactorAuth::EMAIL_2FA => tra('Email 2FA'),
            ],
            'default' => \Tiki\TwoFactorAuth\TwoFactorAuth::TOTP_2FA,
        ],
        'twoFactorAuthEmailTokenLength' => [
            'name' => tra('Email 2FA Token Length'),
            'description' => tra('The token length generated by Tiki for email 2FA.'),
            'type' => 'text',
            'default' => '6',
            'help' => $loginGeneralPreferencesHelp,
            'dependencies' => [
                'twoFactorAuth',
            ],
        ],
        'twoFactorAuthEmailTokenChars' => [
            'name' => tra('Email 2FA Token Allowed Chars'),
            'description' => tra('The list of characters used to generate the email token. Specify as a regex character class, e.g. 0-9 for numbers only.'),
            'type' => 'text',
            'default' => '',
            'help' => $loginGeneralPreferencesHelp,
            'dependencies' => [
                'twoFactorAuth',
            ],
        ],
        'twoFactorAuthEmailTokenTTL' => [
            'name' => tra('Email 2FA Token Time-to-live'),
            'description' => tra('The time-to-live for the token generated by Tiki for email 2FA.'),
            'type' => 'text',
            'default' => '30',
            'help' => $loginGeneralPreferencesHelp,
            'units' => tra('minutes'),
            'dependencies' => [
                'twoFactorAuth',
            ],
        ],
        'twoFactorAuthIntervalDays' => [
            'name' => tra('Number of days before requiring new MFA'),
            'description' => tra('A value of zero (default) means always, a value bigger than zero requires a user to go through the MFA challenge every X days.'),
            'type' => 'text',
            'default' => '0',
            'help' => $loginGeneralPreferencesHelp,
            'dependencies' => [
                'twoFactorAuth',
            ],
        ],
        'twoFactorAuthAllUsers' => [
            'name' => tra('Force all users to use 2FA'),
            'description' => tra('This will force all users to activate 2FA.'),
            'type' => 'flag',
            'help' => $loginGeneralPreferencesHelp,
            'dependencies' => [
                'twoFactorAuth',
            ],
            'default' => 'n',
        ],
        'twoFactorAuthGracePeriod' => [
            'name' => tra('2FA Grace Period'),
            'description' => tra('Number of days to allow users to access the site without 2FA before forcing them to set it up. Note: this applies globally. If you want specific periods per groups, visit the groups settings.'),
            'type' => 'text',
            'default' => '0',
            'help' => $loginGeneralPreferencesHelp,
            'dependencies' => [
                'twoFactorAuth',
            ],
        ],
        'twoFactorAuthIncludedGroup' => [
            'name' => tra('Force users in the indicated groups to enable 2FA'),
            'description' => tra('List of group names.'),
            'type' => 'text',
            'separator' => ';',
            'filter' => 'groupname',
            'profile_reference' => 'group',
            'help' => $loginGeneralPreferencesHelp,
            'dependencies' => [
                'twoFactorAuth',
            ],
            'default' => [],
        ],
        'twoFactorAuthIncludedUsers' => [
            'name' => tra('Force indicated users to enable 2FA'),
            'description' => tra('List of usernames.'),
            'type' => 'text',
            'separator' => ';',
            'filter' => 'username',
            'help' => $loginGeneralPreferencesHelp,
            'profile_reference' => 'user',
            'dependencies' => [
                'twoFactorAuth',
            ],
            'default' => [],
        ],
        'twoFactorAuthExcludedGroup' => [
            'name' => tra('Do not force users in the indicated groups to enable 2FA'),
            'description' => tra('List of group names.'),
            'type' => 'text',
            'separator' => ';',
            'filter' => 'groupname',
            'help' => $loginGeneralPreferencesHelp,
            'profile_reference' => 'group',
            'dependencies' => [
                'twoFactorAuth',
            ],
            'default' => [],
        ],
        'twoFactorAuthExcludedUsers' => [
            'name' => tra('Do not force indicated users to enable 2FA'),
            'description' => tra('List of usernames.'),
            'type' => 'text',
            'separator' => ';',
            'filter' => 'username',
            'profile_reference' => 'user',
            'help' => $loginGeneralPreferencesHelp,
            'dependencies' => [
                'twoFactorAuth',
            ],
            'default' => [],
        ],
        'useGroupTheme' => [
            'name' => tra('Group theme'),
            'description' => tra('Enable groups to each have their own visual theme.'),
            'type' => 'flag',
            'help' => 'Look-and-Feel-Theme',
            'default' => 'n',
        ],
        'sitetitle' => [
            'name' => tra('Site title'),
            'type' => 'text',
            'description' => tr('The displayed title of the website.'),
            'size' => '50',
            'default' => '',
            'help' => $generalLayoutOptionsHelp,
            'tags' => ['basic'],
            'public' => true,
        ],
        'sitesubtitle' => [
            'name' => tra('Subtitle'),
            'type' => 'text',
            'description' => tr('A short phrase that, for example, describes the site.'),
            'size' => '50',
            'default' => '',
            'help' => $generalLayoutOptionsHelp,
            'tags' => ['basic'],
            'public' => true,
        ],
        'maxRecords' => [
            'name' => tra('Maximum number of records in listings'),
            'description' => tra('Default maximum number of items shown per page in paginated listings across the site.'),
            'type' => 'text',
            'size' => '3',
            'units' => tra('records'),
            'default' => 25,
            'help' => 'Pagination-Links',
            'tags' => ['basic'],
            'public' => true,
        ],
        'maxVersions' => [
            'name' => tra('Maximum number of versions:'),
            'description' => tra('Maximum number of previous versions kept in wiki page history. Older versions are removed when the limit is exceeded.'),
            'type' => 'text',
            'help' => 'Wiki-Features',
            'units' => tra('versions'),
            'size' => '5',
            'hint' => tra('0 for unlimited'),
            'default' => 0,
            'keywords' => 'wiki history',
        ],
        'allowRegister' => [
            'name' => tra('Users can register'),
            'description' => tra('Allow site visitors to register, using the registration form. The log-in module will include a "Register" link. If this is not activated, new users will have to be added manually by the admin on the Admin-Users page.'),
            'type' => 'flag',
            'help' => $loginGeneralPreferencesHelp,
            'default' => 'n',
            'tags' => ['basic'],
        ],
        'validateEmail' => [
            'name' => tra("Validate user's email server"),
            'description' => tra('Tiki will attempt to validate the user’s email address by examining the syntax of the email address. It must be a string of letters, or digits or _ or . or - follows by a @ follows by a string of letters, or digits or _ or . or -. Tiki will perform a DNS lookup and attempt to open a SMTP session to validate the email server.'),
            'type' => 'list',
            'help' => $loginGeneralPreferencesHelp,
            'tip' => tra('Some web servers may disable this functionality, thereby disabling this feature. If you are not in in a high security site or if you are on an open users site, do not use this option.'),
            'options' => [
                'n' => tra('No'),
                'y'         => tra('Yes'),
                'd' => tra('Yes, with "deep MX" search'),   // filters out reserved IP addresses and uses checkdnsrr to check for a valid "A" record
            ],
            'default' => 'n',
        ],
        'validateRegistration' => [
            'name' => tra('Require validation by Admin'),
            'description' => tra('The administrator will receive an email for each new user registration, and must validate the user before the user can log in.'),
            'type' => 'flag',
            'help' => $loginGeneralPreferencesHelp,
            'dependencies' => [
                'sender_email',
            ],
            'default' => 'n',
        ],
        'useRegisterPasscode' => [
            'name' => tra('Require passcode to register'),
            'description' => tra('Users must enter an alphanumeric code to register.  The site administrator must inform users of this code. This is to restrict registration to invited users.'),
            'type' => 'flag',
            'help' => $loginGeneralPreferencesHelp,
            'default' => 'n',
            'tags' => ['basic'],
        ],
        'registerPasscode' => [
            'name' => tra('Passcode'),
            'type' => 'text',
            'size' => 15,
            'help' => $loginGeneralPreferencesHelp,
            'hint' => tra('Alphanumeric code required to complete the registration'),
            'default' => '',
            'tags' => ['basic'],
        ],
        'showRegisterPasscode' => [
            'name' => tra('Show passcode on registration form'),
            'description' => tra("Displays the required passcode on the registration form. This is helpful for legitimate users who want to register while making it difficult for automated robots because the passcode is unique for each site and because it is displayed in JavaScript."),
            'type' => 'flag',
            'help' => $loginGeneralPreferencesHelp,
            'default' => 'n',
            'tags' => ['basic'],
        ],
        'registerKey' => [
            'name' => tra('Registration page key'),
            'hint' => tra('Key required to be on included the URL to access the registration page (if not empty).'),
            'description' => tra('To register, users need to go to, for example: tiki-register.php?key=yourregistrationkeyvalue'),
            'type' => 'text',
            'size' => 15,
            'help' => $loginGeneralPreferencesHelp,
            'default' => '',
            'tags' => ['basic'],
        ],
        'userTracker' => [
            'name' => tra('Use a tracker to collect more user information'),
            'description' => tra('Display a tracker form for the user to complete as part of the registration process. This tracker will receive and store additional information about each user.'),
            'type' => 'flag',
            'help' => 'User-Tracker',
            'help' => $loginGeneralPreferencesHelp,
            'dependencies' => [
                'feature_trackers',
            ],
            'hint' => tra('Go to [tiki-admingroups.php|Admin Groups] to select which tracker and fields to display.'),
            'default' => 'n',
        ],
        'groupTracker' => [
            'name' => tra('Use a tracker to collect more group information'),
            'type' => 'flag',
            'help' => $loginGeneralPreferencesHelp,
            'help' => 'Group-Tracker',
            'dependencies' => [
                'feature_trackers',
            ],
            'hint' => tra('Go to [tiki-admingroups.php|Admin Groups] to select which tracker and fields to display.'),
            'default' => 'n',
        ],
        'eponymousGroups' => [
            'name' => tra('Create a new group for each user'),
            'description' => tra('Automatically create a group for each user in order to, for example, assign permissions on the individual-user level.'),
            'type' => 'flag',
            'hint' => tra("The group name will be the same as the user's username"),
            'help' => $groupsHelp,
            'default' => 'n',
            'keywords' => 'eponymous groups',
        ],
        'syncGroupsWithDirectory' => [
            'name' => tra('Synchronize Tiki groups with a directory'),
            'description' => tra('Synchronize Tiki group membership with LDAP directory groups on user login and during bulk sync.'),
            'type' => 'flag',
            'hint' => tra('Define the directory within the "LDAP" tab'),
            'default' => 'n',
            'help' => $ldapAuthenticationHelp,
        ],
        'syncUsersWithDirectory' => [
            'name' => tra('Synchronize Tiki users with a directory'),
            'description' => tra('Synchronize Tiki user profile data with LDAP directory entries on user login and during bulk sync.'),
            'type' => 'flag',
            'hint' => tra('Define the directory within the "LDAP" tab'),
            'default' => 'n',
            'help' => $ldapAuthenticationHelp,
        ],
        'rememberme' => [
            'name' => tra('Remember me'),
            'description' => tra("After logging in, users will automatically be logged in again when they leave and return to the site."),
            'type' => 'list',
            'help' => 'Login-General-Preferences#Remember_Me',
            'options' => [
                'disabled' => tra('Disabled'),
                'all'      => tra("User's choice"),
                'always'   => tra('Always'),
            ],
            'default' => 'all',
            'tags' => ['basic'],
        ],
        'remembertime' => [
            'name' => tra('Duration'),
            'description' => tra('The length of time before the user will need to log in again.'),
            'type' => 'list',
            'help' => $loginGeneralPreferencesHelp,
            'options' => [
                '300'       => '5 ' . tra('minutes'),
                '900'       => '15 ' . tra('minutes'),
                '1800'      => '30 ' . tra('minutes'),
                '3600'      => '1 ' . tra('hour'),
                '7200'      => '2 ' . tra('hours'),
                '14400'     => '4 ' . tra('hours'),
                '21600'     => '6 ' . tra('hours'),
                '28800'     => '8 ' . tra('hours'),
                '36000'     => '10 ' . tra('hours'),
                '72000'     => '20 ' . tra('hours'),
                '86400'     => '1 ' . tra('day'),
                '604800'    => '1 ' . tra('week'),
                '2629743'   => '1 ' . tra('month'),
                '31556926'  => '1 ' . tra('year'),
            ],
            'default' => 2629743,
            'tags' => ['basic'],
        ],
        'urlIndexBrowserTitle' => [
            'name' => tra('Homepage Browser title'),
            'description' => tra('Customize Browser title for the custom homepage'),
            'type' => 'text',
            'help' => $navigationHelp,
            'size' => 50,
            'default' => tra('Homepage'),
            'tags' => ['basic'],
            'dependencies' => [
                'useUrlIndex',
            ],
        ],
        'urlIndex' => [
            'name' => tra('Homepage URL'),
            'description' => tra('URL used as the site homepage when "Use custom homepage" is enabled.'),
            'type' => 'text',
            'size' => 50,
            'default' => '',
            'help' => $navigationHelp,
            'tags' => ['basic'],
            'dependencies' => [
                'useUrlIndex',
            ],
        ],
        'useUrlIndex' => [
            'name' => tra('Use custom homepage'),
            'description' => tra('Use the top page of a Tiki feature or another homepage'),
            'warning' => tra('This option will override the Use Tiki feature as homepage setting.'),
            'type' => 'flag',
            'help' => $navigationHelp,
            'default' => 'n',
            'tags' => ['basic'],
        ],
        'tikiIndex' => [
            'name' => tra('Use the top page of a Tiki feature as the homepage'),
            'description' => tra('Select the Tiki feature to provide the site homepage. Only enabled features are listed.'),
            'type' => 'list',
            'options' => feature_home_pages($partial),
            'default' => 'tiki-index.php',
            'help' => $navigationHelp,
            'tags' => ['basic'],
        ],
        'maxRowsGalleries' => [
            'name' => tra('Maximum rows per page'),
            'description' => tra('Default maximum number of rows shown per page in file gallery listings.'),
            'type' => 'text',
            'help' => 'Gallery-Listings',
            'units' => tra('rows'),
            'default' => '10',
        ],
        'rowImagesGalleries' => [
            'name' => tra('Images per row'),
            'description' => tra('Default number of image thumbnails displayed per row in file gallery.'),
            'type' => 'text',
            'units' => tra('images'),
            'default' => '6',
        ],
        'thumbSizeXGalleries' => [
            'name' => tra('Thumbnail width'),
            'description' => tra('Default width in pixels for image thumbnails in file gallery.'),
            'type' => 'text',
            'units' => tra('pixels'),
            'default' => '80',
        ],
        'thumbSizeYGalleries' => [
            'name' => tra('Thumbnail height'),
            'description' => tra('Default height in pixels for image thumbnails in file gallery.'),
            'type' => 'text',
            'units' => tra('pixels'),
            'default' => '80',
        ],
        'scaleSizeGalleries' => [
            'name' => tra('Default scale size'),
            'description' => tra('Default maximum size in pixels for scaled images displayed in file gallery.'),
            'type' => 'text',
            'units' => tra('pixels'),
            'default' => '',
        ],
        'maintenanceMessageReindex' => [
            'name' => tra('Display maintenance message to users during search re-index'),
            'type' => 'flag',
            'default' => 'n',
            'description' => tra('If enabled, a message will be displayed to users during search re-index.'),
            'help' => $generalPreferencesHelp,
        ],
        'maintenanceReindexMessage' => [
            'name' => tra('Search re-index message'),
            'type' => 'text',
            'default' => tra('The search index is currently rebuilding. You can continue using the site normally, but please be aware that it could be slower than usual.'),
            'size' => 300,
            'help' => $generalPreferencesHelp,
            'description' => tra('The message displayed during search re-indexing. You can customize this message if needed.'),
        ],
        'maintenanceTimeBeforeDisplayMessage' => [
            'name' => tra('Time before maintenance to display the message (minutes)'),
            'description' => tra('The time will be used for display warning message before start maintenance.'),
            'type' => 'text',
            'size' => 3,
            'help' => $generalPreferencesHelp,
            'default' => '60',
            'tags' => ['advanced'],
        ],
        'maintenanceRecurrentEnable' => [
            'name' => tra('Recurrent Maintaine Enabled'),
            'description' => tra('Enable notification during maintenance.'),
            'help' => $generalPreferencesHelp,
            'type' => 'flag',
            'default' => 'n',
            'tags' => ['advanced'],
        ],
        'maintenanceRecurrentPreMessage' => [
            'name' => tra('Before Maintenance Message'),
            'description' => tra('Message to display before maintenance starts. Use TIME for minutes until maintenance and DOWN for duration.'),
            'type' => 'text',
            'size' => 300,
            'default' => tra('This website will be under maintenance in TIME minutes and will be unavailable for DOWN minutes.'),
            'help' => $generalPreferencesHelp,
            'tags' => ['advanced'],
        ],
        'maintenanceRecurrentDuringMessage' => [
            'name' => tra('During Maintenance Message'),
            'description' => tra('Message to display during maintenance. Use DOWN for remaining minutes of downtime.'),
            'type' => 'text',
            'size' => 300,
            'help' => $generalPreferencesHelp,
            'default' => tra('This website is under maintenance and will be back in DOWN minutes.'),
            'tags' => ['advanced'],
        ],
        'maintenanceRecurrentStartTime' => [
            'name' => tra('Start Time'),
            'description' => tra('It is for set when the maintenance will be started (24H format hh:mm)'),
            'help' => $dateAndTimeDateAndTimeFormatsHelp,
            'type' => 'text',
            'size' => '30',
            'help' => $generalPreferencesHelp,
            'default' => '%H:%M',
            'tags' => ['advanced'],
        ],
        'maintenanceRecurrentDuration' => [
            'name' => tra('Duration (Minutes)'),
            'description' => tra('Period to display message while maintenance.'),
            'type' => 'text',
            'size' => 3,
            'help' => $generalPreferencesHelp,
            'default' => '60',
            'tags' => ['advanced'],
        ],
        'maintenanceEnableWeekdays' => [
            'name' => tra('Enable notifitication during maintenance on weekdays'),
            'description' => tra('Days of the week when recurring maintenance warnings and site closure are enabled.'),
            'type' => 'multilist',
            'options' => [
                0 => tra('Sunday'),
                1 => tra('Monday'),
                2 => tra('Tuesday'),
                3 => tra('Wednesday'),
                4 => tra('Thursday'),
                5 => tra('Friday'),
                6 => tra('Saturday'),
            ],
            'default' => [0, 1, 2, 3, 4, 5, 6],
            'help' => $generalPreferencesHelp,
            'tags' => ['advanced'],
        ],
        'maintenanceOnceOffEnable' => [
            'name' => tra('Once off notification maintenance enabled'),
            'description' => tra('Enable notifitication once off maintenance.'),
            'type' => 'flag',
            'help' => $generalPreferencesHelp,
            'default' => 'n',
            'tags' => ['advanced'],
        ],
        'maintenanceOnceOffPreMessage' => [
            'name' => tra('Before Once-Off Maintenance Message'),
            'description' => tra('Message to display before once-off maintenance starts. Use TIME for minutes until maintenance and DOWN for duration.'),
            'type' => 'text',
            'help' => $generalPreferencesHelp,
            'size' => 300,
            'default' => tra('This website will be under once-off maintenance in TIME minutes and will be unavailable for DOWN minutes.'),
            'tags' => ['advanced'],
        ],
        'maintenanceOnceOffDuringMessage' => [
            'name' => tra('During Once-Off Maintenance Message'),
            'description' => tra('Message to display during once-off maintenance. Use DOWN for remaining minutes of downtime.'),
            'type' => 'text',
            'help' => $generalPreferencesHelp,
            'size' => 300,
            'default' => tra('This website is under once-off maintenance and will be back in DOWN minutes.'),
            'tags' => ['advanced'],
        ],
        'maintenanceOnceOffStartDate' => [
            'name' => tra('Start Date'),
            'description' => tra('It is for set date the maintenance will be off'),
            'help' => $generalPreferencesHelp,
            'help' => $dateAndTimeDateAndTimeFormatsHelp,
            'type' => 'text',
            'size' => '30',
            'default' => '%Y-%m-%d',
            'tags' => ['advanced'],
        ],
        'maintenanceOnceOffStartTime' => [
            'name' => tra('Start Time'),
            'description' => tra('It is for set when the maintenance will be off (24H format hh:mm)'),
            'help' => $dateAndTimeDateAndTimeFormatsHelp,
            'type' => 'text',
            'size' => '30',
            'default' => '%H:%M',
            'tags' => ['advanced'],
        ],
        'maintenanceOnceOffDuration' => [
            'name' => tra('Duration (Minutes)'),
            'description' => tra('Period to display message while maintenance once off.'),
            'type' => 'text',
            'size' => 3,
            'help' => $generalPreferencesHelp,
            'default' => '60',
            'tags' => ['advanced'],
        ],
        'scheduledTasksReport' => [
            'name' => tra('Report when scheduled tasks do not run successfully'),
            'description' => tr('Scheduler report'),
            'help' => $generalPreferencesHelp,
            'type' => 'list',
            'options' => [
                'do_not_report' => tra('Do not report'),
                'last_run' => tra('Last Run'),
                'last_number_of_hours' => tra('Last number of hours')
            ],
            'default' => 'last_run',
            'tags' => ['basic'],
        ],
        'scheduledTasksReportHours' => [
            'name' => tr('Nº of hours'),
            'description' => tr('Number of hours to show scheduler logs'),
            'type' => 'text',
            'help' => $generalPreferencesHelp,
            'size' => 100,
            'filter' => 'int',
            'units' => tra('hours'),
            'default' => 24,
            'constraints' => [
                'min' => 1
            ],
        ],
        'scheduledTasksReportMaxDays' => [
            'name' => tr('Do not report failures older than (days)'),
            'description' => tr('failure report days'),
            'type' => 'text',
            'help' => $generalPreferencesHelp,
            'size' => 5,
            'filter' => 'int',
            'units' => tra('Days'),
            'default' => 30,
            'constraints' => [
                'min' => 1
            ],
        ],
    ];
}

/**
 *  Computes the alternate homes for each feature
 *   (used in admin general template)
 *
 * @param $partial bool
 *
 * @return array of url's and labels of the alternate homepages
 * @throws Exception
 * @access public
 */
function feature_home_pages($partial = false)
{
    global $prefs;
    $tikilib = TikiLib::lib('tiki');
    $tikiIndex = [];

    //wiki
    $tikiIndex['tiki-index.php'] = tra('Wiki');

    // Articles
    if (! $partial && $prefs['feature_articles'] == 'y') {
        $tikiIndex['tiki-view_articles.php'] = tra('Articles');
    }
    // Blog
    if (! $partial && $prefs['feature_blogs'] == 'y') {
        if ($prefs['home_blog'] != '0') {
            $bloglib = TikiLib::lib('blog');
            $hbloginfo = $bloglib->get_blog($prefs['home_blog']);
            $home_blog_name = substr($hbloginfo['title'] ?? '', 0, 20);
        } else {
            $home_blog_name = tra('Set blogs homepage first');
        }
        $tikiIndex['tiki-view_blog.php?blogId=' . $prefs['home_blog']] = tra('Blog:') . $home_blog_name;
    }

    // File gallery
    if (! $partial && $prefs['feature_file_galleries'] == 'y') {
        $filegallib = TikiLib::lib('filegal');
        $hgalinfo = $filegallib->get_file_gallery($prefs['home_file_gallery']);
        if ($hgalinfo) {
            $home_gal_name = substr($hgalinfo["name"], 0, 20);
            $tikiIndex['tiki-list_file_gallery.php?galleryId=' . $prefs['home_file_gallery']] = tra('File Gallery:') . $home_gal_name;
        }
    }

    // Forum
    if (! $partial && $prefs['feature_forums'] == 'y') {
        if ($prefs['home_forum'] != '0') {
            $hforuminfo = TikiLib::lib('comments')->get_forum($prefs['home_forum']);
            $home_forum_name = substr($hforuminfo['name'], 0, 20);
        } else {
            $home_forum_name = tra('Set Forum homepage first');
        }
        $tikiIndex['tiki-view_forum.php?forumId=' . $prefs['home_forum']] = tra('Forum:') . $home_forum_name;
    }

    return $tikiIndex;
}
