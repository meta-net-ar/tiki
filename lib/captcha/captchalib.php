<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
//this script may only be included - so its better to die if called directly.

use AltchaOrg\Altcha\V1\ChallengeOptions;
use AltchaOrg\Altcha\V1\Altcha as AltchaLib;
use Tiki\Captcha\CaptchaTypeResolver;

if (str_contains($_SERVER['SCRIPT_NAME'], basename(__FILE__))) {
    header('location: index.php');
    exit;
}

/**
 * A simple class to switch between Laminas\Captcha\Image, AltchaOrg\Altcha\V1\Altcha and
 * Laminas\Captcha\ReCaptcha based on admin preference
 */
class Captcha
{
    /**
     * The type of the captch ('default' when using Laminas\Captcha\Image
     * or 'recaptcha' when using Laminas\Captcha\ReCaptcha)
     *
     * @var string
     */
    public $type = '';

    /**
     * An instance of Laminas\Captcha\Image or Laminas\Captcha\ReCaptcha or AltchaOrg\Altcha\V1\Altcha
     * depending on the value of $this->type
     *
     * @var object
     */
    public $captcha = '';

    /**
     * Fallback error messages for captcha implementations without Laminas-style message APIs.
     *
     * @var array<string, string>
     */
    private $errorMessages = [];

    /**
     * Class constructor: decides whether to create an instance of
     * Laminas\Captcha\Image or Laminas\Captcha\ReCaptcha or Captcha_Question or AltchaOrg\Altcha\V1\Altcha
     *
     * @param string $type recaptcha|questions|default|dumb
     */
    public function __construct($type = '')
    {
        global $prefs;

        if (empty($type)) {
            $type = CaptchaTypeResolver::getConfiguredType($prefs);
        }

        if ($type === 'recaptcha') {
            $this->captcha = new Laminas\Captcha\ReCaptcha(
                [
                    'private_key' => $prefs['recaptcha_privkey'],
                    'public_key' => $prefs['recaptcha_pubkey'],
                ]
            );
            $httpClient = TikiLib::lib('tiki')->get_http_client();
            $this->captcha->getService()->setHttpClient($httpClient);

            $this->captcha->getService()->setOption('theme', $prefs['recaptcha_theme'] ?? 'clean');

            $this->captcha->setOption('ssl', true);

            $this->type = $type;

            $this->recaptchaCustomTranslations();
        } elseif (in_array($type, ['recaptcha20', 'recaptcha30'])) {
            $params = [
                'privkey' => $prefs['recaptcha_privkey'],
                'pubkey' => $prefs['recaptcha_pubkey'],
                'theme' => $prefs['recaptcha_theme'] ?? 'clean',
            ];

            if ($type === 'recaptcha20') {
                include_once('lib/captcha/Captcha_ReCaptcha20.php');
                $this->captcha = new Captcha_ReCaptcha20($params);
            } else {
                include_once('lib/captcha/Captcha_ReCaptcha30.php');
                $this->captcha = new Captcha_ReCaptcha30($params);
            }

            $httpClient = TikiLib::lib('tiki')->get_http_client();
            $this->captcha->getService()->setHttpClient($httpClient);

            $this->captcha->setOption('ssl', true);

            $this->type = $type;

            $this->recaptchaCustomTranslations();
        } elseif ($type === 'default') {
            $this->captcha = new Laminas\Captcha\Image(
                [
                    'wordLen' => $prefs['captcha_wordLen'],
                    'timeout' => 600,
                    'font' => __DIR__ . '/DejaVuSansMono.ttf',
                    'imgdir' => 'temp/public/',
                    'suffix' => '.captcha.png',
                    'width' => $prefs['captcha_width'],
                    'dotNoiseLevel' => $prefs['captcha_noise'],
                ]
            );
            $this->type = 'default';
        } elseif ($type === 'questions') {
            $this->type = 'questions';

            $questions = [];
            $lines = explode("\n", $prefs['captcha_questions']);

            foreach ($lines as $line) {
                $line = explode(':', $line, 2);
                if (count($line) === 2) {
                    $questions[] = [trim($line[0]), trim($line[1])];
                }
            }

            include_once('lib/captcha/Captcha_Questions.php');
            $this->captcha = new Captcha_Questions($questions);
        } elseif ($type === 'altcha') {
            $hmacKey = $prefs['altcha_hmac_key'];
            $this->captcha = new AltchaLib($hmacKey);
            $this->type = $type;
        } else {        // implied $type==='dumb'
            $this->captcha = new Laminas\Captcha\Dumb();
            $this->captcha->setWordlen($prefs['captcha_wordLen']);
            $this->captcha->setLabel(tra('Please type this word backwards'));
            $this->type = 'dumb';
        }

        $this->setErrorMessages();
    }

