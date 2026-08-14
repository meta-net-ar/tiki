<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

use Tiki\HeadlessBrowser\HeadlessBrowserFactory;

function prefs_headlessbrowser_list()
{
    global $prefs;

    $packagesRequired = [];
    $browserType = $prefs['headlessbrowser_integration_type'] ?? HeadlessBrowserFactory::CHROME;
    if ($browserType === HeadlessBrowserFactory::CASPERJS) {
        $packagesRequired = ['jerome-breton/casperjs-installer' => 'CasperJsInstaller\Installer'];
    }

    $generalPreferencesHelp = 'General-Preferences';

    return [
        'headlessbrowser_integration_type' => [
            'name' => tra('Headless Browser Integration Type'),
            'description' => tra('Type of headless browser integration to be used.'),
            'type' => 'list',
            'options' => [
                HeadlessBrowserFactory::CHROME => tra('Direct Chrome Integration'),
                HeadlessBrowserFactory::CASPERJS => tra('CasperJS'),
            ],
            'help' => $generalPreferencesHelp,
            'tags' => ['experimental'],
            'default' => HeadlessBrowserFactory::CHROME,
            'packages_required' => $packagesRequired,
        ],
        'headlessbrowser_chrome_path' => [
            'name' => tra('Chrome Binary Path'),
            'description' => tra('This ensures that the ChromePHP library can locate and execute the Chrome browser for headless operations'),
            'type' => 'text',
            'help' => $generalPreferencesHelp,
            'tags' => ['experimental'],
            'default' => '',
        ],
        'headlessbrowser_chrome_ignore_certificate_errors' => [
            'name' => tra('Headless chrome ignore certificate errors'),
            'description' => tra('Ignore SSL certificate errors when requesting external URLs while generating the pdf content.'),
            'type' => 'flag',
            'tags' => ['experimental'],
            'default' => 'n',
            'help' => $generalPreferencesHelp, // TODO: Add help for this preference
        ],
        'headlessbrowser_chartjs_module' => [
            'name' => tra('Headless chrome with ChartJS ES Module'),
            'description' => tra('Use chartJS >= 3.0  which exposes ES modules. Set this to n in case you want to use version 2.9.4 which is the last version supporting non-module usage.'),
            'type' => 'flag',
            'tags' => ['experimental'],
            'default' => 'y',
            'help' => $generalPreferencesHelp, // TODO: Add help for this preference
        ],
    ];
}
