<?php
require 'vendor/autoload.php';

use AltchaOrg\Altcha\ChallengeOptions;
use AltchaOrg\Altcha\Altcha;

class AltchaWrapper
{
    /**
     * @var Altcha
     */
    public $altcha;

    public function __construct()
    {
        global $prefs;
        $hmacKey = $prefs['altcha_hmac_key'];
        $this->altcha = new Altcha($hmacKey);
    }

    public function createChallenge()
    {
        $options = new ChallengeOptions(
            maxNumber: 50000,
            expires: (new \DateTimeImmutable())->add(new \DateInterval('PT10S'))
        );

        return $this->altcha->createChallenge($options);
    }

    public function verifyChallenge(array $payload): bool
    {
        return $this->altcha->verifySolution($payload, true);
    }
}
