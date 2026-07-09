<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Captcha_ReCaptcha30 extends Laminas\Captcha\ReCaptcha
{
    protected $RESPONSE  = 'g-recaptcha-response';

    private const API_SERVER = 'https://www.google.com/recaptcha/api.js';
    private const VERIFY_SERVER = 'https://www.google.com/recaptcha/api/siteverify';

    /**
     * Validate captcha
     *
     * @see    Zend_Validate_Interface::isValid()
     * @param  mixed      $value
     * @param  array|null $context
     * @return boolean
     */
    public function isValid($value, $context = null)
    {
        if (! is_array($value) && ! is_array($context)) {
            $this->error(self::MISSING_VALUE);
            return false;
        }

        if (empty($value[$this->RESPONSE])) {
            $this->error(self::MISSING_VALUE);
            return false;
        }

        if (! extension_loaded('curl')) {
            $this->error('reCAPTCHA 3 requires the PHP CURL extension');
            return false;
        }

        //set POST variables
        $url = self::VERIFY_SERVER;

        $fields_string = http_build_query([
            'secret' => $this->getSecretKey(),
            'response' => $value[$this->RESPONSE],
            'remoteip' => $_SERVER['REMOTE_ADDR'],
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result = @json_decode(curl_exec($ch), true);

        if (! is_array($result)) {
            $this->error(self::ERR_CAPTCHA);
            return false;
        }

        if ($result['success'] == false) {
            $this->error(self::BAD_CAPTCHA);
            return false;
        }

        // Cache google respnonse to avoid second resubmission on ajax form
        $_SESSION['recaptcha_cache'][] = $value[$this->RESPONSE];

        return true;
    }

    /**
     * Render captcha
     *
     * @return string
     */
    public function render()
    {
        $api_server = self::API_SERVER;

        return <<<EOF
        <script src="{$api_server}?render={$this->getSiteKey()}"></script>
        <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response" value="">
        <style>.grecaptcha-badge {z-index: 1001;}</style>
        <script>
            grecaptcha.ready(function() {
                grecaptcha.execute('{$this->getSiteKey()}', {action: 'submit'})
                .then(function(token) {
                    document.getElementById('g-recaptcha-response').value=token;
                });
            });
        </script>
EOF;
    }

    /**
     * Render captcha though Ajax
     *
     * @return string
     */
    public function renderAjax()
    {
        return <<<EOF
        <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response" value="">
        <style>.grecaptcha-badge {z-index: 1001;}</style>
        <script>
                grecaptcha.ready(function() {
                grecaptcha.execute('{$this->getSiteKey()}', {action: 'submit'})
                .then(function(token) {
                     document.getElementById('g-recaptcha-response').value=token;
            });
           }); 
        </script>
EOF;
    }
}
