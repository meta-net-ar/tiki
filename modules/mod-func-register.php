<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * @return array
 */
function module_register_info()
{
    return [
        'name' => tra('New User Registration'),
        'description' => tra('Permit anonymous visitors to create an account on the site.'),
        'prefs' => ['allowRegister'],
        'params' => [],
    ];
}

/**
 * @param $mod_reference
 * @param $module_params
 * @return array|mixed|Tiki\Lib\Registration\Error|string
 */
function module_register($mod_reference, $module_params)
{
    $smarty = TikiLib::lib('smarty');
    return \SmartyTiki\FunctionHandler\UserRegistration::render($module_params, $smarty->getEmptyInternalTemplate());
}
