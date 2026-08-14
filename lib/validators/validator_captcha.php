<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function validator_captcha($input, $parameter = '', $message = '')
{
    $captchalib = TikiLib::lib('captcha');
    $_REQUEST['captcha'] = ['input' => $input, 'id' => $parameter];
    if (! $captchalib->validate()) {
        if (method_exists($captchalib->captcha, 'getSession')) {
            // the following needed to keep session active for ajax checking
            $session = $captchalib->captcha->getSession();
            $session->setExpirationHops(2, null, true);
            $captchalib->captcha->setSession($session);
            $captchalib->captcha->setKeepSession(false);
        }
        // now return errors
        return $captchalib->getErrors();
    }
    if (method_exists($captchalib->captcha, 'getSession')) {
        // the following needed to keep session active for ajax checking
        $session = $captchalib->captcha->getSession();
        $session->setExpirationHops(2, null, true);
        $captchalib->captcha->setSession($session);
        $captchalib->captcha->setKeepSession(false);
    }
    // now return ok
    return true;
}