    /**
     * Create the default captcha
     *
     * @return string
     */
    public function generate()
    {
        static $key = '';

        if (! empty($key)) {
            return $key;
        }

        try {
            if ($this->type === 'altcha') {
                $options = new ChallengeOptions(
                    maxNumber: 50000,
                    expires: (new \DateTimeImmutable())->add(new \DateInterval('PT10S'))
                );
                $key = $this->captcha->createChallenge($options);
                $_SESSION['altcha_challenge'] = $key;
                return $key;
            } else {
                $key = $this->captcha->generate();
                if ($this->type == 'default' || $this->type == 'questions') {
                    $session = $this->captcha->getSession();
                    $session->setExpirationHops(2, null, true);
                    $this->captcha->setSession($session);
                    $this->captcha->setKeepSession(false);
                }
                return $key;
            }
        } catch (Exception $e) {
            Feedback::error($e->getMessage());
        }
    }

    /**
     * Return CAPTCHA ID
     *
     * The ID is used by the classic CAPTCHA flow to identify the generated challenge,
     * post it back as captcha[id], and store successful validation in session.
     *
     * Not all CAPTCHA implementations expose such an ID. For providers without one
     * (for example Altcha), this returns an empty string. That is acceptable for
     * those providers because they use their own challenge/payload validation flow
     * instead of the legacy captcha[id] mechanism.
     *
     * @return string captcha ID or empty string if not available
     */
    public function getId()
    {
        if (method_exists($this->captcha, 'getId')) {
            return $this->captcha->getId();
        }
        return '';
    }

