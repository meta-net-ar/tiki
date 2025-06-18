<?php

function validator_smarty($input, $parameter = '', $message = '')
{
    // Since this is used only in Tiki modules, we skip validation if user is not allowed to admin modules
    $perms = Perms::get();
    if (! $perms->admin_modules) {
        return true;
    }

    global $prefs;

    // Feedback warning if eval and string is disabled, but still return true to not block saving
    if (! empty($prefs['smarty_enable_string_eval']) && $prefs['smarty_enable_string_eval'] === 'n') {
        Feedback::error(tra('The use of "eval" and "string" has been disabled by the administrator for security reasons.'));
        return true;
    }

    TikiLib::lib('events')->bind('tiki.process.shutdown', function () {
        $content = ob_get_contents();
        if (str_contains($content, 'Fatal error')) {
            ob_end_clean();
            echo 'Fatal error occurred while trying to validate your smarty code.';
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
        // Error is always on line 1 in a string eval, so simplify the error message
        return preg_replace('/Syntax error in template .* on line \d+/', 'Syntax error in: ', $e->getMessage());
    }
}
