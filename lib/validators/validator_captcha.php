<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function validator_captcha($input, $parameter = '', $message = '')
{
    global $prefs;
    $captchalib = TikiLib::lib('captcha');
    $_REQUEST['captcha'] = ['input' => $input, 'id' => $parameter];

    // Save the captcha word from the PHP session before validation, because
    // Laminas\Captcha\AbstractWord::isValid() overwrites it with a new random
    // word on success, which would prevent the form POST validation from passing.
    $sessionNs = 'Laminas_Form_Captcha_' . $parameter;
    $savedWord = $_SESSION[$sessionNs]['word'] ?? null;

    if (! $captchalib->validate()) {
        // Restore the original word so the user can re-submit
        if ($savedWord !== null) {
            $_SESSION[$sessionNs]['word'] = $savedWord;
        }
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
    // Restore the original word so the form POST validation will also succeed
    if ($savedWord !== null) {
        $_SESSION[$sessionNs]['word'] = $savedWord;
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
