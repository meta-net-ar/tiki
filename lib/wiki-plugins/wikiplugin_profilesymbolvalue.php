<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function wikiplugin_profilesymbolvalue_info()
{
    return [
        'name' => tra('Profile Symbol Value'),
        'documentation' => 'PluginProfileSymbolValue',
        'description' => tra('Display the profile symbol related with profile and a reference and optionally with domain or package'),
        'prefs' => ['wikiplugin_profilesymbolvalue'],
        'introduced' => 20,
        'tags' => [ 'basic' ],
        'params' => [
            'domain' => [
                'required' => false,
                'description' => tra('Domain'),
                'since' => '20',
                'filter' => 'text',
                'default' => '',
            ],
            'profile' => [
                'required' => true,
                'description' => tra('Profile name'),
                'since' => '20',
                'filter' => 'text',
                'default' => '',
            ],
            'reference' => [
                'name' => tra('Reference key'),
                'required' => true,
                'description' => tra('Reference name'),
                'since' => '20',
                'filter' => 'text',
                'default' => '',
            ],
            'package' => [
                'name' => tra('Package'),
                'description' => tra('Package extension name'),
                'required' => false,
                'since' => '20',
                'filter' => 'text',
                'default' => '',
            ],
        ],
    ];
}

function wikiplugin_profilesymbolvalue($data, $params)
{
    $domain = $params['domain'];
    $profile = $params['profile'];
    $ref = $params['reference'];
    $package = $params['package'];

    $smarty = TikiLib::lib('smarty');
    return \SmartyTiki\FunctionHandler\ProfileSymbolValue::render([
        'domain' => $domain,
        'profile' => $profile,
        'ref' => $ref,
        'package' => $package
    ], $smarty->getEmptyInternalTemplate());
}
