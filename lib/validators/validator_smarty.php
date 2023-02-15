<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function validator_smarty($input, $parameter = '', $message = '')
{
    // since this is used only in Tiki modules, we skip validation if user is not allowed to admin modules
    $perms = Perms::get();
    if (! $perms->admin_modules) {
        return true;
    }

    TikiLib::lib('events')->bind('tiki.process.shutdown', function() {
        $content = ob_get_contents();
        if (strstr($content, 'Fatal error')) {
            ob_end_clean();
            echo 'Fatal error occured while trying to validate your smarty code.';
        }
    });

    /** @var Smarty_Tiki $smarty */
    $smarty = \TikiLib::lib('smarty');

    try {
        ob_start();
        if ($parameter === 'y') {
            $input = TikiLib::lib('parser')->parse_data($input);
        }
        $smarty->display('eval:' . $input);
        ob_end_clean();
        return true;
    } catch (Exception $e) {
        // error is always on line 1 in a string eval, so simplify the error message a little
        return preg_replace('/Syntax error in template .* on line \d+/', 'Syntax error in: ', $e->getMessage());
    }
}
