<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function prefs_sitelogo_list()
{
    $generalLayoutOptionsHelp = 'General-Layout-Options';

    return [
        'sitelogo_src' => [
            'name' => tra('Logo source (image path)'),
            'type' => 'text',
            'description' => tra('This can be a conventional path to the image file, or the syntax for an image in a Tiki gallery.'),
            'default' => 'img/tiki/Tiki_WCG.png',
            'tags' => ['basic'],
            'fgal_picker' => 'y',
            'help' => $generalLayoutOptionsHelp,
        ],
        // sitelogo_bgcolor pref removed 10/2022
        'sitelogo_title' => [
            'name' => tra('Logo title (on mouseover)'),
            'description' => tr('This appears as tool tip text. The site logo is also a link to the site index (top page).'),
            'type' => 'text',
            'size' => '50',
            'default' => 'Tiki-powered site',
            'tags' => ['basic'],
            'help' => $generalLayoutOptionsHelp,
        ],
        'sitelogo_alt' => [
            'name' => tra('HTML "alt" tag description'),
            'type' => 'text',
            'size' => '50',
            'description' => tr('Normally a description of the image, such as "Example.com logo"'),
            'default' => 'Site Logo',
            'tags' => ['basic'],
            'hint' => tr('Used by text browsers, screen readers, etc.'),
            'help' => $generalLayoutOptionsHelp,
        ],
        'sitelogo_icon' => [
            'name' => tra('Admin navbar icon'),
            'description' => tr('Icon (logo) that displays in the admin pages navbar. (Set the overall site logo using the logo module on tiki-admin_modules.php).'),
            'hint' => tra('Recommended image height: 32 pixels, or legible when scaled down to that size'),
            'type' => 'text',
            'default' => 'img/tiki/tikilogo_icon.png',
            'tags' => ['basic'],
            'help' => 'Look-and-Feel-Theme',
        ],
        'sitelogo_upload_icon' => [
            'name' => tra('Site logo upload icon'),
            'description' => tra('Display an icon for admins to be able to change or upload the site logo.'),
            'type' => 'flag',
            'default' => 'y',
            'tags' => ['basic'],
            'help' => $generalLayoutOptionsHelp,
        ],
    ];
}
