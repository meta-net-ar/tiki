<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\TwoFactorAuth;

use Exception;
use Symfony\Component\HttpFoundation\Session\Session;
use Tiki\TwoFactorAuth\Exception\TwoFactorAuthException;
use TikiDb;
use TikiLib;
use TikiMail;

class Email2FA implements TwoFactorAuthInterface
{
    private $twoFATable;
    private $userlib;
    private $session;
    private $crypt;

    public function __construct()
    {
        $this->twoFATable = TikiDb::get()->table('tiki_2fa_email_tokens');
        $this->userlib = TikiLib::lib('user');
        $this->session = new Session();
        $this->crypt = TikiLib::lib('crypt');
    }

    public function generateCode($user, $isEmail = true)
    {
        global $prefs;

        $lastRequestTime = $this->session->get('last_2fa_request_time') ?? 0;
        if (time() - $lastRequestTime < 60) {
            $errMsg = tr('Please wait about 60 seconds before requesting a new 2fa token.');
            throw new TwoFactorAuthException($errMsg);
        }

        $userInfo = $this->userlib->get_user_info($user);
        if (empty($userInfo)) {
            $errMsg = tr('User does not exist.');
            throw new TwoFactorAuthException($errMsg);
        }

        $tokenLength = intval($prefs['twoFactorAuthEmailTokenLength'] ?? 6);
        $token = $this->generateRandomString($tokenLength);

        if ($isEmail) {
            try {
                $mail = new TikiMail();
                $mail->setSubject(tr('Your 2FA Token'));
                $mail->setText(tr('Your token is: ') . $token);
                if (empty($userInfo['email'])) {
                    $errMsg = tr('User email is not set.');
                    throw new TwoFactorAuthException($errMsg);
                }
                $mail->send([$userInfo['email']]);
            } catch (Exception $e) {
                $errMsg = tr('Failed to send email: ' . $e->getMessage());
                throw new TwoFactorAuthException($errMsg);
            }
        }

        $userId = $userInfo['userId'];
        $hashedToken = $this->crypt->encryptData($token);

        $insertDetails = [
            'userId' => $userId,
            'token' => $hashedToken,
            'type' => 'email',
            'attempts' => 0,
            'created' => time()
        ];

        $isInserted = $this->twoFATable->insertOrUpdate($insertDetails, ['userId' => $userId]);

        if (! $isInserted) {
            $errMsg = tr('Failed to insert 2fa token.');
            throw new TwoFactorAuthException($errMsg);
        }

        $this->session->set('last_2fa_request_time', time());

        return ! $isEmail ? $token : true;
    }

    public function validateCode($user, $code = null)
    {
        if (empty($code)) {
            $errMsg = tr('You have enabled 2FA and 2FA code is required. So, you should login with 2FA.');
            throw new TwoFactorAuthException($errMsg);
        }

        global $prefs;

        $userInfo = $this->userlib->get_user_info($user);
        if (empty($userInfo)) {
            $errMsg = tr('User does not exist.');
            throw new TwoFactorAuthException($errMsg);
        }

        $tokenInfo = $this->twoFATable->fetchFullRow(['userId' => $userInfo['userId']]);
        if (empty($tokenInfo)) {
            $errMsg = tr('2FA token info does not exist.');
            throw new TwoFactorAuthException($errMsg);
        }

        if (intval($tokenInfo['attempts']) >= 3) {
            $errMsg = tr('Attempt limit exceeded. Please request a new 2fa token.');
            throw new TwoFactorAuthException($errMsg);
        }

        $hashedTokenFromDb = $tokenInfo['token'];
        $hashedTokenFromClient = $this->crypt->encryptData($code);
        $attempts = $tokenInfo['attempts'];
        $created = intval($tokenInfo['created']);
        $tokenTTL = intval($prefs['twoFactorAuthEmailTokenTTL'] ?? 30) * 60;

        if (time() - $created > $tokenTTL) {
            $errMsg = tr('2FA token has been expired. Please request a new one from login page.');
            throw new TwoFactorAuthException($errMsg);
        }

        if ($hashedTokenFromDb !== $hashedTokenFromClient) {
            $attempts = $tokenInfo['attempts'] + 1;
            $this->twoFATable->update(['attempts' => $attempts], ['userId' => $userInfo['userId']]);
            return false;
        }

        return true;
    }

    private function generateRandomString($length = 6)
    {
        $list = ['aeiou', 'AEIOU', 'bcdfghjklmnpqrstvwxyz', 'BCDFGHJKLMNPQRSTVWXYZ', '0123456789'];
        shuffle($list);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $ch = $list[$i % count($list)];
            $randomString .= $ch[rand(0, strlen($ch) - 1)];
        }
        return $randomString;
    }
}
