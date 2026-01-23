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

    return [
        'headlessbrowser_integration_type' => [
            'name' => tra('Headless Browser Integration Type'),
            'description' => tra('Type of headless browser integration to be used.'),
            'type' => 'list',
            'options' => [
                HeadlessBrowserFactory::CHROME => tra('Direct Chrome Integration'),
                HeadlessBrowserFactory::CASPERJS => tra('CasperJS'),
            ],
            'tags' => ['experimental'],
            'default' => HeadlessBrowserFactory::CHROME,
            'packages_required' => $packagesRequired,
        ],
        'headlessbrowser_chrome_path' => [
            'name' => tra('Chrome Binary Path'),
            'description' => tra('This ensures that the ChromePHP library can locate and execute the Chrome browser for headless operations'),
            'type' => 'text',
            'tags' => ['experimental'],
            'default' => '',
        ],
        'headlessbrowser_chrome_ignore_certificate_errors' => [
            'name' => tra('Headless chrome ignore certificate errors'),
            'description' => tra('Ignore SSL certificate errors when requesting external URLs while generating the pdf content.'),
            'type' => 'flag',
            'tags' => ['experimental'],
            'default' => 'n',
        ],
        'headlessbrowser_chartjs_path' => [
            'name' => tra('ChartJS path'),
            'description' => tra('Url or local path of chartJS file.'),
            'type' => 'text',
            'tags' => ['experimental'],
            'default' => 'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.bundle.js',
        ],
    ];
}