    /**
     * HTML code for the captcha
     *
     * @return string
     */
    public function render()
    {
        $access = TikiLib::lib('access');
        if ($access->is_xml_http_request()) {
            if (in_array($this->type, ['recaptcha20', 'recaptcha30'])) {
                return $this->captcha->renderAjax();
            } elseif ($this->type == 'recaptcha') {
                $params = json_encode($this->captcha->getService()->getOptions());
                $id = 1;
                TikiLib::lib('header')->add_js('
                    Recaptcha.create("' . $this->captcha->getSiteKey() . '",
                    "captcha' . $id . '",' . $params . '
                    );
                ', 100);
                return '<div id="captcha' . $id . '"></div>';
            } else {
                return $this->captcha->render();
            }
        } elseif ($this->type === 'altcha') {
            $challenge = $_SESSION['altcha_challenge'] ?? null;
            if ($challenge) {
                return '<altcha-widget challengeurl="antibot.php"></altcha-widget>';
            } else {
                return '<div>' . tra('Altcha challenge unavailable') . '</div>';
            }
        } else {
            if (in_array($this->type, ['recaptcha20', 'recaptcha30'])) {
                return $this->captcha->render();
            } elseif ($this->captcha instanceof Laminas\Captcha\ReCaptcha) {
                return $this->captcha->getService()->getHtml();
            } elseif ($this->captcha instanceof Laminas\Captcha\Dumb) {
                return $this->captcha->getLabel() . ': <b>'
                . strrev($this->captcha->getWord())
                . '</b>';
            }
            return $this->captcha->render();
        }
    }

    /**
     * Validate user input for the captcha
     *
     * @param array $input
     * @return bool true or false
     */
    public function validate($input = null)
    {
        if (is_null($input)) {
            $input = $_REQUEST;
        }

        // Check session for previously validated captcha
        $captchaId = null;
        if (isset($input['captcha']) && isset($input['captcha']['id'])) {
            $captchaId = $input['captcha']['id'];

            // Check if this captcha ID was already validated
            $validatedKey = 'captcha_validated_' . $captchaId;
            if (isset($_SESSION[$validatedKey]) && $_SESSION[$validatedKey] === true) {
                return true;
            }
        }

        if (in_array($this->type, ['recaptcha', 'recaptcha20', 'recaptcha30'])) {
            // Temporary workaround of zend/http client uses arg_separator.output for making POST request body
            // which fails with Google recaptcha services if used with '&amp;' value
            // should be fixed in zend/http (pull request submitted)
            // or remove ini_get('arg_separator.output', '&amp;') we have in tiki code tiki-setup_base.php:31
            $oldVal = ini_get('arg_separator.output');
            ini_set('arg_separator.output', '&');
            $result = $this->captcha->isValid($input);
            ini_set('arg_separator.output', $oldVal);
        } elseif ($this->type === 'altcha') {
            $payload = $input['altcha'] ?? null;
            if (! $payload) {
                return false;
            }
            $result = $this->captcha->verifySolution($payload, true);
        } else {
            if (isset($input['captcha'])) {
                $captchaInput = $input['captcha'];
            } else {
                global $jitPost;
                $captchaInput = $jitPost->captcha->asArray();
            }
            $result = $this->captcha->isValid($captchaInput);
        }

        // Store validation result in session if successful
        if ($result && $captchaId) {
            $validatedKey = 'captcha_validated_' . $captchaId;
            $_SESSION[$validatedKey] = true;
        }
        return $result;
    }

    /**
     * Return the full path to the captcha image when using default captcha
     *
     * @return string full path to default captcha image
     */
    public function getPath()
    {
        try {
            if (method_exists($this->captcha, 'getImgDir')) {
                return $this->captcha->getImgDir() . $this->captcha->getId() . $this->captcha->getSuffix();
            }
        } catch (Exception $e) {
            Feedback::error($e->getMessage());
        }
    }

    /**
     * Translate Laminas\Captcha\Image, Laminas\Captcha\Dumb and Laminas\Captcha\ReCaptcha
     * default error messages
     *
     * @return void
     */
    public function setErrorMessages()
    {
        $errors = [
            'missingValue' => tra('Empty CAPTCHA value'),
            'badCaptcha' => tra('You have mistyped the anti-bot verification code. Please try again.')
        ];
        if ($this->type === 'altcha') {
            $errors['errCaptcha'] = tra('Failed to validate CAPTCHA');
        } elseif (in_array($this->type, ['recaptcha', 'recaptcha20', 'recaptcha30'])) {
            $errors['errCaptcha'] = tra('Failed to validate CAPTCHA');
        } else {
            $errors['missingID'] = tra('CAPTCHA ID field is missing');
        }
        $this->errorMessages = $errors;

        if (method_exists($this->captcha, 'setMessages')) {
            $this->captcha->setMessages($errors);
        }
    }

    /**
     * Convert the errors array into a string and return it
     *
     * @return string error messages
     */
    public function getErrors()
    {
        if ($this->type === 'altcha') {
            return '<br />' . ($this->errorMessages['errCaptcha'] ?? tra('Invalid Captcha.'));
        }

        if (method_exists($this->captcha, 'getMessages')) {
            return implode('<br />', $this->captcha->getMessages());
        }

        return implode('<br />', $this->errorMessages);
    }

    /**
     * Custom translation for ReCaptcha interface
     *
     * @return void
     */
    public function recaptchaCustomTranslations()
    {
        $recaptchaService = $this->captcha->getService();
        $recaptchaService->setOption(
            'custom_translations',
            [
                'visual_challenge' => tra('Get a visual challenge'),
                'audio_challenge' => tra('Get an audio challenge'),
                'refresh_btn' => tra('Get a new challenge'),
                'instructions_visual' => tra('Type the two words'),
                'instructions_audio' => tra('Type what you hear'),
                'help_btn' => tra('Help'),
                'play_again' => tra('Play sound again'),
                'cant_hear_this' => tra('Download audio as an MP3 file'),
                'incorrect_try_again' => tra('Incorrect. Try again.')
            ]
        );
    }
}
